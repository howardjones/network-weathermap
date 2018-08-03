#!/bin/bash

## TODO:
# Add error handling. We don't want to strand a potential contributor just because one single package fails
#
# If `set -e` is used, then the script will terminate if one command fails with an error code.
# set -e

# Perhaps tell apt that we're non interactive?
export DEBIAN_FRONTEND=noninteractive


. /vagrant/settings.sh-sample

# Get the common settings (CACTI_VERSION etc)
if [ -f /vagrant/settings.sh ]; then
. /vagrant/settings.sh
fi

echo "Installing 'dos2unix'."
sudo apt-get -y update
sudo apt-get -y install dos2unix

echo "Setting system locale"
cp /vagrant/locale /etc/default/locale
dos2unix -q /etc/default/locale
locale-gen en_US.UTF-8
timedatectl set-timezone ${TIMEZONE}

add-apt-repository ppa:ondrej/php
apt-get update -y
## For 'real' install:
apt-get install -y mysql-server-5.7 snmp rrdtool php7.0 php7.1 php5.6 php5.6-common php5.6-cli php5.6-mysql apache2 libapache2-mod-php5.6 libapache2-mod-php7.0 unzip php5.6-snmp php5.6-gd php-gettext php5.6-mbstring php-xdebug unzip php5.6-xml php7.0-xml php7.0-mbstring php7.0-curl  php7.0-gd  php7.0-mysql php7.0-cli php7.1-xml php7.1-mbstring php7.1-curl  php7.1-gd  php7.1-mysql php7.1-cli php7.1-ldap php7.1-snmp php7.1-gmp
# ## For dev/test, we need these too
apt-get install -y git subversion make xsltproc imagemagick zip curl phpunit nodejs npm pandoc rsync nodejs-legacy php5.6-sqlite3 php7.0-sqlite3 php7.1-sqlite3 php-ast
# ## For composer
apt-get install -y php-mbstring php5.6-curl php7.0-curl php7.1-curl

echo "Adding php error logs"
sed -i -e "s|;error_log = syslog|;error_log = syslog\\nerror_log = ${WEBROOT}/cacti/log/php_errors.log|" \
 -e "s|;date.timezone =|;date.timezone =\\ndate.timezone = ${TIMEZONE}|" \
 /etc/php/${PHP_VERSION}/apache2/php.ini

#Change to selected php
a2dismod php7.0
a2dismod php7.1
a2dismod php5.6
a2enmod php${PHP_VERSION}
rm /etc/alternatives/php
ln -s /usr/bin/php${PHP_VERSION} /etc/alternatives/php

bash -c "cat > /etc/php/${PHP_VERSION}/cli/conf.d/99-cacti.ini" <<'EOF'
[Date]
EOF

cp /etc/php/${PHP_VERSION}/cli/conf.d/99-cacti.ini /etc/php/${PHP_VERSION}/apache2/conf.d/99-cacti.ini

service apache2 restart

echo "Installing bower and updating weathermap project."
#Install and run bower
npm install -g bower
# ln -s /usr/bin/nodejs /usr/bin/node
cd /network-weathermap
su -c 'bower install' - vagrant


#Install and run composer (this requires a swap partition for memory as well..)
echo "Installing swap"
sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
sudo /sbin/mkswap /var/swap.1
sudo /sbin/swapon /var/swap.1

echo "Installing composer"
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

cd /network-weathermap
su -c 'composer update' - vagrant

echo "Starting installation for Cacti ${CACTI_VERSION}"

mkdir ${WEBROOT}/cacti

if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

echo "Unpacking Cacti"
tar --strip-components 1 --directory=${WEBROOT}/cacti -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz

sudo mysql -uroot <<EOF
SET GLOBAL sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
grant all on cacti.* to cactiuser@'%' identified by 'cactiuser';
create database weathermaptest;
grant all on weathermaptest.* to weathermaptest@localhost identified by 'weathermaptest';
grant all on weathermaptest.* to weathermaptest@'%' identified by 'weathermaptest';
flush privileges;
EOF

if [[ ${CACTI_VERSION} == 1.* ]]; then
    echo "Listening on all devices."
    sed -i -e "s|bind-address		= 127.0.0.1|bind-address		= 0.0.0.0|" \
      /etc/mysql/mysql.conf.d/mysqld.cnf


    # Cacti 1.x insists on these
    echo "Adding mysql timezones"
    mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql

    echo "Cacti 1.x"

    # Cacti 1.x also makes a few optional modules required.
    apt-get install -y php5.6-ldap php7.0-ldap php5.6-gmp php7.0-gmp php7.1-gmp php7.1-ldap

    mysql -uroot <<EOF
grant select on mysql.time_zone_name to cactiuser@localhost identified by 'cactiuser';
flush privileges;
ALTER DATABASE cacti CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
EOF
    # it also suggests a lot of database tweaks. mostly they are for performance, but still
    bash -c "cat > /etc/mysql/mysql.conf.d/cacti.cnf" <<'EOF'
[mysqld]
max_heap_table_size=128M
join_buffer_size=64M
tmp_table_size=64M
innodb_buffer_pool_size=512M
innodb_doublewrite=off
innodb_flush_log_at_timeout=3
innodb_read_io_threads=32
innodb_write_io_threads=16
EOF

    service mysql restart
    service apache2 restart
fi

    # this isn't in the recommendations, but otherwise you get no logs!
sudo touch ${WEBROOT}/cacti/log/cacti.log
sudo chmod -R oug+rwx ${WEBROOT}/cacti
sudo chown -R www-data:vagrant ${WEBROOT}/cacti

# optionally seed database with "pre-installed" data instead of empty - can skip the install steps
echo "Loading cacti database"
if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot cacti < ${WEBROOT}/cacti/cacti.sql
fi

echo "Adding cron job"
echo "*/5 * * * * vagrant /usr/bin/php ${WEBROOT}/cacti/poller.php > ${WEBROOT}/cacti/last-cacti-poll.txt 2>&1" > /etc/cron.d/cacti

# if [[ ${CACTI_VERSION} == 1.* ]]; then
  # Cacti 1.x doesn't like to install properly with plugins in the plugins dir
  # echo "Can't yet install weathermap automatically with Cacti 1.x"
  # exit
# fi

#if [ -f /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip ]; then
#  # Install Network Weathermap from release zip
#  echo "Unzipping weathermap from local release zip"
#  unzip /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip -d /var/www/html/cacti/plugins/
#  chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
#fi
#
## alternatively, check out the local git repo into the Cacti dir
#if [ "X$WEATHERMAP_VERSION" = "Xgit" ]; then
#  echo "Cloning weathermap from local git"
#  git clone -b master /network-weathermap ${WEBROOT}/cacti/plugins/weathermap
#  cd ${WEBROOT}/cacti/plugins/weathermap
#  bower install
#  composer install
#  chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
#fi
#
## final possibility: rsync from the local copy into the Cacti dir
## (doesn't really work with windows host, due to line endings)
#if [ "X$WEATHERMAP_VERSION" = "Xrsync" ]; then
#  echo "rsyncing weathermap from local dir"
#  mkdir ${WEBROOT}/cacti/plugins/weathermap
#  rsync -a --exclude=composer.lock --exclude=vendor/ /network-weathermap/ ${WEBROOT}/cacti/plugins/weathermap/
#  cd ${WEBROOT}/cacti/plugins/weathermap
#  bower install
#  composer install
#  chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
#fi


# create the 'last poll' log file
# touch ${WEBROOT}/last-cacti-poll.txt
# chown cacti ${WEBROOT}/last-cacti-poll.txt

# create the database content for the phpunit database tests, if there is now a weathermap installation with tests
if [ -d ${WEBROOT}/cacti/plugins/weathermap/test-suite ]; then
  mysql -uroot weathermaptest < ${WEBROOT}/cacti/plugins/weathermap/test-suite/data/weathermap-empty.sql
fi

# so that the editor and poller don't immediately complain
# chown cacti ${WEBROOT}/cacti/plugins/weathermap/output
# chmod oug+rwx ${WEBROOT}/cacti/plugins/weathermap/output
# chown www-data ${WEBROOT}/cacti/plugins/weathermap/configs

apt install -y build-essential dos2unix dh-autoreconf help2man libssl-dev libmysql++-dev  librrds-perl libsnmp-dev libmysqlclient-dev libmysqld-dev

cd /vagrant
if [ ! -f /vagrant/cacti-spine-${CACTI_VERSION}.tar.gz ]; then
  wget -q  https://www.cacti.net/downloads/spine/cacti-spine-${CACTI_VERSION}.tar.gz
fi

tar xfz cacti-spine-${CACTI_VERSION}.tar.gz
cd cacti-spine-${CACTI_VERSION}/

./bootstrap
./configure
make
make install
chown root:root /usr/local/spine/bin/spine
chmod +s /usr/local/spine/bin/spine

rm -rf /vagrant/cacti-spine-${CACTI_VERSION}/

# any local tweaks can be added to post-install.sh (which needs to be marked executable)
if [ -x /vagrant/post-install.sh ]; then
    /vagrant/post-install.sh
fi
