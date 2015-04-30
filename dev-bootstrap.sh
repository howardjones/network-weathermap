#!/bin/sh

# the config-based tests require imagemagick's compare command
sudo apt-get install imagemagick
# phpdoc needs the XSL extension
sudo apt-get install php5-xsl graphviz php5-curl php5-gd
# the documentation compilation process needs xsltproc
sudo apt-get install xsltproc

# First, we'll need Bower for web packages, and composer for PHP packages
sudo apt-get install nodejs npm
sudo npm install -g bower

wget https://getcomposer.org/composer.phar
chmod +x composer.phar

bower install
./composer.phar install

# create a few empty directories if necessary 
mkdir docs/dev/generated dist build build/logs

echo "Should be ready to go! Try a ./test.sh"
