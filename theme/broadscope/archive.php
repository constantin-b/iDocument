<?php get_header();?>

	<!-- ####### MAIN CONTAINER ####### -->
	<div class='container_wrap' id='main'>
		<div class='container'>
			<div id='template-archive' class='content grid9 first'>
				<h2 class='firstheading'>
					<?php _e('Documentation for ');?>
					<?php 
						$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
						echo $term->name;
					?>
				</h2>	
				
		<?php if (have_posts()) :?>
			<?php while (have_posts()) : the_post();?>
				<div class="post-entry">						       
					<h1 class='post-title'>
						<a href="<?php echo get_permalink() ?>" rel="bookmark" title="<?php the_title(); ?>"><?php the_title(); ?></a>
					</h1>			
					<div class="entry-content">		
						<?php the_excerpt();?>		
						<a href="<?php echo get_permalink();?>"><?php _e('Read more','avia_framework'); ?></a>			
					</div>										        
				</div><!--end post-entry-->
				<div class="hr_invisible"></div>
			<?php endwhile;?>		
		<?php else:?>	
				<div class="entry">
					<h1 class='post-title'><?php _e('Nothing Found', 'avia_framework'); ?></h1>
					<p><?php _e('Sorry, no posts matched your criteria', 'avia_framework'); ?></p>
				</div>
		<?php endif;?>
		
		<?php echo avia_pagination();?>				
				
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