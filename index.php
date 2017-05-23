<?php
	if (session_status() == PHP_SESSION_NONE) {
	  session_start();
	}
	
	//Include Google client library 
	require_once ('google-api-php-client-1.1.7/src/Google/autoload.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Client.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Service/Oauth2.php');
	require_once ('google-api-php-client-1.1.7/src/Google/Service/Analytics.php');
	
	/*
	 * Configuration and setup Google API
	 */
	$client_id = '888974174004-tfj3klejes1c8ghq5lam83opbgm11e77.apps.googleusercontent.com'; //Google client ID
	$client_secret = ''; //Google client secret
	$redirect_uri = 'http://bioinfo.cs.ccu.edu.tw/Crab/index.php'; //Callback URL
	$api_key = '';

	//Config
	require_once('config/db_conn.php');
	$collection = 'google_users';
	
	//Create Client Request to access Google API
	$client = new Google_Client(); //Google_Client is a class provided by the Google PHP SDK
	$client->setApplicationName("PHP Google OAuth Login Example");
	$client->setClientId($client_id);
	$client->setClientSecret($client_secret);
	$client->setRedirectUri($redirect_uri);
	$client->setDeveloperKey($api_key);
	
	//UPDATE:
	$client->setAccessType("offline");
	$client->setApprovalPrompt("force");	
	
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
	  //echo $client->isAccessTokenExpired();
	  session_destroy();
	}
	
	//Step 2: The user accepted your access now you need to exchange it.
	if (isset($_GET['code'])) {
	  $client->authenticate($_GET['code']);
	  $_SESSION['access_token'] = $client->getAccessToken();
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL)); //redirect user back to page
	  exit;
	}
	
	//Step 1:  The user has not authenticated we give them a link to login
	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		$client->setAccessToken($_SESSION['access_token']);
	} else {
	  	$authUrl = $client->createAuthUrl(); // Login with Google+
		
		//UPDATE:
		$_SESSION['access_token'] = $client->getAccessToken();	  
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="This is my thesis project at CCU CSIE. ">
<meta name="keywords" content="A web platform for automatically genome assembly, antibiotic-resistance detection, and virulence estimation using third-generation sequencing">
<meta name="author" content="Yi-Ting Liu, enderman542@gmail.com">
<title>Cяab Server</title>
<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="font-awesome/css/font-awesome.min.css" />
<link rel="stylesheet" type="text/css" href="css/StepsProgressForm.css" />
<link rel="icon" href="images/crab.png">
<style>
.footer {
/*background-color: #f5f5f5;*/
}
.container {
}
.container .text-muted {
	margin: 30px 0;
	text-align: center;
}
/*pure-CSS solution in modern browsers*/
select:required:invalid {
 color: gray;
}
option[value=""][disabled] {
	display: none;
}
option {
	color: black;
}
/*
.text-muted {
    color: #C30;
}
small, .small {
    font-size: 85%;
}*/
</style>
</head>
<body ONDRAGSTART="window.event.returnValue=false" onSelectStart="event.returnValue=false" ONCONTEXTMENU="window.event.returnValue=false">
<div class="container">
  <div class="page-header">
    <?php 
		if(isset($authUrl)){ 
			//show Log in
			echo '<div style="text-align:right">';
			echo '<a class="login" href="' . $authUrl . '"><span class="glyphicon glyphicon-log-in"></span> Log in</a>';
			echo '</div>';
		}else {
			//get user info 
			$user = $service->userinfo->get(); 
			
			//Connecting to MongoDB
			try {
				
				// Construct the MongoDB Manager
				$manager = new MongoDB\Driver\Manager( 'mongodb://'.$dbhost ); 
			}
			catch (MongoDB\Driver\Exception\Exception $e) {
				
				// if there was an error, we catch and display the problem here
				echo $e->getMessage(), "\n";
			}	
			
			//check if user exist in database using counter
			
			// Construct a query with filter
			$filter = ['google_id' => $user->id];
			$query = new MongoDB\Driver\Query($filter);
			$user_count=0;
			
			try {
			
				$cursor = $manager->executeQuery($dbname.'.'.$collection, $query);
		
				// Iterate over all matched documents
				foreach ($cursor as $document) {
					$user_count++; //will return 0 if user doesn't exist
				}
		
			} catch (MongoDB\Driver\Exception\Exception $e) {
				//handle the exception
				echo $e->getMessage(), "\n";
			}
	
			if($user_count) //if user already exist change greeting text to "Welcome Back"
			{
				/*echo '<script type="text/javascript">alert("You have logged in as '.$user->name.'");</script>';*/
				echo '<div style="text-align:right">';
				echo '<img src="'.$user->picture.'" class="circular" />';
				echo '&nbsp;'.$user->name.'  <a href="'.$redirect_uri.'?logout=1"><span class="glyphicon glyphicon-log-out"></span> Log Out</a>';
				echo '</div>';
				
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
				
				//Update user info
				$filter =['google_id' => $user->id];
				$newObj = ['$set' => ['google_id' => $user->id, 'google_name' => $user->name, 'google_email' => $user->email, 'google_link' => $user->link, 'google_picture_link' => $user->picture, 'google_gender' => $user->gender, 'google_locale' => $user->locale, 'modified' => date("Y-m-d H:i:s")]];
				
				$options = ["multi" => false, "upsert" => false];			
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}	

			}
			else //else greeting text "Thanks for registering"
			{ 
				echo '<div style="text-align:right">';
				echo '<img src="'.$user->picture.'" class="circular" />';
				echo '&nbsp;'.$user->name.'  <a href="'.$redirect_uri.'?logout=1"><span class="glyphicon glyphicon-log-out"></span> Log Out</a>';
				echo '</div>';
				
				// Construct a write concern
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
				
				// Create a bulk write object and add our insert operation
				$bulk = new MongoDB\Driver\BulkWrite();		
				
				$bulk->insert(['google_id' => $user->id, 'google_name' => $user->name, 'google_email' => $user->email, 'google_link' => $user->link, 'google_picture_link' => $user->picture, 'google_gender' => $user->gender, 'google_locale' => $user->locale, 'created' => date("Y-m-d H:i:s"), 'modified' => date("Y-m-d H:i:s")]);
	
				try {
					
					//Execute one or more write operations
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					//handle the exception
					echo $e->getMessage(), "\n";
				}		
				
			}	
					
			$_SESSION['google_id'] = $user->id;
			$_SESSION['google_name'] = $user->name;
			$_SESSION['google_email'] = $user->email;
			$_SESSION['google_picture_link'] = $user->picture;
			$_SESSION['google_link'] = $user->link;			
		}
	?>
    <h1>Cяab Server<small> <!--Bacterial Genome Annotation Service-->Steps Progress</small>
      <?php
	if (isset($user->id))
		echo("<a href='jobs.php'><button type='button' class='btn btn-warning btn-sm'><span class='glyphicon glyphicon-bell'></span> Jobs</button></a>");	
	?>
    </h1>
  </div>
</div>
<!-- Steps Progress and Details - START -->
<div class="container" style="margin-top: 50px; margin-bottom: 20px;">
  <div class="row">
    <div class="progress" id="progress1">
      <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"> </div>
      <span class="progress-type">Overall Progress</span> <span class="progress-completed">0%</span> </div>
  </div>
  <div class="row">
    <div class="row step">
      <div id="s1" class="col-md-2 activestep" onclick="javascript: resetActive(event, 0, 'step-1');"> <span class="fa fa-cloud-upload"></span>
        <p>File Upload</p>
      </div>
      <div id="s2" class="col-md-2" onclick="javascript: resetActive(event, 20, 'step-2');"> <span class="fa fa-align-left"></span>
        <p>Canu Assembler</p>
      </div>
      <div id="s3" class="col-md-2" onclick="javascript: resetActive(event, 40, 'step-3');"> <span class="fa fa-search-plus"></span>
        <p>Species Identification</p>
      </div>
      <div id="s4" class="col-md-2" onclick="javascript: resetActive(event, 60, 'step-4');"> <span class="fa fa-circle-o-notch"></span>
        <p>Virulence Estimation</p>
      </div>
      <div id="s5" class="col-md-2" onclick="javascript: resetActive(event, 80, 'step-5');"> <span class="fa fa-spinner"></span>
        <p>AMR Detection</p>
      </div>
      <div id="s6" id="last" class="col-md-2" onclick="javascript: resetActive(event, 100, 'step-6');">
      <span class="fa fa-check-square-o"></span>
      <p>Overview</p>
    </div>
  </div>
</div>
<!-- Steps Progress and Details - END -->

<div class="container">
  <div class="row setup-content step activeStepInfo" id="step-1">
  <div class="col-xs-12">
  <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
  <form id="OverviewSubmitForm" enctype="multipart/form-data" method="post" action="">
    <h1>Step 1: Choose Analysis and Upload File</h1>
    <!--<h3 class="underline">Under Construction</h3>--> 
    <!--     Download the application form from our repository.
          This may require logging in.-->
    
    <div class="inner-wall">
      <div class="form-group">
        <label for="AnalysisTypeSelect">Analysis Type</label>
        <select class="form-control" id="AnalysisTypeSelect">
          <option value="" selected>-- Select analysis type --</option>
          <option value="FullWorkflow">Full Workflow</option>
          <option value="AssemblyFree">Assembly Free</option>
          <option value="AutoFullWorkflow">Auto Full Workflow, For impatient people</option>
          <option value="AutoAssemblyFree">Auto Assembly Free, For impatient people</option> 
        </select>
      </div>
      <div class="form-group">
        <label for="SequenceFile">Select a unassembled sequence file</label>
        <input type="file" class="form-control-file" id="SequenceFile" name="SequenceFile" onchange="GetFileSize()" aria-describedby="fileHelp">
        <p id="fp"></p>
        <small id="fileHelp" class="form-text text-muted">Upload a third-generation sequencing (such as the PacBio RS II or Oxford Nanopore MinION) in FASTA format (2 GB limit).</small> </div>
      <div class="form-group">
        <label for="Email">E-mail</label>
        <input type="email" class="form-control" id="Email" aria-describedby="emailHelp" placeholder="<?php 
		if(isset($_GET['logout'])){
			echo "foo@example.com";
			//session_destroy();
		} else if (isset($user->id)){
			echo $_SESSION['google_email'];
		}else{
			echo "foo@example.com";
		}		
		?>" readonly>
        <!--<small id="emailHelp" class="form-text text-muted">A valid e-mail address</small>--> 
        <small id="emailHelp" class="form-text text-muted">We will email you a link to your page with your quality assessment reports. <br />
        We will also notify you when your report is finished, and contact you if any problems arise.</small> </div>
      <?php
      if (isset($user->id)){
		  echo("<button type='button' class='btn btn-primary' onclick='goToNextStep(2)'>Activate Step 2</button>");
	  } else{
		  echo("<button type='button' class='btn btn-info' onClick=\"alert('You must log in first.')\">Activate Step 2</button>");
	  }	  
	  ?>
    </div>
    </div>
    </div>
    </div>
    <div class="row setup-content step hiddenStepInfo" id="step-2">
      <div class="col-xs-12">
        <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
          <h1>Step 2: A single molecule sequence assembler for genomes</h1>
          <h3 class="underline"><a target="_blank" href="https://github.com/marbl/canu/releases/tag/v1.4">Canu v1.4</a></h3>
          <!--Fill out the application. 
          Make sure to leave no empty fields.-->
          <div class="inner-wall">
            <div class="form-group">
              <label for="">Technology</label>
              <label class="custom-control custom-radio">
                <input id="pacbio-raw" name="radioTech" value="1" type="radio" class="custom-control-input" checked="checked">
                <span class="custom-control-indicator"></span> <span class="custom-control-description">-pacbio-raw</span> </label>
              <label class="custom-control custom-radio">
                <input id="nanopore-raw" name="radioTech" value="0" type="radio" class="custom-control-input">
                <span class="custom-control-indicator"></span> <span class="custom-control-description">-nanopore-raw</span> </label>
            </div>
            <div class="form-group">
              <label for="RawReads">Raw reads (FASTA format)</label>
              <input type="text" class="form-control" id="RawReads" placeholder="" readonly>
            </div>
            <div class="form-group">
              <label for="AssemblyPrefix">Assembly-prefix</label>
              <input type="text" class="form-control" id="AssemblyPrefix" placeholder="">
              <small id="fileHelp" class="form-text text-muted">Named by prefix of file</small> </div>
            <!--<div class="form-group">
              <label for="maxMemory" class="col-2 col-form-label">maxMemory</label>
              <div class="col-10">
                <input class="form-control" type="number" value="8" id="maxMemory" readonly>
              </div>
              <small id="fileHelp" class="form-text text-muted">Maximum memory to use by any component of the assemble</small> </div>-->
            <div class="form-group">
              <label for="maxThreads" class="col-2 col-form-label">maxThreads</label>
              <div class="col-10">
                <input class="form-control" type="number" value="10" id="maxThreads" readonly>
              </div>
              <small id="fileHelp" class="form-text text-muted">Maximum number of compute threads to use by any component of the assembler</small> </div>
            <div class="form-group">
              <label for="genomeSize">genomeSize</label>
              <input type="text" class="form-control" id="genomeSize" placeholder="4.8m">
              <small id="fileHelp" class="form-text text-muted">An estimate of the size of the genome</small> </div>
            <button type="button" class="btn btn-success" onclick="goToNextStep(1)">Go Back</button>
            <button type="button" class="btn btn-primary" onclick="goToNextStep(3)">Activate Step 3</button>
          </div>
        </div>
      </div>
    </div>
    <div class="row setup-content step hiddenStepInfo" id="step-3">
      <div class="col-xs-12">
        <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
          <h1>Step 3: Bacterial species identification</h1>
          <h3 class="underline">NCBI nt database</h3>
          <!--(nr database是protein)--> 
          <!--Check to ensure that all data entered is valid.-->
          <div class="inner-wall"> 
            <!-- AssemblyFree - START -->
            <div class="form-group">
              <label for="contigsFile">Select a sequence file</label>
              <input type="file" class="form-control-file" id="contigsFile" name="contigsFile" onchange="GetContigSize()" aria-describedby="fileHelp">
              <p id="op"></p>
              <small id="fileHelp" class="form-text text-muted">Upload the genome assembly components (contigs, scaffolds, chromosomes) in FASTA format (10 MB limit).</small> </div>
            <!-- AssemblyFree - END -->
            <div class="form-group">
              <label for="evalueParameters">Parameters</label>
              <input type="text" class="form-control" id="evalueParameters" placeholder="-e 0.001" required="required">
              <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for prediction</small> </div>
            <button type="button" class="btn btn-success" onclick="goToNextStep(2)">Go Back</button>
            <button type="button" class="btn btn-primary" onclick="goToNextStep(4)">Activate Step 4</button>
          </div>
        </div>
      </div>
    </div>
    <!--VirulenceFinder-->
    <div class="row setup-content step hiddenStepInfo" id="step-4">
      <div class="col-xs-12">
        <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
          <h1>Step 4: Bacterial virulence estimation</h1>
          <h3 class="underline"><a target="_blank" href="http://www.mgc.ac.cn/VFs/main.htm">VFDB: Virulence Factors Database</a></h3>
          <div class="inner-wall">
            <div class="form-group">
              <label for="VFDBevalueParameters">Parameters</label>
              <input type="text" class="form-control" id="VFDBevalueParameters" placeholder="-e 0.001" required="required">
              <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for prediction</small> </div>
            <div class="form-group">
              <label for="SelectVirulenceThresholdIdentity">Select threshold for %Identity</label>
              <select class="form-control" id="SelectVirulenceThresholdIdentity">
                <option value="100">100 %</option>
                <option value="90">90 %</option>
                <option value="80">80 %</option>
                <option value="70">70 %</option>
                <option value="60">60 %</option>
                <option value="50" selected="selected">50 %</option>
                <option value="40">40 %</option>
              </select>
            </div>
            <div class="form-group">
              <label for="SelectVirulenceMinimumLength">Select minimum length</label>
              <select class="form-control" id="SelectVirulenceMinimumLength">
                <option value="100">100 %</option>
                <option value="80">80 %</option>
                <option value="60" selected="Selected">60 %</option>
                <option value="40">40 %</option>
                <option value="20">20 %</option>
              </select>
              <small id="fileHelp" class="form-text text-muted">% sequence completeness between the query and subject virulence-related sequences</small> </div>
            <button type="button" class="btn btn-success" onclick="goToNextStep(3)">Go Back</button>
            <button type="button" class="btn btn-primary" onclick="goToNextStep(5)">Activate Step 5</button>
          </div>
        </div>
      </div>
    </div>
    <div class="row setup-content step hiddenStepInfo" id="step-5">
      <div class="col-xs-12">
        <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
          <h1>Step 5: Antibiotic resistance detection</h1>
          <h3 class="underline"><a target="_blank" href="https://card.mcmaster.ca/">CARD: The Comprehensive Antibiotic Resistance Database</a></h3>
          <!--Upload the application. 
          This may require a confirmation email.-->
          
          <div class="inner-wall">
            <div class="form-group">
              <label for="VFDBevalueParameters">Parameters</label>
              <input type="text" class="form-control" id="CARDevalueParameters" placeholder="-e 0.001" required="required">
              <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for prediction</small> </div>
            <div class="form-group">
              <label for="SelectVirulenceThresholdIdentity">Select threshold for %Identity</label>
              <select class="form-control" id="SelectResistomeThresholdIdentity">
                <option value="100">100 %</option>
                <option value="90">90 %</option>
                <option value="80">80 %</option>
                <option value="70">70 %</option>
                <option value="60">60 %</option>
                <option value="50" selected="selected">50 %</option>
                <option value="40">40 %</option>
              </select>
            </div>
            <div class="form-group">
              <label for="SelectVirulenceMinimumLength">Select minimum length</label>
              <select class="form-control" id="SelectResistomeMinimumLength">
                <option value="100">100 %</option>
                <option value="80">80 %</option>
                <option value="60" selected="Selected">60 %</option>
                <option value="40">40 %</option>
                <option value="20">20 %</option>
              </select>
              <small id="fileHelp" class="form-text text-muted">% sequence completeness between the query and subject antibiotic-resistant sequences</small> </div>
            <button type="button" class="btn btn-success" onclick="goToNextStep(4)">Go Back</button>
            <button type="button" class="btn btn-primary" onclick="goToNextStep(6)">Activate Step 6</button>
          </div>
        </div>
      </div>
    </div>
    <div class="row setup-content step hiddenStepInfo" id="step-6">
    <div class="col-xs-12">
    <div class="col-md-12 well text-center" style="text-align: -webkit-center;">
      <h1>Step 6: Overview</h1>
      <!--<h3 class="underline">Unfinished</h3>--> 
      <strong>To make any necessary changes</strong> before submitting click on the tabs/steps above.
      <div class="inner-wall">
        <div class="form-group">
          <label for="Title">Title</label>
          <input type="text" class="form-control" id="Title" placeholder="" required="required">
          <small id="fileHelp" class="form-text text-muted">The title of the submission</small> </div>
        <!-- Step Overview - START -->
        
        <div id="switch1" style="display:block">
          <!--Oxford Nanopore Technology-->
          <div class="form-group">
            <label for="">Technology</label>
            <label class="custom-control custom-radio">
              <input id="Overviewpacbio-raw" name="radioTech" value="1" type="radio" class="custom-control-input" readonly>
              <span class="custom-control-indicator"></span> <span class="custom-control-description">-pacbio-raw</span> </label>
            <label class="custom-control custom-radio">
              <input id="Overviewnanopore-raw" name="radioTech" value="0" type="radio" class="custom-control-input" readonly>
              <span class="custom-control-indicator"></span> <span class="custom-control-description">-nanopore-raw</span> </label>
          </div>
          <!--/Oxford Nanopore Technology--> 
<div class="form-group">
            <label for="OverviewRawReads">Raw reads</label>
            <input type="text" class="form-control" id="OverviewRawReads" placeholder="" readonly>
          </div>

        </div>
        <div id="switch2" style="display:none">
          <div class="form-group">
            <label for="OverviewcontigsFile">Contigs</label>
            <input type="text" class="form-control" id="OverviewcontigsFile" placeholder="" readonly>
          </div>
        </div>
        <div class="form-group">
          <label for="OverviewEmail">E-mail</label>
          <input type="email" class="form-control" id="OverviewEmail" aria-describedby="emailHelp" value="<?php 
		if(isset($_GET['logout'])){
			echo "foo@example.com";
			//session_destroy();
		} else if (isset($user->id)){
			echo $_SESSION['google_email'];
		}else{
			echo "foo@example.com";
		}		
		?>" placeholder="<?php 
		if(isset($_GET['logout'])){
			echo "foo@example.com";
			//session_destroy();
		} else if (isset($user->id)){
			echo $_SESSION['google_email'];
		}else{
			echo "foo@example.com";
		}		
		?>" readonly>
          <!--<small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>--> 
        </div>
        <div class="form-group">
          <label for="OverviewevalueParameters">Cutoff Parameters</label>
          <input type="text" class="form-control" id="OverviewevalueParameters" placeholder="-e 0.001" required="required" readonly>
          <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for NCBI</small> </div>
        <div class="form-group"> 
          <!--<label for="OverviewVFDBevalueParameters">Parameters</label>-->
          <input type="text" class="form-control" id="OverviewVFDBevalueParameters" placeholder="-e 0.001" required="required" readonly>
          <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for VFDB</small> </div>
		<div class="form-group"> 
          <!--<label for="OverviewVFDBevalueParameters">Parameters</label>-->
          <input type="text" class="form-control" id="OverviewCARDevalueParameters" placeholder="-e 0.001" required="required" readonly>
          <small id="fileHelp" class="form-text text-muted">-e e-value cutoff for CARD</small> </div>          
        <div id="switch3" style="display:block">
          <div class="form-group">
            <label for="OverviewAssemblyPrefix">Assembly-prefix</label>
            <input type="text" class="form-control" id="OverviewAssemblyPrefix" placeholder="" required="required" readonly>
            <small id="fileHelp" class="form-text text-muted">Named by prefix of file</small> </div>
          <!--<div class="form-group">
            <label for="OverviewmaxMemory" class="col-2 col-form-label">maxMemory</label>
            <div class="col-10">
              <input class="form-control" type="number" value="8" id="OverviewmaxMemory" readonly>
            </div>
            <small id="fileHelp" class="form-text text-muted">Maximum memory to use by any component of the assemble</small> </div>-->
          <div class="form-group">
            <label for="OverviewmaxThreads" class="col-2 col-form-label">maxThreads</label>
            <div class="col-10">
              <input class="form-control" type="number" value="10" id="OverviewmaxThreads" readonly>
            </div>
            <small id="fileHelp" class="form-text text-muted">Maximum number of compute threads to use by any component of the assembler</small> </div>
          <div class="form-group">
            <label for="OverviewgenomeSize">genomeSize</label>
            <input type="text" value="" class="form-control" id="OverviewgenomeSize" readonly>
            <small id="fileHelp" class="form-text text-muted">An estimate of the size of the genome</small> </div>
        </div>
        <!-- Step Overview - END -->
        <div class="form-group">
          <progress id="progressBar" value="0" max="100" style="width:300px;"></progress>
          <h3 id="status"></h3>
          <p id="loaded_n_total"></p>
        </div>
        <button type="button" class="btn btn-success" onclick="goToNextStep(5)">Go Back</button>
        <button type="submit" id="OverviewSubmit" class="btn btn-primary">Finish</button>
        <!-- pseudo div - START -->
        <input id="google_id" type="hidden" name="" value="<?php 
		if (isset($user->id)){
			echo $_SESSION['google_id'];
		}?>">
        <input id="google_name" type="hidden" name="" value="<?php 
		if (isset($user->id)){
			echo $_SESSION['google_name'];
		}?>">
        <!--<input type="hidden" name="Language" value="English">--> 
        <!-- pseudo div - END --> 
      </div>
    </div>
  </form>
</div>
</div>
</div>
</div>

<!-- footer start -->
<footer class="footer">
  <div class="container">
    <hr />
    <p class="text-muted">Copyright © <script>document.write(new Date().getFullYear())</script><a target="_blank" href="http://bioinfo.cs.ccu.edu.tw/bioinfo/">Bioinformatics Lab</a>. All Rights Reserved.</p>
  </div>
</footer>
<!-- footer end --> 

<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script> 
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script> 
<script type="text/javascript" src="js/activeStep.js"></script> 
<script type="text/javascript" src="js/getFileSize.js"></script> 
<script type="text/javascript" src="js/getContigSize.js"></script> 
<script type="text/javascript" src="js/goToNextStep.js"></script>
</body>
</html>