<?php
/**
 * Template for showing event favourite
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

if ( ! empty( $hidden ) ) {
	$visible = 'hidden-svg';
} else {
	$visible = '';
}
if ( is_user_logged_in() ) {
	$is_favourite = $racketmanager->is_user_favourite( 'competition', $event->id );
	if ( $is_favourite ) {
		$tooltip_title = __( 'Remove favourite', 'racketmanager' );
	} else {
		$tooltip_title = __( 'Add favourite', 'racketmanager' );
	}
	?>
	<div class="fav-icon">
		<a href="" id="fav-<?php echo esc_html( $event->id ); ?>" title="<?php echo esc_html( $tooltip_title ); ?>" data-js="add-favourite" data-type="competition" data-favourite="<?php echo esc_html( $event->id ); ?>">
			<i class="fav-icon-svg <?php echo esc_html( $visible ); ?> racketmanager-svg-icon <?php echo $is_favourite ? 'fav-icon-svg-selected' : ''; ?>
			">
				<?php racketmanager_the_svg( 'icon-star' ); ?>
			</i>
		</a>
		<div class="fav-msg" id="fav-msg-<?php echo esc_html( $event->id ); ?>"></div>
	</div>
	<?php
} ?>
