<?php get_header();?>
<!-- ####### MAIN CONTAINER ####### -->
<div class='container_wrap' id='main'>
	<div class='container'>
		<div class='content grid9 first'>		
		<?php if (have_posts()) :?>
			<?php while (have_posts()) : the_post();?>
			<div class="idocs-navigation"><?php echo idocs_breadcrumb();?></div>
			
			<div class='post-entry'>
							       
				<h1 class='post-title'>
					<a href="<?php echo get_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a>
				</h1>
				
				<div class="entry-content">			
					<?php the_content(__('Read more','avia_framework'));  ?>					
				</div>
				<div class="hr_invisible"></div> 								        
			</div><!--end post-entry-->		
				
			<?php endwhile;?>		
		<?php else:?>	
			<div class="entry">
				<h1 class='post-title'><?php _e('Nothing Found', 'avia_framework'); ?></h1>
				<p><?php _e('Sorry, no docs matched your criteria', 'avia_framework'); ?></p>
			</div>
		<?php endif;?>
		
			<div class="idocs-post-nav">
				<div class="one_half first"><?php previous_post_link( '<i class="dashicons dashicons-arrow-left"></i>%link', __( '<span class="meta-nav">Previous:</span> %title', 'idocs' ) );?>&nbsp;</div>
				<div class="one_half"><?php next_post_link( '%link<i class="dashicons dashicons-arrow-right"></i>', __( '<span class="meta-nav">Next:</span> %title', 'idocs' ) );?></div>
			</div>
		
		<!--end content-->
		</div>		
		<?php 
		$avia_config['currently_viewing'] = idocs_post_type();
		//get the sidebar
		load_template( IDOCS_PATH.'theme/broadscope/sidebar.php' );		
		?>		
	</div><!--end container-->		
</div>
<!-- ####### END MAIN CONTAINER ####### -->
<?php get_footer();?>