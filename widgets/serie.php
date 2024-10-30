<?php
	// widget wyświetlający serie wydawnicze (dla księgarni)

add_action( 'widgets_init', 'helion_load_widget_serie' );

function helion_load_widget_serie() {
	register_widget( 'Helion_Widget_Serie' );
}

class Helion_Widget_Serie extends WP_Widget {
	
	function Helion_Widget_Serie() {
		$widget_ops = array( 'classname' => 'helion_widget_serie', 'description' => 'Widget wyświetlający listę serii z danej księgarni' );

		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'helion-widget-serie' );

		$this->WP_Widget( 'helion-widget-serie', 'Helion - Serie', $widget_ops, $control_ops );
	}
	
	function widget($args, $instance) {
		extract( $args );
		
		$bookstore = h_validate_bookstore(get_option("helion_bookstore_ksiegarnia"));

		if(!$bookstore) {
			echo $before_widget;
			echo $before_title . $instance['title'] . $after_title;
		?>
			<p><strong>Wystąpił błąd.</strong> Nie wybrano księgarni. Przejdź do menu Helion->Księgarnia w panelu administratora i podaj wszystkie wymagane dane.</p>
		<?php
			echo $after_widget;
		} else {
		
			echo $before_widget;
			echo $before_title . $instance['title'] . $after_title;

			$lista = helion_get_serie($bookstore);

			$slug = get_option("helion_bookstore_slug");
			
			if($slug) {
				$home = get_bloginfo("url") . "/" . $slug . "/?helion_bookstore=serie";
			} else {
				$home = get_bloginfo("url") . "/?helion_bookstore=serie" ;
			}
			
			echo '<ul class="nad">';
			
			if(count($lista) > 0) {
                            foreach($lista as $key => $value) {
                                echo '<li class="n"><a href="' . $home . "&id=" . $value['id_seria'] . '" rel="nofollow" title="' . ucfirst($value['seria']) . '">' . ucfirst($value['seria']) . '</a></li>';
                            }
			} else {
				
			}
			
			echo '</ul>';
			
			echo $after_widget;
		}
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
	
	function form( $instance ) {
		$defaults = array( 'title' => 'Serie', 'bookstore' => 'helion');
		$instance = wp_parse_args( (array) $instance, $defaults );
		$selected = ' selected="selected" ';
		$checked = ' checked="checked" ';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Tytuł:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>

		<?php
	}
}
?>