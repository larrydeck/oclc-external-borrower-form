$.fn.clear_address_values = function () {
	 $("#address1").val("");
	 $("#address2").val("");
	 $("#city").val("");
	 $("#postal_code").val("");
	console.log("clear select opts");

};
$.fn.clear_home_inst_values = function () {
	console.log("clear select opts");
	 $("#home_institution").val("");

};
$.fn.clear_spouse_name_values = function () {
	console.log("clear select opts");
	 $("#spouse_name").val("");

};

$.fn.update_fields_info = function ($value) {
	switch($value) {
	 case 'value4':
	    $('#spouseDivCheck').remove('no-display').fadeIn('slow');
	    $('#homeInstDivCheck').add('no-display').hide();
	    $('#addressDivCheck').add('no-display').hide();
	    $.fn.clear_address_values();
	    $.fn.clear_home_inst_values();
	    break;
	 case 'value1':
	    $('#spouseDivCheck').add('no-display').hide();
	    $('#addressDivCheck').add('no-display').hide();
	    $('#homeInstDivCheck').remove('no-display').fadeIn('slow');
	    $.fn.clear_address_values();
	    $.fn.clear_spouse_name_values();
	    break;
	 default:
	    $('#homeInstDivCheck').add('no-display').hide();
	    $('#addressDivCheck').add('no-display').hide();
	    $('#spouseDivCheck').add('no-display').hide();
	}

};

$(document).ready(function () {
    
    // Get the curr val
    $curr_val = $('select[name="borrower_cat"]').val();
    $.fn.update_fields_info($curr_val);

    $('select[name="borrower_cat"]').change(function () {
	$selected_val = $(this).val();
        $.fn.update_fields_info($selected_val);
    });
});
