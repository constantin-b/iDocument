<?php

/**
 * Utility function, returns plugin default settings
 */
function idocs_plugin_settings_defaults(){
	$defaults = array(
		'menu_date'				=> true, // post type is public or not
		'breadcrumbs'			=> true, // post type is public or not	
	);
	return $defaults;
}

/**
 * Get plugin settings
 */
function idocs_settings(){
	$defaults = idocs_plugin_settings_defaults();
	$option = get_option('_idocs_settings', $defaults);
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	return $option;
}

/**
 * Utility function, updates plugin settings
 */
function idocs_update_settings(){	
	
	$defaults = idocs_plugin_settings_defaults();
	foreach( $defaults as $key => $val ){
		if( is_numeric( $val ) ){
			if( isset( $_POST[ $key ] ) ){
				$defaults[ $key ] = (int)$_POST[ $key ];
			}
			continue;
		}
		if( is_bool( $val ) ){
			$defaults[ $key ] = isset( $_POST[ $key ] );
			continue;
		}
		
		if( isset( $_POST[ $key ] ) ){
			$defaults[ $key ] = $_POST[ $key ];
		}
	}
	
	update_option('_idocs_settings', $defaults);	
}

/**
 * Individual documentation post defaults
 */
function idocs_post_options_defaults(){
	$defaults = array(
		'toc_show' 			=> false,
		'toc_heading' 		=> 'h2',
		'toc_min_headings' 	=> 2
	);
	return $defaults;
}

/**
 * Return post meta or defaults
 * @param int $post_id
 */
function idocs_get_post_options( $post_id ){
	$post_id 	= absint( $post_id );
	$defaults 	= idocs_post_options_defaults();
	$option 	= (array) get_post_meta( $post_id, '_idocs_post_settings', true );
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	return $option;
}

/**
 * Utility function, updates post meta settings
 */
function idocs_update_post_options( $post_id ){	
	
	$post_id = absint( $post_id );
	
	$defaults = idocs_post_options_defaults();
	foreach( $defaults as $key => $val ){
		if( is_numeric( $val ) ){
			if( isset( $_POST[ $key ] ) ){
				$defaults[ $key ] = (int)$_POST[ $key ];
			}
			continue;
		}
		if( is_bool( $val ) ){
			$defaults[ $key ] = isset( $_POST[ $key ] );
			continue;
		}
		
		if( isset( $_POST[ $key ] ) ){
			$defaults[ $key ] = $_POST[ $key ];
		}
	}
	
	update_post_meta($post_id, '_idocs_post_settings', $defaults);	
}

/**
 * Register widgets.
 */
function idocs_load_widgets() {		
	include IDOCS_PATH.'includes/libs/menu.widget.class.php';
	register_widget( 'IDOCS_Menu_Widget' );	
}
add_action( 'widgets_init', 'idocs_load_widgets' );

/**
 * Displays checked argument in checkbox
 * @param bool $val
 * @param bool $echo
 */
function idocs_check( $val, $echo = true ){
	$checked = '';
	if( is_bool($val) && $val ){
		$checked = ' checked="checked"';
	}
	if( $echo ){
		echo $checked;
	}else{
		return $checked;
	}	
}

/**
 * Returns the post type
 */
function idocs_post_type(){
	global $IDOCS;
	return $IDOCS->get_post_type();
}
/**
 * Returns the post taxonomy
 */
function idocs_taxonomy(){
	global $IDOCS;
	return $IDOCS->get_taxonomy();
}

/**
 * Returns a list of most recent documents written
 * @param int $tag_id - category to retrieve for
 * @param int $limit - number of posts
 * @param string $before - append before post link
 * @param string $after - append after post link
 * @param bool $echo - echo output
 */
function idocs_recent_docs( $tag_id, $limit = 5, $before = '<li %s>', $after = '</li>', $echo = true ){

	$args = array(
		'posts_per_page' => $limit,
		'offset' => 0,
		'post_type' => idocs_post_type(),
		'post_status' => 'publish',
		'supress_filters' => true,
		'orderby' => 'post_date',
		'order' => 'DESC',
		'tax_query' => array(
			array(
				'taxonomy' => idocs_taxonomy(),
				'field' => 'term_id',
				'terms' => $tag_id
			)
		)
	);
	$posts = get_posts( $args );
	if( !$posts ){
		return;	
	}

	$output = '';
	foreach( $posts as $post ){
		$title = apply_filters( 'the_title', $post->post_title );
		$permalink = get_permalink( $post->ID );
		$link = sprintf( '<a href="%s" title="%s">%s</a>', $permalink, esc_attr( $post->post_title ), $title );		
		$output .= sprintf( $before, 'id="idocs-item-' . $post->post_id . '"' ) . $link . $after;
	}
	
	if( $echo ){
		echo $output;
	}
	return $output;
}

/**
 * Add breadcrumb on doc pages
 * @param unknown_type $content
 */
function idocs_breadcrumb( $before = '<div class="idocs-breadcrumbs">', $after = '</div>' ){
	// if isn't post type document, return
	if( !is_singular( idocs_post_type() ) ){
		return false;
	}
	
	global $post;
	$parents = get_ancestors( $post->ID, idocs_post_type() );
	
	$template = '<a href="%1$s" title="%2$s">%2$s</a>';
	$links = array(
		0 => sprintf( $template, get_bloginfo('url'), __('Home', 'idocs') )
	);
	
	if( $parents && is_array($parents) ){		
		$terms  = wp_get_post_terms( $parents[0], idocs_taxonomy() );	
		if( $terms && !is_wp_error( $terms ) ){
			
			$cats = get_ancestors( $terms[0]->term_id, idocs_taxonomy() );
			if( $cats ){
				$cats = array_reverse( $cats );
				foreach( $cats as $cat ){
					$term = get_term($cat, idocs_taxonomy());
					$links[] = sprintf( $template, get_term_link( $term ), $term->name );
				}
			}			
			
			$links[] = sprintf( $template, get_term_link( $terms[0] ), $terms[0]->name );
		}
		
		$parents = array_reverse($parents);
		foreach( $parents as $doc_id ){
			$links[] = sprintf( $template, get_permalink( $doc_id ), get_the_title( $doc_id ) );
		}
	}else{
		$terms  = wp_get_post_terms( $post->ID, idocs_taxonomy() );	
		if( $terms && !is_wp_error( $terms ) ){
			
			$cats = get_ancestors( $terms[0]->term_id, idocs_taxonomy() );
			if( $cats ){
				$cats = array_reverse( $cats );
				foreach( $cats as $cat ){
					$term = get_term($cat, idocs_taxonomy());
					$links[] = sprintf( $template, get_term_link( $term ), $term->name );
				}
			}
			
			$links[] = sprintf( $template, get_term_link( $terms[0] ), $terms[0]->name );
		}
	}
	
	$links[] = $post->post_title;	
	$html = $before. implode(' / ', $links) . $after. "\n";	
	return $html;
}

/**
 * Filter the content to add the breadcrumb
 * @param string $content
 */
function idocs_content_breadcrumb( $content ){
	$settings = idocs_settings();
	if( !is_singular( idocs_post_type() ) || !$settings['breadcrumbs'] ){
		return $content;
	}	
	return idocs_breadcrumb().$content;	
}

add_filter('the_content', 'idocs_content_breadcrumb');

/**
 * Retrieve HTML list content for page list.
 *
 * @uses Walker_Page to create HTML list content.
 * @since 2.1.0
 * @see Walker_Page::walk() for parameters and return description.
 */
function walk_docs_tree($pages, $depth, $current_page, $r) {
	if ( empty($r['walker']) )
		$walker = new Walker_Docs;
	else
		$walker = $r['walker'];

	foreach ( (array) $pages as $page ) {
		if ( $page->post_parent )
			$r['pages_with_children'][ $page->post_parent ] = true;
	}
	
	$args = array($pages, $depth, $r, $current_page);
	return call_user_func_array(array($walker, 'walk'), $args);
}

/**
 * Create HTML list of pages.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class Walker_Docs extends Walker {
	/**
	 * @see Walker::$tree_type
	 * @since 2.1.0
	 * @var string
	 */
	var $tree_type = 'document';

	/**
	 * @see Walker::$db_fields
	 * @since 2.1.0
	 * @todo Decouple this.
	 * @var array
	 */
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');

	/**
	 * @see Walker::start_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 * @param array $args
	 */
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class='children'>\n";
	}

	/**
	 * @see Walker::end_lvl()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 * @param array $args
	 */
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth )
			$indent = str_repeat("\t", $depth);
		else
			$indent = '';

		extract($args, EXTR_SKIP);
		$css_class = array(idocs_post_type().'_item', idocs_post_type().'-item-'.$page->ID);

		if( isset( $args['pages_with_children'][ $page->ID ] ) )
			$css_class[] = 'page_item_has_children';

		if ( !empty($current_page) ) {
			$_current_page = get_post( $current_page );
			if ( in_array( $page->ID, $_current_page->ancestors ) )
				$css_class[] = 'current_'.idocs_post_type().'_ancestor';
			if ( $page->ID == $current_page )
				$css_class[] = 'current_'.idocs_post_type().'_item';
			elseif ( $_current_page && $page->ID == $_current_page->post_parent )
				$css_class[] = 'current_page_parent';
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_'.idocs_post_type().'_parent';
		}

		$css_class = implode( ' ', $css_class );

		if ( '' === $page->post_title )
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );

		if( 'publish' != $page->post_status && current_user_can( 'manage_options' ) ){
			$page->post_title = '<em>' . $page->post_title . '</em><sup>(' . $page->post_status . ')</sup>';
		}

		/** This filter is documented in wp-includes/post-template.php */
		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink( $page->ID ) . '" title="' . esc_attr( apply_filters( 'the_title', $page->post_title, $page->ID ) ) . '">' . $link_before . apply_filters( 'the_title', $page->post_title, $page->ID ) . $link_after . '</a>';

		if ( !empty($show_date) ) {
			if ( 'modified' == $show_date )
				$time = $page->post_modified;
			else
				$time = $page->post_date;

			$output .= " " . '<span class="'.idocs_post_type().'-date">' . mysql2date($date_format, $time) . '</span>';
		}
		
		// include the post excerpt
		if( isset($include_excerpt) && true === $include_excerpt ){
			if( !empty( $page->post_excerpt ) ){
				$output .= '<p>' . $page->post_excerpt . '</p>';
			}
		}		
	}

	/**
	 * @see Walker::end_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object. Not used.
	 * @param int $depth Depth of page. Not Used.
	 * @param array $args
	 */
	function end_el( &$output, $page, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

}