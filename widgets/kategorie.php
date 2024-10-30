<?php

// TODO: pytaj, czy pokazywać kategorie czy subkategorie też


add_action( 'widgets_init', 'helion_load_widget_kategorie' );

function helion_load_widget_kategorie() {
	register_widget( 'Helion_Widget_Kategorie' );
}

class Helion_Widget_Kategorie extends WP_Widget {
	
	function Helion_Widget_Kategorie() {
		$widget_ops = array( 'classname' => 'helion_widget_kategorie', 'description' => 'Widget wyświetlający listę kategorii z danej księgarni' );

		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'helion-widget-kategorie' );

		$this->WP_Widget( 'helion-widget-kategorie', 'Helion - Kategorie', $widget_ops, $control_ops );
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

			$lista = helion_get_kategorie($bookstore);

			$slug = get_option("helion_bookstore_slug");
			
			if($slug) {
				$home = get_bloginfo("url") . "/" . $slug . "/?helion_bookstore=category";
			} else {
				$home = get_bloginfo("url") . "/?helion_bookstore=category" ;
			}
			
			echo '<ul class="nad">';
			
			if(count($lista['pod']) > 0) {
				foreach($lista['nad'] as $id_nad => $nad) {
                                       
                                        # sprawdzamy czy istnieje chociaz jeden podelement
                                        $pod_exist = false;
                                        if($nad != 'eBooki'){
                                            foreach($lista['pod'] as $id => $pod) {
                                                        if(key($pod) == $id_nad) {
                                                            $pod_exist = true;
                                                            break;
                                                        }
                                            }
                                        }

                                        if($pod_exist){
                                            echo '<li class="n">' . $nad;
                                            echo '<ul class="pod">';

                                            foreach($lista['pod'] as $id => $pod) {
                                                    if(key($pod) == $id_nad) {
                                                            echo '<li><a href="' . $home . "&id=" . $id . '" rel="nofollow">' . $pod[key($pod)] .'</a></li>';
                                                    }
                                            }

                                            echo '</ul>';
                                            echo '</li>';
                                            
                                        }else{
                                            
                                            echo '<li class="n"><a href="' . $home . "&id=" . $id_nad . '" rel="nofollow">' . $nad . '</a></li>';
                                            
                                        }
					
				}
			} else {
				foreach($lista['nad'] as $id_nad => $nad) {
					echo '<li class="n"><a href="' . $home . "&id=" . $id_nad . '" rel="nofollow">' . $nad . '</a></li>';
				}
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
		$defaults = array( 'title' => 'Kategorie', 'bookstore' => 'helion');
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