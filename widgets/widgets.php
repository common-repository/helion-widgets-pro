<?php

function helion_widget_get_option($widget, $option) {
	return get_option("helion_widget_$widget_$option");
}

function helion_widget_update_option($widget, $option, $value) {
	return update_option("helion_widget_$widget_$option", $value);
}

add_action('wp_print_styles', 'helion_widget_styles');

function helion_widget_styles() {
	wp_register_style('helion-widgets', plugins_url("css/widgets.css", dirname(__FILE__)));
	wp_enqueue_style('helion-widgets');
}

include_once("random-book.php");
include_once("book-of-the-day.php");
include_once("bestsellers.php");
include_once("single-book.php");
include_once("search.php");
include_once("kategorie.php");
include_once("serie.php");
?>