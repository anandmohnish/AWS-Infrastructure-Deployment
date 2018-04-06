<?php
#AWS Include Statements
require '/var/www/php/aws-autoloader.php';
//Vagrant Box Code
//require '/home/vagrant/php/aws-autoloader.php';
//use Aws\Credentials\CredentialProvider;
//$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
//$provider = CredentialProvider::memoize($provider);
//Vagrant Box Code Ends
use Aws\S3\S3Client;
use Aws\Rds\RdsClient;
use Aws\Sqs\SqsClient;
use Aws\CloudWatch\CloudWatchClient;
use Aws\Exception\AwsException;

$sqs = new Aws\Sqs\SqsClient([
    'version' => 'latest',
    'region'  => 'us-east-1'
]);

#list the SQS Queue URL
$listQueueresult = $sqs->listQueues([ ]);
//echo "Your SQS URL is: " . $listQueueresult['QueueUrls'][0] . "\n";
$queueurl = $listQueueresult['QueueUrls'][0];
$result = $sqs->getQueueAttributes([
    'AttributeNames' => ['ApproximateNumberOfMessages'],
    'QueueUrl' =>$queueurl , // REQUIRED
]);
//print_r($result);
$messages=$result['Attributes']['ApproximateNumberOfMessages'];
//echo $messages;

$cmclient = new  CloudWatchClient([
    //'credentials' => $provider,
    'version' => 'latest',
    'region' => 'us-east-1'
    ]);
    try{

        $jvresult = $cmclient->getMetricStatistics(array(
            'Namespace' => 'AWS/SQS',
            'MetricName' => 'ApproximateNumberOfMessagesVisible',
            //StartTime : mixed type: string (date format)|int (unix timestamp)|\DateTime
            'StartTime' => strtotime('-15 minutes'),
            //EndTime : mixed type: string (date format)|int (unix timestamp)|\DateTime
            'EndTime' => strtotime('now'),
            //The granularity, in seconds, of the returned datapoints. Period must be at least 60 seconds and must be a multiple of 60. The default value is 60
            'Period' => 3000,
            'Statistics' => array('Maximum', 'Minimum'),
        ));
        //var_dump($jvresult);

    }catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}


?>


<html>
<style>
                body {background-color: #CEE0E2;}
                h1   {color: blue;}
                p    {color: black;}
                table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
<head><title>Dashboard</title>
</head>
<center><h1>SQS Dashboard</h1></center>
<body>
<div style="text-align:center; vertical-align:middle">

<div class="pusher">
<!-- Site content !-->

<table>
  <tr>
    <th>Type Of Jobs</th>
    <th>Status</th>
  </tr>
  <tr>
    <td>The number of Messages Visible are</td>
    <td> <?php echo $messages ?> </td>
    
  </tr>
</table>
</div>

<center>
<p>
<a href="index.php">Go To Home</a>
</p>
</center>
</div>
</body>
</html>