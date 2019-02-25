<?php
/**
 * Custom post type taxonomy widget
 */
class IDOCS_Menu_Widget extends WP_Widget{
	/**
	 * Constructor
	 */
	public function __construct(){
		/* Widget settings. */
		$widget_options = array( 
			'classname' 	=> 'widget_idocs', 
			'description' 	=> __('Display documentation menu when navigating a docs category', 'idocs') 
		);

		/* Widget control settings. */
		$control_options = array( 
			'id_base' => 'idocs-taxonomy-docs-widget' 
		);

		/* Create the widget. */
		parent::__construct( 
			'idocs-taxonomy-docs-widget', 
			__('iDocument menu', 'idocs'), 
			$widget_options, 
			$control_options 
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::widget()
	 */
	function widget( $args, $instance ){
		// check if single post is displayed
		if( !is_singular( idocs_post_type() ) && !is_post_type_archive( idocs_post_type() ) && !is_tax( idocs_taxonomy() ) ){
			return;
		}
		
		global $post;		
		extract( $args );
		
		$terms = wp_get_post_terms( $post->ID, idocs_taxonomy() );
		
		if( !$terms ){
			if( current_user_can('manage_options') ){
				echo $before_widget;
				echo $widget_title;
				printf(
					__('No categories detected. Add categories to post %s in order to allow the plugin to display the docs menu.', 'idocs'),
					'<a href="'.get_edit_post_link( $post->ID ).'">'.$post->post_title.'</a>'
				);
				echo $after_widget;	
			}
			return;
		}
		
		if( $terms[0]->parent ){
			$term_id = $terms[0]->parent;
		}else{
			$term_id = $terms[0]->term_id;
		}
		
		$widget_title 	= '';
		// replace title with cateogry title if set
		if( isset( $instance['idocs_replace_title'] ) && $instance['idocs_replace_title'] ){
			if( !is_wp_error($terms) && $terms ){
				$term = get_term( $term_id , idocs_taxonomy());
				$widget_title = $before_title . apply_filters('widget_title', $term->name) . $after_title;
			}
		}
		// put widget title if title is empty
		if( empty($widget_title) && isset( $instance['idocs_widget_title'] ) && !empty( $instance['idocs_widget_title'] ) ){
			$widget_title = $before_title . apply_filters('widget_title', $instance['idocs_widget_title']) . $after_title;
		}

		$post_status = current_user_can( 'manage_options' ) ? 'any' : 'publish';
		
		$args = array(
			'posts_per_page' 		=> -1,
			'offset'				=> -1,
			'post_type'				=> idocs_post_type(),
			'post_status'			=> $post_status,
			'order'					=> 'ASC',
			'orderby'				=> 'menu_order ID',
			'ignore_sticky_posts'	=> true,
			'hierarchical'			=> true,
			'tax_query'				=> array(
				array(
					'taxonomy' 	=> idocs_taxonomy(),
					'field'		=> 'term_id',
					'terms'		=> $term_id,
					'include_children' => false
				)
			)
		);
		
		$docs = get_posts( $args );
		if( is_wp_error($docs) || !$docs ){
			return;
		}
		
		echo $before_widget;
		echo $widget_title;		
		echo '<ul>';
			$settings = idocs_settings();
			echo walk_docs_tree($docs, 0, $post->ID, array(
				'depth' 		=> 0, 
				'show_date' 	=> $settings['menu_date'],
				'date_format' 	=> get_option('date_format'),
				'child_of' 		=> 0, 
				'exclude' 		=> '',
				'title_li' 		=> __('Pages'), 
				'echo' 			=> 1,
				'authors' 		=> '', 
				'sort_column' 	=> 'menu_order, post_title',
				'link_before' 	=> '', 
				'link_after' 	=> '', 
				'walker' 		=> '',
			));
			remove_filter('page_css_class', 'idocs_page_class_walker', 999);
		echo '</ul>';

		// get parent category children
		$args = array(
			'taxonomy' => idocs_taxonomy(),
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
			'parent' =>  $term_id,
			'meta_query' => array(
                array(
                    'key' => idocs_tax_meta_menu_order(),
                    'type' => 'NUMERIC'
                )
			)
		);
		$children = get_terms( $args );//get_term_children( $term_id  , idocs_taxonomy());

		if( !is_wp_error( $children ) ){
			foreach( $children as $term ){
				if( $term->parent != $term_id ){
					continue;
				}
				
				$widget_title = $before_title . apply_filters('widget_title', $term->name) . $after_title;
				$args = array(
					'posts_per_page' 		=> -1,
					'offset'				=> -1,
					'post_type'				=> idocs_post_type(),
					'post_status'			=> $post_status,
					'order'					=> 'ASC',
					'orderby'				=> 'menu_order ID',
					'ignore_sticky_posts'	=> true,
					'hierarchical'			=> true,
					'tax_query'				=> array(
						array(
							'taxonomy' 	=> idocs_taxonomy(),
							'field'		=> 'term_id',
							'terms'		=> $term->term_id,
							'include_children' => true
						)
					)
				);
				$docs = get_posts( $args );
				
				if( !$docs ){
					continue;
				}
				
				echo $widget_title;	
				echo '<ul>';
				$settings = idocs_settings();
				echo walk_docs_tree($docs, 0, $post->ID, array(
					'depth' 		=> 0, 
					'show_date' 	=> $settings['menu_date'],
					'date_format' 	=> get_option('date_format'),
					'child_of' 		=> 0, 
					'exclude' 		=> '',
					'title_li' 		=> __('Pages'), 
					'echo' 			=> 1,
					'authors' 		=> '', 
					'sort_column' 	=> 'menu_order, post_title',
					'link_before' 	=> '', 
					'link_after' 	=> '', 
					'walker' 		=> '',
				));
				remove_filter('page_css_class', 'idocs_page_class_walker', 999);
				echo '</ul>';
			}
		}
		
		echo $after_widget;	
		wp_reset_postdata();	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::update()
	 */
	function update($new_instance, $old_instance){

		$instance = $old_instance;
		$instance['idocs_widget_title'] 	= $new_instance['idocs_widget_title'];
		$instance['idocs_hierarchy'] 		= isset( $new_instance['idocs_hierarchy'] );
		$instance['idocs_replace_title'] 	= isset( $new_instance['idocs_replace_title'] );		
		return $instance;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::form()
	 */
	function form( $instance ){
		
		$defaults 	= $this->get_defaults();;
		$options 	= wp_parse_args( (array)$instance, $defaults );
		
		?>
	<div class="idocs-player-settings-options">
		<p><?php _e('Widget will be visible only on single document post display, plugin taxonomy or documentation archive pages.', 'idocs');?></p>	
		<p>
			<label for="<?php echo  $this->get_field_id('idocs_widget_title');?>"><?php _e('Title', 'idocs');?>: </label>
			<input type="text" name="<?php echo  $this->get_field_name('idocs_widget_title');?>" id="<?php echo  $this->get_field_id('idocs_widget_title');?>" value="<?php echo $options['idocs_widget_title'];?>" class="widefat" />
		</p>		
		<p>
			<input class="checkbox idocs_hierarchy" type="checkbox" name="<?php echo $this->get_field_name('idocs_hierarchy');?>" id="<?php echo $this->get_field_id('idocs_hierarchy')?>"<?php idocs_check((bool)$options['idocs_hierarchy']);?> />
			<label for="<?php echo $this->get_field_id('idocs_hierarchy')?>"><?php _e('Show hierarchy', 'idocs');?></label>
		</p>
		<p>
			<input class="checkbox idocs_replace_title" type="checkbox" name="<?php echo $this->get_field_name('idocs_replace_title');?>" id="<?php echo $this->get_field_id('idocs_replace_title')?>"<?php idocs_check((bool)$options['idocs_replace_title']);?> />
			<label for="<?php echo $this->get_field_id('idocs_replace_title')?>"><?php _e('Replace title with category name', 'idocs');?></label>
		</p>
	</div>	
		<?php 		
	}
	
	/**
	 * Default widget values
	 */
	private function get_defaults(){
		$defaults = array(
			'idocs_widget_title' 	=> __('Documentation categories', 'idocs'),
			'idocs_hierarchy'		=> false,
			'idocs_replace_title'	=> true
		);
		return $defaults;
	}
}