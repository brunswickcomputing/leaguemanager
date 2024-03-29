<?php
/**
 * Template for event clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $event->player ) ) {
	if ( ! empty( $event->players ) ) {
		?>
		<div class="module module--card">
			<div class="module__banner">
				<h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
			</div>
			<div class="module__content">
				<div class="module-container">
					<ul class="player-list ">
						<?php
						foreach ( $event->players as $key => $players ) {
							?>
							<li class="player-list__cat" id="<?php echo esc_html( $key ); ?>">
								<div class="list-divider"><?php echo esc_html( $key ); ?></div>
								<ul class="row player-list-letter">
									<?php
									foreach ( $players as $player ) {
										?>
										<li class="alphabet-list-item col-12 col-sm-6 col-md-4">
											<a href="/<?php echo esc_attr( $event->competition->type ); ?>s/<?php echo esc_html( seo_url( $event->name ) ); ?>/<?php echo esc_attr( $event->current_season['name'] ); ?>/player/<?php echo esc_attr( seo_url( $player->display_name ) ); ?>/">
												<?php echo esc_html( $player->index ); ?>
											</a>
										</li>
									<?php } ?>
								</ul>
							</li>
							<?php
						}
						?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	<?php
} else {
	$player = $event->player;
	require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
	?>
	<div class="page_content row">
		<div class="page-content__main col-12 col-lg-8">
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div class="module">
							<?php
							foreach ( $player->matches as $key => $player_matches ) {
								$league = $player_matches['league'];
								?>
								<h4 class="module-divider">
									<span class="module-divider__body">
										<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>s/<?php echo esc_attr( seo_url( $league->event->name ) ); ?>/<?php echo esc_attr( $league->season ); ?>/">
											<span class="nav-link__value">
												<?php echo esc_html( $league->event->name ); ?>
											</span>
										</a>&nbsp;&#8226;&nbsp;
										<a href="/<?php echo esc_attr( $league->event->competition->type ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_attr( $league->season ); ?>/">
											<span class="nav-link__value">
												<?php echo esc_html( $league->title ); ?>
											</span>
										</a>
									</span>
								</h4>
								<?php
								foreach ( $player_matches['matches'] as $match ) {
									echo $racketmanager->show_match_screen( $match, false, $event->player ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-4">
			<?php
			if ( ! empty( $event->player->statistics ) ) {
				?>
				<div class="module module--card">
					<div class="module__banner">
						<h3 class="module__title"><?php esc_html_e( 'Player statistics', 'racketmanager' ); ?></h3>
					</div>
					<div class="module__content">
						<div class="module-container">
							<div class="module">
								<div class="table-responsive">
									<table id="team-player-stats" class="table table-borderless player-stats" aria-describedby="<?php esc_html_e( 'Player Team Statistics', 'racketmanager' ); ?>">
										<thead>
											<tr>
												<th scope="col">
												</th>
												<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Played', 'racketmanager' ); ?>">
													<?php esc_html_e( 'P', 'racketmanager' ); ?>
												</th>
												<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Won', 'racketmanager' ); ?>">
													<?php esc_html_e( 'W', 'racketmanager' ); ?>
												</th>
												<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Sets', 'racketmanager' ); ?>">
													<?php esc_html_e( 'S', 'racketmanager' ); ?>
												</th>
												<th scope="col" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_html_e( 'Games', 'racketmanager' ); ?>">
													<?php esc_html_e( 'G', 'racketmanager' ); ?>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php
											$player_statistics = $event->player->statistics;
											$stat_rows         = array(
												__( 'Doubles', 'racketmanager' ) => 'd',
											);
											foreach ( $stat_rows as $stat_title => $stat_type ) {
												$matches_won   = ! empty( $player_statistics['played']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['played']['winner'][ $stat_type ] ) : 0;
												$matches_lost  = ! empty( $player_statistics['played']['loser'][ $stat_type ] ) ? array_sum( $player_statistics['played']['loser'][ $stat_type ] ) : 0;
												$matches_drawn = ! empty( $player_statistics['played']['draw'][ $stat_type ] ) ? array_sum( $player_statistics['played']['draw'][ $stat_type ] ) : 0;
												$played        = $matches_won + $matches_lost + $matches_drawn;
												if ( $played ) {
													?>
													<tr>
														<th scope="row">
														<?php echo esc_html( $stat_title ); ?>
														</th>
														<td>
															<?php
															echo esc_html( $played );
															?>
														</td>
														<td>
															<?php
															$matches_won = ! empty( $player_statistics['played']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['played']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $matches_won );
															?>
														</td>
														<td>
															<?php
															$sets_won = ! empty( $player_statistics['sets']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['sets']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $sets_won );
															?>
														</td>
														<td>
															<?php
															$games_won = ! empty( $player_statistics['games']['winner'][ $stat_type ] ) ? array_sum( $player_statistics['games']['winner'][ $stat_type ] ) : 0;
															echo esc_html( $games_won );
															?>
														</td>
													</tr>
													<?php
												}
												?>
												<?php
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
?>
