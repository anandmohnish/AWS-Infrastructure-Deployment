#Execute chmod 755 create-env.sh
#Run the script by typing sh create-env.sh

#!/bin/bash
#Stop executing if error occurs
set -e
#Verbose output
#set -v

#echo "Enter AMI ID : "
#read ami_id
#echo "Enter Number of instances you want to create (count) : "
#read count
#Uncommnet below after final test run
echo "Enter the name of keypair you want to associate with Instances : "
read key_pair
echo "Enter the name of security group you want to associate with Instances : "
read sg
echo "Enter IAM Role name to be associated with Auto Scaling Group : "
read iamrole
echo "Enter Minimum Instance Required for Scaling Group - Desired Value is 1"
read min_instances
echo "Enter Maximum Instances Required for Scaling Group - Desired Value is 3 or 5"
read max_instances
echo "Enter VPC ID you want to associate with Loab Balancer :"
read vpcid

ami_id=ami-c0b22bba
#Commnet below after final test run
#key_pair=itmo-544-key
#sg=sg-036c9a70
#min_instances=1
#max_instances=3
#vpcid=vpc-8cb737f5
#iamrole=test-ec2

echo $ami_id
echo $count
echo $key_pair
echo $sg
echo $min_instances
echo $max_instances

#open database port in security group

#Create IAM Roles and assign Policies to it
#aws iam create-role --role-name auto-scaling-role --assume-role-policy-document file://./iam-policies/auto-scaling-role.json
#aws iam put-role-policy --role-name auto-scaling-role --policy-name S3-Rds-Ec2-Full --policy-document file://./iam-policies/s3-rds-ec2-policy.json

#Create RDS
aws rds create-db-instance \
--db-instance-identifier mydbinstance \
--db-instance-class db.m1.small \
--engine MySQL \
--allocated-storage 20 \
--master-username masterawsuser \
--master-user-password master-userpassword \
--vpc-security-group-ids $sg \
--backup-retention-period 3

aws rds wait db-instance-available --db-instance-identifier mydbinstance

#Get the list of subnets from the VPC
#subnets=$(aws ec2 describe-subnets --filters "Name =vpc-id,Values=$vpcid" --query 'Subnets[*].[SubnetId]')
subnets=`aws ec2 describe-subnets --filters  --query 'Subnets[*].SubnetId' --output text | grep "subnet-"`
echo $subnets

#Creating Launch Config
aws autoscaling create-launch-configuration --key-name $key_pair --launch-configuration-name itmo-544-lc --iam-instance-profile $iamrole \
--image-id $ami_id --instance-type t2.micro --security-groups $sg --instance-monitoring Enabled=true --user-data file://install-app-env.sh

#Creating Load Balancer and Target Group
LB_ARN=$(aws elbv2 create-load-balancer --name itmo-544-lb \
--subnets $subnets \
--security-groups $sg | grep LoadBalancerArn | grep -o -P '(?<="LoadBalancerArn": ").*(?=")')
echo $LB_ARN

#Creating Load Balancer
TARGET_ARN=$(aws elbv2 create-target-group --name itmo-544-targets \
--protocol HTTP --port 80 --vpc-id $vpcid | grep TargetGroupArn | grep -o -P '(?<="TargetGroupArn": ").*(?=")')
echo $TARGET_ARN

#Create Load Balancer Listener
aws elbv2 create-listener --load-balancer-arn $LB_ARN \
--protocol HTTP --port 80  \
--default-actions Type=forward,TargetGroupArn=$TARGET_ARN

#Create LB Stickiness Policy
#aws elb create-lb-cookie-stickiness-policy --load-balancer-name itmo-544-lb --policy-name enable-stickiness-cookie-policy --cookie-expiration-period 60

#Create EC2 instance for Image Processing
aws ec2 run-instances --image-id ami-c0b22bba --security-group-ids $sg \
--count 1 --instance-type t2.micro --key-name $key_pair \
--user-data file://install-process.sh --iam-instance-profile Name=$iamrole --monitoring Enabled=true

#Create Auto Scaling Group with target-group
aws autoscaling create-auto-scaling-group --auto-scaling-group-name itmo-544-asg \
--launch-configuration-name itmo-544-lc \
--availability-zones us-east-1c \
--target-group-arns $TARGET_ARN \
--min-size $min_instances --max-size $max_instances --desired-capacity 2

#create S3 buckets
aws s3api create-bucket --bucket my-pre-bucket --acl public-read-write --region us-east-1
aws s3api create-bucket --bucket my-post-bucket --acl public-read-write --region us-east-1

#sleep 500

#Get DB Address/DNS
DB_Address=$(aws rds describe-db-instances --db-instance-identifier mydbinstance | grep Address | grep -o -P '(?<="Address": ").*(?=")')
#mysql -h $DB_Address -P 3306 -u masterawsuser -p master-userpassword
# Connecting to Database and creating database and table
mysql --host=$DB_Address -P 3306 --user=masterawsuser --password=master-userpassword < create-database.sql
mysql --host=$DB_Address -P 3306 --user=masterawsuser --password=master-userpassword < create-table.sql

#Create LB Stickiness Policy
#aws elb create-lb-cookie-stickiness-policy --load-balancer-name itmo-544-lb --policy-name enable-stickiness-cookie-policy --cookie-expiration-period 60

#Create SNS 
aws sns create-topic --name image-processed

#create SQS
aws sqs create-queue --queue-name my-sqs-ma.fifo --attributes FifoQueue=true

#Enable Group Metric for AutoScaling Group
aws autoscaling enable-metrics-collection --auto-scaling-group-name itmo-544-asg --granularity "1Minute"

#Create Grafana instance
aws ec2 run-instances --image-id ami-ae69f0d4 --security-group-ids $sg \
--count 1 --instance-type t2.micro --key-name $key_pair \
--iam-instance-profile Name=$iamrole --user-data file://start-grafana.sh

#Create Read Replica
aws rds create-db-instance-read-replica \
--db-instance-identifier mydbinstancerr \
--source-db-instance-identifier mydbinstance

aws rds wait db-instance-available --db-instance-identifier mydbinstancerr
