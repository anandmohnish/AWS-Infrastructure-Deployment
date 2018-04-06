<?php
#AWS Include Statements
//require '/var/www/php/aws-autoloader.php';
//Vagrant Box Code
require '/home/vagrant/php/aws-autoloader.php';
use Aws\Credentials\CredentialProvider;
$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
$provider = CredentialProvider::memoize($provider);
//Vagrant Box Code Ends
use Aws\S3\S3Client;
use Aws\Rds\RdsClient;
use Aws\Sqs\SqsClient;
use Aws\CloudWatch\CloudWatchClient;
use Aws\Exception\AwsException;


$cmclient = new  CloudWatchClient([
    'credentials' => $provider,
    'version' => 'latest',
    'region' => 'us-east-1'
    ]);
    try{

        $jvresult = $cmclient->getMetricStatistics(array(
            'Namespace' => 'AWS/SQS',
            'MetricName' => 'ApproximateNumberOfMessagesVisible',
            //StartTime : mixed type: string (date format)|int (unix timestamp)|\DateTime
            'StartTime' => strtotime('-1 days'),
            //EndTime : mixed type: string (date format)|int (unix timestamp)|\DateTime
            'EndTime' => strtotime('now'),
            //The granularity, in seconds, of the returned datapoints. Period must be at least 60 seconds and must be a multiple of 60. The default value is 60
            'Period' => 3000,
            'Statistics' => array('Maximum', 'Minimum'),
        ));
        var_dump($jvresult);

    }catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}


?>
