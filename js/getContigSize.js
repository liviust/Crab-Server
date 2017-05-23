function GetContigSize() {
	var sizeLimit = 10;
	var fi = document.getElementById('contigsFile');
	if (fi.files.length > 0) {
		for (var i = 0; i <= fi.files.length - 1; i++) {
			var fsize = fi.files.item(i).size;
			document.getElementById('fp').innerHTML = "";
			var sizeInKb = (fsize / 1024 / 1024);
			var sizeInMb = Math.ceil(sizeInKb*100)/100;
			document.getElementById('op').innerHTML = document.getElementById('fp').innerHTML + '<br /> ' + 'File Size: <b>' + sizeInMb + '</b> MB';			
			
			if(sizeInMb > sizeLimit){ 			
				document.getElementById("op").style.color = "red";
   				alert('Your file size is: ' + sizeInMb + " MB, and it is too large to upload! Please try to upload smaller file (10 MB or less).");            
				return false;
			}
			else{
				document.getElementById("op").style.color = "black";
			}
		}
	}
}