<?php
/*
Plugin Name: iDocument - info docs
Plugin URI: http://www.constantinb.com/project/wordpress-idocument/
Description: Documentation  plugin.
Author: Constantin Boiangiu
Version: 1.0
Author URI: http://www.constantinb.com
*/

define( 'IDOCS_PATH'		, plugin_dir_path(__FILE__) );
define( 'IDOCS_URL'			, plugin_dir_url(__FILE__) );
define( 'IDOCS_VERSION'		, '1.0');

include_once IDOCS_PATH.'includes/functions.php';
include_once IDOCS_PATH.'includes/shortcodes.php';
include_once IDOCS_PATH.'includes/libs/custom-post-type.class.php';

/**
 * Plugin activation; register permalinks for docs
 */
function idocs_activation_hook(){
	global $IDOCS;
	if( !$IDOCS ){
		return;
	}
	// register custom post
	$IDOCS->register_post();
	// create rewrite ( soft )
	flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'idocs_activation_hook');

/**
 * Templating class
 */
class iDocs_Templates{
	
	private $theme = false;
	
	/**
	 * Class constructor
	 */
	public function __construct(){
		$active_theme = wp_get_theme();
		if( is_object( $active_theme ) ){
			// check if it's child theme			
			if( is_object( $active_theme->parent() ) ){
				// set theme to parent
				$active_theme = $active_theme->parent();
			}
		}		
		$this->theme = strtolower( sanitize_file_name( $active_theme->name ) );		
		
		// register the sidebar
		$this->register_sidebar();
		/**
		 * Filter archive template to add the plugin template instead of the default one
		 * Filter is in function get_query_template() from file wp-includes/template.php, line 23
		 */
		add_filter('archive_template', array($this, 'archive_template'), 10, 1);
		/**
		 * Filter single template to add the plugin template instead of the default one
		 * Filter is in function get_query_template() from file wp-includes/template.php, line 23
		 */
		add_filter('single_template', array($this, 'single_template'), 10, 1);
		// order docs in archive by menu order and ID
		add_filter('pre_get_posts', array($this, 'archive_orderby'), 99999, 1);
		
		add_filter('the_content', array( $this, 'table_of_contents' ), 99999, 1 );
		
		add_filter('the_content', array( $this, 'related_docs' ), 99998, 1 );
	}

	/**
	 * Display table of contents on iDocs single posts
	 *
	 * @param string $content
	 *
	 * @return mixed|string
	 */
	public function table_of_contents( $content ){
		if( !is_singular( idocs_post_type() ) ){
			return $content;
		}
		
		global $post;
		$options = idocs_get_post_options( $post->ID );
		if( !$options['toc_show'] ){
			return $content;
		}
		
		if( !preg_match_all('/(<' . $options['toc_heading'] . '[^>]*>).*<\/' . $options['toc_heading'] . '>/msuU', $content, $matches, PREG_SET_ORDER) ){
			return $content;
		}
		
		if( !$matches || count( $matches ) < absint( $options['toc_min_headings'] ) ){
			return $content;
		}
		
		$find 		= array();
		$replace 	= array();
		$anchors	= array();
		
		foreach( $matches as $key => $match ){
			$find[] = $match[0];
			$replace[] = str_replace(
				array(
					'<' . $options['toc_heading'] . '>',
					'</' . $options['toc_heading'] . '>'
				),
				array(
					'<' . $options['toc_heading'] . '>' . '<span id="idocs-toc-link-' . $key . '">',
					'</' . $options['toc_heading'] . '>' . '</span>'
				),
				$match[0]
			);
			$anchors[] = '<a href="#idocs-toc-link-' . $key . '">' . strip_tags( $match[0] ) . '</a>';
		}
		
		$content = str_replace( $find , $replace, $content );
		
		$toc = '<div class="idocs-toc">';
		$toc.= '<div class="idocs-toc-title"><h2>' . __('Contents', 'idocs') . '</h2></div>';
		$toc.= '<ol class="idocs-toc-list"><li>';
		$toc.= implode( '</li><li>', $anchors );		
		$toc.= '</li></ol>';
		$toc.= '</div>';
		
		return $toc . $content;		
	}

	/**
	 * Callback for post content filter that adds related docs after the content is displayed
	 *
	 * @param $content
	 *
	 * @return string|void
	 */
	public function related_docs( $content ){
		if( !is_singular( idocs_post_type() ) ){
			return $content;
		}

		global $post;
		$related = get_post_meta( $post->ID, '__related_docs', true );

		if( $related ){
			$posts = get_posts(
				array(
					'posts_per_page' => -1,
					'include' => array_keys( $related ),
					'post_status' => 'publish',
					'post_type' => idocs_post_type(),
					'orderby' => 'ID',
					'order' => 'ASC'
				)
			);
			
			if( $posts ){
				$output = sprintf( '<h2>%s</h2>', __( 'Related ' ) );
				$output .= '<ul>';
				foreach( $posts as $p ){
					$output .= sprintf( '<li><a href="%1$s" title="%2$s">%2$s</a></li>',
						get_permalink( $p->ID ),
						esc_attr( $p->post_title )
					);
				}
				$output .= '</ul>';
				$content .= $output;
			}			
		}
		
		return $content;
	}
	
	/**
	 * Register sidebar for plugin post type
	 */
	private function register_sidebar(){
		register_sidebar( array(
		    'name'         => __( 'iDocs sidebar' ),
		    'id'           => 'idocs-sidebar',
		    'description'  => __( 'Displays only on iDocs archive and single doc display.' ),
		    'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		) );
	}	
	
	/**
	 * Checks if current template is taxonomy archive and returns the template from within the plugin
	 * instead of displaying the normal one.
	 * 
	 * @param string $template - path to template
	 */
	public function archive_template( $template ){
		if( !is_tax( idocs_taxonomy() ) ){
			return $template;
		}
		
		$plugin_template = IDOCS_PATH.'theme/'.$this->theme.'/archive.php';
		if( file_exists( $plugin_template ) ){
			$this->enqueue_styles();
			return $plugin_template;
		}
		
		return $template;
	}
	
	/**
	 * Checks if current template is single idocs post and returns the template from within the plugin
	 * instead of the default.
	 * @param string $template - path to template
	 */
	public function single_template( $template ){
		if( !is_singular(idocs_post_type()) ){
			return $template;
		}
		
		$plugin_template = IDOCS_PATH.'theme/'.$this->theme.'/single.php';
		if( file_exists( $plugin_template ) ){
			$this->enqueue_styles();
			return $plugin_template;
		}
		
		return $template;
	}
	
	/**
	 * Order docs by menu_order or ID
	 * @param object $query
	 */
	public function archive_orderby( $query ){
		if( is_admin() || !is_tax( idocs_taxonomy() ) || !$query->is_main_query() ){
			return $query;
		}
		
		$query->set( 'orderby', 'menu_order ID' );
	    $query->set( 'order', 'ASC' );
		return $query;
	}
	
	/**
	 * Add plugin template styling
	 */
	private function enqueue_styles(){
		wp_enqueue_style(
			'idocs-styling',
			IDOCS_URL.'theme/'.$this->theme.'/style.css',
			array('dashicons'),
			'1.0'
		);	
	}
}

function idocs_init(){
	new iDocs_Templates();
}
add_action('init', 'idocs_init');