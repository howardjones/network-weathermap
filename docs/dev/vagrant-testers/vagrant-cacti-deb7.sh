#!/bin/bash

# working: 18 July 2016

# stop apt-get from hanging, waiting for a mysql password
export DEBIAN_FRONTEND=noninteractive

sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password TestPassword'
sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password TestPassword'

sudo apt-get update -y

## For 'real' install:
sudo apt-get install -y mysql-server snmp rrdtool php5-cli php5-mysql apache2 libapache2-mod-php5 unzip php5-snmp php5-gd
# ## For dev/test, we need these too
sudo apt-get install -y subversion make xsltproc imagemagick zip curl phpunit
#

# Get the common settings (CACTI_VERSION etc)
. /vagrant/settings.sh

echo "Starting installation for Cacti $CACTI_VERSION"

WEBROOT="/var/www"

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

if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot -pTestPassword cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot -pTestPassword cacti < ${WEBROOT}/cacti/cacti.sql
fi


sudo echo '# */5 * * * * cacti /usr/bin/php ${WEBROOT}/cacti/poller.php > ${WEBROOT}/last-cacti-poll.txt 2>&' > /etc/cron.d/cacti
