jQuery(document).ready(function( $ ) {
	
	$(".prepay-partial-form").hide();
	$(".prepay-error").hide();

	$(".prepay-partial-toggle").click(function(){

		if($(this).parent().hasClass("open")){
			$(this).next('.prepay-partial-form').fadeOut();
			$(this).parent().addClass("closed");
			$(this).parent().removeClass("open");
			$(this).html('Prepay Custom Amount');
		} else {
			$(this).next('.prepay-partial-form').fadeIn();
			$(this).parent().addClass("open");
			$(this).parent().removeClass("closed");
			$(this).html('Cancel');
		}
	});
	
});

function checkMaxPrepayAmount(input, balance_remaining){

	var current_value = jQuery(input).val();

	if (current_value > balance_remaining){
		jQuery(".prepay-error").show();
		jQuery(".prepay-error").html('Enter amount less than or equal to your remaining balance ($' + balance_remaining + ')');
		jQuery(input).val('');
	} else if (! jQuery.isNumeric(current_value)){
		jQuery(".prepay-error").html('Please enter numeric characters only');
		jQuery(".prepay-error").show();
		jQuery(input).val('');
	} else {
		jQuery(".prepay-error").hide();
	}

}