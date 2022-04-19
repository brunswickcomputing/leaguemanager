<?php
/**
* Roster main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Players', 'racketmanager' ) ?>
		</div>
	</div>
	<h1><?php _e( 'Players', 'racketmanager' ) ?> - <?php echo $club->name ?></h1>

	<!-- View Rosters -->
	<div class="mb-3">
		<!-- Add Player -->
		<h2><?php _e( 'Add Player', 'racketmanager' ) ?></h2>
		<form action="" method="post" class="form-control">
			<?php wp_nonce_field( 'racketmanager_add-roster' ) ?>
			<div class="form-group">
				<label for="firstname"><?php _e( 'First Name', 'racketmanager' ) ?></label>
				<div class="form-input">
					<input required="required" placeholder="<?php _e( 'Enter first name', 'racketmanager') ?>" type="text" name="firstname" id="firstname" value="" size="30" />
				</div>
			</div>
			<div class="form-group">
				<label for="surname"><?php _e( 'Surname', 'racketmanager' ) ?></label>
				<div class="form-input">
					<input required="required"  placeholder="<?php _e( 'Enter surname', 'racketmanager') ?>" type="text" name="surname" id="surname" value="" size="30" />
				</div>
			</div>
			<div class="form-group">
				<label><?php _e('Gender', 'racketmanager') ?></label>
				<div class="form-check">
					<input type="radio" required="required" name="gender" id="genderMale" value="M" />
					<label for "genderMale" class="form-check-label"><?php _e('Male', 'racketmanager') ?></label>
				</div>
				<div class="form-check">
					<input type="radio" required="required" name="gender" id="genderFemale" value="F" />
					<label for "genderFemale" class="form-check-label"><?php _e('Female', 'racketmanager') ?></label>
				</div>
			</div>
			<div class="form-group">
				<label for="btm"><?php _e('BTM', 'racketmanager') ?></label>
				<div class="form-input">
					<input type="number"  placeholder="<?php _e( 'Enter BTM number', 'racketmanager') ?>" name="btm" id="gender" size="11" />
				</div>
			</div>
			<input type="hidden" name="club_Id" id="club_Id" value="<?php echo $club_id ?>" />
			<input type="hidden" name="addRosterPlayer" value="player" />
			<input type="submit" name="addRosterPlayer" value="<?php _e( 'Add Player','racketmanager' ) ?>" class="btn btn-primary" />

		</form>
	</div>

	<div class="mb-3">
		<h2><?php _e( 'View Players', 'racketmanager' ) ?></h2>
		<form id="roster-filter" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'roster-bulk' ) ?>

			<div class="tablenav">
				<!-- Bulk Actions -->
				<select name="action" size="1">
					<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
					<option value="delete"><?php _e('Remove')?></option>
				</select>
				<input type="submit" value="<?php _e('Apply'); ?>" name="dorosterdel" id="dorosterdel" class="btn btn-secondary action" />
			</div>

			<div class="container">
				<div class="row table-header">
					<div class="col-12 col-md-1 check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('roster-filter'));" /></div>
					<div class="col-12 col-md-2"><?php _e( 'Name', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Gender', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'BTM', 'racketmanager' ) ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Removed', 'racketmanager') ?></div>
					<div class="col-12 col-md-2"><?php _e( 'Removed By', 'racketmanager') ?></div>
					<div class="col-12 col-md-1"><?php _e( 'Created On', 'racketmanager') ?></div>
					<div class="col-12 col-md-2"><?php _e( 'Created By', 'racketmanager') ?></div>
				</div>
				<?php if ( !$club_id == 0 ) { $club = get_club($club_id); ?>

					<?php if ( $rosters = $club->getRoster(array()) ) {
						$class = '';
						foreach ( $rosters AS $roster ) { ?>
							<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
							<div class="row table-row <?php echo $class ?>">
								<div class="col-12 col-md-1 check-column">
									<?php if ( !isset($roster->removed_date) ) { ?>
										<input type="checkbox" value="<?php echo $roster->roster_id ?>" name="roster[<?php echo $roster->roster_id ?>]" />
									<?php } ?>
								</div>
								<div class="col-12 col-md-2"><?php echo $roster->fullname ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->gender ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->btm ?></div>
								<div class="col-12 col-md-1"><?php if ( isset($roster->removed_date) ) { echo $roster->removed_date; } ?></div>
								<div class="col-12 col-md-2"><?php echo $roster->removedUserName ?></div>
								<div class="col-12 col-md-1"><?php echo $roster->created_date ?></div>
								<div class="col-12 col-md-2"><?php echo $roster->createdUserName ?></div>
							</div>
						<?php } ?>
					<?php } ?>
				<?php } ?>
			</div>
		</form>
	</div>
</div>
