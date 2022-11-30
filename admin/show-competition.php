<?php
?>

<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <?php echo $competition->name ?>
		</div>
	</div>
	<div class="row justify-content-between">
		<div class="col-auto">
			<h1><?php echo $competition->name ?></h1>
		</div>
	<?php if ( !empty($competition->seasons) ) { ?>
		<!-- Season Dropdown -->
		<div class="col-auto">
			<form action="admin.php" method="get" class="form-control">
				<input type="hidden" name="page" value="racketmanager" />
				<input type="hidden" name="subpage" value="show-competition" />
				<input type="hidden" name="competition_id" value="<?php echo $competition->id ?>" />
				<label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'racketmanager' ) ?></label>
				<select size="1" name="season" id="season">
					<?php foreach ( $competition->seasons AS $s ) { ?>
						<option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season ) { echo ' selected="selected"'; } ?>><?php echo $s['name'] ?></option>
					<?php } ?>
				</select>
				<input type="submit" value="<?php _e( 'Show', 'racketmanager' ) ?>" class="btn btn-secondary" />
			</form>
		</div>
	<?php } ?>
</div>

	<?php $this->printMessage(); ?>
	<div class="container">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="leagues-tab" data-bs-toggle="tab" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="true"><?php _e( 'Leagues', 'racketmanager' ) ?></button>
			</li>
			<?php if ( $competition->competitiontype != 'tournament' ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="playerstats-tab" data-bs-toggle="tab" data-bs-target="#playerstats" type="button" role="tab" aria-controls="playerstats" aria-selected="false"><?php _e( 'Players Stats', 'racketmanager' ) ?></button>
				</li>
			<?php } ?>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="seasons-tab" data-bs-toggle="tab" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="false"><?php _e( 'Seasons', 'racketmanager' ) ?></button>
			</li>
			<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="false"><?php _e( 'Settings', 'racketmanager' ) ?></button>
				</li>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="constitution-tab" data-bs-toggle="tab" data-bs-target="#constitution" type="button" role="tab" aria-controls="constitution" aria-selected="false"><?php _e( 'Constitution', 'racketmanager' ) ?></button>
				</li>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php _e( 'Matches', 'racketmanager' ) ?></button>
				</li>
			<?php } ?>

		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
				<h2><?php _e( 'Leagues', 'racketmanager' ) ?></h2>
				<?php include('competition/leagues.php'); ?>
			</div>
			<?php if ( $competition->competitiontype != 'tournament' ) { ?>
				<div class="tab-pane fade" id="playerstats" role="tabpanel" aria-labelledby="playerstats-tab">
					<h2><?php _e( 'Player Statistics', 'racketmanager' ) ?></h2>
					<?php include(RACKETMANAGER_PATH . 'admin/includes/player-stats.php'); ?>
				</div>
			<?php } ?>
			<div class="tab-pane fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
				<h2><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
				<?php include('competition/seasons.php'); ?>
			</div>
			<?php if ( current_user_can( 'manage_racketmanager' ) ) { ?>
				<div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
					<?php include('competition/settings.php'); ?>
				</div>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<div class="tab-pane fade" id="constitution" role="tabpanel" aria-labelledby="constitution-tab">
					<div id="constitution" class="league-block-container">
						<?php include('competition/constitution.php'); ?>
					</div>
				</div>
			<?php } ?>
			<?php if ( $competition->competitiontype == 'league' ) { ?>
				<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
					<div id="matches" class="league-block-container">
						<?php include('competition/matches.php'); ?>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
