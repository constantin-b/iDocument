<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2><?php _e('iDocuments - Plugin settings', 'idocs');?></h2>
	<form method="post" action="">
		<?php wp_nonce_field('idocs-save-plugin-settings', 'idocs_wp_nonce');?>
		<table class="form-table">
			<tbody>
				<!-- Types -->
				<tr><td colspan="2"><h3><?php _e('Post settings', 'idocs');?> <?php submit_button(__('save settings', 'idocs'), 'secondary', 'submit', false, array('style'=>'margin-left:30px;'));?></h3></td></tr>
				<tr valign="top">
					<th scope="row"><label for="menu_date"><?php _e('Show post date in menus', 'idocs')?>:</label></th>
					<td>
						<input type="checkbox" name="menu_date" value="1" id="menu_date"<?php idocs_check( $options['menu_date'] );?> />
						<span class="description">
						<?php _e('When checked, post date will be displayed in menus next to the title.', 'idocs');?>
						</span>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="breadcrumbs"><?php _e('Show breadcrumb on single post', 'idocs')?>:</label></th>
					<td>
						<input type="checkbox" name="breadcrumbs" value="1" id="breadcrumbs"<?php idocs_check( $options['breadcrumbs'] );?> />
						<span class="description">
						<?php _e('When checked single document post type will display breadcrumb above content.', 'idocs');?>
						</span>
					</td>
				</tr>
								
			</tbody>
		</table>
		<?php submit_button(__('Save settings', 'idocs'));?>
	</form>
</div>