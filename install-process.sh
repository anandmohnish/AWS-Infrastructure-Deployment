#!/bin/bash
cd ~
#git clone git@github.com:illinoistech-itm/manand1.git
cd manand1
git pull
#cd ./manand1/ITMO544/MP-2/php-code-ec2
cd /var/spool/cron/crontabs/
cp /root/manand1/ITMO544/MP-2/root-cron .
mv root-cron root
touch /tmp/process-sh.log
touch /tmp/process.log
chmod 755 /root/manand1/ITMO544/MP-2/process.sh
sleep 15m
sh -x /root/manand1/ITMO544/MP-3/process.sh >>/tmp/process-sh.log &
