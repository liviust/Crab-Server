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
	
	if($_POST['job_id'] && isset($_POST['job_id'])){
		
		$job_id = $_POST['job_id'];
		$title = $_POST['title'];
		$status = $_POST['status'];
		$user_id = $_POST['user_id'];
		
		// Construct a query with filter
		//$filter = ['job_id' => $job_id];
		
		try {

			//With a simple array:
			$filter = array(
			   '$and' => array( 
				  array('job_id' => $job_id), 
				  array('title' => $title),
				  array('status' => $status),
				  array('user_id' => $user_id)
			   )
			);
			
			$options = [];
			
			//Construct a query with filter			
			$query = new \MongoDB\Driver\Query($filter, $options);
			$rows = $manager->executeQuery($dbname.'.'.$collection, $query);
	
			foreach ($rows as $c) {
				$process_id = !empty($c->process_id) ? $c->process_id : '';	
			}			
			
			//print_r($process_id);
						
			//This will kill all processes that have the parent process ID $process_id.
			exec("pkill -P $process_id", $output, $return);
			
			// Return will return non-zero upon an error
			if (!$return) {
				//echo "PDF Created Successfully";
				
				//delete
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->delete(['job_id' => $job_id], ['limit' => 1]);		
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
				$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);		
	
				//print_r($filter);	
				
				$dirPath = '/bip7_disk/WWW/WWW/www/Crab/uploads/'.$user_id.'/'.$job_id;			
				system('rm -rf ' . escapeshellarg($dirPath), $retval);
				
				if($retval == 0){
					// UNIX commands return zero on success
					echo "Your job has been deleted successfully!";
				}else{
					echo "Oops! Something went wrong!";
				}				
				
			} else {
				echo "-bash: kill: ($process_id) - Operation not permitted";
			}
						
		}catch (MongoDB\Driver\Exception\Exception $e) {
					
			echo $e->getMessage(), "\n";
		}		
		
	}
?> 