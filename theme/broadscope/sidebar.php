<?php 
	/**
	 * Display only for custom taxonomy and single idocs post
	 */
	if( is_tax(idocs_taxonomy()) || is_singular(idocs_post_type()) ):
?>
<div class='sidebar sidebar1 grid3'>
<?php dynamic_sidebar('idocs-sidebar');?>
</div>
<?php endif;?>