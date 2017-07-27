$( document ).ready(function() {
	// To disable:
	document.getElementById('s1').style.pointerEvents = 'none';
	document.getElementById('s2').style.pointerEvents = 'none';
	document.getElementById('s3').style.pointerEvents = 'none';
	document.getElementById('s4').style.pointerEvents = 'none';
	document.getElementById('s5').style.pointerEvents = 'none';
	document.getElementById('s6').style.pointerEvents = 'none';
	$('#SequenceFile').prop('disabled', true);	
	
	var checkValue = $("#AnalysisTypeSelect").val();
	if (checkValue == "AssemblyFree") {
		$('#SequenceFile').prop('disabled', true);
	}	

	// disable the button if the selection is changed:
	$("#AnalysisTypeSelect").change(function () {
		if ($(this).val() == "AssemblyFree") {
			$('#SequenceFile').prop('disabled', true);
			$('#contigsFile').prop('disabled', false);
		} else {
			// enable button
			$('#SequenceFile').prop('disabled', false);
			$('#contigsFile').prop('disabled', true);
		}
	});
});

function goToNextStep(currentStep){

var checkValue = $("#AnalysisTypeSelect").val();

    if (currentStep==1) {
		$("#s1").click();
	}else if (currentStep==2){
		
		if (checkValue == ""){
			alert("Please select the type of analysis you would like.");
			
		}else if(($('#SequenceFile').val() != "" && checkValue == "FullWorkflow")){
			
			$('#SequenceFile').prop('disabled', false);
			
			// get the file name, possibly with path (depends on browser)
			var filename = $("#SequenceFile").val();
			
			// Use a regular expression to trim everything before final dot
			var extension = filename.replace(/^.*\./, '');			
			var rawreads = filename.replace(/.*(\/|\\)/, '');
			// trimming 			
			var AssemblyPrefix = rawreads.replace(/(.*)\.(.*?)$/, "$1");
						
			$('#RawReads').attr("placeholder",rawreads);
			$('#OverviewRawReads').attr("placeholder",rawreads);
			
			// Iff there is no dot anywhere in filename, we would have extension == filename,
			// so we account for this possibility now
			if (extension == filename) {
				extension = '';
			} else {
				// if there is an extension, we convert to lower case
				// (N.B. this conversion will not effect the value of the extension
				// on the file upload.)
				extension = extension.toLowerCase();
			}
			
			//alert(extension);
						
			var fileExtension = ['fasta', 'fna', 'fa', 'fastq', 'gz'];
				
			//alert (fileExtension[0]);
			
			if (extension != fileExtension[0] && extension != fileExtension[1] && extension != fileExtension[2] && extension != fileExtension[3] && extension != fileExtension[4]) {
				alert("Invalid extension! Only fastq, fasta, fna, fa, and gz formats are allowed.");
				return false; 
			}
			
			var hasSpace = AssemblyPrefix.indexOf(' ')>=0;			
			if (hasSpace == true){
				alert("Please check your fields for spaces.");
				return false;
			}		

			if(checkValue == "FullWorkflow"){
				$("#s2").click();			
				document.getElementById('s1').style.pointerEvents = 'auto';	
				
			}
			
			//alert(AssemblyPrefix);
			$('#AssemblyPrefix').attr("placeholder",AssemblyPrefix);
			$('#OverviewAssemblyPrefix').attr("placeholder",AssemblyPrefix);							

							
		}else if(($('#SequenceFile').val() == "" && checkValue == "FullWorkflow")){
			// no file selected
			alert("no file selected");
		}else if (checkValue == "AssemblyFree"){
			$("#s3").click();			
			document.getElementById('s1').style.pointerEvents = 'auto';						
		}		
    }else if (currentStep==3){ 		
		
		var hasSpace = $('#AssemblyPrefix').val().indexOf(' ')>=0;
		if (hasSpace == true){
			alert("Please check your fields for spaces.");
			return false;
		}	
		
		if(checkValue == "FullWorkflow"){
			
			//var radios = document.getElementsByName('radioTech');
			
			document.getElementById('AssemblyPrefix').focus();
			//document.getElementById('field').removeAttribute("required");
			
			if ($("#AssemblyPrefix").val() == ""){ 
				alert("Oops! Something went wrong! <assembly-prefix>");
				//document.getElementById('AssemblyPrefix').focus();
				return false;	
			}else if($("#genomeSize").val() == ""){
				//alert("genomeSize=<number>[g|m|k]");
				alert("genomeSize=<number>[m]");
				document.getElementById('genomeSize').focus();				
				return false;					
			}else if ($('#genomeSize').val().indexOf("m") == -1) {
				alert('No "m" symbol');
				document.getElementById('genomeSize').focus();
				return false;
			}
			else{
				var prefix = $("#AssemblyPrefix").val();
				$('#OverviewAssemblyPrefix').attr("placeholder",prefix);
				
				var gSize = $("#genomeSize").val();
				$('#OverviewgenomeSize').attr("placeholder",gSize);				

				$("#s3").click();				
			}		
		}
		document.getElementById('s2').style.pointerEvents = 'auto';
		
    }else if (currentStep==4){
		
		var str = $("#evalueParameters").val();
		var regexp = /^[0-9]+([,.][0-9]+)?$/g;
		var chk = regexp.test(str);
		
		if(checkValue == "AssemblyFree"){
			if($('#contigsFile').val() == ""){
				alert("no file selected");
				return false;
			}else if($('#contigsFile').val() != ""){

				// get the file name, possibly with path (depends on browser)
				var filename = $("#contigsFile").val();
				
				// Use a regular expression to trim everything before final dot
				var extension = filename.replace(/^.*\./, '');			
				var contigs = filename.replace(/.*(\/|\\)/, '');
				// trimming 			
				var contigsPrefix = contigs.replace(/(.*)\.(.*?)$/, "$1");

							
				if (extension == filename) {
					extension = '';
				} else {
					extension = extension.toLowerCase();
				}
							
				var fileExtension = ['fasta', 'fna', 'fa'];
					
				//alert (fileExtension[0]);

				var hasSpace = contigsPrefix.indexOf(' ')>=0;
				
				if (extension != fileExtension[0] && extension != fileExtension[1] && extension != fileExtension[2]) {
					alert("Invalid extension! Only fasta, fna, and fa formats are allowed.");
					return false; 
				}else if (hasSpace == true){
					alert("Please check your fields for spaces.");
					return false;
				}else{
					$('#OverviewcontigsFile').attr("placeholder",contigs);					
				}				
			}
		}
		
		if($("#evalueParameters").val() == ""){
			alert("Oops! Something went wrong!"); 
			document.getElementById('evalueParameters').focus();
			return false;	
		}else if($("#evalueParameters").val() === "0"){
			alert("BLAST query/options error: expect value or cutoff score must be greater than zero");
			return false;
		}else if(chk == false){
			alert("Numeric values only allowed (with decimal point).");
			return false;
		}else{
			
			var ncbi_evalue = $("#evalueParameters").val();
			$('#OverviewevalueParameters').attr("placeholder",ncbi_evalue);
			$("#s4").click();
		}
		document.getElementById('s3').style.pointerEvents = 'auto';
		
    }else if (currentStep==5){
		
		//var vfdbThresholdID = $("#SelectVirulenceThresholdIdentity").val();

		var str = $("#VFDBevalueParameters").val();
		var regexp = /^[0-9]+([,.][0-9]+)?$/g;
		var chk = regexp.test(str);

		if($("#VFDBevalueParameters").val() == ""){
			alert("Oops! Something went wrong!"); 
			document.getElementById('VFDBevalueParameters').focus();
			return false;	
		}else if($("#VFDBevalueParameters").val() === "0"){
			alert("BLAST query/options error: expect value or cutoff score must be greater than zero");
			return false;
		}else if(chk == false){
			alert("Numeric values only allowed (with decimal point).");
			return false;
		}else{
			
			var vfdb_evalue = $("#VFDBevalueParameters").val();
			$('#OverviewVFDBevalueParameters').attr("placeholder",vfdb_evalue);
			$("#s5").click();
		}
		document.getElementById('s4').style.pointerEvents = 'auto';
		
    }else if (currentStep==6){
		
		var str = $("#CARDevalueParameters").val();
		var regexp = /^[0-9]+([,.][0-9]+)?$/g;
		var chk = regexp.test(str);		
		
		if($("#CARDevalueParameters").val() == ""){
			alert("Oops! Something went wrong!"); 
			document.getElementById('CARDevalueParameters').focus();
			return false;	
		}else if($("#CARDevalueParameters").val() === "0"){
			alert("BLAST query/options error: expect value or cutoff score must be greater than zero");
			return false;
		}else if(chk == false){
			alert("Numeric values only allowed (with decimal point).");
			return false;
		}else{
			
			var RadeoButtonStatusCheck = $("input[name='radioTech']:checked").val();  // 1 or 0

			if(RadeoButtonStatusCheck == 1){
				$('#OverviewradioTech').attr("placeholder","-pacbio-raw");				
/*				radiobtn = document.getElementById("Overviewpacbio-raw");
				radiobtn.checked = 1;*/			
			}else {
				$('#OverviewradioTech').attr("placeholder","-nanopore-raw");
/*				radiobtn = document.getElementById("Overviewnanopore-raw");
				radiobtn.checked = 0;*/				
			}
			
			var card_evalue = $("#CARDevalueParameters").val();
			$('#OverviewCARDevalueParameters').attr("placeholder",card_evalue);
			$("#s6").click();
		}		
		document.getElementById('s5').style.pointerEvents = 'auto';	

		if(checkValue == "AssemblyFree"){
			document.getElementById('switch1').style.display = "none";
			document.getElementById('switch2').style.display = "block";
			document.getElementById('switch3').style.display = "none";
		}else{
			document.getElementById('switch1').style.display = "block";
			document.getElementById('switch2').style.display = "none";	
			document.getElementById('switch3').style.display = "block";
		}
		
		//var prefix = $("#AssemblyPrefix").val();
		
		$("#OverviewSubmitForm").submit(function(){
					
			var file_data = document.getElementById("SequenceFile").files[0];
			var contig_data = document.getElementById("contigsFile").files[0];
			
			var Tech = $('input[name="radioTech"]:checked').val();			
			var googleID = document.getElementById("google_id").value;
			var Title = document.getElementById("Title").value;
			var AssemblyPrefix = document.getElementById("AssemblyPrefix").value;
			var genomeSize = document.getElementById("genomeSize").value;
			var Email = document.getElementById("OverviewEmail").value;
			var googleName = document.getElementById("google_name").value;
			
			var ncbi_cutoff = document.getElementById("evalueParameters").value;
			
			var vfdb_cutoff = document.getElementById("VFDBevalueParameters").value;
			var vfdb_threshold_id = document.getElementById("SelectVirulenceThresholdIdentity").value;
			var vfdb_min_length = document.getElementById("SelectVirulenceMinimumLength").value;			

			var card_cutoff = document.getElementById("CARDevalueParameters").value;
			var card_threshold_id = document.getElementById("SelectResistomeThresholdIdentity").value;
			var card_min_length = document.getElementById("SelectResistomeMinimumLength").value;
						
			var form_data = new FormData();                
			form_data.append('SequenceFile', file_data);
			form_data.append('contigsFile', contig_data);
			form_data.append('Tech', Tech);
			form_data.append('googleID', googleID);
			form_data.append('Title', Title);
			form_data.append('AssemblyPrefix', AssemblyPrefix);
			form_data.append('genomeSize', genomeSize);
			form_data.append('Email', Email);
			form_data.append('googleName', googleName);
			form_data.append('ncbi_cutoff', ncbi_cutoff);
			form_data.append('vfdb_cutoff', vfdb_cutoff);
			form_data.append('vfdb_threshold_id', vfdb_threshold_id);
			form_data.append('vfdb_min_length', vfdb_min_length);
			form_data.append('card_cutoff', card_cutoff);
			form_data.append('card_threshold_id', card_threshold_id);
			form_data.append('card_min_length', card_min_length);	
			form_data.append('checkValue', checkValue);						
			
			var xhr = new XMLHttpRequest();
			xhr.upload.addEventListener("progress", progressHandler, false);
			xhr.addEventListener("load", completeHandler, false);
			xhr.addEventListener("error", errorHandler, false);
			xhr.addEventListener("abort", abortHandler, false);
			xhr.open("POST", "upload.php");
			xhr.send(form_data);
			
		/*
		   var form_parameter = {
			   Title: $('#Title').val(),
			   Email: $('#OverviewEmail').val(),
			   AssemblyPrefix: $("#AssemblyPrefix").val(),
			   maxMemory: $('#OverviewmaxMemory').val(),
			   maxThreads: $('#OverviewmaxThreads').val(),
			   genomeSize: $('#OverviewgenomeSize').val(),
			   googleID: $('#google_id').val(),
			   googleName: $('#google_name').val()
		   };
		   	   
		   $.ajax({
			  type: "POST",
			  url: "submit.php",
			  dataType: 'text',
			  data: form_parameter,			  
			  success: function(data, textStatus, jqXHR) {
				console.log(data);
				//top.location.href="jobs.php";
              },
			  error: function (jqXHR, textStatus, errorThrown){
				 alert(errorThrown);
			  }
		   });
		*/
		   //return false;
		   
		});
    }
}

function progressHandler(event){
	document.getElementById("loaded_n_total").innerHTML = "Uploaded "+event.loaded+" bytes of "+event.total;
	var percent = (event.loaded / event.total) * 100;
	document.getElementById("progressBar").value = Math.round(percent);
	document.getElementById("status").innerHTML = Math.round(percent)+"% uploaded... please wait";
}
function completeHandler(event){
	document.getElementById("status").innerHTML = event.target.responseText;
	document.getElementById("progressBar").value = 0;
}
function errorHandler(event){
	document.getElementById("status").innerHTML = "Upload Failed";
}
function abortHandler(event){
	document.getElementById("status").innerHTML = "Upload Aborted";
}