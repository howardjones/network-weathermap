#!/bin/sh

# script assumes it is in the root of the git checkout

BASE="/var/www/html/cacti-0.8.8c/plugins"
SOURCE=`pwd`

sudo rm -rf $BASE/weathermap
sudo unzip $SOURCE/dist/php-weathermap-0.98pre.zip -d $BASE
sudo chown -R www-data $BASE/weathermap/configs
sudo chown -R cacti $BASE/weathermap/output

