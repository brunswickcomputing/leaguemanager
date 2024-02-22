<?php
/**
 * Players main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?><!-- Add Player -->
<div class="mb-3">
	<form action="" method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add-player' ); ?>
		<div class="form-floating mb-3">
			<input required="required" placeholder="<?php esc_html_e( 'Enter first name', 'racketmanager' ); ?>" type="text" name="firstname" id="firstname" value="" size="30" class="form-control"/>
			<label for="firstname"><?php esc_html_e( 'First Name', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input required="required"  placeholder="<?php esc_html_e( 'Enter surname', 'racketmanager' ); ?>" type="text" name="surname" id="surname" value="" size="30" class="form-control" />
			<label for="surname"><?php esc_html_e( 'Surname', 'racketmanager' ); ?></label>
		</div>
		<fieldset>
		<legend class="form-check-label"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></legend>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderMale" value="M" class="form-check-input" /><label=for "genderMale" class="form-check-label"><?php esc_html_e( 'Male', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check">
			<input type="radio" required="required" name="gender" id="genderFemale" value="F" class="form-check-input" /><label=for "genderFemale" class="form-check-label"><?php esc_html_e( 'Female', 'racketmanager' ); ?></label>
		</div>
		</fieldset>
		<div class="form-floating mb-3">
			<input type="number"  placeholder="<?php esc_html_e( 'Enter LTA Tennis Number', 'racketmanager' ); ?>" name="btm" id="btm" size="11" class="form-control" />
			<label for="btm"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="email" placeholder="<?php esc_html_e( 'Enter email address', 'racketmanager' ); ?>" name="email" id="email" class="form-control" autocomplete="no" />
			<label for="email"><?php esc_html_e( 'Email address', 'racketmanager' ); ?></label>
		</div>
		<input type="hidden" name="addPlayer" value="player" />
		<input type="submit" name="addPlayer" value="<?php esc_html_e( 'Add Player', 'racketmanager' ); ?>" class="btn btn-primary" />

	</form>
</div>
<div class="mb-3">
	<form id="player-filter" method="get">
		<input type="hidden" name="page" value="racketmanager-players" />
		<div class="row g-3 mb-3 align-items-center">
			<div class="col-auto">
				<div class="form-floating">
					<input placeholder="<?php esc_html_e( 'Enter search', 'racketmanager' ); ?>" type="text" name="name" id="name" size="30" class="form-control" autocomplete="no" />
					<label for="name"><?php esc_html_e( 'Search by name', 'racketmanager' ); ?></label>
				</div>
			</div>
			<div class="col-auto">
			<button class="btn btn-primary" name="doPlayerSearch" id="doPlayerSearch" data-toggle="tooltip" data-placement="left" title="Tooltip on left" data-html="true"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
			</div>
		</div>
	</form>
	<form id="player-action" method="post" action="" class="form-control">
		<?php wp_nonce_field( 'player-bulk' ); ?>
		<div class="row g-3 mb-3 align-items-center">
			<!-- Bulk Actions -->
			<div class="col-auto">
				<div class="form-floating">
					<select class="form-select" name="action" id="action" size="1">
						<option value="-1"><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete player', 'racketmanager' ); ?></option>
					</select>
					<label for="action"><?php esc_html_e( 'Bulk Action', 'racketmanager' ); ?></label>
				</div>
			</div>
			<div class="col-auto">
				<input type="submit" value="<?php esc_html_e( 'Apply', 'racketmanager' ); ?>" name="doPlayerDel" id="dorPlayerDel" class="btn btn-secondary action" />
			</div>
		</div>

		<div class="container">
			<div id="notifyMessage"></div>
			<div class="row table-header">
				<div class="col-2 col-md-1 check-column"><input type="checkbox" name="checkAll" onclick="Racketmanager.checkAll(document.getElementById('player-action'));" /></div>
				<div class="col-2 col-md-1 column-num">ID</div>
				<div class="col-4 col-md-2"><?php esc_html_e( 'Name', 'racketmanager' ); ?></div>
				<div class="col-2 col-md-1"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></div>
				<div class="col-1"><?php esc_html_e( 'Gender', 'racketmanager' ); ?></div>
				<div class="col-3 col-md-2 col-lg-1"><?php esc_html_e( 'LTA Tennis Number', 'racketmanager' ); ?></div>
				<div class="col-auto"><?php esc_html_e( 'Created', 'racketmanager' ); ?></div>
				<div class="col-auto"><?php esc_html_e( 'Removed', 'racketmanager' ); ?></div>
			</div>
			<?php
			if ( $players ) {
				$class = '';
				foreach ( $players as $player ) {
					$class = ( 'alternate' === $class ) ? '' : 'alternate';
					?>
					<div class="row table-row <?php echo esc_html( $class ); ?>">
						<div class="col-2 col-md-1 check-column">
							<?php if ( empty( $player->removed_date ) ) { ?>
								<input type="checkbox" name="check-<?php echo esc_html( $player->id ); ?>" value="<?php echo esc_html( $player->id ); ?>" name="player[<?php echo esc_html( $player->id ); ?>]" />
							<?php } ?>
						</div>
						<div class="col-2 col-md-1 column-num"><?php echo esc_html( $player->id ); ?></div>
						<div class="col-4 col-md-2"><a href="admin.php?page=racketmanager-players&amp;view=player&amp;player_id=<?php echo esc_html( $player->id ); ?>"><?php echo esc_html( $player->fullname ); ?></a></div>
						<div class="col-2 col-md-1">
							<button type="button" class="btn btn-secondary player-clubs" id="linkedClubs_<?php echo esc_html( $player->id ); ?>" data-bs-toggle="popover" data-bs-placement="left" data-bs-html="true">
								<i class="passwordShow racketmanager-svg-icon">
											<?php racketmanager_the_svg( 'icon-link' ); ?>
								</i>
							</button>
						</div>
						<div class="col-1"><?php echo esc_html( $player->gender ); ?></div>
						<div class="col-3 col-md-2 col-lg-1"><?php echo esc_html( $player->btm ); ?></div>
						<div class="col-auto"><?php echo esc_html( substr( $player->created_date, 0, 10 ) ); ?></div>
						<div class="col-auto">
						<?php
						if ( isset( $player->removed_date ) ) {
							echo esc_html( $player->removed_date );
						}
						?>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
	</form>
</div>
