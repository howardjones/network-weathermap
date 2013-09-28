
sudo apt-get update -y
## For 'real' install:
sudo apt-get install -y mysql-server snmp rrdtool php5-cli php5-mysql apache2 libapache2-mod-php5 unzip php5-snmp php5-gd
# ## For dev/test, we need these too
sudo apt-get install -y subversion make xsltproc imagemagick zip curl phpunit
#

CACTI_VERSION="0.8.8b"

sudo useradd -d /var/www/cacti cacti

sudo mkdir /var/www/cacti

if [ ! -f /vagrant/cacti-${CACTI_VERSION}.tar.gz ]; then
   sudo wget http://www.cacti.net/downloads/cacti-${CACTI_VERSION}.tar.gz -O /vagrant/cacti-${CACTI_VERSION}.tar.gz
fi

sudo tar --strip-components 1 --directory=/var/www/cacti -xvf /vagrant/cacti-${CACTI_VERSION}.tar.gz 
sudo chown -R cacti /var/www/cacti/rra
sudo chown -R cacti /var/www/cacti/log

mysql -uroot <<EOF
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
flush privileges;
EOF

if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot cacti < /var/www/cacti/cacti.sql
fi


sudo echo '# */5 * * * * cacti /usr/bin/php /var/www/cacti/poller.php > /var/www/last-cacti-poll 2>&' > /etc/cron.d/cacti



