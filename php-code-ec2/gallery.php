<html>
<style>
                body {background-color: #CEE0E2;}
                h1   {color: blue;}
                p    {color: black;}
</style>
<head><title>Gallery</title>
</head>

<center><h1>Welcome to Gallery</h1></center>
<body>
<div style="text-align:center; vertical-align:middle">

  <div class="pusher">
    <!-- Site content !-->
  </div>
<?php
//session_start();
require '/var/www/php/aws-autoloader.php';
//use Aws\Credentials\CredentialProvider;
//require '/home/vagrant/php/aws-autoloader.php';
//use Aws\Credentials\CredentialProvider;
//$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
// Cache the results in a memoize function to avoid loading and parsing
 //the ini file on every API operation.
//$provider = CredentialProvider::memoize($provider);
use Aws\Rds\RdsClient;
$rds1 = RdsClient::factory(array(
    //'credentials' => $provider,
    'version' => 'latest',
    'region'  => 'us-east-1'
));
$r = $rds1->describeDBInstances([
    'DBInstanceIdentifier' => 'mydbinstancerr',]);

//Connect to database
$servername = $r['DBInstances'][0]['Endpoint']['Address'];
//echo "Here1";
$username = "masterawsuser";
$password = "master-userpassword";;
$dbname = "itmo544db";
//echo "Here 2";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


//Fetch the Results
$conn->real_query("SELECT * FROM records");
$result = $conn->use_result();
//echo "Result set order...\n";
while ($row = $result->fetch_assoc()) {

    echo "<img src =\" " . $row['s3_raw_url'] . "\" /><img src =\"" .$row['s3_finished_url'] . "\"/>";
    echo nl2br("\n");
    //echo $row['id'] . "Email: " . $row['email'];

}
//echo "successfully executed";
$conn->close();
?>
<center><p>
<a href="index.php">Convert more images</a>
</p></center>

</div>
</body>
</html>
