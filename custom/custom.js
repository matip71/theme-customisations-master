jQuery(document).ready(function($){
	// Custom jQuery goes here
	 
	//Code to move account section into the header 
	const templateContent = $('#added-account-icon').prop('content');
	const clone = $(templateContent).children().clone();
	$('.sp-header-last').append(clone);
});
