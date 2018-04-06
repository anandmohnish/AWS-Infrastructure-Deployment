#!/bin/bash
#sudo apt-get update -y
#sudo apt-get install php -y
#sudo apt-get install apache2 -y
#sudo apt-get install unzip -y
#sudo apt-get install php libapache2-mod-php php-mcrypt php-mysql -y
cd /root/manand1
#git clone git@github.com:illinoistech-itm/manand1.git 
git pull
mv /var/www/html/index.html /var/www/html/index.html_orignal
cp /root/manand1/ITMO544/MP-3/php-code-ec2/* /var/www/html/
service apache2 restart