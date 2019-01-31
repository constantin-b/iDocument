<?php

class iDocs_Shortcodes{
	
	private $shortcodes = array();
	
	/**
	 * Constructor, initiates the shortcodes
	 */
	public function __construct(){
		// holds all shortcodes
		$this->shortcodes = array(
			'idocs_archive' => array(
				'callback' => array( $this, 'archive' ),
				'params' => $this->idocs_archive_defaults('all'),
				'description' => __('Display a list of posts that belong to a certain iDocs category.', 'idocs')
			),
			'idocs_url' => array( 
				'callback' => array( $this, 'post' ),
				'params' => $this->idocs_url_defaults('all'),
				'description' => __('Display a single URL for a given iDocs post (id or slug).', 'idocs')
			),
			'idocs_archive_url' => array(
				'callback' => array( $this, 'archive_url' ),
				'params' => $this->idocs_archive_url_defaults('all'),
				'description' => __('Display a link to an archive page based on term ID or slug.', 'idocs')
			)
		);
		// add the shortcodes
		foreach( $this->shortcodes as $tag => $data ){
			add_shortcode($tag, $data['callback']);
		}
	}
	
	/**
	 * Holds the default values for shortcode idocs_archive
	 * @param string $return - return only values or complete with description
	 */
	private function idocs_archive_defaults( $return = 'values' ){
		$defaults = array(
			'category' => array(
				'desc' 	=> __('The iDocs category ID to return posts for.', 'idocs'),
				'val' 	=> false
			),
			'include_term' => array(
				'desc' => __('Display the list including the term name or just the posts assigned to it.', 'idocs'),
				'val' => false
			),
			'add_excerpt' => array(
				'desc' => __('Include post excerpt for every post from the archive.', 'idocs'),
				'val' => false 
			)
 		);
		if( 'values' == $return ){
			$result = array();
			foreach( $defaults as $k => $v ){
				$result[$k] = $v['val'];
			}
			return $result;
		}
		
		return $defaults;
	}
	
	/**
	 * Shortcode for displaying a docs category with a complete list of links to posts assigned to it
	 * 
	 * Usage: [idocs_archive category="TAXONOMY ID"]
	 * 
	 * @param array $atts
	 */
	public function archive( $atts ){
		// extract the shortcode attributes
		extract( shortcode_atts( $this->idocs_archive_defaults( 'values' ), $atts ) );
		
		if( !$category || empty( $category ) ){
			return;
		}
		
		if( is_numeric( $category ) ){
			$field = 'id';
		}
		if( is_string( $category ) ){
			$field = 'slug';
		}  
		
		$term = get_term_by( $field, $category, idocs_taxonomy() );
		// term error, halt script
		if( is_wp_error($term) || !$term ){
			if( current_user_can( 'manage_options' ) ){
				return sprintf( __( 'iDocs shortcode error: taxonomy for id %s does not exist.' ), $term );
			}
			return;
		}
		// term not specified, halt script
		if( !$term ){
			if( current_user_can( 'manage_options' ) ){
				return __('iDocs shortcode error: please specify a category.');
			}
			return;
		}
		// get the posts
		$args = array(
			'post_type' 		=> idocs_post_type(),
			'post_status'		=> 'publish',
			'posts_per_page' 	=> -1,
			'order' 			=> 'ASC',
			'orderby' 			=> 'menu_order',
			'hierarchical'		=> true,
			'tax_query'			=> array(
				'relation' => 'AND',
				array(
					'taxonomy' 			=> idocs_taxonomy(),
					'include_children'	=> true,
					'field'				=> 'id',
					'terms'				=> array( $term->term_id )				
				)
			)
		);
		$query = new WP_Query( $args );
		// no posts, halt script
		if( 0 == $query->post_count ){
			if( current_user_can( 'manage_options' ) ){
				return sprintf(__('iDocs shortcode error: no published posts found for term %s.'), $term->name);
			}			
			return;
		}
		
		$output = '<ul class="idocs-category archive parent">';
		if( $include_term ){
			$output.= sprintf( '<li class="idocs-title">%s</li>', $term->name);		
		}
		$output.= walk_docs_tree($query->posts, 0, false, array(
			'depth' => 0, 
			'show_date' => false,
			'include_excerpt' => (bool)$add_excerpt,
			'date_format' => get_option('date_format'),
			'child_of' => 0, 
			'exclude' => '',
			'title_li' => __('Pages'), 
			'echo' => 1,
			'authors' => '', 
			'sort_column' => 'menu_order, post_title',
			'link_before' => '', 
			'link_after' => '', 
			'walker' => '',
		));
		
		$output.= '</ul>';
		return $output;
	}
	
	/**
	 * Holds the defaults for shortcode idocs_url
	 * 
	 * @param string $return: return only values for each key of complete with description
	 */
	private function idocs_url_defaults( $return = 'values' ){
		$defaults = array(
			'post_id' 	=> array(
				'desc' => __('ID of the post to retrieve the link for. You can also pass the post slug (post_name in DB).', 'idocs'),
				'val' => false
			),
			'term'		=> array(
				'desc' => __('Optional, post taxonomy slug. Useful when passing post_id as post slug to avoid same name collisions.', 'idocs'),
				'val' => false
			),
			'target'	=> array(
				'desc' => __('Optional, link taget. Can have values: _blank, _self.', 'idocs'),
				'val' => '_self'
			),
			'rel'		=> array(
				'desc' => __('Optional, useful if nofollow is needed on links.', 'idocs'),
				'val' => false
			),
			'class'		=> array(
				'desc' => __('Optional, extra CSS classes to put on link.', 'idocs'),
				'val' => ''
			),
			'text'		=> array(
				'desc' => __('Optional, link text. If not set, the text will be the title of the post.', 'idocs'),
				'val' => false
			),
			'inline_target' => array(
				'desc' => __('A page ID to navigate to.', 'idocs'),
				'val' => false
			),
			'title' => array(
				'desc' => __('Optional, link title. If not set, the link title attribute will be set to the post title.', 'idocs'),
				'val' => false
			)
		);
		
		if( 'values' == $return ){
			$result = array();
			foreach( $defaults as $k => $v ){
				$result[$k] = $v['val'];
			}
			return $result;
		}
		
		return $defaults;
	}
	
	/**
	 * Shortcode to display a link for a given post ID
	 * 
	 * Usage: [idocs_url post_id="DOCS POST ID" target="optional, _self or _blank" rel="optional, can have any rel value for links"]
	 * 
	 * @param array $atts
	 */
	public function post( $atts ){
		// extract the shortcode attributes
		extract( shortcode_atts( $this->idocs_url_defaults( 'values' ) , $atts ) );
		
		// post id can also contain the post slug
		if( !is_numeric( $post_id ) && !empty( $post_id ) && is_string( $post_id ) ){			
			
			$args = array(
				'name' 			=> $post_id,
				'post_type' 	=> idocs_post_type(),
				'post_status' 	=> 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page' => -1
			);			
			$query = new WP_Query( $args );
			
			if( $query->post_count == 1 ){
				$post_id = $query->posts[0]->ID;
			}else if( $query->post_count > 1 ){
				if( $term && !empty( $term ) ){
					//$term = get_term_by('slug', $taxonomy, idocs_taxonomy());
					if( !is_wp_error($term) && $term ){
						foreach( $query->posts as $post ){
							if( has_term( $term, idocs_taxonomy(), $post ) ){
								$post_id = $post->ID;	
								break;
							}
						}						
					}			
				}else{
					if( current_user_can('manage_options') ){
						return sprintf( __('A total of %d posts were found. To have the shortcode return correctly, you could try and set the term parameter.', 'idocs'), $query->post_count );
						return;
					} 
				}				
			}	
		}
		
		$post_id = absint($post_id);
		if( !$post_id ){
			if( current_user_can( 'manage_options' ) ){
				return __('iDocs shortcode error: no post ID');
			}
			return;
		} 
		
		$post = get_post( $post_id );
		if( !$post || is_wp_error( $post ) || 'publish' !== $post->post_status || idocs_post_type() !== $post->post_type ){
			if( current_user_can( 'manage_options' ) ){
				return sprintf( __('iDocs shortcode error: post id %s not found (maybe not published or wrong post type).'), $post_id );
			}
			return;
		}
		
		$rel = $rel ? ' rel="'.$rel.'"' : '';
		if( !$text ){
			$text = apply_filters('the_title', $post->post_title);
		}
		$title = $title ? esc_attr( $title ) : esc_attr( $post->post_title );
		$permalink = get_permalink( $post->ID );
		
		if( $inline_target ){
			$permalink .= '#' . $inline_target;
		}
		
		return sprintf('<a href="%s" class="idocs docs-single-page-url %s" title="%s" target="%s"%s>%s</a>',
			$permalink,
			$class,
			$title,
			$target,
			$rel,
			$text
		);		
	}
	
	/**
	 * Defaults for plugin shortcode idocs_archive_url
	 * @param string $return - return only values (values) or complete with description (all)
	 */
	private function idocs_archive_url_defaults( $return = 'values' ){
		$defaults = array(
			'term_id' 	=> array(
				'desc' => __('ID of the term to retrieve the link for. You can also pass the term slug.', 'idocs'),
				'val' => false
			),
			'target'	=> array(
				'desc' => __('Optional, link taget. Can have values: _blank, _self.', 'idocs'),
				'val' => '_self'
			),
			'rel'		=> array(
				'desc' => __('Optional, useful if nofollow is needed on links.', 'idocs'),
				'val' => false
			),
			'class'		=> array(
				'desc' => __('Optional, extra CSS classes to put on link.', 'idocs'),
				'val' => ''
			),
			'text'		=> array(
				'desc' => __('Optional, link text. If not set, the text will be the title of the post.', 'idocs'),
				'val' => false
			),
			'before' => array(
				'desc' => __('What to add before the link. Useful to wrap the link into a different HTML element.', 'idocs'),
				'val' => ''
			),
			'after' => array(
				'desc' => __('What to add after the link. Useful to close the wrapping HTML element opened in parameter before', 'idocs'),
				'val' => ''
			),
			'include_desc' => array(
				'desc' => __('Include the term description after the link output.', 'idocs'),
				'val' => false
			),
			'read_more' => array(
				'desc' => __('If description is included, it will have a read more link. This parameter sets the text of that link.', 'idocs'),
				'val' => __('read documentation...', 'idocs')
			)
		);
		
		if( 'values' == $return ){
			$result = array();
			foreach( $defaults as $k => $v ){
				$result[$k] = $v['val'];
			}
			return $result;
		}
		
		return $defaults;
	}
	
	/**
	 * Callback function for plugin shortcode idocs_archive_url. Returns a permalink to archive url for a given term.
	 * @param array $atts
	 */
	public function archive_url( $atts ){
		// extract the shortcode attributes
		extract( shortcode_atts( $this->idocs_archive_url_defaults( 'values' ) , $atts ) );
		
		if( !$term_id || empty( $term_id ) ){
			if( current_user_can('manage_options') ){
				return __('iDocs error: no term specified ( field term_id is empty ).', 'idocs');
			}	
			return;
		}
		
		if( is_numeric( $term_id ) ){
			$field = 'id';
		}else if( is_string( $term_id ) ){
			$field = 'slug';
		}
		
		$term = get_term_by( $field, $term_id, idocs_taxonomy() );
		if( is_wp_error( $term ) || !$term ){
			if( current_user_can('manage_options') ){
				return sprintf( __('iDocs error: term not found ( %s ).', 'idocs'), $term_id);
			}	
			return;
		}
		
		$term_link = get_term_link( $term->term_id, idocs_taxonomy() );
		
		$rel = $rel ? ' rel="'.$rel.'"' : '';
		if( !$text ){
			$text = apply_filters('the_title', $term->name);
		}
		$title = __('Online documentation: ').esc_attr( $term->name );
		
		$desc = '';
		$rm = '';
		if( $include_desc ){
			$rm = sprintf('&nbsp;<a href="%s" title="%s" target="%s"%s>%s</a>',
				$term_link,
				$title,
				$target,
				$rel,
				$read_more
			);
			if( !empty( $term->description ) ){
				$desc = '<p>'.$term->description.$rm.'</p>';
			}
		}
		
		return sprintf('%s<a href="%s" class="idocs docs-archive-page-url %s" title="%s" target="%s"%s>%s</a>%s%s',
			$before,
			$term_link,
			$class,
			$title,
			$target,
			$rel,
			$text,
			$after,
			$desc
		);		
	}
	
	/**
	 * Returns the defaults for shortcode idocs_url including the parameters description
	 */
	public function get_shortcodes(){
		return $this->shortcodes;
	}
	
}
$IDOCS_SHORCODES = new iDocs_Shortcodes();