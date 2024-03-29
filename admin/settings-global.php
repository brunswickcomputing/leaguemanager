<?php
/**
 * Global settings administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$menu_page_url = admin_url( 'options-general.php?page=racketmanager-settings' );
?>
<div class='container'>
	<h1><?php esc_html_e( 'Racketmanager Global Settings', 'racketmanager' ); ?></h1>

	<form action='' method='post' name='settings'>
		<?php wp_nonce_field( 'racketmanager_manage-global-league-options', 'racketmanager_nonce' ); ?>

		<input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_html( $tab ); ?>" ?>

		<div class="container">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="club-players-tab" data-bs-toggle="tab" data-bs-target="#club-players" type="button" role="tab" aria-controls="club-players" aria-selected="true"><?php esc_html_e( 'Club Players', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Player Checks', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="matchresults-tab" data-bs-toggle="tab" data-bs-target="#matchresults" type="button" role="tab" aria-controls="matchresults" aria-selected="false"><?php esc_html_e( 'Match Results', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="colors-tab" data-bs-toggle="tab" data-bs-target="#colors" type="button" role="tab" aria-controls="colors" aria-selected="false"><?php esc_html_e( 'Color Scheme', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="championship-tab" data-bs-toggle="tab" data-bs-target="#championship" type="button" role="tab" aria-controls="championship" aria-selected="false"><?php esc_html_e( 'Championship', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab" aria-controls="billing" aria-selected="false"><?php esc_html_e( 'Billing', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys" type="button" role="tab" aria-controls="keys" aria-selected="false"><?php esc_html_e( 'Keys', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="walkover-tab" data-bs-toggle="tab" data-bs-target="#walkover" type="button" role="tab" aria-controls="walkover" aria-selected="false"><?php esc_html_e( 'Walkovers', 'racketmanager' ); ?></button>
				</li>
			</ul>

			<!-- Tab panes -->
			<div class="tab-content mb-3">
				<div class="tab-pane active show fade" id="club-players" role="tabpanel" aria-labelledby="club-players-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/rosters.php'; ?>
				</div>
				<div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/players.php'; ?>
				</div>
				<div class="tab-pane fade" id="matchresults" role="tabpanel" aria-labelledby="matchresults-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/results.php'; ?>
				</div>
				<div class="tab-pane fade" id="colors" role="tabpanel" aria-labelledby="colors-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/colors.php'; ?>
				</div>
				<div class="tab-pane fade" id="championship" role="tabpanel" aria-labelledby="championship-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/championship.php'; ?>
				</div>
				<div class="tab-pane fade" id="billing" role="tabpanel" aria-labelledby="billing-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/billing.php'; ?>
				</div>
				<div class="tab-pane fade" id="keys" role="tabpanel" aria-labelledby="keys-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/keys.php'; ?>
				</div>
				<div class="tab-pane fade" id="walkover" role="tabpanel" aria-labelledby="walkover-tab">
					<?php require RACKETMANAGER_PATH . 'admin/includes/settings/walkover.php'; ?>
				</div>
			</div>
		</div>

		<div class="container">
			<input type='hidden' name='page_options' value='color_headers,color_rows,color_rows_alt,color_rows_ascend,color_rows_descend,color_rows_relegation' />
			<input type='submit' name='updateRacketManager' value='<?php esc_html_e( 'Save Preferences', 'racketmanager' ); ?>' class='btn btn-primary' />
		</div>

	</form>
</div>
