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
    
    #Declare bucket
    $bucket2 = 'my-post-bucket';

    #Connect to Database
    $endpoint = $rdsclient->describeDBInstances(['DBInstanceIdentifier' => 'mydbinstance',]);
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
    
    #Define a SQS Client
    $sqsclient = new SqsClient([
        //'credentials' => $provider,
	    'version' => 'latest',
        'region' => 'us-east-1'
    ]);
    $sqsresultlist = $sqsclient->listQueues([ ]);
    print_r ( $sqsresultlist['QueueUrls']);
    $sqsqueue = $sqsresultlist['QueueUrls'][0];
    echo "<br/>".$sqsqueue."<br/>";
    //$sqsqueue="https://sqs.us-east-1.amazonaws.com/552505984612/my-sqs-ma.fifo";

    #Read SQS for new messages
    try 
    {
        $sqsresult2 = $sqsclient->receiveMessage(array(
            'AttributeNames' => ['SentTimestamp'],
            'MaxNumberOfMessages' => 10,
            'MessageAttributeNames' => ['All'],
            'QueueUrl' => $sqsqueue, // REQUIRED
            'WaitTimeSeconds' => 0,
        ));
        $forloop=(count($sqsresult2->get('Messages')));
        echo "<br/>".$forloop."<br/>";
        if (count($sqsresult2->get('Messages')) > 0) 
        {
            for ($i = 0; $i < $forloop; $i++) 
            {
                echo "<br/>".$i."<br/>";
                //var_dump($sqsresult2->get('Messages')[$i])."<br/>";
                $sqs_uid=$sqsresult2['Messages'][$i]['Body'];
                $receiptHandle = $sqsresult2['Messages'][$i]['ReceiptHandle'];
                echo "<br/>".$sqs_uid."<br/>";
                echo "<br/>".$receiptHandle."<br/>";

                #Query the database
                //$sqlquery = "SELECT s3_raw_url from records";
                $stmt = $conn->prepare("SELECT s3_raw_url from records where uid = ?");
                $stmt->bind_param("s", $uuid);
                $uuid=$sqs_uid;

               echo "<br\>".$uuid."<br\>";
                //$stmt->execute();
		$stmt->execute();
                //$stmt->store_result();
               // echo($stmt); // Should be false
                //echo($conn->error); // A string representation of your error.
                $result = $stmt->get_result();
                if($result ->num_rows > 0) 
                {
               echo "Inside if"; 
			  
		while($row = $result->fetch_assoc()) 
                  {
                    $s3url=$row["s3_raw_url"];
                    echo "<br/>".$s3url."<br/>";
                    //$id[] = $row['id'];
                    //$name[] = $row['name'];
                    //$age[] = $row['age'];
		    $file=file_get_contents($s3url);
			//echo "<br\>".$file."<br\>";
            //$path = "/tmp". basename($url);
            //return !file_exists($path)?file_put_contents($path,$file):false;
            $parts = parse_url($s3url);

            $str = $parts['scheme'].'://'.$parts['host'].$parts['path'];

            echo $str;
            $path_parts = pathinfo($s3url);
            $fileext= $path_parts['extension'];
            $filename= $path_parts['filename'];
            //echo $filename;
            //echo $fileext;
            $target_file= $filename . "." . $fileext;
            chdir('/tmp');
            file_put_contents($target_file, file_get_contents($s3url));
            
            //Get extension of image
            $ext = pathinfo($target_file, PATHINFO_EXTENSION);
            echo $ext;

            if ( $ext == "jpg")
            {
                echo "Inside jpg";
                $im = imagecreatefromjpeg($target_file);
                if (!$im)
                {
                echo "Error Processing JPG File";
                
                }
                else 
                {
                if($im && imagefilter($im, IMG_FILTER_GRAYSCALE))
                {
                    echo 'Image converted to grayscale.';
                
                    imagejpeg($im, '/tmp/processed.jpg');
                }
                else
                {
                    echo 'Conversion to grayscale failed.';
                }
                imagedestroy($im);
                }
            }
            else
            if ($ext == "png")
            {
                echo "Inside png";
                $im = imagecreatefrompng($target_file);
                if (!$im)
                {
                //Image is Jpg
                
                }
                else 
                {
                //Image is Png
                
                if($im && imagefilter($im, IMG_FILTER_GRAYSCALE))
                {
                    echo 'Image converted to grayscale.';
                
                    imagejpeg($im, '/tmp/processed.jpg');
                }
                else
                {
                    echo 'Conversion to grayscale failed.';
                }
                imagedestroy($im);
                }
            }
            
            $result2 = $client->putObject([
                'ACL' => 'public-read',    
                'Bucket' => $bucket2,
                'Key' => $target_file,
                'SourceFile' => '/tmp/processed.jpg' 
            ]);
            $objecturl= $result2['ObjectURL'];

            #update database
            $stmt2 = $conn->prepare("Update records set s3_finished_url = ?,dateprocessed = ?, status = ? where uid = ?");
            $stmt2->bind_param("ssis", $s3furl, $dat, $stat, $uuid);
            $s3furl= $objecturl;
            $dat = date("Y-m-d");
            $stat= '1';
            $uuid=$sqs_uid;
           //echo "<br\>".$uuid."<br\>";
            //$stmt->execute();
            $stmt2->execute();
            
            ##Send Notification
            $snsclient = new Aws\Sns\SnsClient([
                //'credentials' => $provider,
		        'version' => 'latest',
                'region'  => 'us-east-1'
            ]);
            
            $snsresult = $snsclient->listTopics([ ]);
            //print_r ( $snsresult['Topics']);
            $topicarn = $snsresult['Topics'][0]['TopicArn'];
            /*
            $subscriberesult = $sqs->subscribe([
                'Endpoint' => 'hajek@iit.edu',
                'Protocol' => 'email', // REQUIRED
                'TopicArn' => $topicarn, // REQUIRED
            ]);
                    */
            //Publish
            $publishresult = $snsclient->publish([
                'Message' => "Hello World -- its a bit rainy -- try this $objecturl", // REQUIRED
                'Subject' => 'Your Image has been Processed',
                'TopicArn' => $topicarn
            ]);

            ##Delete Message from SQS
            $deletemessageresult = $sqsclient->deleteMessage([
                'QueueUrl' => $sqsqueue, // REQUIRED
                'ReceiptHandle' => $receiptHandle, // REQUIRED
            ]);
                  } 
                }
                  else 
                  {
                    echo "0 results";
                  }
                /*
                $sql = "select s3_raw_url from records where uid='". $sqs_uid. "'";
                //$sql = "select s3_raw_url from records where uid='5a03b4c8e2968'";
                echo "<br/>".$sql."<br/>";
                $result = mysqli_query($conn, $sql);
                
                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $s3url=$row["s3_raw_url"];
                        echo "<br/>".$s3url."<br/>";
                        //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
                    }
                } else {
                    echo "0 results";
                }
                */
                /*
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    // output data of each row
                    while($row = $result->fetch_assoc()) {
                        $s3url=$row["s3_raw_url"];
                        echo "<br/>".$s3url."<br/>";
                        //echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
                    }
                } else {
                    echo "0 results";
                }
                
                $conn->close();
                */
            }
            
            //var_dump($sqsresult2->get('Messages')[0]);
           // $sqsresult2 = $client->deleteMessage([
             //   'QueueUrl' => $queueUrl, // REQUIRED
               // 'ReceiptHandle' => $sqsresult2->get('Messages')[0]['ReceiptHandle'] // REQUIRED
            //]);
        } 
     else 
            {
            echo "No messages in queue. \n";
            }
    }
    catch (AwsException $e) 
    {
        // output error message if fails
        error_log($e->getMessage());
    }
?>
