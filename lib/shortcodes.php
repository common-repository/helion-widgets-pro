<?php

/**
 * Tu są shortcody:
 * [helion_ksiazka]
 * [helion_link]
 * [helion_wyniki_wyszukiwania]
 */

add_shortcode( 'helion_ksiazka', 'helion_ksiazka' );
add_shortcode( 'helion_link', 'helion_link' );
add_shortcode( 'helion_wyniki_wyszukiwania', 'helion_wyniki_wyszukiwania' );

add_action('wp_print_styles', 'helion_shortcode_styles');

function helion_shortcode_styles() {
	wp_register_style('helion-shortcodes', plugins_url("css/shortcodes.css", dirname(__FILE__)));
	wp_enqueue_style('helion-shortcodes');
}

/**
 * Parametry:
 * @param ksiegarnia
 * @param ident
 * @param okladka rozmiar okładki
 * @param width szerokość (bez px)
 * @param float atrybut float (left|right)
 *
 */
function helion_ksiazka($atts) {
	
	extract( shortcode_atts( array(
		'ksiegarnia' => 'helion',
		'ident' => 'markwy',
		'okladka' => '181x236',
		'width' => '',
		'float' => 'left',
                'opis' => '0',
                'substring' => '100',
	), $atts ) );
	
	switch($float) {
		case 'left':
		case 'lewy':
		case 'lewo':
		case 'l':
			$float = "alignleft";
			break;
		case 'right':
		case 'prawo':
		case 'prawy':
		case 'r':
		case 'p':
			$float = "alignright";
			break;
		default:
			$float = "";
			break;
	}
	
	$wymiary = explode("x", $okladka);
	
	if(!$width)
		 // albo rozmiar okładki, albo 200, które większe
		$width = max($wymiary[0], 200);

	$book = helion_get_book_info($ksiegarnia, $ident);
	if($book) {
		$link = helion_get_link($ksiegarnia, $ident, $cyfra, true);
		$okladka = helion_get_cover($ksiegarnia, $ident, $okladka);
		$tytul = ($okladka['alt']) ? $okladka['alt'] : $book['tytul'];
		$autor = $book['autor'];

		$cont = '<div class="helion-ksiazka ' . "helion-ksiazka-" . $ksiegarnia . "-" . $ident . ' ' . $float . '" style="width: ' . $width . 'px">';
		$cont .= '<div class="helion_okladka" style="width: ' . $wymiary[0] . 'px;">';
		$cont .= '<p><a href="' . $link . '" target="_blank" title="' . $tytul . '" rel="nofollow">';
		$cont .= '<img src="' . $okladka['src'] . '" alt="' . $okladka['alt'] . '" />';
		$cont .= '</a></p>';
		$cont .= '</div>';
		$cont .= '<div class="helion_meta">';
		$cont .= '<p class="helion_tytul"><a href="' . $link . '" target="_blank" rel="nofollow" title="' . $tytul . '">' . $tytul . '</a></p>';
		$cont .= '<p class="helion_autor"><span>Autor:</span><br /><span>' . $autor . '</span></p>';
		$cont .= '<p class="helion_isbn"><span>ISBN:</span><br /><span>' . $book['isbn'] . '</span></p>';
                if ($book['format']) {
                    $cont .= '<p class="helion_format"><span>Format:</span><br /><span>' . $book['format'] . '</span></p>';
                }
                if ($book['liczbastron']) {
                    $cont .= '<p class="helion_format"><span>Liczba stron:</span><br /><span>' . $book['liczbastron'] . '</span></p>';
                }
                if ($book['datawydania']) {
                    $cont .= '<p class="helion_data"><span>Data wydania:</span><br /><span>' . $book['datawydania'] . '</span></p>';
                }
                if($opis == '1' && (int)$substring > 0){
                    $cont .= '<p class="helion_opis"><span>Opis:</span><br /><span>' . substr(strip_tags($book['opis']), 0, $substring) . '</span></p>';
                }
		$cont .= '<p class="helion_cena"><span>Cena:</span><br /><span>' . $book['cenadetaliczna'] . 'zł</span></p>';
		$cont .= '<div class="helion-box">';
		$cont .= '<a href="' . $link . '" rel="nofollow" title="Kup teraz">kup teraz</a>';
		$cont .= '</div>';
		$cont .= '</div>';
		$cont .= '</div>';
		
		return $cont;
	} else {
		return "Wystąpił błąd. Nie znaleziono takiej ksiązki.";
	}
}

/**
 * @param ksiegarnia
 * @param ident
 * @param cyfra
 */
function helion_link($atts, $content = null) {
	extract( shortcode_atts( array(
		'ksiegarnia' => 'helion',
		'ident' => 'markwy',
		'cyfra' => '',
	), $atts ) );
	
	$book = helion_get_book_info($ksiegarnia, $ident);
	if($book) {
		$link = helion_get_link($ksiegarnia, $ident, $cyfra);
		
		if($content) {
			$cont = '<a href="' . $link . '" target="_blank" title="' . $book['tytul'] . '" rel="nofollow">' . $content . '</a>';
		} else {
			$cont = '<a href="' . $link . '" target="_blank" title="' . $book['tytul'] . '" rel="nofollow">' . $book['tytul'] . '</a>';
		}
		
		return $cont;
	} else {
		return "Wystąpił błąd. Nie znaleziono takiej książki.";
	}
}

function helion_wyniki_wyszukiwania() {

	$slug = get_option("helion_wyszukiwarka_slug");
	
	if($_REQUEST['helion_wyszukiwarka']) {
            
		$template = '<div class="helion_wyszukiwarka_wyniki">';
		
		$template .= stripslashes(get_option("helion_wyszukiwarka_template"));
		
                # jesli sa wylistowane wyniki, to przy 'helion_wyszukiwarka' podany jest ident
                if(!$_REQUEST['helion_serp']){
                    
                    # walidacja id pozycji
                    if(h_validate_ident($_REQUEST['helion_wyszukiwarka'])){

                        $wynik = helion_get_book_by_ident($_REQUEST['helion_wyszukiwarka']);
                        
                    }
                    
                }else{
		
                    $wynik = helion_wyszukiwarka($_REQUEST['helion_wyszukiwarka']);
                    
                }
		
		if(!$_REQUEST['helion_serp'] && $wynik) {

			$template = helion_parse_template($template, $wynik[0]);
			
			$template .= '</div>';
			
			return $template;
			
		} else if($_REQUEST['helion_serp'] && $wynik) {
			
			$dup_idents = array();
			
			$lista_wynikow = '<h3>Wyszukano frazę: <em>&ldquo;' . strip_tags($_REQUEST['helion_wyszukiwarka']) . '&rdquo;</em></h3>';
			
			$lista_wynikow .= '<ol class="helion_serp">';
			
			foreach($wynik as $w) {
				if(!in_array($w['ident'], $dup_idents)) {
					$lista_wynikow .= '<li><a href="' . $slug . '/?helion_wyszukiwarka=' . $w['ident'] . '" rel="nofollow">' . $w['autor'] . " - " . $w['tytul'] . '</a></li>';
					$dup_idents[] = $w['ident'];
				}
			}
			
			$lista_wynikow .= '</ol>';
			
			return $lista_wynikow;
			
		} else {
		
			$dup_idents = array();
			
			$output = "<p><strong>Nie znaleziono żadnej książki pasującej do zapytania <em>" . strip_tags($_REQUEST['helion_wyszukiwarka']) . "</em>.</strong></p>";
			$output .= "<p>Być może zainteresuje cię jedna z poniższych pozycji:</p>";
			
			$losowe = helion_losowe_ksiazki();
			
			$output .= '<ol class="helion_serp">';
			
			foreach($losowe as $l) {
				if(!in_array($l['ident'], $dup_idents)) {
					$output .= '<li><a href="' . $slug . '/?helion_wyszukiwarka=' . $l['ident'] . '" rel="nofollow">' . $l['autor'] . " - " . $l['tytul'] . '</a></li>';
					$dup_idents[] = $l['ident'];
				}
			}
			
			$output .= '</ol>';
			$output .= '<p>Możesz także zmienić treść zapytania i poszukać książek w oparciu o inne słowa kluczowe.</p>';
			
			return $output;
		}
	} else {
	
		$dup_idents = array();
		
		$output = "<p><strong>Nie znaleziono żadnej książki pasującej do zapytania <em>" . strip_tags($_REQUEST['helion_wyszukiwarka']) . "</em>.</strong></p>";
		$output .= "<p>Być może zainteresuje cię jedna z poniższych pozycji:</p>";
		
		$losowe = helion_losowe_ksiazki();
		
		$output .= '<ol class="helion_serp">';
		
		foreach($losowe as $l) {
			if(!in_array($l['ident'], $dup_idents)) {
				$output .= '<li><a href="' . $slug . '/?helion_wyszukiwarka=' . $l['ident'] . '" rel="nofollow">' . $l['autor'] . " - " . $l['tytul'] . '</a></li>';
				$dup_idents[] = $l['ident'];
			}
		}
		
		$output .= '</ol>';
		$output .= '<p>Możesz także zmienić treść zapytania i poszukać książek w oparciu o inne słowa kluczowe.</p>';
		
		return $output;
	}
}

?>