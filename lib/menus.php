<?php

add_action('admin_menu', 'helion_menu');

function helion_menu() {
	add_menu_page('Helion Widgets and Bookstore', 'Helion', 'manage_options', 'helion_options', 'helion_main_options', $icon_url = plugins_url("images/helion.png", dirname(__FILE__)));
	add_submenu_page('helion_options', 'Helion Widgets and Bookstore - Cache okładek', 'Cache okładek', 'manage_options', 'helion_cache', 'helion_submenu_cache');
	add_submenu_page('helion_options', 'Helion Widgets and Bookstore - Losowa książka', 'Losowa książka', 'manage_options', 'helion_losowa_ksiazka', 'helion_submenu_losowa_ksiazka');
	add_submenu_page('helion_options', 'Helion Widgets and Bookstore - Księgarnia', 'Księgarnia', 'manage_options', 'helion_ksiegarnia', 'helion_submenu_ksiegarnia');
	add_submenu_page('helion_options', 'Helion Widgets and Bookstore - Wyszukiwarka', 'Wyszukiwarka', 'manage_options', 'helion_wyszukiwarka', 'helion_submenu_wyszukiwarka');
	add_submenu_page('helion_options', 'Helion Widgets and Bookstore - Pomoc', 'Pomoc', 'manage_options', 'helion_pomoc', 'helion_submenu_pomoc');
}

function helion_main_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
        
        global $wpdb;
	
	$bookstores = array("helion", "onepress", "sensus", "septem", "ebookpoint", "bezdroza", "videopoint");
	$old_bookstores = get_option("helion_bookstores");
	
	if($_REQUEST['action'] == 'save') {
            
            $helion_partner_id = filter_input(INPUT_POST, "helion_partner_id", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            update_option("helion_partner_id",str_replace(array('"', "'"), "", $helion_partner_id));
		foreach($bookstores as $bookstore) {
                    $ksiegarnia = filter_input(INPUT_POST, "ksiegarnia_" . $bookstore, FILTER_SANITIZE_STRING);
                        if(isset($ksiegarnia) && $ksiegarnia == "on"){
                            // Pobierz bazę na nowo tylko jeśli księgarnia nie była wcześniej wybrana
                            $result = $wpdb->get_row("SELECT COUNT(id) as count FROM " . $wpdb->prefix . "helion_books_" . $bookstore);
                            if($old_bookstores[$bookstore] == 0 || $result->count == 0) {
                                helion_xml_download($bookstore);
                                helion_xml_import($bookstore);
                            }
				
				$new_bookstores[$bookstore] = 1;
			} else {
				if($old_bookstores[$bookstore] == 1) {
                                    helion_clear_books_database($bookstore);
				}
				helion_clear_random_on_disable($bookstore);
				
				$new_bookstores[$bookstore] = 0;
			}
		}
		update_option("helion_bookstores", $new_bookstores);
		
		helion_download_bestsellers();
		helion_import_bestsellers();
	
		?>
		<div id="message" class="updated">
			<p><strong>Zmiany zostały zapisane.</strong></p>
		</div>
		<?php
	}
?>
<div class="wrap">
	<form method="post">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Konfiguracja wtyczki Helion</h2>
	
	<p>Wzbogać swój serwis o ciekawe treści, które przyciągną do Ciebie klientów! Rozwiń
skrzydła w e-biznesie i zacznij dobrze zarabiać. Poszerz swoją ofertę o nowości
oraz bestsellery literatury informatycznej, biznesowej, przewodniki turystyczne,
beletrystykę oraz poradniki psychologiczne. Pamiętaj, książki informatyczne to
najlepiej sprzedające się pozycje w sieci! <strong>Wystarczy, że przystąpisz do jednego
z największych i najlepiej ocenianych programów partnerskich w Polsce. Więcej
informacji znajdziesz na stronie <a href="http://program-partnerski.helion.pl" target="_blank" rel="nofollow">http://program-partnerski.helion.pl</a></strong>.</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="helion_partner_id" title="Identyfikator partnera zdobędziesz po podpisaniu z księgarnią umowy partnerskiej.">Twój identyfikator partnera:</label></th>
			<td><input type="text" name="helion_partner_id" value="<?php echo get_option("helion_partner_id"); ?>" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Księgarnie:</th>
			<td>
				<?php
					$checked = 'checked="checked"';
					
					$current_bookstores = get_option("helion_bookstores");
					foreach($bookstores as $bookstore) {
					?>
						<p><input type="checkbox" name="ksiegarnia_<?php echo $bookstore; ?>" <?php if($current_bookstores[$bookstore]) echo $checked; ?> /> <label for="ksiegarnia_<?php echo $bookstore; ?>">Księgarnia <?php echo ucfirst($bookstore); ?></label></p>
					<?php
					}
				?>
			</td>
		</tr>
	</table>
	
	
	<p>Ze względu na to, że dane na temat książek w każdej z księgarń są pobierane do
lokalnej bazy danych, warto ograniczyć wybór tylko do tych księgarń, z których
naprawdę zamierzasz korzystać.</p>

<p>W razie problemów np. z brakiem danych na temat książek, możesz zresetować bazy odznaczając wybrane księgarnie, zapisując i ponownie je zaznaczając. Spowoduje to pobranie na nowo danych na temat książek.</p>
<?php 
    if(!is_writable(ABSPATH . "/wp-content/helion-cache")) {
        echo '<p class="error">Brak wystarczających uprawnień do utworzenia katalogu <b>"helion-cache"</b>. Nadaj odpowiednie uprawnienia dla katalogu <b>"wp-content"</b></p>';
    }
    if(!extension_loaded("simplexml")){
        echo '<p class="error">Brak zainstalowanego rozszerzenia <b>"simplexml"</b>. Zgłoś prośbę do administratora o dodanie rozszerzenia.</p>';
    }
?>
<?php 
    if($conn = helion_detect_connection_method()){
        if($conn == "none"){
            echo '<p class="error">Brak zainstalowanej funkcji cURL. Zgłoś prośbę do administratora o włączenie jej.</p>';
        }else if($conn == "curl"){

            $cu = curl_init();
            $curl_url = "http://helion.pl/plugins/xml/lista.cgi?pd=1";
            curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($cu, CURLOPT_HEADER, 0);
            curl_setopt($cu, CURLOPT_URL, $curl_url); 
            $out = curl_exec($cu);
            curl_close($cu);
            if(($description = simplexml_load_string($out)) === false){
                echo '<p class="error">Brak możliwości pobrania szczegółów pozycji (simplexml_load_string)</p>';
            }
            
        }else if($conn == "fopen"){
            
        }else{
            $xml = simplexml_load_file("http://helion.pl/plugins/xml/lista.cgi?pd=1");
            if($xml === false){        
               echo '<p class="error">Brak możliwości pobrania książki dnia dla marki helion (simplexml_load_file)</p>';
            }
        }
    }else{
        echo '<p class="error">Brak możliwości wykrycia rodzaju połączenia.</p>';
    }    
?>

	<input type="hidden" name="action" value="save"/>
	<p><input type="submit" class="button-primary" value="<?php _e("Save"); ?>"/></p>
	</form>
</div>
<?php
}

function helion_submenu_cache() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	if($_REQUEST['action'] == 'save') {
		update_option("helion_cache_user", $_REQUEST['helion_cache_user'] * 1024);
		
		helion_reset_cache();
		
		?>
		<div id="message" class="updated">
			<p><strong>Zmiany zostały zapisane.</strong></p>
		</div>
		<?php
	}
	
	$current = helion_get_current_cache_size();
	$suggested = helion_suggested_cache_size();
	$user = get_option("helion_cache_user");
?>
<div class="wrap">
	<?php if(helion_ksiegarnie_wlaczone()) { ?>
	<form method="post">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Ustawienia Cache</h2>
	
	<p>Tutaj możesz ustawić rozmiar przestrzeni dyskowej, jaka zostanie przeznaczona na
przechowywanie okładek książek. Wykorzystanie tego rozwiązania odciąży główny
serwer księgarni Helion i pozwoli na <strong>zwiększenie szybkości pracy Twojej strony</strong>.
Dzięki wykorzystaniu pamięci cache wzrośnie wygoda osób korzystających z Twojej strony do składania zamówień.</p>
	
	<p>Wtyczka zasugeruje Ci na podstawie liczby wybranych przez Ciebie księgarń, jaką
przestrzeń najlepiej przeznaczyć na ten cel, jednak ostateczny wybór należy do
Ciebie.</p>
	
	<p><label for="helion_cache_user"> Ustaw rozmiar pamięci cache:</label> <input name="helion_cache_user" type="text" value="<?php echo $user ? $user / 1024 : "0"; ?>" /> MB.</p>
	
	<p><small>(Aktualnie cache zajmuje: <?php echo round((int) $current / 1024, 2); ?> MB, sugerowany rozmiar to <?php echo (int) $suggested; ?> MB.)</small></p>
	
	<p>Podanie wartości 0 w ustawieniach rozmiaru cache jest równoznaczne z wyłączeniem tej funkcji.</p>
	
	<input type="hidden" name="action" value="save" />
	<p><input type="submit" name="sb" value="Zapisz" class="button-primary" /></p>
	</form>
	<?php } else { ?>
		<p>Należy najpierw włączyć przynajmniej jedną księgarnie w <?php echo admin_url(); ?>/admin.php?page=helion_options</p>
	<?php } ?>
</div>
<?php
}

function helion_submenu_losowa_ksiazka() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	global $wpdb;
	
	if($_REQUEST['action'] == "save") {
		$wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . "helion_widget_random");
		if(!empty($_REQUEST['books'])) {
			foreach($_REQUEST['books'] as $book) {
				$b = explode("-", $book);
				$wpdb->insert($wpdb->prefix . "helion_widget_random", array("typ" => $b[0], "obiekt" => $b[1]));
			}
		}
		
		if(!empty($_REQUEST['ksiegarnie'])) {
			foreach($_REQUEST['ksiegarnie'] as $ksiegarnia) {
				$wpdb->insert($wpdb->prefix . "helion_widget_random", array("typ" => "ksiegarnia", "obiekt" => $ksiegarnia));
			}
		}
		
		?>
		<div id="message" class="updated">
			<p><strong>Zmiany zostały zapisane.</strong></p>
		</div>
		<?php
	}
?>
<style type="text/css">

.items {
	width: 90%;
	margin: 6px auto;
}

.items span {
	cursor: default;
	display: block;
	float: left;
	font-size: 11px;
	line-height: 1.8em;
	margin-right: 25px;
}

.items span a {
	cursor: pointer;
	display: block;
	float: left;
	height: 10px;
	margin: 6px 0 0 -9px;
	overflow: hidden;
	position: absolute;
	text-indent: -9999px;
	width: 10px;
	background: url("/wp-admin/images/xit.gif") no-repeat scroll 0 0 transparent;
}

.items span a:hover {
	background: url("/wp-admin/images/xit.gif") no-repeat scroll -10px 0 transparent;
}

.helion-select-navi {
	margin: 6px 0px;
	clear: both;
}

.helion-select-navi a:hover {
	cursor: pointer;
}

.helion-select-navi a, .helion-select-navi span {
	display: block;
	border: 1px solid;
	padding: 4px 8px;
	border-radius: 4px;
}

.helion-select-navi .prev, .helion-select-navi .empty {
	float: left;
	margin: 0px 4px 0px 0px;
}

.helion-select-navi .next {
	float: right;
	margin: 0px 0px 0px 4px;
}

#selected_books {
	float: right; 
	width: 27%;
	border: 1px solid #dfdfdf;
	background: #fff;
	border-radius: 4px;
	padding: 6px;
	margin-top: 6px;
}

#tabela_wyboru th input[name=wybierz_wszystkie] {
	margin: 0px;
	padding: 4px;
}

</style>
<div class="wrap">
	<?php if(helion_ksiegarnie_wlaczone()) { ?>
	<div id="icon-themes" class="icon32"></div>
	<h2>Ustawienia widgetu Losowa Książka</h2>
	
	<p>Tutaj możesz wybrać, które książki będą wyświetlane w widgecie Losowa książka.</p>
	
	<div id="selections" style="width: 69%; float: left;">
		<table class="form-table">
			<tr>
				<th scope="row">Wszystkie książki z danej księgarni:</th>
				<td>
					<?php
						foreach(get_option("helion_bookstores") as $ksiegarnia => $selected) {
							if($selected) {
						?>
							<input type="button" class="cala_ksiegarnia" name="caly-<?php echo $ksiegarnia; ?>" value="+ <?php echo ucfirst($ksiegarnia); ?>" /><br/>
						<?php
							}
						}
					?>
				</td>
			</tr>
		</table>
		<p>&nbsp;</p>
		<div id="book_select"><?php helion_book_picker(); ?></div>
	</div>
	<div id="selected_books">
		<form method="post">
		<h4>Książki do wyświetlenia:</h4>
		
		<div class="items">
		<?php
			$result = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "helion_widget_random", ARRAY_A);
			$i = 0;
			foreach($result as $r) {
				if($r['typ'] == "ksiegarnia") {
				?>
					<span><a class="bookdelete" id="book-check-num-<?php echo $i; ?>" rel="nofollow">X</a>&nbsp; Cała księgarnia <?php echo ucfirst($r['obiekt']); ?><input type="hidden" name="ksiegarnie[]" value="<?php echo $r['obiekt']; ?>"/> </span> 
				<?php
					$i++;
				}
				
				if($r['typ'] == "helion" || $r['typ'] == "sensus" || $r['typ'] == "onepress" || $r['typ'] == "septem" || $r['typ'] == "ebookpoint") {
				?>
					<span><a class="bookdelete" id="book-check-num-<?php echo $i; ?>" rel="nofollow">X</a>&nbsp;<code title="<?php echo helion_get_book_title($r['typ'], $r['obiekt']); ?>"><?php echo $r['obiekt']; ?></code><input type="hidden" name="books[]" value="<?php echo $r['typ'] . "-" . $r['obiekt']; ?>"/> </span> 
				<?php
					$i++;
				}
			}
		?>
		</div>
		<div class="clear"></div>
		<p><input type="submit" class="button-primary" value="Zatwierdź wybór" /> <input type="button" name="helion_clear" class="button-secondary" value="Wyczyść" /></p>
		<input type="hidden" name="action" value="save" />
		</form>
	</div>
	<?php } else { ?>
		<p>Należy najpierw włączyć przynajmniej jedną księgarnie w <?php echo admin_url(); ?>/admin.php?page=helion_options</p>
	<?php } ?>
</div>
<?php
}

function helion_submenu_ksiegarnia() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	if($_REQUEST['action'] == "save") {
		update_option("helion_bookstore_template_main", $_REQUEST['helion_bookstore_template_main']);
		update_option("helion_bookstore_template_category", $_REQUEST['helion_bookstore_template_category']);
		update_option("helion_bookstore_template_book", $_REQUEST['helion_bookstore_template_book']);
		update_option("helion_bookstore_ksiegarnia", $_REQUEST['helion_bookstore_ksiegarnia']);
		update_option("helion_bookstore_slug", get_slug_by_ID($_REQUEST['helion_bookstore_slug']));
	}
?>
<div class="wrap">
	<?php if(helion_ksiegarnie_wlaczone()) { ?>
	<div id="icon-edit-pages" class="icon32"></div>
	<h2>Ustawienia modułu Księgarnia Helion</h2>
	<form method="post">
	<p>Aby moduł księgarni mógł działać, należy najpierw utworzyć lub edytować stronę i w jej treści umieścić krótki kod <code>[helion_ksiegarnia]</code> (w trybie tekstowym).</p>
	
	<p>Do poprawnego działania tego modułu twój blog musi mieć włączone bezpośrednie odnośniki.</p>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="helion_premalinks">Bezpośrednie odnośniki: </label></th>
			<td><?php if(get_option('permalink_structure') == '') { ?>
				<span style="color: red; font-weight: bold;">Wyłączone</span> (Aby ten moduł działał poprawnie, włącz bezpośrednie odnośniki w menu <a href="<?php admin_url("options-permalink.php"); ?>" rel="nofollow">Ustawienia->Bezpośrednie odnośniki</a>)
			<?php } else { ?>
				<span style="color: green; font-weight: bold;">Włączone</span>
			<?php } ?>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="helion_bookstore_slug">Strona z kodem: </label></th>
			<td><?php printf( __( '%s' ), wp_dropdown_pages( array( 
		'name' => 'helion_bookstore_slug', 
		'echo' => 0, 
		'show_option_none' => __( '&mdash; Select &mdash;' ), 
		'option_none_value' => '0', 
		'selected' => get_ID_by_slug(get_option('helion_bookstore_slug')) ) ) ); ?> (W treści musi znajdować się kod <code>[helion_ksiegarnia]</code>)</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="helion_bookstore_ksiegarnia">Wybrana księgarnia:</label></th>
			<td><select id="helion_bookstore_ksiegarnia" name="helion_bookstore_ksiegarnia">
			<?php
				$selected = ' selected="selected" ';
				
				foreach(get_option("helion_bookstores") as $bs => $sel) {
						if($sel) {
				?>
					<option <?php if(get_option("helion_bookstore_ksiegarnia") == $bs) echo $selected; ?> value="<?php echo $bs; ?>"><?php echo ucfirst($bs); ?></option>
				<?php
					}
				}
			?>
			</select> (Dostępne do wyboru są tylko włączone księgarnie)</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="helion_bookstore_template_main">Wygląd strony głównej: </label></th>
			<td><textarea name="helion_bookstore_template_main" cols="68" rows="12"><?php echo stripslashes(get_option("helion_bookstore_template_main")); ?></textarea>
			<br/>
                        <p>Dostępne znaczniki: <code>%nowosci%</code>, <code>%bestsellery%</code>, <code>%polecane%</code>. Znaczniki są dokładnie opisane w <a href="<?php echo admin_url("admin.php?page=helion_pomoc#znaczniki_ksiegarnia"); ?>" rel="nofollow">dziale pomocy</a>.</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="helion_bookstore_template_book">Wygląd strony z informacjami o książce:</label></th>
			<td>
				<textarea name="helion_bookstore_template_book" cols="68" rows="12"><?php echo stripslashes(get_option("helion_bookstore_template_book")); ?></textarea>
				<br/>
				<p>Dostępne znaczniki: <code>%ident%</code>, <code>%autor%</code>, <code>%tytul%</code>, <code>%opis%</code>, <code>%tytul_orig%</code>, <code>%tlumacz%</code>, <code>%isbn%</code>, <code>%cena%</code>, <code>%cenadetaliczna%</code>, <code>%znizka%</code>, <code>%marka%</code>, <code>%format%</code>, <code>%liczbastron%</code>, <code>%oprawa%</code>, <code>%datawydania%</code>, <code>%issueurl%</code>.</p>
				
				<p>Znaczniki wstawiające okładkę: <code>%okladka65x85%</code>, <code>%okladka72x95%</code>, <code>%okladka88x115%</code>, <code>%okladka90x119%</code>, <code>%okladka120x156%</code>, <code>%okladka125x163%</code>, <code>%okladka181x236%</code>, <code>%okladka326x466%</code>.</p>
				
				<p>Znaczniki wstawiające linki: <code>%dokoszyka%</code>, <code>%link%</code>.</p>
				
				<p>Znaczniki są dokładnie opisane w <a href="<?php echo admin_url("admin.php?page=helion_pomoc#znaczniki_ksiegarnia"); ?>" rel="nofollow">dziale pomocy</a>.</p>
			</td>
		</tr>
	</table>
	
	<input type="hidden" name="action" value="save" />
	
	<p><input type="submit" name="sb" value="Zapisz" class="button-primary" /></p>
	</form>
	<?php } else { ?>
		<p>Należy najpierw włączyć przynajmniej jedną księgarnię w <?php echo admin_url(); ?>/admin.php?page=helion_options</p>
	<?php } ?>
</div>
<?php
}

function helion_submenu_wyszukiwarka() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	if($_REQUEST['action'] == 'save') {
		update_option("helion_wyszukiwarka_slug", get_slug_by_ID($_REQUEST['helion_wyszukiwarka_slug']));
		update_option("helion_wyszukiwarka_template", $_REQUEST['helion_wyszukiwarka_template']);
	}
?>
<div class="wrap">
	<?php if(helion_ksiegarnie_wlaczone()) { ?>
	<form method="post">
		<div id="icon-edit-comments" class="icon32"></div>
		<h2>Ustawienia widgetu Wyszukiwarka</h2>
		<p>Aby wyszukiwarka działała poprawnie, należy:</p>
		<ol>
			<li>Stworzyć nową stronę o dowolnym tytule, a w jej treści umieścić krótki kod <code>[helion_wyniki_wyszukiwania]</code>.</li>
			<li>Wybrać z listy poniżej utworzoną przed chwilą stronę.</li>
			<li>Umieścić np. na pasku bocznym widget Helion Wyszukiwarka.</li>
			<li>Do poprawnego działania tego modułu twój blog musi mieć włączone bezpośrednie odnośniki.</li>
		</ol>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="helion_premalinks">Bezpośrednie odnośniki: </label></th>
				<td><?php if(get_option('permalink_structure') == '') { ?>
					<span style="color: red; font-weight: bold;">Wyłączone</span> (Aby ten moduł działał poprawnie, włącz bezpośrednie odnośniki w menu <a href="<?php admin_url("options-permalink.php"); ?>" rel="nofollow">Ustawienia->Bezpośrednie odnośniki</a>)
				<?php } else { ?>
					<span style="color: green; font-weight: bold;">Włączone</span>
				<?php } ?>
				</td>
			</tr>
		</table>
		
		<h3>Strona z wynikami wyszukiwania</h3>
		
		<p><label for="page_for_posts"><?php printf( __( 'Nazwa strony z wynikami wyszukiwania: %s' ), wp_dropdown_pages( array( 
		'name' => 'helion_wyszukiwarka_slug', 
		'echo' => 0, 
		'show_option_none' => __( '&mdash; Select &mdash;' ), 
		'option_none_value' => '0', 
		'selected' => get_ID_by_slug(get_option('helion_wyszukiwarka_slug')) ) ) ); ?></label></p>
		
		<h3>Wygląd strony z opisem książki</h3>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="helion_wyszukiwarka_template">Edytor wyglądu:</label>
				</th>
				<td>
					<textarea name="helion_wyszukiwarka_template" cols="68" rows="20"><?php echo stripslashes(get_option("helion_wyszukiwarka_template")); ?></textarea>
					<br/>
					<p>Dostępne znaczniki: <code>%ident%</code>, <code>%autor%</code>, <code>%tytul%</code>, <code>%opis%</code>, <code>%tytul_orig%</code>, <code>%tlumacz%</code>, <code>%isbn%</code>, <code>%cena%</code>, <code>%cenadetaliczna%</code>, <code>%znizka%</code>, <code>%marka%</code>, <code>%format%</code>, <code>%liczbastron%</code>, <code>%oprawa%</code>, <code>%datawydania%</code>, <code>%issueurl%</code>. </p>
					
					<p>Znaczniki wstawiające okładkę: <code>%okladka65x85%</code>, <code>%okladka72x95%</code>, <code>%okladka88x115%</code>, <code>%okladka90x119%</code>, <code>%okladka120x156%</code>, <code>%okladka125x163%</code>, <code>%okladka181x236%</code>, <code>%okladka326x466%</code>.</p>
					
					<p>Znaczniki wstawiające linki: <code>%dokoszyka%</code>, <code>%link%</code>.</p>
					
					<p>Znaczniki są dokładnie opisane w <a href="<?php echo admin_url("admin.php?page=helion_pomoc#znaczniki_ksiegarnia"); ?>" rel="nofollow">dziale pomocy</a>. Znaczniki są te same co dla strony z opisem książki w księgarni.</p>
				</td>
			</tr>
		</table>
		
		<input type="hidden" name="action" value="save" />
		<p><input type="submit" name="sb" value="Zapisz" class="button-primary" /></p>
	</form>
	<?php } else { ?>
		<p>Należy najpierw włączyć przynajmniej jedną księgarnie w <?php echo admin_url(); ?>/admin.php?page=helion_options</p>
	<?php } ?>
</div>
<?php
}

function helion_submenu_pomoc() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
?>
<div class="wrap">
	<div id="icon-edit-comments" class="icon32"></div>
	<h2>Pomoc i instrukcje dla wtyczki Helion Widgets Pro</h2>
	
	<p>Wzbogać swój serwis o ciekawe treści, które przyciągną do Ciebie klientów! Rozwiń
skrzydła w e-biznesie i zacznij dobrze zarabiać. Poszerz swoją ofertę o nowości
oraz bestsellery literatury informatycznej, biznesowej, przewodniki turystyczne,
beletrystykę oraz poradniki psychologiczne. Pamiętaj, książki informatyczne to
najlepiej sprzedające się pozycje w sieci!</p>
	
	<p>Program partnerski działa praktycznie bezobsługowo, a jego zasady są proste i
przejrzyste. Partner może publikować wszystkie informacje o książkach dostępnych
w księgarniach Grupy Wydawniczej Helion, a mianowicie: <a href="https://www.onepress.pl" target="_blank" rel="nofollow">onepress.pl</a>, <a href="https://www.helion.pl" target="_blank" rel="nofollow">helion.pl</a>, <a href="http://www.sensus.pl" target="_blank" rel="nofollow">sensus.pl</a>, <a href="http://www.septem.pl" target="_blank" rel="nofollow">septem.pl</a>, <a href="http://www.bezdroza.pl" target="_blank" rel="nofollow">bezdroza.pl</a>, <a href="http://www.ebookpoint.pl" target="_blank" rel="nofollow">ebookpoint.pl</a> i <a href="http://www.videopoint.pl" target="_blank" rel="nofollow">videopoint.pl</a>, w tym fragmenty książek, okładki, filmy wideo, szczegółowe
opisy oraz spisy treści wraz z mechanizmem dodawania książek do koszyka.</p>
	
	<p>W zamian za prezentację naszych produktów otrzymasz wynagrodzenie w postaci
prowizji od każdego zakupu zrealizowanego w księgarniach za pośrednictwem swojej
strony. Prowizja od każdego zrealizowanego zamówienia wynosi 5%.</p>
	
	<p><strong>Już teraz zapoznaj się z Programem Partnerskim na stronie <a href="https://program-
	partnerski.helion.pl" target="_blank" rel="nofollow">https://program-partnerski.helion.pl</a> i dołącz do 4 tysięcy partnerów współpracujących z nami!</strong></p>

	<p>Tutaj znajdziesz informacje na temat korzystania z wtyczki i dostępnych w niej opcji.</p>
	
	<h3 id="top">Spis treści</h3>
	<ol>
		<li><a href="#wymagania" rel="nofollow">Jakie są wymagania wtyczki?</a></li>
		<li><a href="#jakzaczac" rel="nofollow">Jak zacząć korzystać z wtyczki?</a></li>
		<li><a href="#jakwidgety" rel="nofollow">Jak używać widgetów?</a></li>
		<li><a href="#rodzajewidgetow" rel="nofollow">Jakie rodzaje widgetów są dostępne i czym się charakteryzują?</a></li>
		<li><a href="#losowaksiazka" rel="nofollow">Jak wybrać książki do wyświetlania w widgecie Losowa Książka?</a></li>
		<li><a href="#wyszukiwarka" rel="nofollow">Jak działa widget Wyszukiwarka i jak go skonfigurować?</a></li>
		<li><a href="#ksiegarnia" rel="nofollow">Jak ustawić i skonfigurować Księgarnię?</a></li>
		<li><a href="#personalizacja" rel="nofollow">Jak spersonalizować wygląd Księgarni i Wyszukiwarki?</a></li>
		<li><a href="#box" rel="nofollow">Jak wstawić Boks z opisem książki do wpisu?</a></li>
		<li><a href="#link" rel="nofollow">Jak wstawić Link do książki do wpisu?</a></li>
		<li><a href="#cache" rel="nofollow">Co to jest cache i jak go używać?</a></li>
		<li><a href="#wyglad" rel="nofollow">Chcę zmienić sposób wyświetlania elementów - jak to zrobić?</a></li>
		<li><a href="#bug" rel="nofollow">Znalazłem błąd - gdzie mogę go zgłosić?</a></li>
		<li><a href="#forum" rel="nofollow">Gdzie znajdę pomoc dotyczącą Programu Partnerskiego i tej wtyczki?</a></li>
	</ol>
	
	<h3 id="wymagania">Jakie są wymagania wtyczki?</h3>
	<p>Oprócz działającego bloga WordPress i posiadania numeru uczestnika programu
partnerskiego potrzebne jest także około 3 – 6 MB miejsca w bazie danych na
każdą używaną księgarnię oraz łącznie około 100 – 500 MB miejsca na dysku serwera,
przeznaczonego na przechowywanie okładek.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="jakzaczac">Jak zacząć korzystać z wtyczki?</h3>
	<p>Aby zacząć korzystać z wtyczki, wystarczy przejść do menu <a href="<?php echo admin_url("admin.php?page=helion_options"); ?>" rel="nofollow">Helion</a>, podać tam swój numer partnera księgarni Helion (otrzymasz go po podpisaniu umowy partnerskiej na stronie <a href="http://program-
	partnerski.helion.pl" target="_blank" rel="nofollow">https://program-partnerski.helion.pl</a>). Następnie należy wybrać księgarnie, z których chcesz korzystać. Gdy numer jest wpisany, a księgarnie wybrane, możesz zacząć korzystać z możliwości wtyczki: wstawiać widgety, korzystać z księgarni, wstawiać opisy książek do wpisów.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="jakwidgety">Jak używać widgetów?</h3>
	<p>Aby wstawiać widgety wyświetlające książki, przejdź do menu <a href="<?php echo admin_url("widgets.php"); ?>" rel="nofollow">Wygląd->Widgety</a> i przeciągnij wybrany przez ciebie widget Helion na odpowiednią pozycję, w której ma się on pojawić. Następnie wybierz dodatkowe opcje i kliknij "Zapisz". Widget powinien pojawić się na twoim blogu.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="rodzajewidgetow">Jakie rodzaje widgetów są dostępne i czym się charakteryzują?</h3>
	<p>W chwili obecnej możesz używać następujących widgetów:</p>
	<ol style="list-style-position: inside; padding-left: 40px;">
	
		<li><strong>Losowa książka</strong> - w tym widgecie możesz wybrać pulę książek, które będą się
pojawiały na stronie. Przy każdym odświeżeniu będzie losowana inna książka spośród
wybranych przez Ciebie. Możesz wybierać: całe księgarnie, wszystkie książki
danego autora oraz pojedyncze książki. Możesz także łączyć te zakresy i np. wybrać
wszystkie książki jednego autora, a następnie dodać do nich trzy zupełnie inne.
Konfiguracja odbywa się za pośrednictwem menu Helion->Losowa książka.</li>

		<li><strong>Pojedyncza książka</strong> - w tym widgecie możesz wskazać tylko jedną książkę, którą
chcesz wyświetlić. Konfiguracja odbywa się za pośrednictwem menu Wygląd->Widgety.</li>

		<li><strong>Bestsellery</strong> - ten widget wyświetla losowo wybraną książkę z listy 15 najlepiej
sprzedających się pozycji z danej księgarni. Konfiguracja odbywa się za pomocą
menu Wygląd->Widgety.</li>
		
		<li><strong>Książka Dnia</strong> - każdego dnia w księgarniach GW Helion wybierana jest jedna
książka, która przez 24 godziny jest sprzedawana z rabatem 20 lub 30%. W tym
widgecie możesz wyświetlać aktualną książkę dnia z wybranej przez siebie księgarni.
Konfiguracja odbywa się za pomocą menu Wygląd->Widgety.</li>
		
		<li><strong>Wyszukiwarka</strong> - pozwala na umieszczenie na blogu wyszukiwarki książek z
danej księgarni, dzięki czemu użytkownik może wyszukać interesującą go książkę,
przeczytać informacje na jej temat, a następnie kliknąć link, który doda książkę do
koszyka i skieruje go do formularza z płatnością.</li>
		
	</ol>
	
	<p>Nic nie stoi na przeszkodzie, aby na jednej stronie był wyświetlany więcej niż jeden
widget, o ile pozwala na to moc Twojego serwera.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="losowaksiazka">Jak wybrać książki do wyświetlania w widgecie Losowa Książka?</h3>
	<p>Przejdź do menu Helion->Losowa książka, a następnie wyszukaj za pomocą
wyszukiwarki książki, które chcesz wyświetlać. Możesz wybierać pojedyncze książki,
a także całe księgarnie.</p> 
	<p>Przycisk „+” umieszczony w lewym górnym i dolnym rogu przeglądarki książek
powoduje dodanie wszystkich pozycji.</p>
	<p>Gdy już wybierzesz książki, które chcesz wyświetlać, koniecznie zatwierdź swój
wybór.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="wyszukiwarka">Jak działa widget wyszukiwarka i jak go skonfigurować?</h3>
	<p>Widget Wyszukiwarka pozwala Twoim czytelnikom na wyszukiwanie książek, które
ich interesują.</p>
	<p>Wyszukiwarka składa się z dwóch części: widgetu oraz statycznej strony, która wyświetla wyniki.</p>

	<p>Aby zainstalować wyszukiwarkę na blogu:</p>
	
	<ol style="list-style-position: inside; padding-left: 40px;">
		<li>Utwórz nową stronę o dowolnym tytule (może być pusty) i umieść w jej treści kod <code>[helion_wyniki_wyszukiwania]</code>.</li>
		<li>Strona z wynikami wyszukiwania nie powinna być dostępna z poziomu menu ani widgetów.</li>
		<li>Przejdź do menu Helion->Wyszukiwarka</li>
		<li>Wybierz z listy stronę z wynikami wyszukiwania, którą przed chwilą stworzyłeś.</li>
		<li>Umieść widget Wyszukiwarka na swoim blogu.</li>
	</ol>
	
	<p>Do poprawnego działania tego modułu twój blog musi mieć włączone bezpośrednie odnośniki w menu <a href="<?php admin_url("options-permalink.php"); ?>" rel="nofollow">Ustawienia->Bezpośrednie odnośniki</a>.</p>
	
	<p>Możesz samodzielnie ustalić wygląd strony z opisem książki, używając do tego kodu HTML oraz znaczników, np. %tytul%. Znaczniki zostały <a href="#znaczniki_ksiegarnia" rel="nofollow">opisane w ramach modułu Księgarni</a>, ponieważ zarówno Wyszukiwarka, jak i Księgarnia korzystają z tego samego silnika wyświetlającego opisy i posiadają te same znaczniki.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="ksiegarnia">Jak skonfigurować księgarnię</h3>
	<p>Stwórz własną księgarnię, która będzie zawierała kopię całej oferty księgarni z Grupy Helion i sprzedawaj książki tematycznie powiązane z twoim blogiem.</p>
	
	<p>Do poprawnego działania tego modułu twój blog musi mieć włączone bezpośrednie odnośniki w menu <a href="<?php admin_url("options-permalink.php"); ?>" rel="nofollow">Ustawienia->Bezpośrednie odnośniki</a>.</p>
	
	<h4>Księgarnia jako osobny blog</h4>
	
	<p>Ta opcja pozwala utworzyć księgarnię np. w subdomenie ksiegarnia.twojastrona.pl. Zaleca się tworzenie księgarni na osobnej instalacji WordPress (nie na MultiSite).</p>
	
	<ol style="list-style-position: inside; padding-left: 40px;">
		<li>Utwórz nową stronę i umieść w jej treści kod <code>[helion_ksiegarnia]</code>. Strona może być bez tytułu.</li>
		<li>W menu Ustawienia->Czytanie wybierz: "Strona główna wyświetla <strong>statyczną stronę</strong>", a następnie z listy dla strony głównej wybierz nowo utworzoną stronę z kodem (jeśli wybrałeś opcję bez tytułu, tutaj jedna z dostępnych stron będzie reprezentowana przez puste miejsce).</li>
		<li>Umieść na pasku bocznym widget Helion Kategorie, aby uzyskać listę kategorii.</li>
		<li>Zalecane jest także umieszczenie wyszukiwarki w formie widgetu i odpowiednie skonfigurowanie jej.</li>
	</ol>
	
	<h4>Księgarnia podstrona bloga</h4>
	
	<ol style="list-style-position: inside; padding-left: 40px;">
		<li>Utwórz nową stronę i umieść w jej treści kod <code>[helion_ksiegarnia]</code>.</li>
		<li>Nowa strona powinna być dostępna z poziomu menu bloga, aby czytelnicy mogli się do niej dostać.</li>
		<li>Umieść na pasku bocznym widget Helion Kategorie, aby uzyskać listę kategorii.</li>
		<li>Zalecane jest także umieszczenie wyszukiwarki w formie widgetu i odpowiednie skonfigurowanie jej.</li>
	</ol>
	
	<h4 id="znaczniki_ksiegarnia">Personalizacja wyglądu prezentowanych danych o książkach</h4>
	
	<p>Wygląd można personalizować przy użyciu kodu HTML oraz dostępnych znaczników. Znaczniki są podczas wyświetlania książki zamieniane na konkrente informacje.</p>
	
	<p>Dla <strong>strony z opisem pojedynczej książki</strong> dostępne są następujące znaczniki:</p>
	
	<ul style="list-style-type: square; list-style-position: inside; padding-left: 40px;">
		<li><code>%ident%</code> - 5-6 znakowy kod identyfikacyjny książki, </li>
		<li><code>%autor%</code> - nazwisko autora lub autorów, </li>
		<li><code>%tytul%</code> - tytuł książki, </li>
		<li><code>%opis%</code> - pełny opis książki, </li>
		<li><code>%tytul_orig%</code> - tytuł w oryginalnym języku, </li>
		<li><code>%tlumacz%</code> - nazwisko tłumacza, </li>
		<li><code>%isbn%</code> - numer ISBN, </li>
		<li><code>%cena%</code> - aktualna cena książki, w tym cena po uwzględnieniu zniżki jeśli taka jest, </li>
		<li><code>%cenadetaliczna%</code> - normalna cena, przed uwzględnieniem zniżki, </li>
		<li><code>%znizka%</code> - informacja o wielkości zniżki (w zł), </li>
		<li><code>%marka%</code> - nazwa księgarni, z której pochodzi książka, </li>
		<li><code>%format%</code> - format książki,</li> 
		<li><code>%liczbastron%</code> - liczba stron, </li>
		<li><code>%oprawa%</code> - rodzaj okładki, </li>
		<li><code>%datawydania%</code> - pełna data wydania, </li>
		<li><code>%issueurl%</code> - link do podglądu fragmentu książki</li>
		<li><code>%okladka65x85%</code>, <code>%okladka72x95%</code>, <code>%okladka88x115%</code>, <code>%okladka90x119%</code>, <code>%okladka120x156%</code>, <code>%okladka125x163%</code>, <code>%okladka181x236%</code>, <code>%okladka326x466%</code> - wstawia okładkę o podanym rozmiarze (tzn. kod HTML: <code>&lt;img ... /></code>)</li>
		<li><code>%dokoszyka%</code> - link dodający książkę do koszyka</li>
		<li><code>%link%</code> - zwykły link prowadzący do strony z opisem książki na oficjalnej stronie księgarni (np. na helion.pl czy onepress.pl)</li>
	</ul>
	
	<p>Dla <strong>strony głównej</strong> są dostępne następujące znaczniki:</p>
	
	<ul style="list-style-type: square; list-style-position: inside; padding-left: 40px;">
		<li><code>%nowosci%</code> - wyświetla 4 boxy z losowo wybranymi nowymi książkami w ofercie, </li>
		<li><code>%bestsellery%</code> - wyświetla listę 10 losowo wybranych bestsellerów</li>
                <li><code>%polecane%</code> - wyświetla listę 10 wybranych pozycji ustalonych w widgecie "Losowa książka"</li>
	</ul>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	
	<h3 id="box">Jak wstawić Boks z opisem książki do wpisu?</h3>
	<p>W każdym wpisie możesz łatwo wstawić Boks z okładką i danymi na temat dowolnej książki. Wystarczy w wybranym przez ciebie miejscu wstawić następujący kod: <code>[helion_ksiazka ksiegarnia="helion" ident="markwy" okladka="120x156" width="250" float="right" opis="1" substring="100"]</code>, gdzie dostępne są następujące parametry:</p>
	<ul style="list-style-type: square; list-style-position: inside; padding-left: 40px;">
		<li>ksiegarnia: (helion|onepress|sensus|septem|ebookpoint|bezdroza|videopoint) nazwa księgarni, do której należy książka (obowiązkowy)</li>
		<li>ident: identyfikator książki (obowiązkowy)</li>
		<li>okladka: rozmiar wyświetlonej w boxie okładki. Możesz wybierać spośród następujących rozmiarów: 326x466, 181x236, 125x163, 120x156, 90x119, 88x115, 72x95 i 65x85. Inne rozmiary nie są dostępne. (opcjonalny, domyślnie 120x156)</li>
		<li>width: szerokość boxu w pikselach (opcjonalny, domyślnie taki sam jak szerokość okładki, minimum 200px)</li>
		<li>float: określa, po której stronie ma się znaleźć box. Dostępne parametry: left, right (opcjonalny, domyślnie left)</li>
                <li>opis: określa, czy wyświetlać opis (1 - tak, 0 - nie (opcjonalnie))</li>
                <li>substring: określa, do ilu znaków ma zostać skrócony opis (opcjonalnie 100)</li>
	</ul>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="link">Jak wstawić link do książki do wpisu?</h3>
	<p>Jeżeli znasz parametr ident książki i wiesz, z jakiej księgarni ona pochodzi, możesz z łatwością wstawić do niej link za pomocą następującego kodu: <code>[helion_link ksiegarnia="helion" ident="markwy" cyfra="123"]</code>, gdzie parametry są następujące:</p>
	<ul style="list-style-type: square; list-style-position: inside; padding-left: 40px;">
		<li>ksiegarnia: (helion|onepress|sensus|septem|ebookpoint|bezdroza|videopoint) nazwa księgarni (obowiązkowa)</li>
		<li>ident: identyfikator książki (obowiązkowy)</li>
		<li>cyfra: jest to dodatkowy parametr, dzięki którego możesz zbadać skuteczność kampanii. Jeśli prowadzisz dwie strony, na jednej możesz ustawić cyfrę 1 a na drugiej cyfrę 2 - gdy ktoś dokona zakupu, wówczas otrzymasz maila w którym oprócz informacji o zakupach zostanie przekazana ta cyfra i będziesz wiedział, z jakiej strony został dokonany zakup. (opcjonalna)</li>
	</ul>
	<p>Powyższy kod spowoduje wstawienie linku z twoim identyfikatorem klienta do treści wpisu. Wstawiony link będzie zawierał tytuł książki. Jeżeli chcesz nadać linkowi inną treść, wystarczy, że wstawisz kod w następującej formie: <code>[helion_link ksiegarnia="helion" ident="markwy" cyfra="123"]Treść linku[/helion_link]</code></p>
	<p>Co ważne, nie będziesz musiał nic zmieniać, jeśli na przykład w przyszłości zmieni się format linków. Dzięki temu nie będzie konieczności przeszukiwania wszystkich wpisów i aktualizacji linków, ponieważ stanie się to automatycznie.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="cache">Co to jest cache i jak go używać?</h3>
	<p>Cache to katalog, w którym przechowywane są okładki książek często używane na Twoim blogu. Raz pobrana okładka jest następnie serwowana z Twojego hostingu, a nie z głównego serwera księgarni. Pozwala to na odciążenie serwerów głównych Grupy Helion, co z kolei skutkuje ich szybszym działaniem i większą niezawodnością.</p>
	<p>Samodzielnie decydujesz, jak dużą przestrzeń chcesz poświęcić na cache. Wtyczka zaproponuje Ci określony rozmiar przestrzeni dyskowej, jaki przy wypranych przez Ciebie opcjach warto na ten cel przeznaczyć.</p>
	<p>Podanie wartości 0 w ustawieniach rozmiaru cache jest równoznaczne z wyłączeniem tej funkcji.</p>
	<p>Możesz zmienić rozmiar cache w menu Helion->Cache okładek.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="wyglad">Chcę zmienić sposób wyświetlania elementów - jak to zrobić?</h3>
	<p>Wtyczka jest wyposażona w domyślny zestaw stylów CSS, dzięki czemu od samego początku wygląd elementów jest już w jakimś stopniu zdefinowany. Jeśli jednak chcesz wprowadzić zmiany, możesz dodać własne style w plikach twojego szablonu, np. za pomocą edytora w menu Wygląd->Edycja. Prawie każdy widget i element posiada własne klasy CSS, dzięki czemu możesz łatwo zapanować nad jego wyglądem. Przydatnym narzędziem w tym przypadku będzie wtyczka Firebug dla przeglądarki Firefox.</p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="bug">Znalazłem błąd. Gdzie mogę go zgłosić?</h3>
	<p>Zaloguj się na forum Programu Partnerskiego pod adresem <a href="https://program-partnerski.helion.pl/forum/" target="_blank" rel="nofollow">https://program-partnerski.helion.pl/forum/</a>, albo napisz maila do autora wtyczki: <a href="mailto:mdzimiera@helion.pl?subject=Helion+Widgets+Pro" rel="nofollow">mdzimiera@helion.pl</a></p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
	
	<h3 id="forum">Gdzie znajdę pomoc dotyczącą Programu Partnerskiego i tej wtyczki?</h3>
	<p>Wszelką pomoc dotyczącą PP Helion znajdziesz na specjalnie do tego celu stworzonym forum pod adresem <a href="https://program-partnerski.helion.pl/forum/" target="_blank" rel="nofollow">https://program-partnerski.helion.pl/forum/</a></p>
	
	<p><small><a href="#top" rel="nofollow">&uarr; Powrót do spisu treści</a></small></p>
</div>
<?php
}

?>
