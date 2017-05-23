function GetFileSize() {
	var sizeLimit = 2;
	var fi = document.getElementById('SequenceFile');
	if (fi.files.length > 0) {
		for (var i = 0; i <= fi.files.length - 1; i++) {
			var fsize = fi.files.item(i).size;
			document.getElementById('fp').innerHTML = "";
			var sizeInKb = (fsize / 1024 / 1024);
			var sizeInMb = (fsize / 1024 / 1024 / 1024);
			var sizeInGb = Math.ceil(sizeInMb*100)/100;
			document.getElementById('fp').innerHTML = document.getElementById('fp').innerHTML + '<br /> ' + 'File Size: <b>' + sizeInGb + '</b> GB';			
			
			if(sizeInGb > sizeLimit){ 			
				document.getElementById("fp").style.color = "red";
   				alert('Your file size is: ' + sizeInGb + " GB, and it is too large to upload! Please try to upload smaller file (2 GB or less).");            
				return false;
			}
			else{
				document.getElementById("fp").style.color = "black";
			}
		}
	}
}