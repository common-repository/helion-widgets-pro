<?php
/*
	Plugin Name: Helion Widgets Pro
	Plugin URI: https://wordpress.org/extend/plugins/helion-widgets-pro/
	Description: Widgety i Księgarnia dla uczestników Programu Partnerskiego GW Helion.
	Version: 1.5.7  
	Author: Paweł Pela, Marek Dzimiera
	License: GPL2
*/

include_once("lib/lib.php");
include_once("widgets/widgets.php");

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

register_activation_hook(__FILE__, "helion_install");
add_action("update_plugin_complete_actions", "helion_install");

function helion_install() {
	global $wpdb;
	
	$bookstores = array("helion", "sensus", "onepress", "septem", "ebookpoint", "bezdroza", "videopoint");
	
	foreach($bookstores as $bookstore) {
		$table_name = $wpdb->prefix . "helion_books_" . $bookstore;
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		  
			$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				ident text NULL,
				isbn text NULL,
				tytul text NULL,
				tytul_orig text NULL,
				link text NULL,
				autor text NULL,
				tlumacz text NULL,
				cena text NULL,
				cenadetaliczna text NULL,
				znizka text NULL,
				marka text NULL,
				nazadanie bool NULL,
				format text NULL,
				liczbastron text NULL,
				oprawa text NULL,
				nosnik text NULL,
				datawydania text NULL,
				issueurl text NULL,
				online text NULL,
				bestseller bool NULL,
				nowosc bool NULL,
				videos text NULL,
				powiazane text NULL,
				opis text NULL,
				kategorie text NULL,
				seriewydawnicze text NULL,
				serietematyczne text NULL,
				UNIQUE KEY id (id)
			) CHARACTER SET latin2;";

			dbDelta($sql);
		}
		
	}
	
	$widgets = array("random", "bookstore");
	
	foreach($widgets as $widget) {
		$table_name = $wpdb->prefix . "helion_widget_" . $widget;
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		  
			$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				typ text NOT NULL,
				obiekt text NOT NULL,
				UNIQUE KEY id (id)
			) CHARACTER SET latin2;";

			dbDelta($sql);
		}
		
	}
	
	$table_name = $wpdb->prefix . "helion_bestsellers";
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
	  
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			bookstore text NOT NULL,
			ident text NOT NULL,
			UNIQUE KEY id (id)
		) CHARACTER SET latin2;";

		dbDelta($sql);
	}
	
	@helion_setup_cache();

}

function helion_activation() {
	wp_schedule_event(time() + 12 * 60 * 60, 'daily', 'helion_download_xmls');
	wp_schedule_event(time() + 12 * 60 * 60 + 600, 'daily', 'helion_import_xmls');
	
	wp_schedule_event(time() + 12 * 60 * 60 + 3600, 'daily', 'helion_download_bestsellers');
	wp_schedule_event(time() + 12 * 60 * 60 + 600 + 3600, 'daily', 'helion_import_bestsellers');
	
	wp_schedule_event(time() + 61800, 'daily', 'helion_cron_cache_size');
	
	wp_schedule_single_event(time() + 30 * 24 * 60 * 60, 'helion_reset_cache');
	
	@helion_clear_bestsellers();
	
	$bs = array("helion","onepress", "sensus", "septem", "ebookpoint", "bezdroza", "videopoint");
	foreach($bs as $b) {
		@helion_clear_books_database($b);
		@helion_xml_download($b, true);
		@helion_xml_import($b, true);
	}
	
	if(!get_option("helion_wyszukiwarka_template")) {
	
		$template = '<div class="helion_ksiazka"><div style="float: left; width: 40%;"><a href="%dokoszyka%" rel="nofollow">%okladka181x236%</a></div><div style="float: right; width: 58%;"><h2><a href="%dokoszyka%" rel="nofollow">%tytul%</a></h2><p>autor: %autor%</p><p>format: %format%</p><p>data wydania: %datawydania%</p><div class="helion-box"><div class="helion-cena">%cena% zł</div><a href="%dokoszyka%" rel="nofollow">kup teraz</a></div></div><div style="clear: both;"></div><hr/><div>%opis%</div><div class="helion-box"><div class="helion-cena">%cena% zł</div><a href="%dokoszyka%" rel="nofollow">kup teraz</a></div></div>';
		
		update_option("helion_wyszukiwarka_template", $template);
	}
	
	if(!get_option("helion_bookstore_template_main")) {
	
		$template = '<h2>Nowości</h2>%nowosci%<hr/><h2>Bestsellery</h2>%bestsellery%';
		
		update_option("helion_bookstore_template_main", $template);
	}
		
	update_option("helion_bookstore_template_category", '%kategoria% %paginacja%');
	
	if(!get_option("helion_bookstore_template_book")) {
	
		$template = '<div class="helion_ksiazka"><div style="float: left; width: 40%;"><a href="%dokoszyka%" rel="nofollow">%okladka181x236%</a></div><div style="float: right; width: 58%;"><h2><a href="%dokoszyka%" rel="nofollow">%tytul%</a></h2><p><b>Autor:</b> %autor%</p><p><b>Format:</b> %format%</p><p><b>Data wydania:</b> %datawydania%</p>'
                        . '<div class="helion-box"><div class="helion-cena">%cena% zł</div><a href="%dokoszyka%" rel="nofollow">kup teraz</a></div></div><div style="clear: both;"></div><hr/><div>%opis%</div><div class="helion-box"><div class="helion-cena">%cena% zł</div><a href="%dokoszyka%" rel="nofollow">kup teraz</a></div></div>';
		
		update_option("helion_bookstore_template_book", $template);
	}
}

function helion_deactivation() {
	$timestamp = wp_next_scheduled('helion_download_xmls');
    wp_unschedule_event($timestamp, 'helion_download_xmls');
	$timestamp = wp_next_scheduled('helion_import_xmls');
    wp_unschedule_event($timestamp, 'helion_import_xmls');
	
	$timestamp = wp_next_scheduled('helion_download_bestsellers');
    wp_unschedule_event($timestamp, 'helion_download_bestsellers');
	$timestamp = wp_next_scheduled('helion_import_bestsellers');
    wp_unschedule_event($timestamp, 'helion_import_bestsellers');
	
	$timestamp = wp_next_scheduled('helion_cron_cache_size');
    wp_unschedule_event($timestamp, 'helion_cron_cache_size');
	
	$timestamp = wp_next_scheduled('helion_reset_cache');
    wp_unschedule_event($timestamp, 'helion_reset_cache');
}

register_activation_hook(__FILE__, 'helion_activation');
register_deactivation_hook(__FILE__, 'helion_deactivation');
add_action('helion_download_xmls', 'helion_download_xmls');
add_action('helion_download_bestsellers', 'helion_download_bestsellers');
add_action('helion_import_xmls', 'helion_import_xmls');
add_action('helion_import_bestsellers', 'helion_import_bestsellers');
add_action('helion_cron_cache_size', 'helion_cron_cache_size');
add_action('helion_reset_cache', 'helion_reset_cache');

register_uninstall_hook(__FILE__, "helion_uninstall");

function helion_uninstall() {
	global $wpdb;
	
	$bookstores = array("helion", "sensus", "onepress", "septem", "ebookpoint", "bezdroza", "videopoint");
	
	foreach($bookstores as $bookstore) {
		$xml_table = $wpdb->prefix . "helion_books_" . $bookstore;
		$wpdb->query("DROP TABLE " . $xml_table);
	}
	
	$wpdb->query("DROP TABLE " . $wpdb->prefix . "helion_widget_bookstore");
	$wpdb->query("DROP TABLE " . $wpdb->prefix . "helion_widget_random");
	$wpdb->query("DROP TABLE " . $wpdb->prefix . "helion_bestsellers");
	
	// TODO: usunąć wszystkie opcje
	$helion_options = array(
		"helion_bookstores", "helion_partner_id", "helion_bookstore_template_main",
		"helion_cache_max",
		);
	
	foreach($helion_options as $o) {
		delete_option($o);
	}
	
	helion_clear_cache();
	
	helion_clear_bestsellers();
}

function helion_admin_scripts() {
	wp_enqueue_script('book_selector', plugins_url('/js/book_selector.js', __FILE__), array('jquery'));
}

function helion_scripts() {
	wp_enqueue_script('widget_kategorie', plugins_url('/js/widget_kategorie.js', __FILE__), array('jquery'));
}

add_action("admin_init", "helion_admin_scripts");
add_action("init", "helion_scripts");
add_action('wp_ajax_helion_book_selector', 'helion_book_picker');

?>
