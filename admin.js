jQuery(document).ready(function($) {
	$('#cat_temps .ct_template').each(function() {
		if ($(this).val() == '0') {
			$(this).parent().parent().parent().find('.ct_template, .ct_priority').parent().fadeTo(0, 0.3);
		}
	}).change(function() {
		if ($(this).val() == '0') {
			$(this).parent().parent().parent().find('.ct_template, .ct_priority').parent().fadeTo(0, 0.3);
			//$(this).parent().find('input:checkbox').attr('checked', '');
			//$(this).parent().parent().find('.ct_priority').attr('selectedIndex', 0);
		} else {
			$(this).parent().parent().parent().find('.ct_template, .ct_priority').parent().fadeTo(0, 1);
			
		}
	}).hover(function() {
		$(this).parent().parent().parent().find('.ct_template, .ct_priority').parent().fadeTo(0, 1);
	}, function() {
		if ($(this).val() == '0') {
			$(this).parent().parent().parent().find('.ct_template, .ct_priority').parent().fadeTo(0, 0.3);
		}
	});
});