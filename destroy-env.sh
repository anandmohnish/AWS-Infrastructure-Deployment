#Delete Bucket
aws s3 rb s3://my-pre-bucket --force
aws s3 rb s3://my-post-bucket --force

#Delete Database
aws rds delete-db-instance --db-instance-identifier mydbinstancerr --skip-final-snapshot
aws rds wait db-instance-deleted --db-instance-identifier mydbinstancerr
aws rds delete-db-instance --db-instance-identifier mydbinstance --skip-final-snapshot

#Delete AutoScaling Group
aws autoscaling delete-auto-scaling-group --auto-scaling-group-name itmo-544-asg --force-delete

#Delete Autoscaling Configuration
aws autoscaling delete-launch-configuration --launch-configuration-name itmo-544-lc

#Delete Load Balancer
lb_arn=$(aws elbv2 describe-load-balancers --names itmo-544-lb | grep LoadBalancerArn | grep -o -P '(?<="LoadBalancerArn": ").*(?=")')
lis_arn=$(aws elbv2 describe-listeners --load-balancer-arn $lb_arn |grep ListenerArn | grep -o -P '(?<="ListenerArn": ").*(?=")')
aws elbv2 delete-listener --listener-arn $lis_arn
aws elbv2 delete-load-balancer --load-balancer-arn $lb_arn

#Delete LB Target Group
tg_arn=$(aws elbv2 describe-target-groups --names itmo-544-targets | grep TargetGroupArn | grep -o -P '(?<="TargetGroupArn": ").*(?=")')
#Deregister from target group
aws ec2 describe-instances --query 'Reservations[*] .Instances[*].[InstanceId]' --filters Name=instance-state-name,Values=running --output text >ec2
iid1=$(head -n 1 ec2 | tail -1)
iid2=$(head -n 2 ec2 | tail -1)
iid3=$(head -n 3 ec2 | tail -1)
iid4=$(head -n 4 ec2 | tail -1)
aws elbv2 deregister-targets --target-group-arn $tg_arn --targets Id=$iid1 Id=$iid2
aws elbv2 delete-target-group --target-group-arn $tg_arn
aws ec2 terminate-instances --instance-ids $iid1
aws ec2 terminate-instances --instance-ids $iid2
aws ec2 terminate-instances --instance-ids $iid3
aws ec2 terminate-instances --instance-ids $iid4
#Delete SNS
topicarn=$(aws sns list-topics | grep TopicArn | grep -o -P '(?<="TopicArn": ").*(?=")')
aws sns delete-topic --topic-arn $topicarn

#Delete SQS
sqsqueue=$(aws sqs list-queues | grep https | grep -o -P '(?<=").*(?=")')
aws sqs delete-queue --queue-url $sqsqueue
