<?php
/**
Template page for a single match

The following variables are usable:

$match: contains data of displayed match

You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
global $wp_query;
$postID = $wp_query->post->ID;
?>
<?php if ( $match ) { ?>

	<div class="match" id="match-<?php echo $match->id ?>">
		<h1 class="header"><?php echo $match->match_title ?></h1>
		<?php include('league-selections.php'); ?>

		<?php if ( is_user_logged_in() ) { ?>
			<div id="viewMatchRubbers">
					<div id="splash" class="d-none">
							<section id="waitingMatch">
									<p>Please wait</p>
									<div class="spinnerMatch"></div>
							</section>
					</div>
					<div id="showMatchRubbers">
						<?php if (isset($match->league->num_rubbers) && $match->league->num_rubbers > 0 ) {
							$racketmanager->showRubbersScreen($match);
						} else {
							$racketmanager->showMatchScreen($match);
						} ?>
					</div>
			</div>
		<?php } else { ?>
			<div class="row justify-content-center">
				<div class="col-auto">
					<?php _e('You need to ','racketmanager') ?><a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>"><?php _e('login', 'racketmanager') ?></a> <?php _e('to enter match information', 'racketmanager') ?>
				</div>
			</div>
		<?php } ?>
	</div>

<?php }  ?>