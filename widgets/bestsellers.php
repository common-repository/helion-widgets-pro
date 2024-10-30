<?php

add_action( 'widgets_init', 'helion_load_widget_bestsellers' );

function helion_load_widget_bestsellers() {
	register_widget( 'Helion_Widget_Bestsellers' );
}

class Helion_Widget_Bestsellers extends WP_Widget {
	
	function Helion_Widget_Bestsellers() {
		$widget_ops = array( 'classname' => 'helion_widget_bestsellers', 'description' => 'Widget wyświetlający losową książkę spośród bestsellerów', 'okladka' => '120x156' );

		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'helion-widget-bestsellers' );

		$this->WP_Widget( 'helion-widget-bestsellers', 'Helion - Bestsellery', $widget_ops, $control_ops );
	}
	
	function widget($args, $instance) {
		extract( $args );
		
		$random_book = $this->random_book($instance['bookstore']);
		
		if(empty($random_book))
			return false;
			
		$book = helion_get_book_info($random_book['bookstore'], $random_book['ident']);
		
		if(!$book) {
			echo $before_widget;
			echo $before_title . $instance['title'] . $after_title;
			?>
			<p>Nie było możliwe pobranie danych na temat książki.</p>
			<?php
			echo $after_widget;
		} else {
			$dokoszyka = helion_get_link($instance['bookstore'], $book['ident'], $instance['cyfra'], true);
					
			if($instance['koszyk']) {
				$link = $dokoszyka;
			} else {
				$link = helion_get_link($instance['bookstore'], $book['ident'], $instance['cyfra']);
			}
			
			$okladka = helion_get_cover($random_book['bookstore'], $random_book['ident'], $instance['cover']);
			$tytul = $okladka['alt'];
		
			echo $before_widget;
			echo $before_title . $instance['title'] . $after_title;
			?>
			<div class="helion_okladka" style="width: <?php echo $okladka['width']; ?>px;">
					<a href="<?php echo $link; ?>" target="_blank" title="<?php echo $tytul; ?>" rel="nofollow">
                                            <img src="<?php echo $okladka['src']; ?>" alt="<?php echo $okladka['alt']; ?>" onerror="<?php echo $okladka['onerror']?>" />
					</a>
				</div>
				<div class="helion_meta" style="width: <?php echo $okladka['width'] + 70; ?>px;">
					<?php if($instance['tytul']) { ?>
					<p class="helion_tytul"><a href="<?php echo $link; ?>" target="_blank" rel="nofollow"><?php echo $tytul; ?></a></p>
					<?php } ?>
					<?php if($instance['autor'] && $book['autor']) { ?>
						<p class="helion_autor">autor: <?php echo $book['autor']; ?></p>
					<?php } ?>
					<?php if($instance['cena']) { ?>
					<p class="helion_cena">Cena: <?php echo $book['cena']; ?> zł</p>
					<?php } ?>
					<?php if($instance['dodatkowe']) { ?>
						<?php 
							if($book['nowosc']) {
								$dod[] = '<img src="https://static01.helion.com.pl/helion/img/nowosc.gif" alt="nowość" />';
							}
							if($book['bestseller']) {
								$dod[] = '<img src="https://static01.helion.com.pl/helion/img/bestseller.gif" alt="bestseller" />';
							}
							
							if(!empty($dod)) { ?>
								<p class="helion_dodatkowe">
							<?php
								echo join(" ", $dod);
							?>
								</p>
							<?php
							}
						?>
					<?php } ?>
					<?php if($instance['przycisk']) { ?>
						<div class="helion-box">
                                                    <a href="<?php echo $dokoszyka; ?>" title="Kup teraz" rel="nofollow">kup teraz</a>
                                                </div>
					<?php } ?>
				</div>
			<?php
			echo $after_widget;
		}
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['cover'] = strip_tags( $new_instance['cover'] );
		$instance['cyfra'] = strip_tags( $new_instance['cyfra'] );
		$instance['tytul'] = strip_tags( $new_instance['tytul'] );
		$instance['autor'] = strip_tags( $new_instance['autor'] );
		$instance['cena'] = strip_tags( $new_instance['cena'] );
		$instance['dodatkowe'] = strip_tags( $new_instance['dodatkowe'] );
		$instance['bookstore'] = strip_tags( $new_instance['bookstore'] );
		$instance['przycisk'] = strip_tags( $new_instance['przycisk'] );
		$instance['koszyk'] = strip_tags( $new_instance['koszyk'] );

		return $instance;
	}
	
	function form( $instance ) {
		$defaults = array( 'title' => 'Bestsellery', 'cover' => '125x163' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$selected = ' selected="selected" ';
		$checked = ' checked="checked" ';
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Tytuł widgetu:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'bookstore' ); ?>">Księgarnia:</label>
			<select id="<?php echo $this->get_field_id( 'bookstore' ); ?>" name="<?php echo $this->get_field_name( 'bookstore' ); ?>" class="widefat">
			<?php
				foreach(get_option("helion_bookstores") as $bs => $sel) {
						if($sel) {
				?>
					<option <?php if($instance['bookstore'] == $bs) echo $selected; ?> value="<?php echo $bs; ?>"><?php echo ucfirst($bs); ?></option>
				<?php
					}
				}
			?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'cyfra' ); ?>">Cyfra:</label>
			<input id="<?php echo $this->get_field_id( 'cyfra' ); ?>" name="<?php echo $this->get_field_name( 'cyfra' ); ?>" value="<?php echo $instance['cyfra']; ?>" class="widefat" />
		</p>
		<p><small><strong>Cyfra:</strong> Jest to dodatkowy (nieobowiązkowy) parametr, dzięki któremu możesz zbadać skuteczność kampanii. Jeśli prowadzisz dwie strony, na jednej możesz ustawić cyfrę 1 a na drugiej cyfrę 2 - gdy ktoś dokona zakupu, wówczas otrzymasz maila w którym oprócz informacji o zakupach zostanie przekazana ta cyfra i będziesz wiedział, z jakiej strony został dokonany zakup. </small></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'cover' ); ?>">Rozmiar okładki:</label>
			<select id="<?php echo $this->get_field_id( 'cover' ); ?>" name="<?php echo $this->get_field_name( 'cover' ); ?>" class="widefat" style="width:100%;">
				<option <?php if($instance['cover'] == "65x85") echo $selected; ?>>65x85</option>
				<option <?php if($instance['cover'] == "72x95") echo $selected; ?>>72x95</option>
				<option <?php if($instance['cover'] == "72x95") echo $selected; ?>>72x95</option>
				<option <?php if($instance['cover'] == "90x119") echo $selected; ?>>90x119</option>
				<option <?php if($instance['cover'] == "120x156") echo $selected; ?>>120x156</option>
				<option <?php if($instance['cover'] == "125x163") echo $selected; ?>>125x163</option>
				<option <?php if($instance['cover'] == "181x236") echo $selected; ?>>181x236</option>
				<option <?php if($instance['cover'] == "326x466") echo $selected; ?>>326x466</option>
			</select>
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'tytul' ); ?>" name="<?php echo $this->get_field_name( 'tytul' ); ?>" <?php if($instance['tytul']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'tytul' ); ?>">Wyświetlać tytuł książki?</label>
			<br/>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'autor' ); ?>" name="<?php echo $this->get_field_name( 'autor' ); ?>" <?php if($instance['autor']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'autor' ); ?>">Wyświetlać autora książki?</label>
			<br/>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'cena' ); ?>" name="<?php echo $this->get_field_name( 'cena' ); ?>" <?php if($instance['cena']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'cena' ); ?>">Wyświetlać cenę książki?</label>
			<br/>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'dodatkowe' ); ?>" name="<?php echo $this->get_field_name( 'dodatkowe' ); ?>" <?php if($instance['dodatkowe']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'dodatkowe' ); ?>">Wyświetlać dodatkowe informacje o książce (nowość, bestseller itp.)?</label>
			<br/>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'przycisk' ); ?>" name="<?php echo $this->get_field_name( 'przycisk' ); ?>" <?php if($instance['przycisk']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'przycisk' ); ?>">Wyświetlać przycisk "Kup Teraz"?</label>
			<br/>
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'koszyk' ); ?>" name="<?php echo $this->get_field_name( 'koszyk' ); ?>" <?php if($instance['koszyk']) echo $checked; ?> />
			<label for="<?php echo $this->get_field_id( 'koszyk' ); ?>">Zaznacz, aby link w okładce i tytule dodawał książkę do koszyka, a nie prowadził do strony z opisem książki</label>
			<br/>
		</p>

		<?php
	}
	
	function random_book($bookstore) {
		global $wpdb;
		
		$book = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "helion_bestsellers WHERE bookstore = %s ORDER BY RAND() LIMIT 1", $bookstore), ARRAY_A);
		
		return $book;
	}
}
?>
