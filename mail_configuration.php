<?php
	require_once("PHPMailer-master/PHPMailerAutoload.php"); //匯入PHPMailer類別       
	
	//$title = "Cяab Server result for your query";
	$title = "Forgot Password Recovery";
	$uri = "http://bioinfo.cs.ccu.edu.tw/Crab/result.php?jobid=".$job_id;
	//$uri = "http://bioinfo.cs.ccu.edu.tw/Crab/result.php?jobid=".$job_id."/output.zip";
	
	$mail= new PHPMailer(); //建立新物件      
	$mail->IsSMTP(); //設定使用SMTP方式寄信        
	$mail->SMTPAuth = true; //設定SMTP需要驗證        
	$mail->SMTPSecure = "ssl"; // Gmail的SMTP主機需要使用SSL連線   
	$mail->Host = "smtp.gmail.com"; //Gamil的SMTP主機        
	$mail->Port = 465;  //Gamil的SMTP主機的SMTP埠位為465埠。        
	$mail->CharSet = "UTF-8"; //設定郵件編碼        
	
	$mail->Username = "noreply@imyes.net"; //設定驗證帳號        
	$mail->Password = "cc246810"; //設定驗證密
	$mail->SetFrom('noreply@imyes.net', 'Cяab Server');
	
	$mail->Subject = $title; //設定郵件標題
	$mail->Body = "Hi ".$Username.",<br /><br />We received a request to reset your password for your account: <a href=mailto:".$email.">".$email."</a><br /><br />Simply click on the link to set a new password: <a href=".$uri.">Link</a><br /><br />Cheers,"; //設定郵件內容        
	$mail->IsHTML(true); //設定郵件內容為HTML
	$mail->AddAddress($email, $Username); //設定收件者郵件及名稱
	$mail->Send();
	//Better yet, add them as Carbon Copy recipients.
	//$mail->AddCC('enderman542@imyes.net', 'Yi-Ting Liu');
	/*
	if(!$mail->Send()) { 
		echo '<script type="text/javascript">alert("Mailer Error: "'.$mail->ErrorInfo.');</script>';        
	} else {        
		echo '<script type="text/javascript">alert("Good job!");</script>';      
	} 
	*/
?>
