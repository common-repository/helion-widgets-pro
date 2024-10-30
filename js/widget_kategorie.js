jQuery(document).ready(function() {
	jQuery('.helion_widget_kategorie ul.pod').hide();
	
	jQuery('.helion_widget_kategorie ul.nad .n').click(function() {
		jQuery(this).children('.pod').toggle('slow');
	});
});