#!/bin/sh

# First, we'll need Bower for web packages, and composer for PHP packages

sudo apt-get install nodejs
sudo npm install -g bower

wget https://getcomposer.org/composer.phar
chmod +x composer.phar

bower install
./composer.phar install

# the config-based tests require imagemagick's compare command
sudo apt-get install imagemagick

echo "Should be ready to go! Try a ./test.sh"