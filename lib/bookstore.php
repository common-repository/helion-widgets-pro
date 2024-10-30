<?php

add_action('wp_print_styles', 'helion_ksiegarnia_styl');

function helion_ksiegarnia_styl() {
	wp_register_style('helion-ksiegarnia', plugins_url("css/ksiegarnia.css", dirname(__FILE__)));
	wp_enqueue_style('helion-ksiegarnia');
}

add_shortcode('helion_ksiegarnia', 'helion_bookstore');

function helion_bookstore($atts) {

	global $wpdb;
	
	if(helion_bookstore_available(get_option("helion_bookstore_ksiegarnia"))) {
		$template = '<div class="helion_ksiegarnia">';

		switch($_REQUEST['helion_bookstore']) {
			case 'category':
                                if(get_option("helion_bookstore_template_category") == NULL)
                                    $template .= "%kategoria% %paginacja%"; // jesli opcja null ustaw domyslnie
                                else
                                    $template .= stripslashes(get_option("helion_bookstore_template_category"));
                                
				$template .= '</div>';
				$template = helion_parse_category_template($template, $_REQUEST['id'], (isset($_REQUEST['helion_page'])) ? $_REQUEST['helion_page'] : 0);
				break;
			case 'book':
				$dane = helion_get_book_info(h_validate_bookstore($_REQUEST['ksiegarnia']), h_validate_ident($_REQUEST['ident']));
				if(empty($dane)) {
					$template .= "<p>Ta książka nie jest w tej chwili w sprzedaży.</p>";
				} else {
					$template .= stripslashes(get_option("helion_bookstore_template_book"));
				}
				$template .= '</div>';
				$template = helion_parse_template($template, $dane);
				break;
                        case 'serie':
                                if(get_option("helion_bookstore_template_serie") == NULL)
                                    $template .= "%seria% %paginacja%"; // jesli opcja null ustaw domyslnie
                                else
                                    $template .= stripslashes(get_option("helion_bookstore_template_serie"));
                                
				$template .= '</div>';
				$template = helion_parse_serie_template($template, $_REQUEST['id'], $_REQUEST['helion_page']);
				break;
			default:
				$template .= stripslashes(get_option("helion_bookstore_template_main"));
				$template .= '</div>';
				$template = helion_parse_bookstore_template($template);
				break;
		}
		
		return $template;
		
	} else {
		return "<p>Wystąpił błąd. Moduł księgarni nie może odnaleźć danych o książkach z tej księgarni. Sprawdź, czy wybrana przez ciebie księgarnia nie została całkowicie wyłączona w menu Helion->Helion.</p>";
	}
}

?>