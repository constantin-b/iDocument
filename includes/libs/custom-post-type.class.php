<?php
/**
 * 
 * Register post type and taxonomy and provide functionality for them
 * @author Constantin
 *
 */
class iDocument{
	/**
     * Post type name
	 * @var string
	 */
	private $post_type = 'documentation';
	/**
     * Taxonomy name
	 * @var string
	 */
	private $taxonomy  = 'documents';
	/**
     * Name of meta field holding the taxonomy order in menu
	 * @var string
	 */
	private $tax_meta_order = 'idocs_menu_order';
	/**
	 * @var iDocument_Meta_Boxes
	 */
	private $metabox;

	/**
	 * iDocument constructor.
	 */
	public function __construct(){

        $this->load_classes();

		// init action, register post type
		add_action('init', array($this, 'register_post'), 100);
		
		// modify post permalink to include taxonomy
		add_filter('post_type_link', array($this, 'modify_permalink'), 10, 2);
		
		// on archive pages order docs by menu order
		add_filter( 'pre_get_posts', array($this, 'reorder_tax_docs' ), 999 );

		// modify adjacent post query for docs post type to stay within the same category
		add_filter( 'get_previous_post_join', array($this, 'adjacent_post_join'), 10, 3);
		add_filter( 'get_previous_post_where', array($this, 'adjacent_post_where'), 10, 3);
		add_filter( 'get_next_post_join', array($this, 'adjacent_post_join'), 10, 3);
		add_filter( 'get_next_post_where', array($this, 'adjacent_post_where'), 10, 3);
		
		// modify post title to include the taxonomy name
		add_filter( 'single_post_title', array($this, 'prepend_taxonomy'), 10, 2 );
	}

	/**
	 * Loads the other classes that are needed
	 */
	private function load_classes(){
		// start admin
		include_once IDOCS_PATH . 'includes/libs/admin.class.php';
		new iDocument_Admin( $this );

		// start meta boxes
		include_once IDOCS_PATH . 'includes/libs/metaboxes.class.php';
		$this->metabox = new iDocument_Meta_Boxes( $this );
    }

	/**
	 * Register post type and taxonomy
	 */
	public function register_post(){
		
		$labels = array(
			'name' 					=> _x('Documents', 'Documents', 'idocs'),
	    	'singular_name' 		=> _x('Document', 'Document', 'idocs'),
	    	'add_new' 				=> _x('Add new', 'Add new document', 'idocs'),
	    	'add_new_item' 			=> __('Add new document', 'idocs'),
	    	'edit_item' 			=> __('Edit document', 'idocs'),
	    	'new_item'				=> __('New document', 'idocs'),
	    	'all_items' 			=> __('All documents', 'idocs'),
	    	'view_item' 			=> __('View', 'idocs'),
	    	'search_items' 			=> __('Search', 'idocs'),
	    	'not_found' 			=> __('No documents found', 'idocs'),
	    	'not_found_in_trash' 	=> __('No documents in trash', 'idocs'), 
	    	'parent_item_colon' 	=> '',
	    	'menu_name' 			=> __('Documents', 'idocs')
		);
		
		$args = array(
    		'labels' 				=> $labels,
    		'public' 				=> true,
			'exclude_from_search'	=> false,
    		'publicly_queryable' 	=> true,
			'show_in_nav_menus'		=> true,
		
    		'show_ui' 				=> true,
			'show_in_menu' 			=> true,
			'menu_position' 		=> 20,
			'menu_icon'				=> IDOCS_URL.'assets/back-end/images/menu-icon.png',	
		
    		'query_var' 			=> true,
    		'capability_type' 		=> 'post',
    		'has_archive' 			=> false, 
    		'hierarchical' 			=> true,
    		'rewrite'				=> array(
				'slug' 			=> $this->get_post_type().'/%'.$this->get_taxonomy().'%',
				'with_front' 	=> false
			),
			'register_meta_box_cb' => array( $this->metabox, 'init' ),
    		'supports' 			=> array( 
    			'title', 
    			'editor', 
    			'author', 
    			'thumbnail', 
    			'excerpt', 
    			/*'trackbacks',*/
				/*'custom-fields',*/
				'page-attributes', // for hierarchy
    			/*'comments',*/  
    			'revisions',
    			'post-formats' 
			),			
 		); 
 		
 		register_post_type($this->get_post_type(), $args);
  
  		// Add new taxonomy, make it hierarchical (like categories)
  		$cat_labels = array(
	    	'name' 					=> _x( 'Document categories', 'document', 'idocs' ),
	    	'singular_name' 		=> _x( 'Document category', 'document', 'idocs' ),
	    	'search_items' 			=> __( 'Search document category', 'idocs' ),
	    	'all_items' 			=> __( 'All document categories', 'idocs' ),
	    	'parent_item' 			=> __( 'Document category parent', 'idocs' ),
	    	'parent_item_colon'		=> __( 'Document category parent:', 'idocs' ),
	    	'edit_item' 			=> __( 'Edit document category', 'idocs' ), 
	    	'update_item' 			=> __( 'Update document category', 'idocs' ),
	    	'add_new_item' 			=> __( 'Add new document category', 'idocs' ),
	    	'new_item_name' 		=> __( 'Document category name', 'idocs' ),
	    	'menu_name' 			=> __( 'Document categories', 'idocs' ),
		); 	

		register_taxonomy($this->get_taxonomy(), array($this->get_post_type()), array(
			'public'			=> true,
    		'show_ui' 			=> true,
			'show_in_nav_menus' => true,
			'show_admin_column' => true,		
			'hierarchical' 		=> true,
			'rewrite' 			=> array( 
				'slug' 			=> $this->get_taxonomy(),
				'with_front'	=> false
			),
			'capabilities'		=> array('edit_posts'),		
    		'labels' 			=> $cat_labels,    		
    		'query_var' 		=> $this->get_taxonomy()
  		));  		
	}
	
	/**
	 * Modify permalink to include taxonomy
	 * 
	 * @param string $url
	 * @param object $post
	 */
	public function modify_permalink($url, $post) {
	
		// limit to certain post type. remove if not needed
	    if ( $post->post_type != $this->get_post_type() ) {
	        return $url;
	    }
	    
	    // fetches term
	    $term = get_the_terms( $post->ID, $this->get_taxonomy() );
	    if ($term && count($term)) {
	    	$term = array_pop($term);
	        // takes only 1st one
	        $term = $term->slug;	      	
	    }else{
	    	$term = 'uncategorized';	
	    }
	        
	    return str_replace('%'.$this->get_taxonomy().'%', $term, $url);
	}
	
	/**
	 * On single doc display, prepend taxonomy name to title
	 * 
	 * Enter description here ...
	 * @param unknown_type $title
	 * @param unknown_type $sep
	 * @param unknown_type $seplocation
	 */
	public function prepend_taxonomy( $post_title, $post ){
		if( $this->get_post_type() != $post->post_type ){
			return $post_title;
		}
		
		$terms = wp_get_post_terms( $post->ID, idocs_taxonomy(), array('fields'=>'names') );
		if($terms){
			return $terms[0].' - '.$post_title;
		}		
		return $post_title;
	}
	
	/**
	 * On taxonomy archive page, order docs by menu_order instead of publish date
	 */
	public function reorder_tax_docs($query){
		if ( is_tax( $this->get_taxonomy() ) && !is_admin() && $query->is_main_query() ){
			// get plugin settings
			$query->set( 'order', 'ASC' );
			$query->set( 'orderby', 'menu_order title' );						
		}	
		
		if( is_admin() ){
			global $pagenow;			
			
			if( 'edit.php' == $pagenow && ( isset( $_GET['post_type'] ) && $this->get_post_type() == $_GET['post_type'] ) &&  $query->is_main_query() ){
				$query->set( 'orderby', '' );
			}			
		}
		
		return $query;	
	}
	
	/**
	 * Modify join on adjacent post function to keep next/prev post navigation into the same category
	 * @param string $join
	 * @param bool $in_same_term
	 * @param array $excluded_terms
	 */
	public function adjacent_post_join($join, $in_same_term, $excluded_terms){
		global $post;
		if( $this->get_post_type() != $post->post_type ){
			return $join;
		}
		
		$term_array = wp_get_object_terms( $post->ID, $this->get_taxonomy(), array( 'fields' => 'ids' ) );
		if ( ! $term_array || is_wp_error( $term_array ) ){
			return $join;
		}	
		
		$join = "INNER JOIN wp_term_relationships AS tr 
				 ON p.ID = tr.object_id 				
				 INNER JOIN wp_term_taxonomy tt 
				 ON tr.term_taxonomy_id = tt.term_taxonomy_id 
				 AND tt.taxonomy = '{$this->get_taxonomy()}' 
				 AND tt.term_id IN (". implode( ',', array_map( 'intval', $term_array ) ) .") ";
		
		return $join;
	}
	
	/**
	 * Modify where on adjacent post function to keep next/prev post navigation into the same category
	 * @param string $where
	 * @param bool $in_same_term
	 * @param array $excluded_terms
	 */
	public function adjacent_post_where($where, $in_same_term, $excluded_terms){
		global $post;
		if( $this->get_post_type() != $post->post_type ){
			return $where;
		}
		
		$where.= " AND tt.taxonomy = '{$this->get_taxonomy()}'";
		return $where;
	}

	/**
	 * Return post type name - use function idocs_post_type() instead
	 */
	public function get_post_type(){
		return $this->post_type;
	}
	
	/**
	 * Return taxonomy - use function idocs_taxonomy() instead
	 */
	public function get_taxonomy(){
		return $this->taxonomy;
	}

	public function get_tax_meta_order(){
	    return $this->tax_meta_order;
    }
}
global $IDOCS;
$IDOCS = new iDocument();