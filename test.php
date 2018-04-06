
<?php
//require '/var/www/php/aws-autoloader.php';
//Vagrant Box Code
require '/home/vagrant/php/aws-autoloader.php';
use Aws\Credentials\CredentialProvider;
$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
// Cache the results in a memoize function to avoid loading and parsing
// the ini file on every API operation.
$provider = CredentialProvider::memoize($provider);
//Vagrant Box Code Ends
use Aws\S3\S3Client;
use Aws\Rds\RdsClient;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

/*
$client = new  S3Client([
'version' => 'latest',
'region' => 'us-east-1'
]);

$rdsclient = RdsClient::factory(array(
'version' => 'latest',
'region'  => 'us-east-1'
));
$bucket = 'my-pre-bucket';
*/
$client = new  S3Client([
'credentials' => $provider,
'version' => 'latest',
'region' => 'us-east-1'
]);
$rdsclient = RdsClient::factory(array(
'credentials' => $provider,    
'version' => 'latest',
'region'  => 'us-east-1'
));

$sqsclient = new SqsClient([
    'credentials' => $provider,
    'version' => 'latest',
    'region' => 'us-east-1'
]);

$sqsresult = $sqsclient->listQueues([ ]);
print_r ( $sqsresult['QueueUrls']);
$sqsqueue = $sqsresult['QueueUrls'][0];
echo $sqsqueue;
