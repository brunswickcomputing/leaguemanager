<?php
/**
 * Template page for the Winners
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $winners: array of all winners
 *  $curr_season: current season
 *  $tournaments: array of all tournaments
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $racketmanager_shortcodes;
$post_id = isset( $wp_query->post->ID ) ? $wp_query->post->ID : ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<div id="winners">
	<h1><?php echo esc_html( sprintf( '%s %s', $curr_entry, __( 'Winners', 'racketmanager' ) ) ); ?></h1>
	<div id="racketmanager_archive_selections" class="">
		<form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_winners">
			<input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
			<input type="hidden" name="competitionSeason" id="competitionSeason" value="<?php echo esc_html( $season ); ?>" />
			<input type="hidden" name="competitionType" id="competitionType" value="<?php echo esc_html( $competitiontype ); ?>" />
			<select size="1" name="selection" id="selection">
				<option value=""><?php esc_html_e( 'Season', 'racketmanager' ); ?></option>
				<?php foreach ( $selections as $selection ) { ?>
					<option value="<?php echo esc_html( $selection->name ); ?>"
						<?php
						if ( $selection->name === $curr_entry ) {
							echo ' selected="selected"';
						}
						?>
					><?php echo esc_html( $selection->name ); ?></option>
				<?php } ?>
			</select>
		</form>
	</div>
	<?php require RACKETMANAGER_PATH . 'templates/includes/winners-body.php'; ?>
</div>
