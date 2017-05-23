<?php
	
	if (isset($_GET['userid']) and isset($_GET['active'])){
	
		$userid = $_GET['userid'];
		$client_active = $_GET['active'];
		
		openConnection();
		
		for ($i = 0, $timeout = 180; $i < $timeout; $i++ ) { //最多查詢180次 
		
			$SQL = "SELECT active FROM table WHERE user='$userid';";
			$result = mysql_query($SQL) or die("Couldn t execute query.".mysql_error());
			$row = mysql_fetch_array($result);
			$active = $row[active];
		
			if ($active<>$client_active) { //馬上傳回最新的資料
				$responce->active = $active;
				echo json_encode($responce);
				flush(); //強迫將Buffer資料提前秀出
				exit(0);
			}
			
			usleep(1000000); //微秒為單位 不用傳回資料
			
		}
		
		$responce->active = $active;
		echo json_encode($responce);
		flush();
	
	}

?>