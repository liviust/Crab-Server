<?php
	require_once("PHPMailer-master/PHPMailerAutoload.php"); //匯入PHPMailer類別       
	
	$title = "Cяab Server for your query has started";
	$uri = "http://bioinfo.cs.ccu.edu.tw/Crab/result.php?jobid=".$job_id;
	
	$mail= new PHPMailer(); //建立新物件      
	$mail->IsSMTP(); //設定使用SMTP方式寄信        
	$mail->SMTPAuth = true; //設定SMTP需要驗證        
	$mail->SMTPSecure = "ssl"; // Gmail的SMTP主機需要使用SSL連線   
	$mail->Host = "smtp.gmail.com"; //Gamil的SMTP主機        
	$mail->Port = 465;  //Gamil的SMTP主機的SMTP埠位為465埠。        
	$mail->CharSet = "UTF-8"; //設定郵件編碼        
	
	$mail->Username = "noreply@imyes.net"; //設定驗證帳號        
	$mail->Password = ""; //設定驗證密碼
	$mail->SetFrom('noreply@imyes.net', 'Cяab Server');
	
	$mail->Subject = $title; //設定郵件標題
	$mail->Body = "Dear User,<br /><br />&#8195;Your job of Cяab Server has started. The job id is <strong>".$job_id."</strong>. You can check job status from ".$uri."<br /><br /> &#8195;Thanks,"; //設定郵件內容        
	$mail->IsHTML(true); //設定郵件內容為HTML
	$mail->AddAddress($Email, $googleName); //設定收件者郵件及名稱
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
