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
<?php
	// Get current URL
	$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	//echo $actual_link;
	//echo parse_url($actual_link, PHP_URL_QUERY);
	$JobCode = parse_url($actual_link, PHP_URL_QUERY);
	$JobCode = trim($JobCode, 'jobid=');
	
	//echo $JobCode;
	if($JobCode == null){
		header('Location: 404-event.php');
	}

	// Construct a query with filter
	$filter = ['user_id' => $_SESSION['google_id'], 'job_id' => $JobCode];
	$query = new MongoDB\Driver\Query($filter);
	
	try {
	
		$cursor = $manager->executeQuery($dbname.'.'.$collection, $query);

		// Iterate over all matched documents
		foreach ($cursor as $document) {
			 //echo $document->job_id . "<br />";
		}
		
		//echo $document->types_of_analysis;

	} catch (MongoDB\Driver\Exception\Exception $e) {
		//handle the exception
		echo $e->getMessage(), "\n";
	}
?>
<?php
	//Species-Organism
	$Species_header = array('Organism','GenBank', 'Contig', '%Identity', 'Max score', 'BioProject', 'BioSample');	
	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')) != false)){
		$csv = array_map('str_getcsv', file('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv'));	
		$c = count($csv);
		$s1 = ($csv[0][1]);
		$s2 = ($csv[1][1]);
		$s3 = ($csv[2][1]);
		$s4 = ($csv[3][1]);
		$s5 = ($csv[4][1]);
	}
	
	//VFDB
	$VFDB_csv = 0;
	$VFDB_header = array('Virulence factor', '%Identity', '%VFCoverage', 'Contig', 'Position in contig', 'Protein function', 'Accession number');
	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-html.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-html.csv')) != false)){
		$VFDB_csv = array_map('str_getcsv', file('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-html.csv'));
	}

	//AMR
	$AMR_csv = 0;
	$AMR_header = array('Resistance gene', '%Identity', '%AMRCoverage', 'Contig', 'Position in contig', 'Predicted phenotype', 'Accession number');
	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/AMR-blast-html.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/AMR-blast-html.csv')) != false)){
		$AMR_csv = array_map('str_getcsv', file('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/AMR-blast-html.csv'));
	}
	
	//View Log
	$jobLog_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/jobLog.err';
	$jobLogfile = file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/jobLog.err', FILE_USE_INCLUDE_PATH);
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
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.13/css/dataTables.bootstrap.min.css" />
<!--<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.13/css/dataTables.jqueryui.min.css" />
<link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css" />-->
<link rel="stylesheet" type="text/css" href="D3/css/style.css" />
<link rel="icon" href="images/crab.png">
<link rel="stylesheet" type="text/css" href="css/load5.css" />
<link rel="stylesheet" type="text/css" href="css/load6.css" />
<script type="text/javascript" src="http://d3js.org/d3.v3.js?2.9.1"></script>
<script type="text/javascript" src="https://d3js.org/d3.v4.min.js"></script>
<style>
.container .text-muted {
	margin: 30px 0;
	text-align: center;
}
.grey {
	color: grey;
}
#jobLogContents {
	height: 400px;
	text-align: left;
	color: #00F;
	border: solid 1px #ccc;
	background: #fff;
	margin: 0 8px 8px 8px;
	padding: 8px;
	width: 100%;
	overflow-x: hidden;
	overflow-y: auto;
	-moz-border-radius: 6px;
	-webkit-border-radius: 6px;
}
::selection {
	background: #3CC;
	color: #FFF;
}
::-moz-selection {
 background: #3CC;
 color: #FFF;
}
pre {
	display: none;
}
</style>
</head>
<body>
<!--<body ONDRAGSTART="window.event.returnValue=false" onSelectStart="event.returnValue=false" ONCONTEXTMENU="window.event.returnValue=false">
-->
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
    <h1>Cяab Server<small> Reports</small>
      <?php
	if (isset($_SESSION['google_id']))
		echo("<a href='jobs.php'><button type='button' class='btn btn-warning btn-sm'><span class='glyphicon glyphicon-bell'></span> Jobs</button></a>");	
	?>
    </h1>
  </div>
  <h4>The reports are visible only for you.</h4>
  <?php
/*        echo $path_parts['dirname'], "<br />";
        echo $path_parts['basename'], "<br />";
        echo $path_parts['extension'], "<br />";
        echo $path_parts['filename'], "<br />"; // filename is only since PHP 5.2.0	*/	  
		
		$rawfilename = "";
		$contigsfilename = "";
		$quast_zip = "";
		$contig_browser_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/quast-reports/icarus_viewers/contig_size_viewer.html';
		$statistics_quast_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/quast-reports/report.html';
		$zip_quast_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/quast-reports.zip';
		$VFDB_blast_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-results.csv';
		$AMR_blast_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/CARD-blast-results.csv';
		$species_blast_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv';

		// Full Workflow
		if($document->types_of_analysis == 1){
			foreach (glob('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/*.contigs.fasta') as $rawfilename) {
				//echo $rawfilename ."<br />";
			}		
			if($rawfilename != ''){
				$path_parts = pathinfo($rawfilename);
			}			
		}else{
			// Assembly Free
			foreach (glob('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/*.f*') as $contigsfilename) {
				//echo $contigsfilename ."<br />";
			}			
			if($contigsfilename != ''){
				$path_parts = pathinfo($contigsfilename);
			}
		}
		
		// Download
		foreach (glob('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/quast-reports.zip') as $quast_zip) {
			//echo $quast_zip ."<br />";
		}				
    ?>
  <br />
  <!--  <h1>Response from server:</h1>
        <div id="response"></div>-->
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#assembler"><i class="fa fa-align-left"></i> Assembler</a></li>
    <li><a data-toggle="tab" href="#species"><i class="fa fa-search-plus"></i> Species</a></li>
    <li><a data-toggle="tab" href="#virulence"><i class="fa fa-circle-o-notch"></i> Virulence</a></li>
    <li><a data-toggle="tab" href="#resistance"><i class="fa fa-spinner"></i> Resistance</a></li>
    <li><a data-toggle="tab" href="#download"><i class="fa fa-cloud-download"></i> Download</a></li>
    <li><a data-toggle="tab" href="#log"><i class="fa fa-cog"></i> View Log</a></li>
  </ul>
  <div class="tab-content"> 
    <!--Assembler Panels start-->
    <div id="assembler" class="tab-pane fade in active">
      <?php 
		if($quast_zip == ''){
			echo '<div class="loader6">Loading...</div>';
		}
		else{
			echo '<div>';	
		
    ?>
      <h3><strong>Input Files:&nbsp;<i class="grey">
        <?php 
		if($rawfilename != ''){ 
			echo $path_parts['basename']; 
		}else if($contigsfilename != ''){
			echo $path_parts['filename'];
		}
		?>
        </i></strong></h3>
      <br />
      <?php 
	  if($rawfilename != '')
	  	$zip_contigs_directory = 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/'.$path_parts['filename'].'.zip';

	  ?>
      <script type="text/javascript">
        function SetCwinHeight()
        {
        var iframeid=document.getElementById("mainframe"); //iframe id
          if (document.getElementById)
          {   
           if (iframeid && !window.opera)
           {   
            if (iframeid.contentDocument && iframeid.contentDocument.body.offsetHeight)
             {   
               iframeid.height = iframeid.contentDocument.body.offsetHeight;   
             }else if(iframeid.Document && iframeid.Document.body.scrollHeight)
             {   
               iframeid.height = iframeid.Document.body.scrollHeight;   
              }   
            }
           }
        }
        </script>
      <h3>QUAST full report</h3>
      <?php
        if($quast_zip != ''){
		  echo '<iframe src="'.$statistics_quast_directory.'" name="mainframe" width="100%" marginwidth="0" marginheight="0" onload="Javascript:SetCwinHeight()"  scrolling="No" frameborder="0" id="mainframe"></iframe>';
		}
      ?>
      <?php echo '</div>';} ?> </div>
    <!--Assembler Panels end-->
    
    <div id="species" class="tab-pane fade">
      <?php 
        if(!file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')){
            echo '<div class="loader5">Loading...</div>';
        }
        else{
            echo '<div>';
    ?>
      <h3>Organism:
        <?php	  
	  	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')) != false)){
			//echo "";
			echo '<a target="_blank" href="https://www.ncbi.nlm.nih.gov/nuccore/'.$s1.','.$s2.','.$s3.','.$s4.','.$s5.'">	  
				  <button type="button" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-star"></span> Top '.$c.'</button></a>';
		}
	  ?>
      </h3>
      <table id="tableSpecies" class="table table-striped table-bordered display table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <?php
				foreach( $Species_header as $key ){
					echo '<th>'.$key.'</th>';
				}
            ?>
          </tr>
        </thead>
        <tbody>
          <?php
		   	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/ncbi-reports/ncbi_top5_species.csv')) != false)){
			foreach( $csv as $row ){
				echo '<tr>';
				foreach($row as $column => $value){
					if ($column == 1) {
						echo "<td><a href='https://www.ncbi.nlm.nih.gov/nuccore/$value' target='_blank'>$value</a></td>";
					} else if ($column == 5){
						echo "<td><a href='https://www.ncbi.nlm.nih.gov/bioproject/?term=$value' target='_blank'>$value</a></td>";
					} else if ($column == 6){
						echo "<td><a href='https://www.ncbi.nlm.nih.gov/biosample/?term=$value' target='_blank'>$value</a></td>";
					}else {
						echo "<td>$value</td>";
					}
				}			
				echo '</tr>';
			}
		}			
		?>
        </tbody>
      </table>
      <?php echo '</div>';} ?> </div>
    <div id="virulence" class="tab-pane fade">
      <?php
	  if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/flare.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/flare.csv')) != false)){
	  echo '<h3>Zoomable Sunburst</h3><p>The inner circle is <strong>virulence factor category</strong>; besides, <strong>gene full names</strong> was presented in the outermost circle.<br /><br /><span style="color:#F00">Click on any arc to zoom in, and click on the center circle to zoom out.</span></p>';
	  echo '<div style="text-align:center" id="ZoomableVFs"></div>';
	  }
	  ?>
      <pre id="csv"><?php 
	  if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/flare.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/flare.csv')) != false)){
        echo file_get_contents('http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/flare.csv');	
	  }?>
</pre>
      <?php 
        if(!file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-results.csv')){
            echo '<div class="loader6">Loading...</div>';
        }
        else{
            echo '<div>';	
    ?>
      <h3>Virulence genes</h3>
      <?php 
	  if (count($VFDB_csv) > 0): ?>
      <table id="tableVFDB" class="table table-striped table-bordered display table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <?php
				foreach( $VFDB_header as $key ){
					echo '<th>'.$key.'</th>';
				}
             ?>
          </tr>
        </thead>
        <tbody>
          <?php
		  if (is_array($VFDB_csv)) {	  
		  	foreach ($VFDB_csv as $row): array_map('htmlentities', $row);
		  ?>
          <tr>
            <td><?php echo implode('</td><td>', $row); ?></td>
          </tr>
          <?php endforeach; }?>
        </tbody>
      </table>
      <?php endif; ?>
      <?php echo '</div>';} ?> </div>
    <div id="resistance" class="tab-pane fade">
      <?php
	  if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/INPUTFILE.json')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/INPUTFILE.json')) != false)){
	  echo '<h3>Zoomable Sunburst</h3><p>Summary of resistome annotations of the genome (<strong>Inner to outer</strong>):<br />
			circles 2 show the <strong>antibiotic classification</strong><br />
			circles 3 show the <strong>antibiotic agent</strong> or <strong>subclasses of antibiotics</strong><br />
			circles 4 show the <strong>antibiotic resistance gene</strong><br /><br /><span style="color:#F00">Click on any arc to zoom in, and click on the center circle to zoom out.</span></p>';
	  echo '<div style="text-align:center" id="ZoomableARG"></div>';
	  }
	  ?>
      <?php 
        if(!file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/CARD-blast-results.csv')){
            echo '<div class="loader5">Loading...</div>';
        }
        else{
            echo '<div>';	
    ?>
      <h3>Resistance genes</h3>
      <!--<p>Hover over for details</p>--> 
      <!--<div id="Aster_plot"></div>-->
      <?php if (count($AMR_csv) > 0): ?>
      <table id="tableAMR" class="table table-striped table-bordered display table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <?php
                    foreach( $AMR_header as $key ){
                        echo '<th>'.$key.'</th>';
                    }
                ?>
          </tr>
        </thead>
        <tbody>
          <?php
		   	if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/AMR-blast-html.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/AMR-blast-html.csv')) != false)){
			foreach( $AMR_csv as $row ){
				echo '<tr>';
				foreach($row as $column => $value){	
					if ($column == 7) { //ontology
						echo "";
					}else if ($column == 6) { //Accession number
						echo "<td><a href='https://card.mcmaster.ca/ontology/$row[7]' target='_blank'>$value</a></td>";
					}else {
						echo "<td>$value</td>";
					}	
				}			
				echo '</tr>';
			}
		}			
		?>
        </tbody>
      </table>
      <?php endif; ?>
      <?php echo '</div>';} ?> </div>
    <div id="download" class="tab-pane fade">
      <h3>Download</h3>
      <p>The below table lists a set of downloadable files containing the resulting data of this analysis.</p>
      <table id="tableDownload" class="table table-striped table-bordered display table-hover" cellspacing="0" width="100%">
        <thead>
          <tr>
            <th>File</th>
            <th>Type</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          <?php 
		if($rawfilename != ''){ 
          echo '<tr>
            <td><a href="'.$zip_contigs_directory.'">'.$path_parts['filename'].'.zip</a></td>
            <td>Canu assembly contigs</td>
            <td>The assembled contigs into one compressed .zip file</td>
          </tr>';
          }
       ?>
          <?php
		if($quast_zip != ''){
		  echo '<tr>
			<td><a href="'.$zip_quast_directory.'">quast-reports.zip</a></td>
			<td>Standard QUAST report</td>
			<td>Text, TSV and Latex versions of the table, plots in PDF. Additionally, detailed contigs and genome statistics.</td>
		  </tr>';
		}
	   ?>
          <?php
		if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-results.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/vfdb-reports/VFDB-blast-results.csv')) != false)){
		  echo '<tr>
			<td><a href="'.$VFDB_blast_directory.'">VFDB-blast-results.csv</a></td>
			<td>VFDB BLAST Results Table</td>
			<td>A table of the resulting VFDB BLAST hits, in csv format. The results may take a while to download for large data sets.</td>
		  </tr>';
		}
	   ?>
          <?php
		if (file_exists('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/CARD-blast-results.csv')&&(trim(file_get_contents('/bip7_disk/WWW/WWW/www/Crab/uploads/'.$_SESSION['google_id'].'/'.$JobCode.'/card-reports/CARD-blast-results.csv')) != false)){
		  echo '<tr>
			<td><a href="'.$AMR_blast_directory.'">CARD-blast-results.csv</a></td>
			<td>CARD BLAST Results Table</td>
			<td>A table of the resulting CARD BLAST hits, in csv format. The results may take a while to download for large data sets.</td>
		  </tr>';
		}
	   ?>
        </tbody>
      </table>
    </div>
    <div id="log" class="tab-pane fade">
      <h3><strong>LOG</strong></h3>
      <!--<p>Download Master Log</p>--> 
      <a href="<?= $jobLog_directory ?>" class="btn btn-primary" download> <span class="glyphicon glyphicon-download"></span> Download Master Log</a> <br />
      <br />
      <div id="jobLogContents"> <?php echo nl2br($jobLogfile); ?> </div>
    </div>
  </div>
  <!-- footer start -->
  <footer class="footer">
    <div class="container">
      <hr />
      <p class="text-muted">Copyright © <script>document.write(new Date().getFullYear())</script><a target="_blank" href="http://bioinfo.cs.ccu.edu.tw/bioinfo/"> Bioinformatics Lab</a>. All Rights Reserved.</p>
    </div>
  </footer>
  <!-- footer end --> 
</div>
<script type="text/javascript" src="js/jquery-3.1.1.min.js"></script> 
<script type="text/javascript" src="bootstrap/js/bootstrap.min.js"></script> 
<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js"></script> 
<script type="text/javascript" src="https://cdn.datatables.net/1.10.13/js/dataTables.bootstrap.min.js"></script> 
<script type="text/javascript" src="http://labratrevenge.com/d3-tip/javascripts/d3.tip.v0.6.3.js"></script> 
<script type="text/javascript" src="http://d3js.org/d3.v3.js?2.9.1"></script> 
<script type="text/javascript" src="https://d3js.org/d3.v4.min.js"></script> 
<script type="text/javascript" src="http://d3js.org/d3.v3.min.js"></script> 
<script type="text/javascript" src="https://d3js.org/d3-dsv.v1.min.js"></script> 
<script type="text/javascript" src="js/ZoomableVFG.js"></script> 
<script type="text/javascript">
$(document).ready(function() {
    $('#tableSpecies').DataTable({
		//"bPaginate": false,
		"bLengthChange": false,
		"order": [[ 4, "desc" ]]
		//"iDisplayLength": 5
	});
		
    $('#tableAMR').DataTable({
        "order": [[ 5, "asc" ]]
	});
	
	$('#tableVFDB').DataTable({
        "order": [[ 3, "asc" ]]
	});
	
	$('#tableDownload').DataTable();
	
	// get query string values
	function getParameterByName(name, url) {
		if (!url) {
		  url = window.location.href;
		}
		name = name.replace(/[\[\]]/g, "\\$&");
		var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
			results = regex.exec(url);
		if (!results) return null;
		if (!results[2]) return '';
		return decodeURIComponent(results[2].replace(/\+/g, " "));
	}
	
	// query string: ?jobid=1D3F812D-B26D-A891-8EF7-EE14BF4D8CAD
	var jobid = getParameterByName('jobid');
	//console.log(jobid);
});
</script> 
<script type="text/javascript">
<?= 'var google_id = '.json_encode($_SESSION['google_id']).';'; ?>
<?= 'var jobid = '.json_encode($JobCode).';'; ?>

//console.log(jobid);

var width = 950,
    height = 1000,
    radius = Math.min(width, height) / 2;

var x = d3.scale.linear()
    .range([0, 2 * Math.PI]);

var y = d3.scale.linear()
    .range([0, radius]);

var color = d3.scale.category20c();

var svg = d3.select("#ZoomableARG").append("svg")
    .attr("width", width)
    .attr("height", height)
  .append("g")
    .attr("transform", "translate(" + width / 2 + "," + (height / 2 + 10) + ")");

var partition = d3.layout.partition()
    .value(function(d) { return d.count; }); //{"count": 1, "name": "PmrF"}

var arc = d3.svg.arc()
    .startAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x))); })
    .endAngle(function(d) { return Math.max(0, Math.min(2 * Math.PI, x(d.x + d.dx))); })
    .innerRadius(function(d) { return Math.max(0, y(d.y)); })
    .outerRadius(function(d) { return Math.max(0, y(d.y + d.dy)); });

$.ajax({
    url: 'http://bioinfo.cs.ccu.edu.tw/Crab/uploads/'+ google_id + '/' + jobid +'/card-reports/INPUTFILE.json',
    type: 'GET',
    error: function()
    {
        //not exists
    },
    success: function()
    {
        // exists
		
		d3.json("http://bioinfo.cs.ccu.edu.tw/Crab/uploads/"+ google_id + "/" + jobid +"/card-reports/INPUTFILE.json", function(error, root) {
		  var g = svg.selectAll("g")
			  .data(partition.nodes(root))
			.enter().append("g");
		
		  var path = g.append("path")
			.attr("d", arc)
			.style("fill", function(d) { return color((d.children ? d : d.parent).name); })
			.on("click", click);
		
		  var text = g.append("text")
			.attr("transform", function(d) { return "rotate(" + computeTextRotation(d) + ")"; })
			.attr("x", function(d) { return y(d.y); })
			.attr("dx", "6") // margin
			.attr("dy", ".35em") // vertical-align
			.text(function(d) { return d.name; });
		
		  function click(d) {
			// fade out all text elements
			text.transition().attr("opacity", 0);
		
			path.transition()
			  .duration(750)
			  .attrTween("d", arcTween(d))
			  .each("end", function(e, i) {
				  // check if the animated element's data e lies within the visible angle span given in d
				  if (e.x >= d.x && e.x < (d.x + d.dx)) {
					// get a selection of the associated text element
					var arcText = d3.select(this.parentNode).select("text");
					// fade in the text element and recalculate positions
					arcText.transition().duration(750)
					  .attr("opacity", 1)
					  .attr("transform", function() { return "rotate(" + computeTextRotation(e) + ")" })
					  .attr("x", function(d) { return y(d.y); });
				  }
			  });
		  }
		});		
		
    }
}); // end $.ajax

d3.select(self.frameElement).style("height", height + "px");

// Interpolate the scales!
function arcTween(d) {
  var xd = d3.interpolate(x.domain(), [d.x, d.x + d.dx]),
      yd = d3.interpolate(y.domain(), [d.y, 1]),
      yr = d3.interpolate(y.range(), [d.y ? 20 : 0, radius]);
  return function(d, i) {
    return i
        ? function(t) { return arc(d); }
        : function(t) { x.domain(xd(t)); y.domain(yd(t)).range(yr(t)); return arc(d); };
  };
}

function computeTextRotation(d) {
  return (x(d.x + d.dx / 2) - Math.PI / 2) / Math.PI * 180;
}
</script>
</body>
</html>