<?php
	if (session_status() == PHP_SESSION_NONE) {
	  session_start();
	}
	
	//Config
	require_once('config/db_conn.php');
	$collection = 'jobs';

	//Connecting to MongoDB
	try {
		
		// Construct the MongoDB Manager
		$manager = new MongoDB\Driver\Manager( 'mongodb://'.$dbhost ); 
	}
	catch (MongoDB\Driver\Exception\Exception $e) {
		
		// if there was an error, we catch and display the problem here
		echo $e->getMessage(), "\n";
	}	
	

	// Construct a query with filter
	$filter = ['user_id' => $_SESSION['google_id']];
	
	//$filter = ['user_id' => '117555268755893720105'];
	$query = new MongoDB\Driver\Query($filter);
	
	try {
	
		$cursor = $manager->executeQuery($dbname.'.'.$collection, $query);
		$arr = array();
		
		foreach($cursor as $c){
			
			$job_id = !empty($c->job_id) ? $c->job_id : '';
			$title = !empty($c->title) ? $c->title : '';
			$submitted = !empty($c->submitted) ? $c->submitted : '';
			$status = !empty($c->status) ? $c->status : '';			
			$my_dat = !empty($c->finished) ? $c->finished : '';
			//print_r($my_dat);
			
			if(!empty($my_dat)){

				$dateTimeZoneTaipei = new DateTimeZone("Asia/Taipei");	
				$millisecondsString = (string)$my_dat;
				$utcdatetime = new MongoDB\BSON\UTCDateTime($millisecondsString);
				$datetime = $utcdatetime->toDateTime()->setTimezone($dateTimeZoneTaipei)->format('Y-m-d H:i:s');
				//print_r($datetime);
			
			}else{ 
				$datetime = "N/A";
			}
			
			$temp = array("Submission" => $job_id, "Title" => $title, "Submitted On" => $submitted, "Finish Time" => $datetime, "Status" => $status);			
			array_push($arr, $temp);
		}
		header('Content-Type: application/json'); // add this line here
		echo json_encode($arr);
		
	} catch (MongoDB\Driver\Exception\Exception $e) {
		//handle the exception
		echo $e->getMessage(), "\n";
	}
  ?> 