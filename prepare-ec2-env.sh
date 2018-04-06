#!/bin/bash
sudo apt-get update -y
sudo apt-get install php -y
sudo apt-get install apache2 -y
sudo apt-get install unzip -y
sudo apt-get install php libapache2-mod-php php-mcrypt php-mysql -y
sudo apt-get install php7.0-gd -y
sudo apt-get install php-xml -y
sudo apt-get install php-simplexml -y
sudo service apache2 restart
cd /var/www/
mkdir php
cd php
wget https://github.com/aws/aws-sdk-php/releases/download/3.36.26/aws.zip
unzip aws.zip
mkdir -p ~/.aws/
cd ~/.aws/
sudo service apache2 restart