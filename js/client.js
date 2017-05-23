$(document).ready(function(){ //AJAX long-polling

	/*var userid = 0;
	var active = 0;
*/
	doPoll(); /* Start the inital request */
	
	function doPoll(){	
		$.ajax({
			type: "GET",
			//url:"data_json.php?userid=" + userid + "&active=" + active,
			//url: "longpoll.php",
			uri: "longpoll.php?userid=" + userid + "&jobid=" + jobid + "&active=" + active,
			async: true,
			dataType: "json",
			timeout: 1000000, /* Timeout in ms */
			success: function(data){
				
				if(data == 0){
					console.log("Finished");
					//addmsg("alert", "Finished!");
				} else {				
					//addmsg("new", "Running  PID:"+data); /* Add response to a .msg div (with the "new" class)*/
					console.log("Running");
				}
				
				// update data
				
/*				if (data.active=='1') {
				 $('#active').text("Active");
				 active = 1;
				} else {
				 $('#active').text("Inactive");
				 active = 0;
				}*/
				
					
			},
			complete: function() {
				setTimeout(
				 doPoll, /* Request next message */
				 1000 /* ..after 1 seconds */
				);
			}
		});	
	}
	
	
	
	
	
	
	
	
	
	
	

});