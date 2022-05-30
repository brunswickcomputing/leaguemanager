<?php
$tab = '';
if ( $referrer ) {
	$tab = $referrer;
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-results&tab=results"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $match->match_title ?>
		</div>
	</div>
	<h1><?php _e('Match details', 'racketmanager') ?></h1>
	<div id="viewMatchRubbers">
		<div id="splash" style="display:none">
			<section id="waitingMatch">
				<p>Please wait</p>
				<div class="spinnerMatch"></div>
			</section>
		</div>
		<div id="showMatchRubbers">
			<?php if (isset($match->league->num_rubbers) && $match->league->num_rubbers > 0 ) {
				$matchDisplay = $this->showRubbersScreen($match);
			} else {
				$matchDisplay = $this->showMatchScreen($match);
			} ?>
		</div>
	</div>
	<div class="">
		<a href="admin.php?page=racketmanager-results&amp;tab=<?php echo $tab ?>" class="button button-secondary"><?php _e('Back to results', 'racketmanager'); ?></a>
	</div>
</div>
