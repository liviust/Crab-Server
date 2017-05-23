<?php
	if (session_status() == PHP_SESSION_NONE) {
	  session_start();
	}
	header("refresh: 180;");
	//Include Google client library 
	require_once ('google-api-php-client-1.1.7/src/Google/autoload.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Client.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Service/Oauth2.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Service/Analytics.php');
	
	/*
	 * Configuration and setup Google API
	 */
	$client_id = '888974174004-tfj3klejes1c8ghq5lam83opbgm11e77.apps.googleusercontent.com'; //Google client ID
	$client_secret = 'd9TEZyfjEIucMb-Ej4xcUunp'; //Google client secret
	$redirect_uri = 'http://bioinfo.cs.ccu.edu.tw/Crab/index.php'; //Callback URL
	$api_key = 'AIzaSyDWffthbwZ4ZttbZsOZC-hYPttXpG4hH9w';

	//Config
	require_once('config/db_conn.php');
	$collection = 'jobs';
	
	//Create Client Request to access Google API
	$client = new Google_Client(); //Google_Client is a class provided by the Google PHP SDK
	$client->setApplicationName("PHP Google OAuth Login Example");
	$client->setClientId($client_id);
	$client->setClientSecret($client_secret);
	$client->setRedirectUri($redirect_uri);
	$client->setDeveloperKey($api_key);
	$client->addScope(array(
		//Know your basic profile info and list of people in your circles.
		 "https://www.googleapis.com/auth/plus.login",
		//Know who you are on Google
		"https://www.googleapis.com/auth/plus.me",
		// View basic information about your account
		"https://www.googleapis.com/auth/userinfo.profile",	
		//View your email address
		"https://www.googleapis.com/auth/userinfo.email",
		//"https://www.googleapis.com/auth/plus.profile.emails.read"
	));
	
	//Send Client Request
	$service = new Google_Service_Oauth2($client);
	
	//For loging out.
	if (isset($_GET['logout'])) { // if ($_GET['logout'] == "1") {
	  unset($_SESSION['access_token']);
	  session_destroy();
	}
	
	//Step 2: The user accepted your access now you need to exchange it.
	if (isset($_GET['code'])) {
	  $client->authenticate($_GET['code']);
	  $_SESSION['access_token'] = $client->getAccessToken();
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL)); //redirect user back to page
	  exit;
	}
	
	
	// With his comments, Fabian Parzefall helped me getting this fixed.
//	if($client->isAccessTokenExpired()) {
//	
//		$authUrl = $client->createAuthUrl();
//		header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
//	
//	}
	
	
	//Step 1:  The user has not authenticated we give them a link to login
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	  $client->setAccessToken($_SESSION['access_token']);
	} else {
	  $authUrl = $client->createAuthUrl(); // Login with Google+
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cяab Server</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="font-awesome/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css" href="css/StepsProgressForm.css" />
<link rel="icon" href="images/crab.png">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.13/css/dataTables.bootstrap.min.css" />
<style>
#tableJobs tbody tr.selected {
    background-color: #FFFAA1;
}
.container .text-muted {
	margin: 30px 0;
	text-align: center;
}
</style>
</head>

<body ONDRAGSTART="window.event.returnValue=false" onSelectStart="event.returnValue=false" ONCONTEXTMENU="window.event.returnValue=false">
<div class="container">
  <div class="page-header">
    <?php
	if(!isset($_SESSION['google_id'])){
		echo "<script>alert('You must be logged in to use this feature.')</script>";
		echo ("<script>location.href='index.php'</script>");
	}else if(isset($_SESSION['google_id'])){
		echo '<div style="text-align:right">';
		echo '<img src="'.$_SESSION['google_picture_link'].'" class="circular" />';
		echo '&nbsp;'.$_SESSION['google_name'].'  <a href="'.$redirect_uri.'?logout=1"><span class="glyphicon glyphicon-log-out"></span> Log Out</a>';
		echo '</div>';
	}
  ?>
    <h1>Cяab Server<small> Submissions</small>
      <?php
	if (isset($_SESSION['google_id']))
		echo("<a href='index.php'><button type='button' class='btn btn-warning btn-sm'><span class='glyphicon glyphicon-home'></span> Home Page</button></a>");	
	?>
    </h1>
  </div>
  <button id="button" type="button" name="remove_levels" class="btn btn-danger"><span class="glyphicon glyphicon-trash"></span> Delete selected row</button><br /><br />
  <table id="tableJobs" class="table table-striped table-bordered display table-hover" style="cursor:pointer" cellspacing="0" width="100%">
  </table>
  
<!-- footer start -->
<footer class="footer">
  <div class="container">
    <hr />
    <p class="text-muted">Copyright © <script>document.write(new Date().getFullYear())</script><a target="_blank" href="http://bioinfo.cs.ccu.edu.tw/bioinfo/">Bioinformatics Lab</a>. All Rights Reserved.</p>
  </div>
</footer>
<!-- footer end -->  
  
</div>
<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script> 
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script> 
<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script> 
<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/dataTables.bootstrap.min.js"></script>
<script src="http://bootboxjs.com/bootbox.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	$.ajax({
	  type: "POST",
	  url: "submit.php",
	  data: "",
	  dataType: "json",
	  success: function(resultData) {
		  var opt={
			"bProcessing":true,
			"sPaginationType":"full_numbers",
			"aoColumns":
			[
                {"mDataProp":"Submission","sTitle":"Submission","sType":"string"},
                {"mDataProp":"Title","sTitle":"Title","sType":"string"},
                {"mDataProp":"Submitted On","sTitle":"Submitted On","sType":"date"},
				{"mDataProp":"Finish Time","sTitle":"Finish Time","sType":"date"},
				{"mDataProp":"Status","sTitle":"Status","sType":"string"},
//				{"mDataProp":"Delete","sTitle":'<span class="glyphicon glyphicon-trash"></span>',"sDefaultContent":'<a class="delete">Delete</a>',"sType":"string"}
			],
			"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
				$('td:eq(0)', nRow).html('<a href="http://bioinfo.cs.ccu.edu.tw/Crab/result.php?jobid=' + aData.Submission + '">' + aData.Submission + '</a>');
				return nRow;
			},	
			"order": [[ 2, "desc" ]],			
			"aaData": resultData
		 };

/*		$('.delete').click(function() {
			console.log(oTable);
			return false;
		});	*/
				
		var oTable = $("#tableJobs").dataTable(opt);
		var table = $('#tableJobs').DataTable();
		$('#tableJobs tbody').on( 'click', 'tr', function () {
			if ( $(this).hasClass('selected') ) {
				$(this).removeClass('selected');
			}
			else {
				table.$('tr.selected').removeClass('selected');
				$(this).addClass('selected');
			}
		} );
		
		<?= 'var google_id = '.json_encode($_SESSION['google_id']).';'; ?>
		
		oTable.on('click','tr',function() {	
			var row = oTable.fnGetData(this);
			
			console.log(row);
			
			$('#button').click( function () {
				if(table.row('.selected')){
				bootbox.confirm("Are you sure you want to delete this job?", function(result) {
					if(result == true){
						//alert("Confirm result: " + result);
						console.log(row.Submission, row.Title, row.Status, google_id);	
						//table.row('.selected').remove().draw(false);
						
						$.ajax({
							type: "POST",
							cache:false,
							url: "deleteSubmission.php",
							data: {job_id: row.Submission, title: row.Title, status: row.Status, user_id: google_id},
							success: function(html){
								alert(html);
								table.row('.selected').remove().draw(false);
							}
						});
						
					}								  
				});	
				}
			});	
		});	
	 

			
/*			$.ajax({
				type:'POST',
				url:'delete.php',
				data:{del_id:del_id},
				success: function(data){
					 if(data=="YES"){
						 $ele.fadeOut().remove();
					 }else{
						 alert("can't delete the row")
					 }
				}
			
				 })
			})*/

/*		oTable.on('click','tr',function() {	
			var row = oTable.fnGetData(this);
			console.log(row);
			
			//console.log(row.Submission, row.Title, row.Status);
			//var url="http://bioinfo.cs.ccu.edu.tw/Crab/result.php?jobid="+ row.Submission;
			//window.location.href = url;			
		});*/
		
	   }
	 });
});
</script>
</body>
</html>