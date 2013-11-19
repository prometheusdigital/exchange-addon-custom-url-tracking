jQuery( document ).ready( function( $ ) {
	$('.existing-url-method').on('change',function(element) {
		if ($(this).is(':checked')) {
			$(this).parent().siblings('.existing-url-builder-layout-div').addClass('hide-if-js');
		}else{
			$(this).parent().siblings('.existing-url-builder-layout-div').removeClass('hide-if-js');
		}
	}).trigger('change');
});
