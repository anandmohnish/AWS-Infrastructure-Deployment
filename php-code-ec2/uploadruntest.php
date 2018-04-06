<!--PHP Code Reference -  https://bavotasan.com/2010/how-to-upload-zip-file-using-php/ -->
<?php
//
require '/var/www/php/aws-autoloader.php';
//Vagrant Box Code
//require '/home/vagrant/php/aws-autoloader.php';
//use Aws\Credentials\CredentialProvider;
//$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
// Cache the results in a memoize function to avoid loading and parsing
 //the ini file on every API operation.
//$provider = CredentialProvider::memoize($provider);
//Vagrant Box Code Ends
use Aws\S3\S3Client;
use Aws\Rds\RdsClient;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
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
    
    $sqsclient = new SqsClient([
        //'credentials' => $provider,
        'version' => 'latest',
        'region' => 'us-east-1'
	]);
	$s3client = new  S3Client([
		//'credentials' => $provider,
		'version' => 'latest',
		'region' => 'us-east-1'
		]);
		$snsclient = new Aws\Sns\SnsClient([
			//'credentials' => $provider,
			'version' => 'latest',
			'region'  => 'us-east-1'
		]);
	$sqsresult = $sqsclient->listQueues([ ]);
	//print_r ( $sqsresult['QueueUrls']);
	$sqsqueue = $sqsresult['QueueUrls'][0];
	//echo $sqsqueue;
	// Create connection
	$r = $rdsclient->describeDBInstances([
		'DBInstanceIdentifier' => 'mydbinstance',]);
	
	//Connect to database
	$servername = $r['DBInstances'][0]['Endpoint']['Address'];
	$username = "masterawsuser";
	$password = "master-userpassword";;
	$dbname = "itmo544db";
	$conn = new mysqli($servername, $username, $password, $dbname);
	$endpoint = $rdsclient->describeDBInstances([
		'DBInstanceIdentifier' => 'mydbinstance',]);
	$servername = $endpoint['DBInstances'][0]['Endpoint']['Address'];
	$username = "masterawsuser";
	$password = "master-userpassword";;
	$dbname = "itmo544db";
	// Check connection
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	echo "<br/>"."Connected successfully"."<br/>";
	$bucket = 'my-pre-bucket';
//

if($_FILES["zip_file"]["name"]) {
	$filename = $_FILES["zip_file"]["name"];
	$source = $_FILES["zip_file"]["tmp_name"];
	$type = $_FILES["zip_file"]["type"];
	
	$name = explode(".", $filename);
	$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
	foreach($accepted_types as $mime_type) {
		if($mime_type == $type) {
			$okay = true;
			break;
		} 
	}
	
	$continue = strtolower($name[1]) == 'zip' ? true : false;
	if(!$continue) {
		$message = "The file you are trying to upload is not a .zip file. Please try again.";
	}

	$target_path = "/tmp/".$filename;  // change this to the correct site path
	if(move_uploaded_file($source, $target_path)) {
		$zip = new ZipArchive();
		$x = $zip->open($target_path);
		if ($x === true) {
			$zip->extractTo("/tmp/"); // change this to the correct site path
			$zip->close();
	
			unlink($target_path);
		}
		$message = "Your .zip file was uploaded and unpacked.";
	} else {	
		$message = "There was a problem with the upload. Please try again.";
	}
//

$dir="/tmp/".$name[0];
//echo $dir;
$ignore = array( 'cgi-bin', '.', '..','._' );
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
			if (!in_array($ignore) and substr($file, 0, 1) != '.')
{
			//echo "filename: .".$file."<br />";
			$target_file=$dir . "/" . $file;
			//echo $target_file;
			$im = file_get_contents($target_file);
			$result = $s3client->putObject([
				'ACL' => 'public-read',
				'Bucket' => $bucket,
				'Key' => $file,
				'SourceFile' => $target_file, 
				]);
				//echo "<br/>".$result['ObjectURL']."<br/>";
				//echo "\n";
				//Write to database
				$ID= uniqid();
				
				//Prepare Statement
				$stmt = $conn->prepare("INSERT INTO records (email, phone, s3_raw_url, s3_finished_url, uid, status, reciept) VALUES (?,?,?,?,?,?,?)");
				$stmt->bind_param("sssssii", $email, $phone, $s3_raw_url, $s3_finished_url, $uid, $status, $reciept);
				$email = 'xyx@xyx.com';
				$phone = '333333';
				$s3_raw_url = $result['ObjectURL'];
				$s3_finished_url = NULL;
				$uid = $ID; //inserting the uniqueid
				$status = 0; // for unprocessed images, status is set to zero
				$reciept = "1"; 
				$stmt->execute();
				
				//echo "<br\>"."New records created successfully"."<br\>";
				
				$stmt->close();
				try {
					//$result = $client->sendMessage($params);
					$sqsresult = $sqsclient->sendMessage([
						//'DelaySeconds' => 30,
						'MessageBody' => $ID, // REQUIRED
						'MessageDeduplicationId' => $ID, // for fifo list
						'MessageGroupId' => 'fifo-list',
						'QueueUrl' => $sqsqueue, // REQUIRED
					]);
					//var_dump($sqsresult);
				} catch (AwsException $e) {
					// output error message if fails
					error_log($e->getMessage());
				}
//*/
}
//sns here	

}
	}
	$snsresult = $snsclient->listTopics();
	//print_r ( $snsresult['Topics']);
	$topicarn = $snsresult['Topics'][0]['TopicArn'];
	//echo $topicarn;
	$subscriberesult = $snsclient->subscribe([
		'Endpoint' => $_POST["emailaddress"],
		'Protocol' => 'email', // REQUIRED
		'TopicArn' => $topicarn, // REQUIRED
	]);	
}
/*
			//$target_file = basename($_FILES['fileToUpload']['name']);
			$target_file=$dir.$file;
			echo $target_file;
			$result = $s3client->putObject([
				'ACL' => 'public-read',
				'Bucket' => $bucket,
				'Key' => $file,
				'SourceFile' => $target_file 
				]);
			echo "<br/>".$result['ObjectURL']."<br/>";
			echo "\n";
			//Write to database
			$ID= uniqid();
			
			//Prepare Statement
			$stmt = $conn->prepare("INSERT INTO records (email, phone, s3_raw_url, s3_finished_url, uid, status, reciept) VALUES (?,?,?,?,?,?,?)");
			$stmt->bind_param("sssssii", $email, $phone, $s3_raw_url, $s3_finished_url, $uid, $status, $reciept);
			$email = 'xyx@xyx.com';
			$phone = '333333';
			$s3_raw_url = $result['ObjectURL'];
			$s3_finished_url = NULL;
			$uid = $ID; //inserting the uniqueid
			$status = 0; // for unprocessed images, status is set to zero
			$reciept = "1"; 
			$stmt->execute();
			
			echo "<br\>"."New records created successfully"."<br\>";
			
			$stmt->close();
			try {
                //$result = $client->sendMessage($params);
                $sqsresult = $sqsclient->sendMessage([
                    //'DelaySeconds' => 30,
                    'MessageBody' => $ID, // REQUIRED
                    'MessageDeduplicationId' => $ID, // for fifo list
                    'MessageGroupId' => 'fifo-list',
                    'QueueUrl' => $sqsqueue, // REQUIRED
                ]);
                //var_dump($sqsresult);
            } catch (AwsException $e) {
                // output error message if fails
                error_log($e->getMessage());
            }
		}
        closedir($dh);
	}
	//sns
	##Subscribe User to SNS
        
$snsclient = new Aws\Sns\SnsClient([
	'credentials' => $provider,
'version' => 'latest',
	'region'  => 'us-east-1'
]);
$snsresult = $snsclient->listTopics();
//print_r ( $snsresult['Topics']);
$topicarn = $snsresult['Topics'][0]['TopicArn'];
//echo $topicarn;
$subscriberesult = $snsclient->subscribe([
	'Endpoint' => $_POST["emailaddress"],
	'Protocol' => 'email', // REQUIRED
	'TopicArn' => $topicarn, // REQUIRED
]);

}
*/
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<style>
    
        body {background-color: #CEE0E2;}
        h1   {color: blue;}
        p    {color: black;}
        </style>
        <script>
function myFunction()
{
alert("Please confirm the email subscription!");
}
</script>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Run Test</title>
</head>
<body>
<center><h1>Convert Image to Old Times - Black & White</h1></center>
<div style="text-align:center; vertical-align:middle">
<?php if($message) echo "<p>$message</p>"; ?>
<form enctype="multipart/form-data" method="post" action="">
<p>
<center>Cannot leave email blank</center>
</p>
<label>Choose a zip file to upload: <input type="file" name="zip_file" /></label>
<br />
<p><label for="email">Your Email</label>
<input type='text' name='emailaddress' style="background-color:#8CBBDF" id='email' placeholder="Enter Email" required/>
<input type="submit" name="submit" value="Upload" onclick="myFunction()" value="Show alert box" style="background-color:#8CBBDF"  />
<center><p>
<a href="index.php">Go to Home Page</a>
</p></center>
</form>
</div>
</body>
</html>
