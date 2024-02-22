<?php
/**
 * Events main page administration panel
 *
 * @package Racketmanager/Admin
 */

namespace Racketmanager;

?>
<div class="container mb-3">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<?php require_once 'includes/events.php'; ?>
	<?php
	if ( isset( $tournament ) ) {
		?>
		<div class="mt-3">
			<a class="btn btn-secondary" href="admin.php?page=racketmanager-admin&amp;view=competitions&amp;season=<?php echo esc_html( $tournament->season ); ?>&amp;type=tournament&amp;tournamenttype=<?php echo esc_html( $tournament->type ); ?>&amp;tournament=<?php echo esc_html( $tournament->id ); ?>"><?php esc_html_e( 'Add Competitions', 'racketmanager' ); ?></a>
		</div>
	<?php } ?>
</div>
