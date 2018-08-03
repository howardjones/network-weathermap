#!/bin/bash

## TODO:
# Add error handling. We don't want to strand a potential contributor just because one single package fails
##

# some OK defaults (better than blanks if the settings.sh is missing)
CACTI_VERSION="0.8.8h"
WEATHERMAP_VERSION="git"
PHP_VERSION="5.6"

sudo add-apt-repository ppa:ondrej/php
sudo apt-get update -y
## For 'real' install:
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server-5.7 snmp rrdtool php7.0 php7.1 php5.6 php5.6-common php5.6-cli php5.6-mysql apache2 libapache2-mod-php5.6 libapache2-mod-php7.0 unzip php5.6-snmp php5.6-gd php-gettext php5.6-mbstring php-xdebug unzip php5.6-xml php7.0-xml php7.0-mbstring php7.0-curl  php7.0-gd  php7.0-mysql php7.0-cli php7.1-xml php7.1-mbstring php7.1-curl  php7.1-gd  php7.1-mysql php7.1-cli php7.1-ldap php7.1-snmp php7.1-gmp
# ## For dev/test, we need these too
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y git subversion make xsltproc imagemagick zip curl phpunit nodejs npm pandoc rsync nodejs-legacy php5.6-sqlite3 php7.0-sqlite3 php7.1-sqlite3 php-ast
# ## For composer
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php-mbstring php5.6-curl php7.0-curl php7.1-curl
#

#Install and run bower
sudo npm install -g bower
# sudo ln -s /usr/bin/nodejs /usr/bin/node
cd /network-weathermap
bower install

sudo bash -c "cat > /etc/php/$PHP_VERSION/cli/conf.d/99-cacti.ini" <<'EOF'
[Date]
date.timezone=Europe/London
EOF

sudo cp /etc/php/$PHP_VERSION/cli/conf.d/99-cacti.ini /etc/php/$PHP_VERSION/apache2/conf.d/99-cacti.ini

sudo service apache2 restart

#Install and run composer (this requires a swap partition for memory as well..)
sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
sudo /sbin/mkswap /var/swap.1
sudo /sbin/swapon /var/swap.1
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# cd /network-weathermap
# composer install



WEBROOT="/var/www/html"
echo "Starting installation for Cacti $CACTI_VERSION"


sudo mkdir ${WEBROOT}/cacti

sudo useradd -d ${WEBROOT}/cacti cacti

if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   sudo wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

echo "Unpacking Cacti"
sudo tar --strip-components 1 --directory=${WEBROOT}/cacti -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz
sudo touch ${WEBROOT}/cacti/log/cacti.log
sudo chown -R cacti ${WEBROOT}/cacti/rra
sudo chown -R cacti ${WEBROOT}/cacti/log

sudo mysql -uroot <<EOF
SET GLOBAL sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
create database weathermaptest;
grant all on weathermaptest.* to weathermaptest@localhost identified by 'weathermaptest';
flush privileges;
EOF

# Cacti 1.x insists on these
echo "Adding mysql timezones"
mysql_tzinfo_to_sql /usr/share/zoneinfo | sudo mysql -u root mysql

if [[ $CACTI_VERSION == 1.* ]]; then
    # Cacti 1.x also makes a few optional modules required.
    sudo apt-get install -y php5.6-ldap php7.0-ldap php5.6-gmp php7.0-gmp php7.1-gmp php7.1-ldap


    sudo mysql -uroot <<EOF
grant select on mysql.time_zone_name to cactiuser@localhost identified by 'cactiuser';
flush privileges;
ALTER DATABASE cacti CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
EOF
    # it also suggests a lot of database tweaks. mostly they are for performance, but still
    sudo bash -c "cat > /etc/mysql/mysql.conf.d/cacti.cnf" <<'EOF'
[mysqld]
max_heap_table_size=64M
join_buffer_size=64M
tmp_table_size=64M
innodb_buffer_pool_size=250M
innodb_doublewrite=off
innodb_flush_log_at_timeout=3
innodb_read_io_threads=32
innodb_write_io_threads=16
EOF

    sudo service mysql restart
    sudo service apache2 restart    

    # this isn't in the recommendations, but otherwise you get no logs!
    sudo chmod g+wrx $WEBROOT/cacti/log
    sudo touch $WEBROOT/cacti/log/cacti.log
    sudo chmod g+wr $WEBROOT/cacti/log/cacti.log

    sudo chown -R www-data.cacti $WEBROOT/cacti/resource/ $WEBROOT/cacti/scripts $WEBROOT/cacti/log $WEBROOT/cacti/cache
fi

# optionally seed database with "pre-installed" data instead of empty - can skip the install steps
echo "Loading cacti database"
if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  sudo mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  sudo mysql -uroot cacti < ${WEBROOT}/cacti/cacti.sql
fi

if [ -f /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip ]; then
  # Install Network Weathermap from release zip
  echo "Unzipping weathermap from local release zip"
  sudo unzip /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip -d /var/www/html/cacti/plugins/
  sudo chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
fi

# alternatively, check out the local git repo into the Cacti dir
if [ "X$WEATHERMAP_VERSION" = "Xgit" ]; then
  echo "Cloning weathermap from local git"
  git clone -b database-refactor /network-weathermap $WEBROOT/cacti/plugins/weathermap
  cd ${WEBROOT}/cacti/plugins/weathermap
  bower install
  composer install
  sudo chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
fi

# final possibility: rsync from the local copy into the Cacti dir
# (doesn't really work with windows host, due to line endings)
if [ "X$WEATHERMAP_VERSION" = "Xrsync" ]; then
  echo "rsyncing weathermap from local dir"
  mkdir $WEBROOT/cacti/plugins/weathermap
  rsync -a --exclude=composer.lock --exclude=vendor/ /network-weathermap/ $WEBROOT/cacti/plugins/weathermap/
  cd ${WEBROOT}/cacti/plugins/weathermap
  bower install
  composer install
  sudo chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
fi


# create the 'last poll' log file
sudo touch $WEBROOT/last-cacti-poll.txt
sudo chown cacti ${WEBROOT}/last-cacti-poll.txt

# create the database content for the phpunit database tests, if there is now a weathermap installation with tests
if [ -d ${WEBROOT}/cacti/plugins/weathermap/test-suite ]; then   
  sudo mysql -uroot weathermaptest < ${WEBROOT}/cacti/plugins/weathermap/test-suite/data/weathermap-empty.sql
fi 

# so that the editor and poller don't immediately complain
sudo chown cacti /var/www/html/cacti/plugins/weathermap/output
sudo chown www-data /var/www/html/cacti/plugins/weathermap/configs

# any local tweaks can be added to post-install.sh (which needs to be marked executable)
if [ -x /vagrant/post-install.sh ]; then
    /vagrant/post-install.sh
fi
