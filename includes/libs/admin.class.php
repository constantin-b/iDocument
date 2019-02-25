<?php

class iDocument_Admin{
	/**
	 * @var iDocument
	 */
	private $main;

	/**
	 * Idocs_Admin constructor.
	 *
	 * @param iDocument $main
	 */
	public function __construct( iDocument $main ) {
		if( is_admin() ){
			$this->main = $main;
			$this->init();
		}
	}

	/**
	 * Initialize hooks
	 */
	private function init(){
		// add extra menu pages
		add_action('admin_menu', array( $this, 'menu_pages' ), 1);

		// add columns to posts table
		add_filter('manage_edit-' . $this->main->get_post_type() . '_columns', array( $this, 'extra_columns' ));
		add_action('manage_' . $this->main->get_post_type() . '_posts_custom_column', array($this, 'output_extra_columns'), 10, 2);

		// document save action
		add_action('save_post_' . $this->main->get_post_type(), array( $this, 'save_document' ), 10, 3);

		// taxonomy
		add_action( $this->main->get_taxonomy() . '_add_form_fields', array( $this, 'taxonomy_fields_add' ) );
		add_action( $this->main->get_taxonomy() . '_edit_form_fields', array( $this, 'taxonomy_fields_edit' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'tax_quick_edit' ), 10, 3 );

		add_action( 'edited_' . $this->main->get_taxonomy(), array( $this, 'taxonomy_save_fields' ) );
		add_action( 'created_' . $this->main->get_taxonomy(), array( $this, 'taxonomy_save_fields' ) );

		// taxonomy columns
		add_filter( 'manage_' . $this->main->get_taxonomy() . '_custom_column', array( $this, 'tax_column' ), 10, 3 );
		add_filter('manage_edit-'. $this->main->get_taxonomy() .'_columns', array( $this, 'add_tax_columns' ) );
	}

	/**
	 * Add extra menu pages
	 */
	public function menu_pages(){

		$settings = add_submenu_page(
			'edit.php?post_type=' . $this->main->get_post_type(),
			__('Settings', 'idocs'),
			__('Settings', 'idocs'),
			'manage_options',
			'idocs_settings',
			array($this, 'plugin_settings'));

		$shortcodes = add_submenu_page(
			'edit.php?post_type=' . $this->main->get_post_type(),
			__('Shortcodes', 'idocs'),
			__('Shortcodes', 'idocs'),
			'manage_options',
			'idocs_shortcodes',
			array($this, 'plugin_shortcodes'));

		add_action( 'load-'  .$settings, array( $this, 'plugin_settings_onload' ) );
	}

	/**
	 * Output plugin settings page
	 * @internal
	 */
	public function plugin_settings(){
		$options = idocs_settings();
		include IDOCS_PATH.'views/plugin_settings.php';
	}

	/**
	 * Process plugin settings
	 * @internal
	 */
	public function plugin_settings_onload(){
		if( isset( $_POST['idocs_wp_nonce'] ) ){
			if( check_admin_referer('idocs-save-plugin-settings', 'idocs_wp_nonce') ){
				idocs_update_settings();
			}
		}
	}

	/**
	 * Output shortcodes page
	 * @internal
	 */
	public function plugin_shortcodes(){
		global $IDOCS_SHORCODES;
		$shortcodes = $IDOCS_SHORCODES->get_shortcodes();

		include IDOCS_PATH.'views/plugin_shortcodes.php';
	}

	/**
	 * Extra columns in list table
	 * @param array $columns
	 */
	public function extra_columns( $columns ){

		$cols = array();
		foreach( $columns as $c => $t ){
			$cols[$c] = $t;
			if( 'title' == $c ){
				$cols['menu_order'] = __('Order', 'idocs');
			}
		}
		return $cols;
	}

	/**
	 * Extra columns in list table output
	 * @param string $column_name
	 * @param int $post_id
	 */
	public function output_extra_columns($column_name, $post_id){
		switch( $column_name ){
			case 'menu_order':
				$post = get_post( $post_id );
				echo $post->menu_order;
				break;
		}
	}

	/**
	 * Store custom post options
	 * @param int $post_id
	 * @param object $post
	 * @param bool $update
	 */
	public function save_document( $post_id, $post, $update ){
		if( !current_user_can('edit_post', $post_id) ){
			wp_die( __('You are not allowed to do this.', 'idocs' ) );
		}

		if( isset( $_POST['idocs_toc_nonce'] ) ){
			check_admin_referer( 'idocs-save-toc-options', 'idocs_toc_nonce' );
			idocs_update_post_options( $post_id );

			if( isset( $_POST['related_docs'] ) ){
				update_post_meta( $post_id, '__related_docs', $_POST['related_docs'] );
			}
		}
	}

	/**
	 * @internal
	 * @param $term
	 *
	 */
	public function taxonomy_fields_add( $term ){
		?>
		<?php wp_nonce_field( 'idocs-set-menu-order', 'idocs_nonce' );?>
		<div class="form-field term-doc-page-wrap">
			<label for="doc-page"><?php _e( 'Page ID', 'idocs' );?></label>
			<input name="doc_page" id="doc-page" type="text" value="0" size="40" />
			<p><?php _e( 'Page ID that the documentation category belongs to. If filled, when displaying sidebar menu for this main category, it will display a list of page + any subpages set for it.', 'idocs' );?></p>
		</div>
		<div class="form-field term-doc-order-wrap">
			<label for="doc_menu_order"><?php _e( 'Menu order', 'idocs' );?></label>
			<input name="doc_menu_order" id="doc_menu_order" type="text" value="0" size="40" />
			<p><?php _e( 'Order in menu widget', 'idocs' );?></p>
		</div>
		<?php
	}

	/**
	 * @internal
	 * @param $term
	 */
	public function taxonomy_fields_edit( $term ){
		$order = get_term_meta( $term->term_id, $this->main->get_tax_meta_order(), true );
		if( !$order ){
			$order = 0;
		}
		?>
		<tr class="form-field term-doc-order-wrap">
			<th scope="row"><label for="doc_menu_order"><?php _e( 'Menu order', 'idocs' );?></label></th>
			<td>
				<?php wp_nonce_field( 'idocs-set-menu-order', 'idocs_nonce' );?>
				<input name="doc_menu_order" id="doc_menu_order" type="text" value="<?php echo $order;?>" size="5" />
				<p class="description"><?php _e( 'Order in menu widget.', 'idocs' );?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * @internal
	 * @param $term_id
	 */
	public function taxonomy_save_fields( $term_id ){
		if( isset( $_POST['idocs_nonce'] ) ) {
			check_admin_referer( 'idocs-set-menu-order', 'idocs_nonce' );
			$value = isset( $_POST['doc_menu_order'] ) && is_numeric( $_POST['doc_menu_order'] ) ? absint( $_POST['doc_menu_order'] ) : 0;
			update_term_meta( $term_id, $this->main->get_tax_meta_order(), $value );
		}
	}

	/**
	 * @internal
	 * @param $column_name
	 * @param $screen
	 * @param $taxonomy
	 */
	public function tax_quick_edit( $column_name, $screen, $taxonomy ){
		if( $taxonomy != $this->main->get_taxonomy() || $column_name != 'idocs_order' ){
			return;
		}
		?>
		<fieldset>
			<?php wp_nonce_field( 'idocs-set-menu-order', 'idocs_nonce' );?>
			<div class="inline-edit-col inline-edit-<?php echo $this->main->get_taxonomy();?>">
				<label>
					<span class="order title"><?php _e( 'Order', 'idocs' ); ?></span>
					<span class="input-text-wrap"><input name="doc_menu_order" id="doc_menu_order" type="text" value="" size="5" style="width: 50px;" /></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * @internal
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_tax_columns( $columns ){
		$columns['idocs_order'] = __( 'Order', 'idocs' );
		return $columns;
	}

	/**
	 * @internal
	 * @param $content
	 * @param $column_name
	 * @param $term_id
	 *
	 * @return mixed
	 */
	public function tax_column( $content, $column_name, $term_id ){
		switch( $column_name ){
			case 'idocs_order':
				$content = get_term_meta( $term_id, $this->main->get_tax_meta_order(), true );
				break;
		}
		return $content;
	}
}