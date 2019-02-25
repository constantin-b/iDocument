<?php

/**
 * Class iDocument_Meta_Boxes
 */
class iDocument_Meta_Boxes{
	/**
	 * @var iDocument
	 */
	private $main;

	/**
	 * iDocument_Meta_Boxes constructor.
	 *
	 * @param iDocument $main
	 */
	public function __construct( iDocument $main ) {
		$this->main = $main;
	}

	/**
	 * Initialize the meta boxes
	 */
	public function init(){
		// table of contents meta box options
		add_meta_box(
			'idocs-table-of-contents',
			__('Table of contents', 'idocs'),
			array( $this, 'meta_box_table_of_contents' ),
			$this->main->get_post_type(),
			'side',
			'core'
		);

		add_meta_box(
			'idocs-post-shortcode-link',
			__('Shortcode link', 'idocs'),
			array( $this, 'meta_box_shortcode_link' ),
			$this->main->get_post_type(),
			'side',
			'core'
		);

		add_meta_box(
			'idocs-related-posts',
			__('Related posts', 'idocs'),
			array( $this, 'meta_box_related' ),
			$this->main->get_post_type(),
			'normal',
			'core'
		);
	}

	/**
	 * Table of contents meta box output
	 * @param object $post - current post object
	 */
	public function meta_box_table_of_contents( $post ){
		$options = idocs_get_post_options( $post->ID );
		wp_nonce_field( 'idocs-save-toc-options', 'idocs_toc_nonce' );
		?>
		<p>
			<input type="checkbox" name="toc_show" value="1" <?php if( $options['toc_show'] ):?> checked="checked"<?php endif;?> />
			<label for=""><?php _e('Show table of contents', 'idocs');?></label>
		</p>

		<p><label for=""><?php _e('Create table of contents from heading', 'idocs')?>:</label></p>
		<input type="text" name="toc_heading" value="<?php echo $options['toc_heading'];?>" size="2" />

		<p><label for=""><?php _e('Show table if h tags found exceeds', 'idocs');?>:</label></p>
		<input type="text" name="toc_min_headings" value="<?php echo $options['toc_min_headings'];?>" size="2" />
		<?php
	}

	/**
	 * Displays shortcode link
	 * @param unknown $post
	 */
	public function meta_box_shortcode_link( $post ){
		$terms = wp_get_post_terms( $post->ID, $this->main->get_taxonomy() );
		$shortcode = '[idocs_url post_id="%s" term="%s" target="_self" rel="" class="" text="" inline_target=""]';
		echo '<p>';
		printf( $shortcode, $post->post_name, $terms[0]->slug );
		echo '</p>';
	}

	/**
	 * Related docs metabox output
	 * @param unknown $post
	 */
	public function meta_box_related( $post ){
		$args = array(
			'posts_per_page' => -1,
			'post__not_in' => array( $post->ID ),
			'post_type' => $this->main->get_post_type(),
			'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
			'orderby' => 'ID',
			'order' => 'ASC'
		);
		$query = new WP_Query( $args );

		$related = get_post_meta( $post->ID, '__related_docs', true );
		$post_ids = $related ? array_keys( $related ) : array();

		if( $query->have_posts() ){
			while( $query->have_posts() ){
				$query->the_post();

				printf( '<div style="float:left; border:1px #CCC solid; margin-right:10px; padding:4px 6px; backgruound:#FFF; margin-bottom:5px;"><label>%s <input type="checkbox" name="related_docs[%d]" value="1" %s /></label></div>',
					get_the_title(),
					get_the_ID(),
					( in_array( get_the_ID(), $post_ids ) ? 'checked="checked"' : '' )
				);

				wp_reset_postdata();
			}
		}

		echo '<div style="float:none; clear:both;display:block; position:relative;"></div>';
	}
}