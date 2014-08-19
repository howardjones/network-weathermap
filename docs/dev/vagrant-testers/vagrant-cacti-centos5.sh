sudo rpm -Uvh http://dl.fedoraproject.org/pub/epel/5/x86_64/epel-release-5-4.noarch.rpm
sudo yum install -y rrdtool net-snmp-utils apg
sudo yum install -y mysql-server mysql-client
# yum install php-session php-sockets php-snmp php-gd php-xml php-mysql httpd mod_php
sudo yum install -y php-session php-sockets php-gd php-xml php-mysql httpd mod_php
# Extras for SPINE
sudo yum install -y subversion php-ldap unzip
sudo yum install -y autoconf mysql-devel libtool automake
sudo yum install -y gcc kernel-headers net-snmp-devel

/sbin/chkconfig mysqld on
/sbin/chkconfig httpd on
/sbin/service mysqld start
/sbin/service httpd start

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
