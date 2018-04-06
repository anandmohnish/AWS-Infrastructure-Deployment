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
use Aws\Exception\AwsException;
    #Create Clinet for S3 and RDS
    $client = new  S3Client([
        //'credentials' => $provider,
        'version' => 'latest',
        'region' => 'us-east-1'
        ]);
        $rdsclient = RdsClient::factory(array(
        //'credentials' => $provider,
        'version' => 'latest',
        'region'  => 'us-east-1'
        ));

     #Connect to Database
     $endpoint = $rdsclient->describeDBInstances(['DBInstanceIdentifier' => 'mydbinstancerr',]);
     $servername = $endpoint['DBInstances'][0]['Endpoint']['Address'];
     $username = "masterawsuser";
     $password = "master-userpassword";
     $dbname = "itmo544db";
     // Create connection
     $conn = new mysqli($servername, $username, $password, $dbname);
     // Check connection
     if ($conn->connect_error) 
         {
             die("Connection failed: " . $conn->connect_error);
         }
     echo "<br/>"."Connected successfully"."<br/>";
	 $dat = date("Y-m-d");
	echo $dat;
    #Query DB to find number of jobs processed today
    $stmt = $conn->prepare("SELECT id from records where dateprocessed = ?");
    $stmt->bind_param("s", $dat);
    
//$dat = date("Y-m-d");
	//$uuid=$sqs_uid;

   //echo "<br\>".$uuid."<br\>";
    //$stmt->execute();
$stmt->execute();
$result = $stmt->get_result();  
echo "<br\>"."The number of Jobs processed today are : $result->num_rows "."<br\>";
//echo $result->num_rows;

//Pending Process below
$stmt2 = $conn->prepare("SELECT id from records where status = ?");
$stmt2->bind_param("i", $stat);
$stat = '0';
//$dat = date("Y-m-d");
//$uuid=$sqs_uid;

//echo "<br\>".$uuid."<br\>";
//$stmt->execute();
$stmt2->execute();
$result2 = $stmt2->get_result();  
echo "<br\>"."The number of Jobs pending to be processed are : $result2->num_rows "."<br\>";

$stmt3 = $conn->prepare("SELECT id from records where status = ?");
$stmt3->bind_param("i", $stat2);
$stat2 = '1';
//$dat = date("Y-m-d");
//$uuid=$sqs_uid;

//echo "<br\>".$uuid."<br\>";
//$stmt->execute();
$stmt3->execute();
$result3 = $stmt3->get_result();  
echo "<br\>"."The number of Jobs processed till date are : $result3->num_rows "."<br\>";

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
<center><h1>Database Dashboard</h1></center>
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
    <td>The number of Jobs processed today are</td>
    <td> <?php echo $result->num_rows?> </td>
    
  </tr>
  <tr>
    <td>The number of Jobs pending to be processed are</td>
    <td><?php echo $result2->num_rows ?> </td>
    
  </tr>
  <tr>
    <td>The number of Jobs processed till date are</td>
    <td><?php echo $result3->num_rows ?> </td>
    
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
