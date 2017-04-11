sudo add-apt-repository ppa:ondrej/php
sudo apt-get update -y
## For 'real' install:
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server-5.7 snmp rrdtool php7.0 php5.6 php5.6-common php5.6-cli php5.6-mysql apache2 libapache2-mod-php5.6 libapache2-mod-php7.0 unzip php5.6-snmp php5.6-gd php-gettext php5.6-mbstring php-xdebug unzip php5.6-xml
# ## For dev/test, we need these too
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y git subversion make xsltproc imagemagick zip curl phpunit nodejs npm pandoc
#

#Install and run bower
sudo npm install -g bower
sudo ln -s /usr/bin/nodejs /usr/bin/node
cd /network-weathermap
bower install --allow-root

#Change to php 5.6
sudo a2dismod php7.0
sudo a2enmod php5.6
sudo rm /etc/alternatives/php
sudo ln -s /usr/bin/php5.6 /etc/alternatives/php
sudo service apache2 restart

# Get the common settings (CACTI_VERSION etc)
. /vagrant/settings.sh

WEBROOT="/var/www/html"
echo "Starting installation for Cacti $CACTI_VERSION"


sudo mkdir ${WEBROOT}/cacti

sudo useradd -d ${WEBROOT}/cacti cacti

if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   sudo wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

sudo tar --strip-components 1 --directory=${WEBROOT}/cacti -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz 
sudo chown -R cacti ${WEBROOT}/cacti/rra
sudo chown -R cacti ${WEBROOT}/cacti/log

sudo mysql -uroot <<EOF
SET GLOBAL sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
flush privileges;
EOF

if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  sudo mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  sudo mysql -uroot cacti < ${WEBROOT}/cacti/cacti.sql
fi

sudo echo '# */5 * * * * cacti /usr/bin/php ${WEBROOT}/cacti/poller.php > ${WEBROOT}/last-cacti-poll.txt 2>&' > /etc/cron.d/cacti

# Install Network Weathermap
sudo unzip /network-weathermap/releases/php-weathermap-${WEATHERMAP_VERSION}.zip -d /var/www/html/cacti/plugins/
sudo chown -R cacti ${WEBROOT}/cacti/plugins/weathermap
