<?php

add_action('wp_print_styles', 'helion_wyszukiwarka_styl');

function helion_wyszukiwarka_styl() {
	wp_register_style('helion-wyszukiwarka', plugins_url("css/wyszukiwarka.css", dirname(__FILE__)));
	wp_enqueue_style('helion-wyszukiwarka');
}

add_action( 'widgets_init', 'helion_load_widget_wyszukiwarka' );

function helion_load_widget_wyszukiwarka() {
	register_widget( 'Helion_Widget_Wyszukiwarka' );
}

class Helion_Widget_Wyszukiwarka extends WP_Widget {
	
	function Helion_Widget_Wyszukiwarka() {
		$widget_ops = array( 'classname' => 'helion_widget_wyszukiwarka', 'description' => 'Widget wyszukiwarki książek Helion' );

		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'helion-widget-wyszukiwarka' );

		$this->WP_Widget( 'helion-widget-wyszukiwarka', 'Helion - Wyszukiwarka', $widget_ops, $control_ops );
	}
	
	function widget($args, $instance) {
		extract( $args );
		
		$slug = get_option("helion_wyszukiwarka_slug");
		
		if($slug) {
			if($html5) {
				echo $before_widget;
				echo $before_title . $instance['title'] . $after_title;
				?>
					<form action="<?php echo $slug; ?>" method="get">
						<input type="hidden" name="helion_serp" value="1" />
						<input type="search" name="helion_wyszukiwarka" autocomplete="on" placeholder="<?php echo $instance['placeholder']; ?>" /> 
						<input type="submit" name="hw" value="<?php echo $instance['przycisk']; ?>" />
					</form>
				<?php
				echo $after_widget;
			} else {
				echo $before_widget;
				echo $before_title . $instance['title'] . $after_title;
				?>
					<form action="<?php echo $slug; ?>" method="get">
						<input type="hidden" name="helion_serp" value="1" />
						<input type="text" name="helion_wyszukiwarka" value="<?php echo $instance['placeholder']; ?>" onclick="this.value='';" />
						<input type="submit" name="hw" value="<?php echo $instance['przycisk']; ?>" />
					</form>
				<?php
				echo $after_widget;
			}
		} else {
			echo $before_widget;
			echo $before_title . $instance['title'] . $after_title;
			?>
				<p><strong>Widget nie jest skonfigurowany.</strong> Skonfiguruj widget wyszukiwarki w menu Helion->Wyszukiwarka</p>
			<?php
			echo $after_widget;
		}
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['placeholder'] = strip_tags( $new_instance['placeholder'] );
		$instance['przycisk'] = strip_tags( $new_instance['przycisk'] );

		return $instance;
	}
	
	function form( $instance ) {
		$defaults = array( 'title' => 'Wyszukiwarka książek', 'przycisk' => 'Szukaj', 'placeholder' => 'wpisz szukane słowo...' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$selected = ' selected="selected" ';
		$checked = ' checked="checked" ';
		?>
		<p><small>Konfiguracja wyglądu strony z wynikami odbywa się za pomocą menu <a href="<?php echo admin_url("admin.php?page=helion_wyszukiwarka"); ?>" rel="nofollow">Helion->Wyszukiwarka</a>.</small></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Tytuł:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'placeholder' ); ?>">Tekst w polu wyszukiwania:</label>
			<input id="<?php echo $this->get_field_id( 'placeholder' ); ?>" name="<?php echo $this->get_field_name( 'placeholder' ); ?>" value="<?php echo $instance['placeholder']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'przycisk' ); ?>">Tekst na przycisku wyszukiwania:</label>
			<input id="<?php echo $this->get_field_id( 'przycisk' ); ?>" name="<?php echo $this->get_field_name( 'przycisk' ); ?>" value="<?php echo $instance['przycisk']; ?>" class="widefat" />
		</p>
		<?php
	}
}
?>