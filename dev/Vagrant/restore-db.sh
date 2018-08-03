#!/usr/bin/env bash

# Get the common settings (CACTI_VERSION etc)
. /vagrant/settings.sh

echo "Dropping and recreating cacti database"
echo "drop database cacti; create database cacti;" | mysql -uroot

echo "Loading cacti data"
if [ -f /vagrant/cacti-${CACTI_VERSION}-post-install.sql ]; then
  echo "From restore"
  sudo mysql -uroot cacti < /vagrant/cacti-${CACTI_VERSION}-post-install.sql
else
  echo "From default"
  sudo mysql -uroot cacti < ${WEBROOT}/cacti/cacti.sql
fi