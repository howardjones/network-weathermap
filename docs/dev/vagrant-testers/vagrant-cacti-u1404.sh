
sudo apt-get update -y
## For 'real' install:
sudo apt-get install -y mysql-server-5.5 snmp rrdtool php5-cli php5-mysql apache2 libapache2-mod-php5 unzip php5-snmp php5-gd
# ## For dev/test, we need these too
sudo apt-get install -y subversion make xsltproc imagemagick zip curl phpunit
#

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

mysql -uroot <<EOF
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
flush privileges;
EOF

if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  mysql -uroot cacti < ${WEBROOT}/cacti/cacti.sql
fi

sudo echo '# */5 * * * * cacti /usr/bin/php ${WEBROOT}/cacti/poller.php > ${WEBROOT}/last-cacti-poll.txt 2>&' > /etc/cron.d/cacti

