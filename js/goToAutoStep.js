jQuery(document).ready(function($){

	$('#btnAutoFullWorkflow').on('click', function() {});
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