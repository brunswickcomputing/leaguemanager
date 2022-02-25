<?php

?>
<script type='text/javascript'>
jQuery(function() {
	jQuery("#tabs").tabs({
		active: <?php echo $tab ?>
	});
});
</script>
<div class="wrap"  style="margin-bottom: 1em;">

	<h1><?php _e( 'Racketmanager', 'racketmanager' ) ?></h1>

	<div id="tabs" class="racketmanager-blocks">
		<ul id="tablist" style="display: none;">
			<li><a href="#competitions-table"><?php _e( 'Competitions', 'racketmanager' ) ?></a></li>
			<li><a href="#seasons-table"><?php _e( 'Seasons', 'racketmanager' ) ?></a></li>
			<li><a href="#roster-table"><?php _e( 'Rosters', 'racketmanager' ) ?></a></li>
			<li><a href="#player-table"><?php _e( 'Players', 'racketmanager' ) ?></a></li>
			<li><a href="#rosterrequest-table"><?php _e( 'Roster Request', 'racketmanager' ) ?></a></li>
			<li><a href="#teams-table"><?php _e( 'Teams', 'racketmanager' ) ?></a></li>
			<li><a href="#clubs-table"><?php _e( 'Clubs', 'racketmanager' ) ?></a></li>
			<li><a href="#results-table"><?php _e( 'Results', 'racketmanager' ) ?></a></li>
			<li><a href="#results-checker-table"><?php _e( 'Results Checker', 'racketmanager' ) ?></a></li>
			<li><a href="#tournament-table"><?php _e( 'Tournaments', 'racketmanager' ) ?></a></li>
		</ul>

		<div id="competitions-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Competitions', 'racketmanager' ) ?></h2>
			<?php include('main/competitions.php'); ?>
		</div>
		<div id="seasons-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
			<?php include('main/seasons.php'); ?>
		</div>
		<div id="roster-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Rosters', 'racketmanager' ) ?></h2>
			<?php include('main/rosters.php'); ?>
		</div>
		<div id="rosterrequest-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Roster Request', 'racketmanager' ) ?></h2>
			<?php include('main/roster-requests.php'); ?>
		</div>
		<div id="player-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Players', 'racketmanager' ) ?></h2>
			<?php include('main/players.php'); ?>
		</div>
		<div id="teams-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Teams', 'racketmanager' ) ?></h2>
			<?php include('main/teams.php'); ?>
		</div>
		<div id="clubs-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Clubs', 'racketmanager' ) ?></h2>
			<?php include('main/clubs.php'); ?>
		</div>
		<div id="results-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Results', 'racketmanager' ) ?></h2>
			<?php include('main/results.php'); ?>
		</div>
		<div id="results-checker-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Results Checker', 'racketmanager' ) ?></h2>
			<?php include('main/results-checker.php'); ?>
		</div>
		<div id="tournament-table" class="league-block-container">
			<h2 class="header"><?php _e( 'Tournaments', 'racketmanager' ) ?></h2>
			<?php include('main/tournaments.php'); ?>
		</div>
		<?php include(RACKETMANAGER_PATH . '/admin/includes/match-modal.php'); ?>
	</div>
</div>
