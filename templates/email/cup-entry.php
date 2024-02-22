<?php
/**
 * Send fixtures email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

$email_subject = $organisation . ' - ' . ucfirst( $competition_name ) . ' ' . __( 'Cup Entry', 'racketmanager' ) . ' - ' . $season;
require 'email-header.php';
?>
			<?php
			$title_text  = __( 'Entry confirmation', 'racketmanager' );
			$title_level = '1';
			require 'components/title.php';
			?>
			<?php
			$salutation_link = $club;
			require 'components/salutation.php';
			?>
			<?php
			/* translators: $s: competition name */
			$paragraph_text = sprintf( __( 'Thank you for your entry for the %s. You will find confirmation of your entry below.', 'racketmanager' ), $competition_name );
			require 'components/paragraph.php';
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$title_text  = __( 'Entry Details', 'racketmanager' );
			$title_level = '2';
			require 'components/title.php';
			?>
			<?php
			$title_text  = __( 'Events', 'racketmanager' );
			$title_level = '3';
			require 'components/title.php';
			?>
			<?php
			foreach ( $cup_entries as $event_entry ) {
				?>
				<?php
				$title_text  = $event_entry['event'];
				$title_level = '4';
				require 'components/title.php';
				?>
				<div style="font-size: 14px; color: #000; background-color: #fff; padding: 0 20px;">
					<table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
						<tbody>
							<tr>
								<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
									<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
										<tbody>
											<tr>
												<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
													<table width="100%" style="height: 100%; text-align: left; margin-left: 10px;" role="presentation" cellspacing="0" cellpadding="0">
														<tbody>
															<tr style="line-height: 22px;">
																<td style="width: 40%; font-size: 14px; font-weight: 500; vertical-align: top;"><h5 style="font-size:14px; display:inline;"><?php echo esc_html( $event_entry['teamName'] ); ?></h5>:</td>
																<td>
																	<?php echo esc_html( $event_entry['matchday'] ); ?> <?php esc_html_e( 'at', 'racketmanager' ); ?> <?php echo esc_html( $event_entry['matchtime'] ); ?>
																	<br>
																	<?php echo esc_html( $event_entry['captain'] ); ?>
																	<?php
																	if ( $event_entry['contactno'] > '' ) {
																		?>
																		<br>
																		<?php echo esc_html( $event_entry['contactno'] ); ?>
																		<?php
																	}
																	?>
																	<?php
																	if ( $event_entry['contactemail'] > '' ) {
																		?>
																		<br>
																		<?php echo esc_html( $event_entry['contactemail'] ); ?>
																		<?php
																	}
																	?>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<?php
			}
			?>
			<?php
			if ( ! empty( $comments ) ) {
				require 'components/hr.php';
				$title_text  = __( 'Additional comments', 'racketmanager' );
				$title_level = '3';
				require 'components/title.php';
				$paragraph_text = $comments;
				require 'components/paragraph.php';
			}
			?>
			<?php require 'components/hr.php'; ?>
			<?php
			$paragraph_text = __( 'Captains will be notified when the draws have taken place.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<?php
			if ( ! empty( $contact_email ) ) {
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
<?php
require 'email-footer.php';
