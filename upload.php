<?php 
	if (session_status() == PHP_SESSION_NONE) {
	  session_start();
	}
	
	require_once ('uniqid.php');

	//Config
	require_once('config/db_conn.php');
	$collection = 'jobs';
	
	//parameter 
	$Assembling = $_POST['Tech'];
	$google_id_dir = $_POST['googleID'];
	$Title = $_POST['Title'];
	$AssemblyPrefix = $_POST['AssemblyPrefix'];
	$genomeSize = $_POST['genomeSize'];
	$Email = $_POST['Email'];
	$googleName = $_POST['googleName'];
	$ncbi_cutoff = $_POST['ncbi_cutoff'];
	$vfdb_cutoff = $_POST['vfdb_cutoff'];
	$vfdb_threshold_id = $_POST['vfdb_threshold_id'];
	$vfdb_min_length = $_POST['vfdb_min_length'];
	$card_cutoff = $_POST['card_cutoff'];
	$card_threshold_id = $_POST['card_threshold_id'];
	$card_min_length = $_POST['card_min_length'];	
	$checkValue = $_POST['checkValue'];
	
	$fileID = 'uploads/'.$google_id_dir;
	$filename = 'uploads/'.$google_id_dir.'/'.$job_id;
	
	if (!file_exists($filename) && isset($_POST['googleID'])){	
		mkdir('uploads/'.$google_id_dir.'/'.$job_id, 0755, true);
		chmod('uploads/'.$google_id_dir.'/'.$job_id, 0755);
	}
	
	if($checkValue == 'FullWorkflow'){
		
		$target_file = $filename . '/' . basename($_FILES["SequenceFile"]["name"]);

		if ( 0 < $_FILES['SequenceFile']['error'] ) {
			echo 'Error: ' . $_FILES['SequenceFile']['error'];
		}
		else {
			move_uploaded_file($_FILES['SequenceFile']['tmp_name'], $target_file);
			echo "The file ". basename( $_FILES["SequenceFile"]["name"]). " has been uploaded.";
			
			
			// Construct a write concern
			$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
			
			// Create a bulk write object and add our insert operation
			$bulk = new MongoDB\Driver\BulkWrite();		
			
			$bulk->insert(['user_id' => $_POST['googleID'], 'job_id' => $job_id, 'process_id' => '', 'title' => $_POST['Title'], 'submitted' => date("Y-m-d H:i:s"), 'finished' => '', 'status' => 'Queued', 'status_code' => 1, 'types_of_analysis' => 1]);
			
			try {
			
			//Execute one or more write operations
			$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
			
			} catch (MongoDB\Driver\Exception\Exception $e) {
			
			//handle the exception
			echo $e->getMessage(), "\n";
			}		
			
			//gnuplot 5.0 patchlevel 5
			$gnuplot_path = '/usr/bin/gnuplot';
			
			if($Assembling == 1){ //Assembling PacBio data				
				
				//$command = 'nohup time /bip7_disk/WWW/WWW/www/Crab/Model/canu/Linux-amd64/bin/canu -d '.$filename.'/ -p '.$AssemblyPrefix.' gnuplot='.$gnuplot_path.' genomeSize=4.8m useGrid=false maxThreads=10 -pacbio-raw '.$target_file.' > '.$filename.'/process.err 2>&1';
				$command = 'nohup time ./Model/programs/full.sh '.$filename.' '.$AssemblyPrefix.' -pacbio-raw '.$target_file.' '.$ncbi_cutoff.' '.$genomeSize.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
				$command .= ' & echo $!';
			
			}else{ // Assembling Oxford Nanopore data	
						 
				//$command = 'nohup time /bip7_disk/WWW/WWW/www/Crab/Model/canu/Linux-amd64/bin/canu -d '.$filename.'/ -p '.$AssemblyPrefix.' gnuplot='.$gnuplot_path.' genomeSize=4.8m useGrid=false maxThreads=10 -nanopore-raw '.$target_file.' > '.$filename.'/process.err 2>&1';
				$command = 'nohup time ./Model/programs/full.sh '.$filename.' '.$AssemblyPrefix.' -nanopore-raw '.$target_file.' '.$ncbi_cutoff.' '.$genomeSize.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
				$command .= ' & echo $!';
			}

			exec($command, $pid, $return_var);
			
			/*echo 'LP 1<pre>';
			print_r($pid);
			echo '</pre>';	
			
			echo 'LR 1<pre>';
			print_r($return_var);
			echo '</pre>';	*/
			
			// true if the process is still running
			//exec("kill -s 0 $pid 1>/dev/null 2>&1; echo $?") === '0'
			
			if(!empty($pid))
			{			
					
				//require_once ('started.php');
					
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
					
				//Update status			
				$filter = ['job_id' => $job_id];
				$newObj = ['$set' => ['status' => 'Running', 'process_id' => $pid[0], 'status_code' => 0]];
				$options = ["multi" => false, "upsert" => false];		
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}				

			}
			
			$monitor = 'kill -s 0 '.$pid[0].' 1>/dev/null 2>&1; echo $?';
			
			//echo $monitor; 
			
			exec($monitor, $output, $return_var);
			
			
			if(!empty($pid))
			{
				if($output[0]==1)
				{
					
					//require_once ('done.php');
					 
					$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
						
					//Update status			
					$filter = ['job_id' => $job_id];
					$newObj = ['$set' => ['status' => 'DONE', 'process_id' => $pid[0], 'status_code' => 1]];
					$options = ["multi" => false, "upsert" => false];		
					$bulk = new MongoDB\Driver\BulkWrite;
					$bulk->update($filter, $newObj, $options);			
					
					try {
						
						$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
						
					} catch (MongoDB\Driver\Exception\Exception $e) {
						
						echo $e->getMessage(), "\n";
					}				
					
				}
				 
			}
			
			/*echo 'LM 2<pre>';
			print_r($monitor);
			echo '</pre>';	
			
			echo 'LO 2<pre>';
			print_r($output);
			echo '</pre>';	
			
			echo 'LP 2<pre>';
			print_r($pid);
			echo '</pre>';
			
			exit();		
			
			if(file_exists('/proc/'.$pid[0]) && $output[0] == 0){
		
				//Auto Mail Sender
				//require_once ('started.php');
		
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
					
				//Update status			
				$filter = ['job_id' => $job_id];
				$newObj = ['$set' => ['status' => 'Running', 'process_id' => $pid[0], 'status_code' => 0]];
				$options = ["multi" => false, "upsert" => false];		
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}				
			}else{
				
				
				echo '<script type="text/javascript">alert("Unknown upload error");</script>';
				$ReturnUrl="index.php";
				echo ("<script>location.href='$ReturnUrl'</script>");
			}*/
			
			
		}
	}else if($checkValue == 'AssemblyFree'){
		
		$target_file2 = $filename . '/' . basename($_FILES["contigsFile"]["name"]);	
		
		if ( 0 < $_FILES['contigsFile']['error'] ) {
			echo 'Error: ' . $_FILES['contigsFile']['error'];
		}
		else {
			move_uploaded_file($_FILES['contigsFile']['tmp_name'], $target_file2);
			echo "The file ". basename( $_FILES["contigsFile"]["name"]). " has been uploaded.";
		}
		
		// Construct a write concern
		$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
		
		// Create a bulk write object and add our insert operation
		$bulk = new MongoDB\Driver\BulkWrite();		
		
		$bulk->insert(['user_id' => $_POST['googleID'], 'job_id' => $job_id, 'process_id' => '', 'title' => $_POST['Title'], 'submitted' => date("Y-m-d H:i:s"), 'finished' => '', 'status' => 'Queued', 'status_code' => 1, 'types_of_analysis' => 2]);
		
		try {
					
			//Execute one or more write operations
			$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
			
			} catch (MongoDB\Driver\Exception\Exception $e) {
			
			//handle the exception
			echo $e->getMessage(), "\n";
		}

		//Extract partial sequence:1-100000
		//$partial = 'samtools faidx '.$target_file2.' $(cat '.$target_file2.' | /bip7_disk/WWW/WWW/www/Crab/Model/bioawk/bioawk -c fastx \'{ print length($seq), $name }\' | sort -k1,1rn | head -1 | cut -f 2):1-100000 > '.$filename.'/contigs.draft';
		//exec($partial, $pid, $return_var);
		
		//if($return_var == 0){ //NCBI
			
			//$command = 'nohup time /bip7_disk/WWW/WWW/www/Crab/Model/ncbi-blast-2.6.0+/bin/blastn -task blastn -db /bip7_disk/WWW/WWW/www/Crab/Model/nt/nt -query '.$target_file2.' -gilist /bip7_disk/WWW/WWW/www/Crab/Model/sequence_gi/bacteria.nt.gi.min.txt -evalue '.$ncbi_cutoff.' -outfmt 6 -out '.$filename.'/species.tsv -num_threads 35 > '.$filename.'/process.err 2>&1';	
			//$command = 'nohup time /bip7_disk/WWW/WWW/www/Crab/Model/ncbi-blast-2.6.0+/bin/blastn -task blastn -db /bip7_disk/WWW/WWW/www/Crab/Model/nt/nt -query '.$filename.'/contigs.draft -gilist /bip7_disk/WWW/WWW/www/Crab/Model/sequence_gi/bacteria.nt.gi.min.txt -evalue '.$ncbi_cutoff.' -outfmt 6 -out '.$filename.'/species.tsv -num_threads 10 > '.$filename.'/process.err 2>&1';
			$command = 'nohup time ./Model/programs/free.sh '.$filename.' '.$ncbi_cutoff.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
			$command .= ' & echo $!';
			
			exec($command, $pid, $return_var);
			
			$monitor = 'kill -s 0 '.$pid[0].' 1>/dev/null 2>&1; echo $?';
			exec($monitor, $output, $return_var);		
			
			if(file_exists('/proc/'.$pid[0]) && $output[0] == 0){
		
				//Auto Mail Sender
				//require_once ('started.php');
		
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
					
				//Update status			
				$filter = ['job_id' => $job_id];
				$newObj = ['$set' => ['status' => 'Running', 'process_id' => $pid[0], 'status_code' => 0]];
				$options = ["multi" => false, "upsert" => false];		
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}				
			}else{
				echo '<script type="text/javascript">alert("Unknown upload error");</script>';
				$ReturnUrl="index.php";
				echo ("<script>location.href='$ReturnUrl'</script>");
			}
		//}
		
		
	}else if($checkValue == 'AutoFullWorkflow'){
		
		$target_file3 = $filename . '/' . basename($_FILES["SequenceFile"]["name"]);

		if ( 0 < $_FILES['SequenceFile']['error'] ) {
			echo 'Error: ' . $_FILES['SequenceFile']['error'];
		}
		else {
			move_uploaded_file($_FILES['SequenceFile']['tmp_name'], $target_file3);
			echo "The file ". basename( $_FILES["SequenceFile"]["name"]). " has been uploaded.";
			
			// Construct a write concern
			$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
			
			// Create a bulk write object and add our insert operation
			$bulk = new MongoDB\Driver\BulkWrite();		
			
			$bulk->insert(['user_id' => $_POST['googleID'], 'job_id' => $job_id, 'process_id' => '', 'title' => $_POST['Title'], 'submitted' => date("Y-m-d H:i:s"), 'finished' => '', 'status' => 'Queued', 'status_code' => 1, 'types_of_analysis' => 3]);
			
			try {
			
			//Execute one or more write operations
			$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
			
			} catch (MongoDB\Driver\Exception\Exception $e) {
			
			//handle the exception
			echo $e->getMessage(), "\n";
			}		
			
			//gnuplot 5.0 patchlevel 5
			$gnuplot_path = '/usr/bin/gnuplot';
			
			if($Assembling == 1){ //Assembling PacBio data				
				
				$command = 'nohup time ./Model/programs/full.sh '.$filename.' '.$AssemblyPrefix.' -pacbio-raw '.$target_file3.' '.$ncbi_cutoff.' '.$genomeSize.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
				$command .= ' & echo $!';
			
			}else{ // Assembling Oxford Nanopore data	
						 
				$command = 'nohup time ./Model/programs/full.sh '.$filename.' '.$AssemblyPrefix.' -nanopore-raw '.$target_file3.' '.$ncbi_cutoff.' '.$genomeSize.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
				$command .= ' & echo $!';
			}

			exec($command, $pid, $return_var);
			
			if(!empty($pid))
			{			
					
				//require_once ('started.php');
					
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
					
				//Update status			
				$filter = ['job_id' => $job_id];
				$newObj = ['$set' => ['status' => 'Running', 'process_id' => $pid[0], 'status_code' => 0]];
				$options = ["multi" => false, "upsert" => false];		
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}				
			}
			
			$monitor = 'kill -s 0 '.$pid[0].' 1>/dev/null 2>&1; echo $?';
			
			//echo $monitor; 
			
			exec($monitor, $output, $return_var);
			
			if(!empty($pid))
			{
				if($output[0]==1)
				{
					
					//require_once ('done.php');
					 
					$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
						
					//Update status			
					$filter = ['job_id' => $job_id];
					$newObj = ['$set' => ['status' => 'DONE', 'process_id' => $pid[0], 'status_code' => 1]];
					$options = ["multi" => false, "upsert" => false];		
					$bulk = new MongoDB\Driver\BulkWrite;
					$bulk->update($filter, $newObj, $options);			
					
					try {
						
						$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
						
					} catch (MongoDB\Driver\Exception\Exception $e) {
						
						echo $e->getMessage(), "\n";
					}
				} 
			}
		}		
	}else if($checkValue == 'AutoAssemblyFree'){
		

		$target_file3 = $filename . '/' . basename($_FILES["contigsFile"]["name"]);	
		
		if ( 0 < $_FILES['contigsFile']['error'] ) {
			echo 'Error: ' . $_FILES['contigsFile']['error'];
		}
		else {
			move_uploaded_file($_FILES['contigsFile']['tmp_name'], $target_file3);
			echo "The file ". basename( $_FILES["contigsFile"]["name"]). " has been uploaded.";
		}
		
		// Construct a write concern
		$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
		
		// Create a bulk write object and add our insert operation
		$bulk = new MongoDB\Driver\BulkWrite();		
		
		$bulk->insert(['user_id' => $_POST['googleID'], 'job_id' => $job_id, 'process_id' => '', 'title' => $_POST['Title'], 'submitted' => date("Y-m-d H:i:s"), 'finished' => '', 'status' => 'Queued', 'status_code' => 1, 'types_of_analysis' => 4]);
		
		try {
					
			//Execute one or more write operations
			$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
			
			} catch (MongoDB\Driver\Exception\Exception $e) {
			
			//handle the exception
			echo $e->getMessage(), "\n";
		}

			$command = 'nohup time ./Model/programs/free.sh '.$filename.' '.$ncbi_cutoff.' '.$vfdb_cutoff.' '.$vfdb_threshold_id.' '.$vfdb_min_length.' '.$card_cutoff.' '.$card_threshold_id.' '.$card_min_length.' '.$_POST['googleID'].' '.$job_id.' '.$Email.' '.$googleName.' > '.$filename.'/jobLog.err 2>&1';
			$command .= ' & echo $!';
			
			exec($command, $pid, $return_var);
			
			$monitor = 'kill -s 0 '.$pid[0].' 1>/dev/null 2>&1; echo $?';
			exec($monitor, $output, $return_var);		
			
			if(file_exists('/proc/'.$pid[0]) && $output[0] == 0){
		
				//Auto Mail Sender
				//require_once ('started.php');
		
				$wc = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY,1000);
					
				//Update status			
				$filter = ['job_id' => $job_id];
				$newObj = ['$set' => ['status' => 'Running', 'process_id' => $pid[0], 'status_code' => 0]];
				$options = ["multi" => false, "upsert" => false];		
				$bulk = new MongoDB\Driver\BulkWrite;
				$bulk->update($filter, $newObj, $options);			
				
				try {
					
					$result = $manager->executeBulkWrite($dbname.'.'.$collection, $bulk, $wc);
					
				} catch (MongoDB\Driver\Exception\Exception $e) {
					
					echo $e->getMessage(), "\n";
				}				
			}else{
				echo '<script type="text/javascript">alert("Unknown upload error");</script>';
				$ReturnUrl="index.php";
				echo ("<script>location.href='$ReturnUrl'</script>");
			}
	}	
?>

