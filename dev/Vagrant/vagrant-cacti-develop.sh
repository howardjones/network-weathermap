#!/bin/bash

## TODO:
# Add error handling. We don't want to strand a potential contributor just because one single package fails
#
# If `set -e` is used, then the script will terminate if one command fails with an error code.
# set -e

# Perhaps tell apt that we're non interactive?
export DEBIAN_FRONTEND=noninteractive

# Use the sample file as default values
. /vagrant/settings.sh-sample

# Get the common settings (CACTI_VERSION etc)
if [ -f /vagrant/settings.sh ]; then
. /vagrant/settings.sh
fi

CACTI_HOME=${WEBROOT}/cacti
CACTI_PLUGINS=${CACTI_HOME}/plugins

echo "Installing 'dos2unix'."
apt-get -y update
apt-get -y install dos2unix

echo "Setting system locale"
cp /vagrant/locale /etc/default/locale
dos2unix -q /etc/default/locale
. /vagrant/locale
locale-gen $LANG
timedatectl set-timezone $TIMEZONE

add-apt-repository ppa:ondrej/php
apt-get update -y
## For 'real' install:
apt-get install -y mysql-server-5.7 snmp rrdtool apache2 php-gettext php-xdebug unzip
for PHP_VERSION in ${PHP_VERSIONS}; do
  echo "Installing PHP ${PHP_VERSION}."
  apt-get install -y \
    php${PHP_VERSION} php${PHP_VERSION}-common php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-snmp php${PHP_VERSION}-gd php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl libapache2-mod-php${PHP_VERSION}

  # disable the php version
  a2dismod php${PHP_VERSION}

  echo "Modifying PHP configuration."
  sed -i -e "s|;error_log = syslog|;error_log = syslog\\nerror_log = ${CACTI_HOME}/log/php_errors.log|" \
 -e "s|;date.timezone =|;date.timezone =\\ndate.timezone = ${TIMEZONE}|" \
 /etc/php/${PHP_VERSION}/apache2/php.ini

  sed -i -e "s|;error_log = syslog|;error_log = syslog\\nerror_log = ${CACTI_HOME}/log/php_errors.log|" \
   -e "s|;date.timezone =|;date.timezone =\\ndate.timezone = ${TIMEZONE}|" \
   /etc/php/${PHP_VERSION}/cli/php.ini

done

echo "Enabling PHP ${PHP_VERSION}"
a2enmod php${PHP_VERSION}
rm /etc/alternatives/php
ln -s /usr/bin/php${PHP_VERSION} /etc/alternatives/php

# ## For dev/test, we need these too
apt-get install -y git subversion make xsltproc imagemagick zip curl phpunit nodejs npm pandoc rsync nodejs-legacy php-ast
for PHP_VERSION in ${PHP_VERSIONS}; do
  echo "Installing development dependencies for PHP ${PHP_VERSION}."
  apt-get install -y php${PHP_VERSION}-sqlite3
done

# Again this is not recommended. However otherwise there are file permission issues.
echo "Changing apache user to 'vagrant'."
sed -i -e "s|export APACHE_RUN_USER=www-data|export APACHE_RUN_USER=vagrant|" \
  -e "s|export APACHE_RUN_GROUP=www-data|export APACHE_RUN_GROUP=vagrant|" \
  /etc/apache2/envvars

service apache2 restart

echo "Installing bower"
npm install -g bower

echo "Installing swap"
dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
mkswap /var/swap.1
swapon /var/swap.1

echo "Installing composer"
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

echo "Starting installation for Cacti version '${CACTI_VERSION}'"

mkdir ${CACTI_HOME}
if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

echo "Unpacking Cacti"
tar --strip-components 1 --directory=${CACTI_HOME} -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz

mysql -uroot <<EOF
SET GLOBAL sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
grant all on cacti.* to cactiuser@'%' identified by 'cactiuser';
create database weathermaptest;
grant all on weathermaptest.* to weathermaptest@localhost identified by 'weathermaptest';
grant all on weathermaptest.* to weathermaptest@'%' identified by 'weathermaptest';
flush privileges;
EOF

echo "Listening on all devices."
sed -i -e "s|bind-address		= 127.0.0.1|bind-address		= 0.0.0.0|" \
  /etc/mysql/mysql.conf.d/mysqld.cnf


if [[ ${CACTI_VERSION} == 1.* ]]; then
    # Cacti 1.x insists on these
    echo "Adding mysql timezones"
    mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql

    for PHP_VERSION in ${PHP_VERSIONS}; do
      echo "Installing Cacti dependencies for PHP ${PHP_VERSION}."
      apt-get install -y php${PHP_VERSION}-ldap php${PHP_VERSION}-gmp
    done

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
touch ${CACTI_HOME}/log/cacti.log
chmod -R oug+rwx ${CACTI_HOME}/log
chown -R vagrant ${CACTI_HOME}

# optionally seed database with "pre-installed" data instead of empty - can skip the install steps
echo "Loading cacti database"
if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot cacti < ${CACTI_HOME}/cacti.sql
fi

# if WEATHERMAP_VERSION is a version number, this will look for locally-made release files (for pre-release tests)
if [ -f /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip ]; then
  echo "Unzipping weathermap from local release zip"
  unzip /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip -d ${CACTI_HOME}plugins/
  chown -R vagrant ${CACTI_PLUGINS}/weathermap
fi

if [ "${WEATHERMAP_VERSION}" == "git" ]; then
  echo "Cloning weathermap from local git"
  git clone -b database-refactor /network-weathermap ${CACTI_PLUGINS}/weathermap

  chown -R vagrant ${CACTI_PLUGINS}/weathermap
  su -c "cd ${CACTI_PLUGINS}/weathermap && composer update" - vagrant
  su -c "cd ${CACTI_PLUGINS}/weathermap && bower install" - vagrant
  su -c "${CACTI_PLUGINS}/weathermap && composer install" - vagrant
fi

if [ "${WEATHERMAP_VERSION}" == "rsync" ]; then
  echo "rsyncing weathermap from local dir"
  mkdir ${CACTI_PLUGINS}/weathermap
  rsync -a --exclude=composer.lock --exclude=vendor/ /network-weathermap/ ${CACTI_PLUGINS}/weathermap/

  chown -R vagrant ${CACTI_PLUGINS}/weathermap
  su -c "cd ${CACTI_PLUGINS}/weathermap && composer update" - vagrant
  su -c "cd ${CACTI_PLUGINS}/weathermap && bower install" - vagrant
  su -c "${CACTI_PLUGINS}/weathermap && composer install" - vagrant
fi

if [ "${WEATHERMAP_VERSION}" == "mount" ]; then
  echo "Mounting weathermap from vagrant host"

  chown -R vagrant ${CACTI_PLUGINS}/weathermap
  su -c "cd ${CACTI_PLUGINS}/weathermap && composer update" - vagrant
  su -c "cd ${CACTI_PLUGINS}/weathermap && bower install" - vagrant
  su -c "cd ${CACTI_PLUGINS}/weathermap && composer install" - vagrant
fi

# cronjob added but disabled (to enable testing of install process)
# use post-install.sh to override this if required
echo "Adding cron job (disabled)"
echo "# */5 * * * * vagrant /usr/bin/php ${CACTI_HOME}/poller.php > ${CACTI_HOME}/last-cacti-poll.txt 2>&1" > /etc/cron.d/cacti

# create the 'last poll' log file
touch ${CACTI_HOME}/last-cacti-poll.txt
chown vagrant ${CACTI_HOME}/last-cacti-poll.txt

# create the database content for the phpunit database tests, if there is now a weathermap installation with tests
if [ -d ${CACTI_HOME}plugins/weathermap/test-suite ]; then
  echo "Adding php unit database content."
  mysql -uroot weathermaptest < ${CACTI_PLUGINS}/weathermap/test-suite/data/weathermap-empty.sql
fi

if [ "${WITH_SPINE}" == "yes" ]; then
  apt-get install -y build-essential dos2unix dh-autoreconf help2man libssl-dev libmysql++-dev librrds-perl libsnmp-dev libmysqlclient-dev libmysqld-dev

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
fi

# any local tweaks can be added to post-install.sh
if [ -x /vagrant/post-install.sh ]; then
    . /vagrant/post-install.sh
fi
