<?php
	//Config
	require_once('config/db_conn.php');
	$collection = 'users';

	$id = substr($_SERVER["QUERY_STRING"], 3); //id=597fe936fda14361050616d2
	
	// If the values are posted, insert them into the database.
    if (isset($id)){		
		// Construct a write concern
		$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
		
		// Create a bulk write object and add our insert operation
		$bulk = new MongoDB\Driver\BulkWrite();
		
		// Construct a query with filter
		$id = new \MongoDB\BSON\ObjectId($id);
		
		$user_count = 0;
		
		$filter = ['_id' => $id];
		$options = ['projection' => ['_id' => 0]];
		$query = new \MongoDB\Driver\Query($filter, $options);
		
		try {
		
			$cursor = $manager->executeQuery($dbname.'.'.$collection, $query);
	
			// Iterate over all matched documents
			foreach ($cursor as $document) {				
				$user_count++; //will return 0 if user doesn't exist
				$Username = !empty($document->Username) ? $document->Username : '';
			}
	
		} catch (MongoDB\Driver\Exception\Exception $e) {
			//handle the exception
			echo $e->getMessage(), "\n";
		}
		
		if (isset($_POST['Password']) && isset($_POST['rePassword'])){
		
			//Update user password		
			$pwd1 = base64_encode($_POST['Password']);
			$pwd2 = base64_encode($_POST['rePassword']);
			
			$filter = ['_id' => $id];
			$newObj = ['$set' => ['Password' => $pwd1, 'rePassword' => $pwd2]];
			
			$options = ["multi" => false, "upsert" => false];			
			$bulk = new MongoDB\Driver\BulkWrite;
			$bulk->update($filter, $newObj, $options);
			
			try {
				
				$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
				
			} catch (MongoDB\Driver\Exception\Exception $e) {
				
				echo $e->getMessage(), "\n";
			}
			
			if($result){
				$smsg = "You have successfully changed your password.";
				header("Refresh: 3;url=http://bioinfo.cs.ccu.edu.tw/Crab/");				
			}
		}
	}
?>
<div style="margin:auto; max-width:500px">
  <?php if(isset($smsg)){ ?>
  <div class="alert alert-success"role="alert"><?php echo $smsg; ?></div>
  <?php } ?>
  <?php if(isset($fmsg)){ ?>
  <div class="alert alert-danger" role="alert"><?php echo $fmsg; ?></div>
  <?php } ?>
</div>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="This is my thesis project at CCU CSIE. ">
<meta name="keywords" content="A web platform for automatically genome assembly, antibiotic-resistance detection, and virulence estimation using third-generation sequencing">
<meta name="author" content="Yi-Ting Liu, enderman542@gmail.com">
<title>Reset Password</title>
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="font-awesome/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css" href="css/StepsProgressForm.css" />
<link rel="stylesheet" type="text/css" href="css/signupForm.css" />
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="icon" type="image/png" href="images/crab.png">
</head>

<body>
<form id="ResetForm" enctype="multipart/form-data" method="post" action="" autocomplete="off">
  <div class="login">
    <div class="login-screen">
      <div class="app-title">
        <h1>Reset Your Password</h1>
      </div>
      <div class="login-form">
        <hr />
        <span>Hello <?php 
			if (isset($id)){
				echo $Username;
			}	
		 ?>, Please enter your password 2x below to reset.
        <br /><br />
        <div class="control-group">
          <input type="password" class="login-field" value="" name="Password" placeholder="Password" autocomplete="new-password" id="password1" required="required">
          <label class="login-field-icon fui-lock" for="login-pass"></label>
        </div>
        <div class="control-group">
          <input type="password" class="login-field" value="" name="rePassword" placeholder="Confirm Password" autocomplete="new-password" id="password2" required="required">
          <label class="login-field-icon fui-lock" for="confirm-pass"></label>
        </div>
        <!--<div class="alert alert-warning" role="alert" style="display:none" id="validate"><p id="validate-status"></p></div>-->
        <div class="alert alert-warning" role="alert" id="validate-status" style="display:none"></div>
        <button type="submit" class="btn btn-primary" id="ResetFormSubmit">Reset Password</button>
        <!--<a class="btn btn-primary btn-large btn-block"  href="#">Create my account</a>--> <!--<a class="login-link" href="#">Lost your password?</a>--> </div>
    </div>
  </div>
</form>
<!-- footer start -->
<footer class="footer">
  <div class="container" style="text-align:center; color:#FFF">
    <p>Copyright © <script>document.write(new Date().getFullYear())</script>&nbsp;<a style="color:#FFF" target="_blank" href="http://bioinfo.cs.ccu.edu.tw/bioinfo/">Bioinformatics Lab</a>. All Rights Reserved.</p>
  </div>
</footer>
<!-- footer end -->
<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script> 
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#password2").keyup(validate);
});

function validate() {
	$("#validate-status").show();
	var password1 = $("#password1").val();
	var password2 = $("#password2").val();
	
	if(password1 == password2) {
		$("#validate-status").text("valid");        
	}
	else {
		$("#validate-status").text("Passwords Don't Match");  
	}
    
}
</script>
</body>
</html>