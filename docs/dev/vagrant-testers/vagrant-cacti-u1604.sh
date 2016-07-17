#!/bin/bash

# working: 17 July 2016
# (except that Cacti itself pukes on PHP 7)

function wait_for_apt_lock {
  i=0
  tput sc
  while fuser /var/lib/dpkg/lock >/dev/null 2>&1 ; do
      case $(($i % 4)) in
          0 ) j="-" ;;
          1 ) j="\\" ;;
          2 ) j="|" ;;
          3 ) j="/" ;;
      esac
      tput rc
      echo -en "\r[$j] Waiting for other software managers to finish..."
      sleep 0.5
      ((i=i+1))
  done
}

# ubuntu 16 kicks off a background update on first boot, which makes all the apt-get
# calls below fail due to the lock clash.

# This should wait for the lock to clear:

# stop apt-get from hanging, waiting for a mysql password
export DEBIAN_FRONTEND=noninteractive

sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password TestPassword'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password TestPassword'

wait_for_apt_lock
sudo apt-get update -y

## For 'real' install:
wait_for_apt_lock
sudo apt-get install -y mysql-server-5.7 snmp rrdtool php7.0-cli php7.0-mysql apache2 libapache2-mod-php7.0 unzip php7.0-snmp php7.0-gd

# ## For dev/test, we need these too
wait_for_apt_lock
sudo apt-get install -y subversion make xsltproc imagemagick zip curl phpunit
#

# Get the common settings (CACTI_VERSION etc)
. /vagrant/settings.sh

WEBROOT="/var/www/html"
echo "Starting installation for Cacti $CACTI_VERSION"

sudo mkdir -p ${WEBROOT}/cacti
sudo rm ${WEBROOT}/index.html

sudo useradd -d ${WEBROOT}/cacti cacti

if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   sudo wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

sudo tar --strip-components 1 --directory=${WEBROOT}/cacti -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz
sudo chown -R cacti ${WEBROOT}/cacti/rra
sudo chown -R cacti ${WEBROOT}/cacti/log

# fix the config file to include the prefix
cp ${WEBROOT}/cacti/include/config.php  ${WEBROOT}/cacti/include/config.php-dist
head -n -1 ${WEBROOT}/cacti/include/config.php-dist > ${WEBROOT}/cacti/include/config.php
echo '$url_path = "/cacti/";' >> ${WEBROOT}/cacti/include/config.php

mysql -uroot -pTestPassword <<EOF
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
flush privileges;
EOF

sudo service apache2 restart

if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot -pTestPassword cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot -pTestPassword cacti < ${WEBROOT}/cacti/cacti.sql
fi

sudo echo '# */5 * * * * cacti /usr/bin/php ${WEBROOT}/cacti/poller.php > ${WEBROOT}/last-cacti-poll.txt 2>&' > /etc/cron.d/cacti
