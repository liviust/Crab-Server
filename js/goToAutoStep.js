jQuery(document).ready(function($){

	$('#btnAutoFullWorkflow').on('click', function() {
		
		var checkValue = $("#AnalysisTypeSelect").val();

		$("#AutoFullWorkflowSubmitForm").submit(function(e){

			// Check if third radio is selected (by name and value)
/*			if ($('input[name=autoradioTech][value=1]:checked').length == 1){
				alert("-pacbio-raw");
			}*/
			
			// Check if something in radio group is choosen
			if ($('input[name=autoradioTech]:checked').length <= 0){
				alert("Please select your Technology.");
				return false;
			}else if($('#fileToUploadAutoFull').val() != "" && checkValue == "AutoFullWorkflow"){
				
					// get the file name, possibly with path (depends on browser)
					var filename = $("#fileToUploadAutoFull").val();
					
					// Use a regular expression to trim everything before final dot
					var extension = filename.replace(/^.*\./, '');			
					var rawreads = filename.replace(/.*(\/|\\)/, '');
					// trimming 			
					var AssemblyPrefix = rawreads.replace(/(.*)\.(.*?)$/, "$1");
					
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
					//alert(AssemblyPrefix);
								
					var fileExtension = ['fasta', 'fna', 'fa', 'fastq'];
						
					//alert (fileExtension[0]);
					
					if (extension != fileExtension[0] && extension != fileExtension[1] && extension != fileExtension[2] && extension != fileExtension[3]) {
						alert("Invalid extension! Only fastq, fasta, fna, and fa formats are allowed.");
						return false; 
					}
					
					var hasSpace = AssemblyPrefix.indexOf(' ')>=0;			
					if (hasSpace == true){
						alert("Please check your fields for spaces.");
						return false;
					}
					
					//File uploaded successfully
					//alert("File uploaded successfully!");
					
					var file_data = document.getElementById("fileToUploadAutoFull").files[0];
					//var contig_data = document.getElementById("contigsFile").files[0];
					
					var Tech = $('input[name="autoradioTech"]:checked').val();			
					var googleID = document.getElementById("google_id").value;
					var Title = AssemblyPrefix;
					var AssemblyPrefix = AssemblyPrefix;
					var genomeSize = "5.5m";
					var Email = document.getElementById("OverviewEmail").value;
					var googleName = document.getElementById("google_name").value;
					
					var ncbi_cutoff = 0.00001;
					var vfdb_cutoff = 0.00001;
					var card_cutoff = 0.00001;
					
					var vfdb_threshold_id = "50%";
					var vfdb_min_length = "60%";
					var card_threshold_id = "50%";
					var card_min_length = "60%";
								
					var form_data = new FormData();                
					form_data.append('SequenceFile', file_data);
					//form_data.append('contigsFile', contig_data);
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
			
			}else if($('#fileToUploadAutoFull').val() == "" && checkValue == "AutoFullWorkflow"){
				alert("no file selected");
				return false;
			}		
			
		});
	});

	$('#btnAutoAssemblyFree').on('click', function() {});

	$("#fileToUploadAutoFull").change(function() {	
		var sizeLimit = 2;
		var fi = document.getElementById('fileToUploadAutoFull');
		if (fi.files.length > 0) {
			for (var i = 0; i <= fi.files.length - 1; i++) {
				var fsize = fi.files.item(i).size;
				document.getElementById('msgAutoFull').innerHTML = "";
				var sizeInKb = (fsize / 1024 / 1024);
				var sizeInMb = (fsize / 1024 / 1024 / 1024);
				var sizeInGb = Math.ceil(sizeInMb*100)/100;
				document.getElementById('msgAutoFull').innerHTML = document.getElementById('msgAutoFull').innerHTML + '<br /> ' + 'File Size: <b>' + sizeInGb + '</b> GB';			
				
				if(sizeInGb > sizeLimit){ 			
					document.getElementById("msgAutoFull").style.color = "red";
					alert('Your file size is: ' + sizeInGb + " GB, and it is too large to upload! Please try to upload smaller file (2 GB or less).");            
					return false;
				}
				else{
					document.getElementById("msgAutoFull").style.color = "black";
				}
			}
		}		
	});

	$("#fileToUploadAutoFree").change(function() {	
		var sizeLimit = 10;
		var fi = document.getElementById('fileToUploadAutoFree');
		if (fi.files.length > 0) {
			for (var i = 0; i <= fi.files.length - 1; i++) {
				var fsize = fi.files.item(i).size;
				document.getElementById('msgAutoFree').innerHTML = "";
				var sizeInKb = (fsize / 1024 / 1024);
				var sizeInMb = Math.ceil(sizeInKb*100)/100;
				document.getElementById('msgAutoFree').innerHTML = document.getElementById('msgAutoFree').innerHTML + '<br /> ' + 'File Size: <b>' + sizeInMb + '</b> MB';			
				
				if(sizeInMb > sizeLimit){ 			
					document.getElementById("msgAutoFree").style.color = "red";
					alert('Your file size is: ' + sizeInMb + " MB, and it is too large to upload! Please try to upload smaller file (10 MB or less).");            
					return false;
				}
				else{
					document.getElementById("msgAutoFree").style.color = "black";
				}
			}
		}	
	});

	function progressHandler(event){
		document.getElementById("loaded_n_total_AFW").innerHTML = "Uploaded "+event.loaded+" bytes of "+event.total;
		var percent = (event.loaded / event.total) * 100;
		document.getElementById("progressBar_AFW").value = Math.round(percent);
		document.getElementById("status_AFW").innerHTML = Math.round(percent)+"% uploaded... please wait";
	}
	function completeHandler(event){
		document.getElementById("status_AFW").innerHTML = event.target.responseText;
		document.getElementById("progressBar_AFW").value = 0;
	}
	function errorHandler(event){
		document.getElementById("status_AFW").innerHTML = "Upload Failed";
	}
	function abortHandler(event){
		document.getElementById("status_AFW").innerHTML = "Upload Aborted";
	}	

    $('#AnalysisTypeSelect').change(function(){
		var opt = $(this).val();
		if(opt == 'FullWorkflow'){
			
			$('#initial_step').show();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').hide();
			$('#fileHelp').show();
			
		}else if(opt == 'AssemblyFree'){
			
			$('#initial_step').show();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').hide();
			
		}else if(opt == 'AutoFullWorkflow'){
		
			$('#initial_step').hide();
			$('#btnAutoFullWorkflow').show();
			$('#btnAutoAssemblyFree').hide();
			$('#SequenceFile').prop('disabled', true);
			$('#contigsFile').prop('disabled', true);
			
		}else if(opt == 'AutoAssemblyFree'){
			
			$('#initial_step').hide();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').show();
			$('#SequenceFile').prop('disabled', true);
			$('#contigsFile').prop('disabled', true);
						
		}
    });
});