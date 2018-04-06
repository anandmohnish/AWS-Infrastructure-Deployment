<html>
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
<body>
<center><h1>Convert Image to Old Times - Black & White</h1></center>

<form action="upload.php" method="post" enctype="multipart/form-data">
<div style="text-align:center; vertical-align:middle">
<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
<p><label for="myfile">Choose image to upload</label></p>
<p><center>
<!--<input name="fileToUpload" id="myfile" type="file" style="margin-left: 150;" />-->
<input type="file" name="files[]" multiple="multiple" style="margin-left: 150;" />
</center></p>
<p><label for="name">Your Name</label>
<input type='text' name='fullname' id='name'style="background-color:#8CBBDF"  placeholder="Enter Name"/>
</p>
<p><label for="email">Your Email</label>
<input type='text' name='emailaddress' style="background-color:#8CBBDF" id='email' placeholder="Enter Email" required/>
</p><label for="phone">Phone No:</label>
<input type="text" style="background-color:#8CBBDF" name="phone" placeholder="Enter Phone"><br>
</p>
<p><input type="submit" value='Upload File' onclick="myFunction()" value="Show alert box" style="background-color:#8CBBDF" ></p>
</form>
</body>
</div>
</html>
