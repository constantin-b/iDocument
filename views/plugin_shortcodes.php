<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2><?php _e('iDocuments - Plugin shortcodes', 'idocs');?></h2>
	<p><?php _e('Below are all shortcodes registered by this plugin and description of parameters and how to use them in posts.', 'idocs');?></p>
	<?php foreach($shortcodes as $tag => $data):?>
	<h3><?php echo $tag;?></h3>
	<p><?php echo $data['description'];?></p>	
	
	<h4><?php _e('Params', 'idocs');?></h4>
	<ol>
	<?php $output = array();?>
	<?php foreach( $data['params'] as $param => $desc ):?>
		<?php 
			$default = false === $desc['val'] ? 'false' : $desc['val'];	
			if( '' === $default ){
				$default = __('empty string', 'idocs');				
			}		
		?>
		<li><strong><?php echo $param;?></strong> : <?php echo $desc['desc'];?> (<?php _e('default:', 'idocs');?> <em><?php echo (string)$default;?></em>)</li>
	<?php $output[] = $param.'="'.$desc['val'].'"' ;?>
	<?php endforeach;?>
	</ol>
	<h4><?php _e('Usage', 'idocs');?></h4>
	[<?php echo $tag;?> <?php echo implode(' ', $output);?>]
	<hr />
	<?php endforeach;?>
</div>