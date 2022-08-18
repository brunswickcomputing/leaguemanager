<div class=container>
	<form id="seasons-filter" action="" method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'seasons-bulk' ) ?>

		<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
		<div class="tablenav">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doactionseason" id="doactionseason" class="btn btn-secondary action" />
		</div>

		<div class=container>
			<div class="row table-header">
				<div class="col-1 me-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('seaons-filter'));" /></div>
				<div class="col-3 col-lg-1"><?php _e( 'Season', 'racketmanager' ) ?></div>
				<div class="col-2 col-lg-1"><?php _e( 'Match Days', 'racketmanager' ) ?></div>
				<div class="col-2 col-lg-1"><?php _e( 'Type', 'racketmanager' ) ?></div>
				<div class="col-auto"><?php _e( 'Status', 'racketmanager' ) ?></div>
			</div>
			<?php if ( !empty($competition->seasons) ) {
				$class = '';
				foreach( (array)$competition->seasons AS $key => $season ) {
					$class = ( 'alternate' == $class ) ? '' : 'alternate' ?>
					<div class="row table-row <?php echo $class ?>">
						<div class="col-1 me-1 check-column"><input type="checkbox" value="<?php echo $key ?>" name="del_season[<?php echo $key ?>]" /></div>
						<div class="col-3 col-lg-1"><a href="admin.php?page=racketmanager&amp;subpage=show-season&amp;competition_id=<?php echo $competition->id ?>&amp;season=<?php echo $key ?>"><?php echo $season['name'] ?></a></div>
						<div class="col-2 col-lg-1"><?php echo $season['num_match_days'] ?></div>
						<div class="col-2 col-lg-1"><?php if (isset($season['homeAway'])) { if ( $season['homeAway'] ) { echo 'both'; } else { echo 'home only'; } } ?></div>
						<div class="col-auto"><?php if (isset($season['status'])) { echo $season['status']; } ?></div>
					</div>
				<?php } ?>
			<?php } ?>
		</form>
	</div>

	<h3><?php _e( 'Add Season', 'racketmanager' ) ?></h3>
	<form action="" method="post"  class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-season' ) ?>
		<input type="hidden" name="competition_id" value="<?php echo $competition_id ?>" />
		<table class="lm-form-table">
			<div class="form-floating mb-3">
				<?php if ( $season_id ) { ?>
					<input type="number" class="form-control" name="season" id="season" value="<?php echo $season_data['name'] ?>" size="4" />
				<?php } else { ?>
					<select size="1" name="season" id="season" class="form-select">
						<option><?php _e( 'Select season' , 'racketmanager') ?></option>
						<?php $seasons = $racketmanager->getSeasons( "DESC" );
						foreach ( $seasons AS $season ) { ?>
							<option value="<?php echo $season->name ?>"><?php echo $season->name ?></option>
						<?php } ?>
					</select>
				<?php } ?>
				<label for="season"><?php _e( 'Season', 'racketmanager' ) ?></label>
			</div>
		</div>
		<div class="form-floating mb-3">
			<input type="number" class="form-control" min="1" step="1" class="small-text" name="num_match_days" id="num_match_days" value="<?php echo $season_data['num_match_days'] ?>" size="2" />
			<label for="num_match_days"><?php if ($competition->is_championship) { _e( 'Number of rounds', 'racketmanager' ); } else { _e( 'Number of match days', 'racketmanager' ); } ?></label>
		</div>
	</table>

	<input type="hidden" name="season_id" value="<?php echo $season_id ?>" />
	<input type="submit" name="saveSeason" class="btn btn-primary" value="<?php _e( 'Add Season', 'racketmanager' ) ?>" />
</form>
</div>
