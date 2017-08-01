<?php
	//Config
	require_once('config/db_conn.php');
	$collection = 'users';
	
	// If the values are posted, insert them into the database.
    if (isset($_POST['Email'])){
		$email = $_POST['Email'];	
		
		// Construct a write concern
		$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
		
		// Create a bulk write object and add our insert operation
		$bulk = new MongoDB\Driver\BulkWrite();		
		
		//check if user exist in database using counter
		
		// Construct a query with filter
		$filter = ['Email' => $email];
		$query = new MongoDB\Driver\Query($filter);
		$user_count = 0;
		
		try {
		
			$cursor = $manager->executeQuery($dbname.'.'.$collection, $query);
	
			// Iterate over all matched documents
			foreach ($cursor as $document) {
				$user_count++; //will return 0 if user doesn't exist
				$Password = !empty($document->Password) ? $document->Password : '';
				$Username = !empty($document->Username) ? $document->Username : '';
			}
	
		} catch (MongoDB\Driver\Exception\Exception $e) {
			//handle the exception
			echo $e->getMessage(), "\n";
		}

		if($user_count){	
			require_once("mail_configuration.php");
			$smsg = "We have sent an email, you should get it shortly.";
		}else{
			$fmsg = "Email address not found.";
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
<title>Forgot Password</title>
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="font-awesome/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css" href="css/StepsProgressForm.css" />
<link rel="stylesheet" type="text/css" href="css/signupForm.css" />
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<link rel="icon" type="image/png" href="images/crab.png">
</head>

<body>
<form id="forgotForm" enctype="multipart/form-data" method="post" action="" autocomplete="off">
  <div class="login">
    <div class="login-screen">
      <div class="app-title">
        <h1>Forgot Your Password?</h1>
      </div>
      <div class="login-form">
        <hr />
        <span>If you have forgotten your password, please enter your account's email address below and click the "<strong>Reset My Password</strong>" button. You will receive an email that contains a link to set a new password.</span> <br />
        <!--<span>Return to the Login Screen <a href="index.php" style="text-decoration: underline;">Sign in</a></span> <br />-->
        <br />
        <div class="control-group">
          <input type="text" class="login-field" value="" name="Email" placeholder="Email" id="login-email" required="required" autocomplete="nope">
          <label class="login-field-icon fui-user" for="login-email"></label>
        </div>
        <!--<div class="alert alert-warning" role="alert" style="display:none" id="validate"><p id="validate-status"></p></div>-->
        <div class="alert alert-warning" role="alert" id="validate-status" style="display:none"></div>
        <button type="submit" class="btn btn-primary" id="forgotFormSubmit">Reset My Password</button>
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
</body>
</html>