<?php
if ($tournament->numcourts == 0) {
	$numCourts = 1;
} else {
	$numCourts = $tournament->numcourts;
}
$numMatches = count($finalMatches);
$maxMatches = ceil($numMatches / $numCourts) + 1;
if ( $tournament->timeincrement == "01:00:00" ) { $maxMatches = $maxMatches * 2; }
$columnWidth = floor(12 / $numCourts) ;
$matchLength = strtotime($tournament->timeincrement);
if ( !is_array($tournament->orderofplay) || count($tournament->orderofplay) != $tournament->numcourts ) {
	for ($i=0; $i < $tournament->numcourts ; $i++) {
		$orderofplay[$i]['court'] = 'Court '.($i + 1);
		$orderofplay[$i]['matches'] = array();
	}
} else {
	$orderofplay = $tournament->orderofplay;
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-tournaments"><?php _e( 'RacketManager Tournaments', 'racketmanager' ) ?></a> &raquo; <?php _e('Tournament Planner', 'racketmanager') ?>
		</div>
	</div>
	<div class="row">
		<h1><?php _e('Tournament Planner', 'racketmanager') ?> - <?php echo $tournament->name ?></h1>
	</div>
	<form id="tournamentDetails" class="form-control" method="POST">
		<?php wp_nonce_field( 'racketmanager_tournament' ) ?>
		<input type="hidden" name="tournamentId" value=<?php echo $tournament->id ?> />
		<div class="form-floating mb-3">
			<input type="text" class="form-control" name="venue" id="venue" readonly value="<?php echo $tournament->venueName ?>">
			<label for="venue"><?php _e( 'Venue', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="date" class="form-control" name="date" id="date" readonly value="<?php echo $tournament->date ?>" size="20" />
			<label for="date"><?php _e( 'Date', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="time" class="form-control" name="starttime" id="starttime" value="<?php echo $tournament->starttime ?>" size="20" />
			<label for="starttime"><?php _e( 'Start Time', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="time" class="form-control" name="timeincrement" id="timeincrement" value="<?php echo $tournament->timeincrement ?>" size="20" />
			<label for="timeincrement"><?php _e( 'Time Increment', 'racketmanager' ) ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="number" class="form-control" name="numcourts" id="numcourts" value="<?php echo $tournament->numcourts ?>" />
			<label for="numcourts"><?php _e( 'Number of courts', 'racketmanager' ) ?></label>
		</div>
		<div class="mb-3">
			<button type="submit" name="saveTournament" class="btn btn-primary"><?php _e('Save tournament', 'racketmanager') ?></button>
		</div>
	</form>
	<div class="row">
		<h2><?php _e( 'Final matches', 'racketmanager' ) ?></h2>
		<div class="col-2 col-sm-1"></div>
		<div class="col-10 col-sm-11">
			<div class="row text-center">
				<?php foreach ($finalMatches as $match ) { ?>
					<div class="col-12 col-md-<?php echo $columnWidth?> mb-3">
						<div class="btn <?php if ( !is_numeric($match->home_team) || !is_numeric($match->away_team) ) { echo 'btn-warning'; } else { echo 'btn-success'; } ?> final-match" name="match-<?php echo $match->id ?>" id="match-<?php echo $match->id ?>" draggable="true">
							<div><strong><?php echo $match->league->title ?></strong></div>
							<div><?php echo $match->teams['home']->title ?></div>
							<div><?php echo $match->teams['away']->title ?></div>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
		<h2><?php _e( 'Schedule', 'racketmanager' ) ?></h2>
		<form id="tournament-planner" method="post" action="">
			<?php wp_nonce_field( 'racketmanager_tournament-planner' ) ?>
			<input type="hidden" name="numFinals" value=<?php echo $numMatches ?> />
			<input type="hidden" name="numCourts" value=<?php echo $tournament->numcourts ?> />
			<input type="hidden" name="startTime" value=<?php echo $tournament->starttime ?> />
			<input type="hidden" name="tournamentId" value=<?php echo $tournament->id ?> />
			<div class="row text-center mb-3">
				<div class="col-2 col-sm-1"><?php _e('Time', 'racketmanager') ?></div>
				<div class="col-10 col-sm-11">
					<div class="row">
						<?php for ($i=0; $i < $tournament->numcourts; $i++) { ?>
							<div class="col-<?php echo $columnWidth?>">
								<input type="text" class="form-control" name="court[<?php echo $i ?>]" value="<?php echo $orderofplay[$i]['court']; ?>" />
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="mb-3">
				<?php	$startTime = strtotime($tournament->starttime);
				for ($i=0; $i < $maxMatches; $i++) { ?>
					<div class="row align-items-center text-center mb-3">
						<div class="col-2 col-sm-1">
							<?php echo date('H:i', $startTime); ?>
						</div>
						<div class="col-10 col-sm-11">
							<div class="row">
								<?php for ($c=0; $c < $tournament->numcourts; $c++) { ?>
									<div class="col-<?php echo $columnWidth?> tournament-match" name="schedule[<?php echo $c ?>][<?php echo $i ?>]" id="schedule-<?php echo $c ?>-<?php echo $i ?>">
										<input type="hidden" class="matchId" name="match[<?php echo $c ?>][<?php echo $i ?>]" id="match-<?php echo $c ?>-<?php echo $i ?>" value="<?php if (isset($orderofplay[$c]['matches'][$i]) ) { echo $orderofplay[$c]['matches'][$i]; } ?>" />
										<input type="hidden" class="" name="matchtime[<?php echo $c ?>][<?php echo $i ?>]" id="matchtime-<?php echo $c ?>-<?php echo $i ?>" value="<?php echo date('H:i', $startTime); ?>" />
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<?php $startTime = $startTime + $matchLength;
				} ?>
			</div>
			<div class="mb-3">
				<input type="submit" name="saveTournamentPlanner" value="<?php _e('Save schedule', 'racketmanager') ?>" class="btn btn-primary" />
			</div>
		</form>
	</div>
</div>
<?php wp_register_script( 'racketmanager-draggable', plugins_url('/js/draggable.js', dirname(__FILE__)), array(), RACKETMANAGER_VERSION );
wp_enqueue_script('racketmanager-draggable');
?>