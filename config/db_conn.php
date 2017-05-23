<?php 

	$dbhost = 'localhost:27017';
	$dbname = 'Thesis';
	
	try {
		
		$manager = new MongoDB\Driver\Manager( 'mongodb://'.$dbhost ); 
	}
	catch (MongoDB\Driver\Exception\Exception $e) {
		
		echo $e->getMessage(), "\n";
	}

?>