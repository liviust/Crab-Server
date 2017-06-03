jQuery(document).ready(function($){

	$('#btnAutoFullWorkflow').on('click', function() {
		
	});

	$('#btnAutoAssemblyFree').on('click', function() {

	});
	
    $('#AnalysisTypeSelect').change(function(){
		var opt = $(this).val();
		if(opt == 'FullWorkflow'){
			
			$('#initial_step').show();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').hide();
			
		}else if(opt == 'AssemblyFree'){
			
			$('#initial_step').show();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').hide();
			
		}else if(opt == 'AutoFullWorkflow'){
		
			$('#initial_step').hide();
			$('#btnAutoFullWorkflow').show();
			$('#btnAutoAssemblyFree').hide();			
			
		}else if(opt == 'AutoAssemblyFree'){
			
			$('#initial_step').hide();
			$('#btnAutoFullWorkflow').hide();
			$('#btnAutoAssemblyFree').show();
		}
    });
});