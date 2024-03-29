<?php
/**
 * Results administration panel
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<!-- Nav tabs -->
	<ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
	<li class="nav-item" role="presentation">
		<button class="nav-link active" id="competitions-cup-tab" data-bs-toggle="tab" data-bs-target="#competitions-cup" type="button" role="tab" aria-controls="competitions-cup" aria-selected="true"><?php esc_html_e( 'Cups', 'racketmanager' ); ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="competitions-league-tab" data-bs-toggle="tab" data-bs-target="#competitions-league" type="button" role="tab" aria-controls="competitions-league" aria-selected="false"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></button>
	</li>
	<li class="nav-item" role="presentation">
		<button class="nav-link" id="competitions-tournament-tab" data-bs-toggle="tab" data-bs-target="#competitions-tournament" type="button" role="tab" aria-controls="competitions-tournament" aria-selected="false"><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></button>
	</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
	<?php
	$competition_types = Racketmanager_Util::get_competition_types();
	$i                 = 0;
	foreach ( $competition_types as $competition_type ) {
		$i ++;
		?>
		<div id="competitions-<?php echo esc_html( $competition_type ); ?>" class="tab-pane fade
			<?php
			if ( 1 === $i ) {
				echo ' active show';
			}
			?>
		" role="tabpanel" aria-labelledby="competitions-<?php echo esc_html( $competition_type ); ?>-tab">

		<div class="form-control">
			<div class="form-floating mb-3">
			<select class="form-select" id="role" name="<?php echo esc_html( $competition_type ); ?>[matchCapability]">
				<option value="none"
				<?php
				if ( isset( $options[ $competition_type ]['matchCapability'] ) && 'none' === $options[ $competition_type ]['matchCapability'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
				<option value="captain"
				<?php
				if ( isset( $options[ $competition_type ]['matchCapability'] ) && 'captain' === $options[ $competition_type ]['matchCapability'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Captain', 'racketmanager' ); ?></option>
				<option value="player"
				<?php
				if ( isset( $options[ $competition_type ]['matchCapability'] ) && 'player' === $options[ $competition_type ]['matchCapability'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Player', 'racketmanager' ); ?></option>
			</select>
			<label for="<?php echo esc_html( $competition_type ); ?>[matchCapability]"><?php esc_html_e( 'Minimum level to update results', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
			<select class="form-select" id="<?php echo esc_html( $competition_type ) . '-resultEntry'; ?>" name="<?php echo esc_html( $competition_type ); ?>[resultEntry]">
				<option value="none"
				<?php
				if ( isset( $options[ $competition_type ]['resultEntry'] ) && 'none' === $options[ $competition_type ]['resultEntry'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
				<option value="home"
				<?php
				if ( isset( $options[ $competition_type ]['resultEntry'] ) && 'home' === $options[ $competition_type ]['resultEntry'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Home', 'racketmanager' ); ?></option>
				<option value="either"
				<?php
				if ( isset( $options[ $competition_type ]['resultEntry'] ) && 'either' === $options[ $competition_type ]['resultEntry'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Either', 'racketmanager' ); ?></option>
			</select>
			<label for="<?php echo esc_html( $competition_type ); ?>[resultEntry]"><?php esc_html_e( 'Result Entry', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
			<select class="form-select" id="<?php echo esc_html( $competition_type ) . '-resultConfirmation'; ?>" name="<?php echo esc_html( $competition_type ); ?>[resultConfirmation]">
				<option value="none"
				<?php
				if ( isset( $options[ $competition_type ]['resultConfirmation'] ) && 'none' === $options[ $competition_type ]['resultConfirmation'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
				<option value="auto"
				<?php
				if ( isset( $options[ $competition_type ]['resultConfirmation'] ) && 'auto' === $options[ $competition_type ]['resultConfirmation'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Automatic', 'racketmanager' ); ?></option>
			</select>
			<label for="<?php echo esc_html( $competition_type ); ?>[resultConfirmation]"><?php esc_html_e( 'Result Confirmation', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
			<input type="email" class="form-control" name="<?php echo esc_html( $competition_type ); ?>[resultConfirmationEmail]" id="<?php echo esc_html( $competition_type ); ?>.'-resultConfirmationEmail'" value='<?php echo isset( $options[ $competition_type ]['resultConfirmationEmail'] ) ? esc_html( $options[ $competition_type ]['resultConfirmationEmail'] ) : ''; ?>' />
			<label for="<?php echo esc_html( $competition_type ); ?>[resultConfirmationEmail]"><?php esc_html_e( 'Notification Email Address', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
			<select class="form-select" id="<?php echo esc_html( $competition_type ); ?>.'-resultNotification'" name="<?php echo esc_html( $competition_type ); ?>[resultNotification]">
				<option value="none"
				<?php
				if ( isset( $options[ $competition_type ]['resultNotification'] ) && 'none' === $options[ $competition_type ]['resultNotification'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
				<option value="captain"
				<?php
				if ( isset( $options[ $competition_type ]['resultNotification'] ) && 'captain' === $options[ $competition_type ]['resultNotification'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Captain', 'racketmanager' ); ?></option>
				<option value="secretary"
				<?php
				if ( isset( $options[ $competition_type ]['resultNotification'] ) && 'secretary' === $options[ $competition_type ]['resultNotification'] ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?></option>
			</select>
			<label for="<?php echo esc_html( $competition_type ); ?>[resultNotification]"><?php esc_html_e( 'Result Notification', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating col-12 col-lg-3 mb-3">
			<input type="number" class="form-control" name='<?php echo esc_html( $competition_type ); ?>[resultPending]' id='<?php echo esc_html( $competition_type ); ?>-resultPending' value='<?php echo isset( $options[ $competition_type ]['resultPending'] ) ? esc_html( $options[ $competition_type ]['resultPending'] ) : ''; ?>' />
			<label for='resultPending'><?php esc_html_e( 'Chasing pending result (hours)', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating col-12 col-lg-3 mb-3">
			<input type="number" class="form-control" name='<?php echo esc_html( $competition_type ); ?>[confirmationPending]' id='<?php echo esc_html( $competition_type ); ?>-confirmationPending' value='<?php echo isset( $options[ $competition_type ]['confirmationPending'] ) ? esc_html( $options[ $competition_type ]['confirmationPending'] ) : ''; ?>' />
			<label for='confirmationPending'><?php esc_html_e( 'Chase result confirmation (hours)', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating col-12 col-lg-3 mb-3">
			<input type="number" class="form-control" name='<?php echo esc_html( $competition_type ); ?>[confirmationTimeout]' id='<?php echo esc_html( $competition_type ); ?>-confirmationTimeout' value='<?php echo isset( $options[ $competition_type ]['confirmationTimeout'] ) ? esc_html( $options[ $competition_type ]['confirmationTimeout'] ) : ''; ?>' />
			<label for='confirmationTimeout'><?php esc_html_e( 'Result confirmation timeout (hours)', 'racketmanager' ); ?></label>
			</div>
		</div>
		</div>
	<?php } ?>
	</div>
</div>
