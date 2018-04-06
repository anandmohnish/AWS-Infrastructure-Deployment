<?php
//require '/var/www/php/aws-autoloader.php';
//Vagrant Box Code
require '/home/vagrant/php/aws-autoloader.php';
use Aws\Credentials\CredentialProvider;
$provider = CredentialProvider::ini('default','/home/vagrant/.aws/credentials');
// Cache the results in a memoize function to avoid loading and parsing
 //the ini file on every API operation.
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

//$sqsqueue="https://sqs.us-east-1.amazonaws.com/552505984612/my-sqs-ma.fifo";

//New Code

$errors = array();
$uploadedFiles = array();
$extension = array("jpeg","jpg","png","gif");
$bytes = 1024;
$KB = 1024;
$totalBytes = $bytes * $KB;
$UploadFolder = "/tmp/";
 
$counter = 0;
 
foreach($_FILES["files"]["tmp_name"] as $key=>$tmp_name){
    $temp = $_FILES["files"]["tmp_name"][$key];
    $name = $_FILES["files"]["name"][$key];
    $target_file = $UploadFolder . basename($_FILES["files"]["name"][$key]);
    echo "<br/>" . $target_file . "<br/>";
    if(empty($temp))
    {
        break;
    }
     
    $counter++;
    $UploadOk = true;
     
    if($_FILES["files"]["size"][$key] > $totalBytes)
    {
        $UploadOk = false;
        array_push($errors, $name." file size is larger than the 1 MB.");
    }
     
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    if(in_array($ext, $extension) == false){
        $UploadOk = false;
        array_push($errors, $name." is invalid file type.");
    }
     
    if(file_exists($UploadFolder."/".$name) == true){
        $UploadOk = false;
        array_push($errors, $name." file is already exist.");
    }
     
    if($UploadOk == true){
        move_uploaded_file($temp,$UploadFolder."/".$name);
        array_push($uploadedFiles, $name);
        //Place Image in S3
        $bucket = 'my-pre-bucket';
        $s3client = new  S3Client([
            'credentials' => $provider,
            'version' => 'latest',
            'region' => 'us-east-1'
            ]);
        $result = $s3client->putObject([
            'ACL' => 'public-read',
            'Bucket' => $bucket,
            'Key' => $name,
            'SourceFile' => $target_file 
            ]);
        echo "<br/>".$result['ObjectURL']."<br/>";
        echo "\n";
        //Write to database
        $ID= uniqid();
        $endpoint = $rdsclient->describeDBInstances([
            'DBInstanceIdentifier' => 'mydbinstance',]);
        $servername = $endpoint['DBInstances'][0]['Endpoint']['Address'];
        $username = "masterawsuser";
        $password = "master-userpassword";;
        $dbname = "itmo544db";
        
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        echo "<br/>"."Connected successfully"."<br/>";
        //Prepare Statement
        $stmt = $conn->prepare("INSERT INTO records (email, phone, s3_raw_url, s3_finished_url, uid, status, reciept) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssii", $email, $phone, $s3_raw_url, $s3_finished_url, $uid, $status, $reciept);
        $email = $_POST["emailaddress"];
        $phone = $_POST["phone"];
        $s3_raw_url = $result['ObjectURL'];
        $s3_finished_url = NULL;
        $uid = $ID; //inserting the uniqueid
        $status = 0; // for unprocessed images, status is set to zero
        $reciept = "1"; 
        $stmt->execute();
        
        echo "<br\>"."New records created successfully"."<br\>";
        
        $stmt->close();
        $conn->close();

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

        //Place Message in SQS
            
            //$ID= uniqid();


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
}
 
if($counter>0){
    if(count($errors)>0)
    {
        echo "<b>Errors:</b>";
        echo "<br/><ul>";
        foreach($errors as $error)
        {
            echo "<li>".$error."</li>";
        }
        echo "</ul><br/>";
    }
     
    if(count($uploadedFiles)>0){
        echo "<b>Uploaded Files:</b>";
        echo "<br/><ul>";
        foreach($uploadedFiles as $fileName)
        {
            echo "<li>".$fileName."</li>";
        }
        echo "</ul><br/>";
         
        echo count($uploadedFiles)." file(s) are successfully uploaded.";
    }                               
}
else{
    echo "Please, Select file(s) to upload.";
}


//Get Message from Queue
try {
    $sqsresult2 = $sqsclient->receiveMessage(array(
        'AttributeNames' => ['SentTimestamp'],
        'MaxNumberOfMessages' => 10,
        'MessageAttributeNames' => ['All'],
        'QueueUrl' => $sqsqueue, // REQUIRED
        'WaitTimeSeconds' => 0,
    ));
    $forloop=(count($sqsresult2->get('Messages')));
    echo "<br/>".$forloop."<br/>";
    if (count($sqsresult2->get('Messages')) > 0) {
        for ($i = 0; $i < $forloop; $i++) {
            echo "<br/>".$i."<br/>";
            var_dump($sqsresult2->get('Messages')[$i])."<br/>";    
        }
        //var_dump($sqsresult2->get('Messages')[0]);
       // $sqsresult2 = $client->deleteMessage([
         //   'QueueUrl' => $queueUrl, // REQUIRED
           // 'ReceiptHandle' => $sqsresult2->get('Messages')[0]['ReceiptHandle'] // REQUIRED
        //]);
    } else {
        echo "No messages in queue. \n";
    }
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}

/*

$target_dir = "/tmp/";
$target_file = $target_dir . basename($_FILES['fileToUpload']['name']);
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_file)) {
        //S3 Put here
        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}

chmod( "/tmp/".$_FILES["fileToUpload"]["name"], 0777 );
//$attempt = $client->putObject([$bucket, $_FILES["fileToUpload"]["name"], "/tmp/".$_FILES["fileToUpload"]["name"]]);
echo $target_file;


$result = $client->putObject([
    'ACL' => 'public-read',
    'Bucket' => $bucket,
	'Key' => $_FILES["fileToUpload"]["name"],
	'SourceFile' => $target_file 
    ]);
echo $result['ObjectURL']; 

//Connecting to database below
$r = $rdsclient->describeDBInstances([
    'DBInstanceIdentifier' => 'mydbinstance',]);

//Connect to database
$servername = $r['DBInstances'][0]['Endpoint']['Address'];
$username = "masterawsuser";
$password = "master-userpassword";;
$dbname = "itmo544db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";

$stmt = $conn->prepare("INSERT INTO records (email, phone, s3_raw_url, s3_finished_url, status, reciept) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssii", $email, $phone, $s3_raw_url, $s3_finished_url, $status, $reciept);
$email = $_POST["emailaddress"];
$phone = $_POST["phone"];
$s3_raw_url = $result['ObjectURL'];
$s3_finished_url = $result2['ObjectURL'];
$status = $uploadOk;
$reciept = "1";
//$stmt->bind_param($email, $phone, $s3_raw_url, $s3_finsihed_url);
$stmt->execute();

echo "New records created successfully";

$stmt->close();
$conn->close();
header('Location: gallery.php')
*/
header('Location: gallery.php')
?>
