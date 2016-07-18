

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

mysql -uroot <<EOF
create database cacti;
grant all on cacti.* to cactiuser@localhost identified by 'cactiuser';
flush privileges;
EOF
