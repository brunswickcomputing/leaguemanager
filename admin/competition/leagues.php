<div class="container">
	<div class="container">
		<form id="leagues-filter" method="post" action="">
			<?php wp_nonce_field( 'leagues-bulk' ) ?>

			<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Delete')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="doactionleague" id="doactionleague" class="btn btn-secondary action" />
			</div>
			
			<div class="row table-header">
				<div class="col-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('leagues-filter'));" /></div>
				<div class="col-1 column-num">ID</div>
				<div class="col-3"><?php _e( 'League', 'racketmanager' ) ?></div>
				<div class="col-1 column-num"><?php _e( 'Teams', 'racketmanager' ) ?></div>
				<div class="col-1 column-num"><?php _e( 'Matches', 'racketmanager' ) ?></div>
			</div>

			<?php
			if ( $leagues = $competition->getLeagues( array('competition' => $competition_id)) ) {
				$class = '';
				foreach ( $leagues AS $league ) {
					$league = get_league($league);
					$class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-1 check-column"><input type="checkbox" value="<?php echo $league->id ?>" name="league[<?php echo $league->id ?>]" /></div>
						<div class="col-1 column-num"><?php echo $league->id ?></div>
						<div class="col-3"><a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a></div>
						<div class="col-1 column-num"><?php echo $league->num_teams_total ?></div>
						<div class="col-1 column-num"><?php echo $league->num_matches_total ?></div>
						<div class="col-1"><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>&amp;editleague=<?php echo $league->id ?>"><?php _e( 'Edit', 'racketmanager' ) ?></a></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>
	<!-- Add New League -->
	<?php if ( !$league_id ) {
		$action = __( 'Add League', 'racketmanager' );
	} else {
		$action = __( 'Update League', 'racketmanager' );
	} ?>
	<div class="container">

		<h3><?php echo $action ?></h3>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-league' ) ?>
			<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
			<input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
			<div class="form-group">
				<div class="form-label">
					<label for="league_title"><?php _e( 'League', 'racketmanager' ) ?></label>
				</div>
				<div class="form-input">
					<input type="text" required="required" placeholder="<?php _e( 'Enter new league name', 'racketmanager') ?>"name="league_title" id="league_title" value="<?php echo $league_title ?>" size="30" />
				</div>
			</div>
			<div class="form-group">
				<div class="form-input">
					<input type="submit" name="addLeague" value="<?php echo $action ?>" class="btn btn-primary" />
				</div>
			</div>
		</form>
	</div>
</div>
