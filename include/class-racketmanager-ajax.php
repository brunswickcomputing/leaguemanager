<?php
/**
 * AJAX response methods

 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager;

/**
 * Implement AJAX responses for calls from both front end and admin.
 *
 * @author Paul Moffat
 */
class Racketmanager_Ajax extends RacketManager {
	/**
	 * Register ajax actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_racketmanager_get_player_details', array( &$this, 'get_player_details' ) );
		add_action( 'wp_ajax_racketmanager_match_mode', array( &$this, 'match_mode' ) );
		add_action( 'wp_ajax_racketmanager_update_match_header', array( &$this, 'update_match_header' ) );
		add_action( 'wp_ajax_racketmanager_update_match', array( &$this, 'update_match' ) );
		add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_rubbers' ) );
	}

	/**
	 * Ajax Response to get player information
	 */
	public function get_player_details() {
		global $wpdb;
		$valid       = true;
		$search_term = null;
		$message     = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$name = isset( $_POST['name'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : '';
			$name = $wpdb->esc_like( $name ) . '%';
			if ( ! empty( $_POST['affiliatedClub'] ) ) {
				$affiliated_club = sanitize_text_field( wp_unslash( $_POST['affiliatedClub'] ) );
				$search_term     = $wpdb->prepare(
					' AND C.`id` = %s',
					$affiliated_club
				);
			}
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT  P.`display_name` AS `fullname`, C.`name` as club, R.`id` as roster_id, C.`id` as club_id, P.`id` as player_id, P.`user_email` FROM $wpdb->racketmanager_club_players R, $wpdb->users P, $wpdb->racketmanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND C.`id` = R.`affiliatedclub` $search_term AND `display_name` like %s ORDER BY 1,2,3",
					$name
				)
			);
			$players = array();
			$player  = array();
			if ( $results ) {
				foreach ( $results as $r ) {
					$player['label']      = addslashes( $r->fullname ) . ' - ' . $r->club;
					$player['value']      = addslashes( $r->fullname );
					$player['id']         = $r->roster_id;
					$player['club_id']    = $r->club_id;
					$player['club']       = $r->club;
					$player['playerId']   = $r->player_id;
					$player['user_email'] = $r->user_email;
					$player['contactno']  = get_user_meta( $r->player_id, 'contactno', true );
					array_push( $players, $player );
				}
			} else {
				$players[] = array(
					'label' => __( 'No results found', 'racketmanager' ),
					'value' => 'null',
				);
			}
		}
		if ( $valid ) {
			$response = wp_json_encode( $players );
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $message, 500 );
		}
	}

	/**
	 * Match screen mode
	 */
	public function match_mode() {
		global $racketmanager;
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$match_screen = '';
			$match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
			$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
			if ( ! empty( $match_id ) ) {
				$match = get_match( $match_id );
			}
			if ( 'edit' === $mode ) {
				$is_edit_mode = true;
			} else {
				$is_edit_mode = false;
			}
			if ( $match ) {
				$match_screen = $racketmanager->show_match_screen( $match, $is_edit_mode );
			}
			wp_send_json_success( $match_screen );
		} else {
			wp_send_json_error( $message, 500 );
		}
	}

	/**
	 * Update match header
	 */
	public function update_match_header() {
		global $racketmanager;
		$valid   = true;
		$message = null;
		if ( isset( $_POST['security'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'ajax-nonce' ) ) {
				$valid   = false;
				$message = __( 'Security token invalid', 'racketmanager' );
			}
		} else {
			$valid   = false;
			$message = __( 'No security token found in request', 'racketmanager' );
		}
		if ( $valid ) {
			$match_header = '';
			$match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
			if ( ! empty( $match_id ) ) {
				$match = get_match( $match_id );
				if ( $match ) {
					$match_header = $racketmanager->show_match_header( $match );
					wp_send_json_success( $match_header );
				} else {
					$valid   = false;
					$message = __( 'Match not found', 'racketmanager' );
				}
			} else {
				$valid   = false;
				$message = __( 'Match id not found', 'racketmanager' );
			}
		}
		if ( ! $valid ) {
			wp_send_json_error( $message, 500 );
		}
	}

	/**
	 * Update match scores
	 */
	public function update_match() {
		global $league, $match, $racketmanager;

		$return    = array();
		$err_msg   = array();
		$err_field = array();
		$error     = false;
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'scores-match' ) ) {
			$error       = true;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} else {
			$match_id                    = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
			$match                       = get_match( $match_id );
			$league                      = get_league( $match->league_id );
			$match_round                 = isset( $_POST['match_round'] ) ? sanitize_text_field( wp_unslash( $_POST['match_round'] ) ) : null;
			$match_confirmed             = 'P';
			$matches[ $match_id ]        = $match_id;
			$home_points[ $match_id ]    = 0;
			$away_points[ $match_id ]    = 0;
			$home_team[ $match_id ]      = isset( $_POST['home_team'] ) ? intval( $_POST['home_team'] ) : null;
			$away_team[ $match_id ]      = isset( $_POST['away_team'] ) ? intval( $_POST['away_team'] ) : null;
			$custom[ $match_id ]['sets'] = isset( $_POST['sets'] ) ? $_POST['sets'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$season[ $match_id ]         = isset( $_POST['current_season'] ) ? sanitize_text_field( wp_unslash( $_POST['current_season'] ) ) : null;
			$match_status                = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
			$set_prefix                  = 'set_';
			$errors['err_msg']           = $err_msg;
			$errors['err_field']         = $err_field;
			$sets                        = isset( $custom[ $match_id ]['sets'] ) ? $custom[ $match_id ]['sets'] : null;
			$match_validate              = $this->validate_match_score( $match, $sets, $set_prefix, $errors, false, null, null, $match_status );
			$error                       = $match_validate[0];
			$err_msg                     = $match_validate[1];
			$home_points[ $match_id ]    = $match_validate[2];
			$away_points[ $match_id ]    = $match_validate[2];
			$err_field                   = $match_validate[2];
			$sets                        = $match_validate[5];
			$custom[ $match_id ]['sets'] = $sets;
			if ( $match_status ) {
				switch ( $match_status ) {
					case 'walkover_player1':
						$custom[ $match_id ]['walkover'] = 'home';
						break;
					case 'walkover_player2':
						$custom[ $match_id ]['walkover'] = 'away';
						break;
					case 'retired_player1':
						$custom[ $match_id ]['retired'] = 'home';
						break;
					case 'retired_player2':
						$custom[ $match_id ]['retired'] = 'away';
						break;
					default:
						break;
				}
			}
		}

		if ( ! $error ) {
			$match->update_sets( $sets );
			$match_count = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, $match_round, $match_confirmed );
			if ( $match_count > 0 ) {
				$match_message            = __( 'Result saved', 'racketmanager' );
				$match                    = get_match( $match_id );
				$home_points[ $match_id ] = $match->home_points;
				$away_points[ $match_id ] = $match->away_points;
				$msg                      = $match_message;
				$rm_options               = $racketmanager->get_options();
				$result_confirmation      = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
				if ( 'auto' === $result_confirmation || ( current_user_can( 'manage_racketmanager' ) ) ) {
					$update = $this->update_league_with_result( $match );
					$msg    = $update->msg;
					if ( current_user_can( 'manage_racketmanager' ) ) {
						$this->result_notification( $match_confirmed, $match_message, $match );
					}
				}
				$this->result_notification( $match_confirmed, $match_message, $match );
			} else {
				$msg = __( 'No result to save', 'racketmanager' );
			}
			array_push( $return, $msg, $match->home_points, $match->away_points, $match->winner_id, $sets );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to update match result', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field );
			wp_send_json_error( $return, 500 );
		}
	}

	/**
	 * Update match rubber scores
	 */
	public function update_rubbers() {
		global $racketmanager, $league, $match;
		$return          = array();
		$msg             = '';
		$err_field       = array();
		$err_msg         = array();
		$error           = false;
		$updated_rubbers = '';
		if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'rubbers-match' ) ) {
			$error       = true;
			$err_field[] = '';
			$err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
		} elseif ( isset( $_POST['updateRubber'] ) ) {
			$updated_rubbers       = '';
			$match_id              = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
			$match                 = get_match( $match_id );
			$home_points           = array();
			$away_points           = array();
			$rm_options            = $racketmanager->get_options();
			$match_confirmed       = '';
			$user_can_update_array = $racketmanager->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
			$user_can_update       = $user_can_update_array[0];
			$user_type             = $user_can_update_array[1];
			$user_team             = $user_can_update_array[2];
			$result_confirmation   = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
			$match_comments        = isset( $_POST['matchComments'] ) ? wp_unslash( $_POST['matchComments'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$confirm_comments      = isset( $_POST['resultConfirmComments'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirmComments'] ) ) : '';
			if ( 'results' === $_POST['updateRubber'] ) {
				$user_can_update = true;
				if ( $user_can_update ) {
					$player_found = false;
					if ( 'player' === $user_type ) {
						if ( 'home' === $user_team || 'both' === $user_team ) {
							if ( get_current_user_id() === intval( $match->teams['home']->captain_id ) || get_current_user_id() === intval( $match->teams['home']->club->matchsecretary ) ) {
								$player_found = true;
							}
							$club_id = $match->teams['home']->affiliatedclub;
						} elseif ( 'away' === $user_team ) {
							if ( get_current_user_id() === intval( $match->teams['away']->captain_id ) || get_current_user_id() === intval( $match->teams['away']->club->match_secretary ) ) {
								$player_found = true;
							}
							$club_id = $match->teams['away']->affiliatedclub;
						}
						if ( ! $player_found ) {
							$club           = get_club( $club_id );
							$club_player    = $club->get_players(
								array(
									'player' => get_current_user_id(),
									'active' => true,
								)
							);
							$club_player_id = $club_player[0]->roster_id;
							for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
								$players = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
								if ( 'home' === $user_team || 'both' === $user_team ) {
									$home_players = (array) $players['home'];
									$player_found = array_search( $club_player_id, $home_players, true );
								}
								if ( ! $player_found && ( 'away' === $user_team || 'both' === $user_team ) ) {
									$away_players = (array) $players['away'];
									$player_found = array_search( $club_player_id, $away_players, true );
								}
							}
						}
						if ( ! $player_found ) {
							$user_can_update = false;
							$err_msg[]       = __( 'Player cannot submit results', 'racketmanager' );
							$error           = true;
						}
					}
				}
				if ( $user_can_update ) {
					$rubber_result   = $this->update_rubber_results( $match, $rm_options );
					$error           = $rubber_result[0];
					$match_confirmed = $rubber_result[1];
					$err_msg         = $rubber_result[2];
					$err_field       = $rubber_result[3];
					$updated_rubbers = $rubber_result[4];
				}
			} elseif ( 'confirm' === $_POST['updateRubber'] ) {
				$result_confirm  = isset( $_POST['resultConfirm'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirm'] ) ) : null;
				$match_confirmed = $this->confirm_rubber_results( $result_confirm );
				if ( empty( $match_confirmed ) ) {
					$error       = true;
					$err_field[] = 'resultConfirm';
					$err_field[] = 'resultChallenge';
					$err_msg[]   = __( 'Either confirm or challenge result', 'racketmanager' );
				} elseif ( 'C' === $match_confirmed ) {
					if ( empty( $confirm_comments ) ) {
						$error       = true;
						$err_field[] = 'resultConfirmComments';
						$err_msg[]   = __( 'You must enter a reason for challenging the result', 'racketmanager' );
					}
				}
			}
		}
		if ( ! $error ) {
			if ( $match_confirmed ) {
				if ( isset( $_POST['result_home'] ) ) {
					$actioned_by = 'home';
				} elseif ( isset( $_POST['result_away'] ) ) {
					$actioned_by = 'away';
				} else {
					$actioned_by = '';
				}
				$match_updated_by = $match->update_match_status( $match_confirmed, $match_comments, $confirm_comments, $user_team, $actioned_by );
				if ( 'A' === $match_confirmed ) {
					$match_message = __( 'Result Approved', 'racketmanager' );
				} elseif ( 'C' === $match_confirmed ) {
					$match_message = __( 'Result Challenged', 'racketmanager' );
				} elseif ( 'P' === $match_confirmed ) {
					$match_message = __( 'Result Saved', 'racketmanager' );
				} else {
					$match_confirmed = '';
				}
				$msg = $match_message;
				if ( ( 'A' === $match_confirmed && 'auto' === $result_confirmation ) || ( 'admin' === $user_type ) ) {
					$update = $this->update_league_with_result( $match );
					$msg    = $update->msg;
					if ( 'admin' !== $user_type ) {
						if ( $update->updated || 'Y' === $match->updated ) {
							$match_confirmed = 'Y';
						}
						$this->result_notification( $match_confirmed, $match_message, $match, $match_updated_by );
					}
				} elseif ( 'A' === $match_confirmed ) {
					$this->result_notification( $match_confirmed, $match_message, $match, $match_updated_by );
				} elseif ( 'C' === $match_confirmed ) {
					$this->result_notification( $match_confirmed, $match_message, $match, $match_updated_by );
				} elseif ( ! current_user_can( 'manage_racketmanager' ) && 'P' === $match_confirmed ) {
					$this->result_notification( $match_confirmed, $match_message, $match, $match_updated_by );
				}
			} elseif ( ! $msg ) {
				$msg = __( 'No results to save', 'racketmanager' );
			}
			$home_points = isset( $updated_rubbers['homepoints'] ) ? $updated_rubbers['homepoints'] : null;
			$away_points = isset( $updated_rubbers['awaypoints'] ) ? $updated_rubbers['awaypoints'] : null;
			array_push( $return, $msg, $home_points, $away_points, $updated_rubbers );
			wp_send_json_success( $return );
		} else {
			$msg = __( 'Unable to save result', 'racketmanager' );
			array_push( $return, $msg, $err_msg, $err_field, $updated_rubbers );
			wp_send_json_error( $return, 500 );
		}
	}

	/**
	 * Update league with results of match
	 *
	 * @param object $match matach object.
	 * @return object
	 */
	public function update_league_with_result( $match ) {
		$return                    = new \stdClass();
		$league                    = get_league( $match->league_id );
		$matches[ $match->id ]     = $match->id;
		$home_points[ $match->id ] = $match->home_points;
		$away_points[ $match->id ] = $match->away_points;
		$home_team[ $match->id ]   = $match->home_team;
		$away_team[ $match->id ]   = $match->away_team;
		if ( $league->is_championship ) {
			if ( ! empty( $match->final_round ) ) {
				$round_data = $league->championship->get_finals( $match->final_round );
				$round      = $round_data['round'];
				$league->championship->update_final_results( $matches, $home_points, $away_points, array(), $round, $match->season );
				$return->msg     = __( 'Match saved', 'racketmanager' );
				$return->updated = true;
			} else {
				$return->msg     = __( 'No round specified', 'racketmanager' );
				$return->updated = false;
			}
		} else {
			$match_count = $league->update_match_results( $matches, $home_points, $away_points, array(), $match->season );
			if ( $match_count > 0 ) {
				/* translators: %s: match count */
				$return->msg     = sprintf( __( 'Saved Results of %d matches', 'racketmanager' ), $match_count );
				$return->updated = true;
			} else {
				$return->msg     = __( 'No matches to save', 'racketmanager' );
				$return->updated = false;
			}
		}
		return $return;
	}
	/**
	 * Update results for each rubber
	 *
	 * @param object $match match details.
	 * @param array  $options options for match.
	 */
	public function update_rubber_results( $match, $options ) {
		global $wpdb, $racketmanager, $league, $match;
		$return              = array();
		$error               = false;
		$err_msg             = array();
		$err_field           = array();
		$match_confirmed     = '';
		$home_team_score     = 0;
		$away_team_score     = 0;
		$home_team_score_tie = 0;
		$away_team_score_tie = 0;
		if ( ! empty( $match->leg ) && '2' === $match->leg && ! empty( $match->linked_match ) ) {
			$linked_match = get_match( $match->linked_match );
			if ( ! empty( $linked_match->winner_id ) ) {
				$home_team_score_tie = $linked_match->home_points;
				$away_team_score_tie = $linked_match->away_points;
			}
		}
		$players                              = array();
		$match_players                        = array();
		$player_options                       = $racketmanager->get_options( 'player' );
		$club                                 = get_club( $match->teams['home']->affiliatedclub );
		$player['walkover']['male']['home']   = $club->get_player( $player_options['walkover']['male'] );
		$player['walkover']['female']['home'] = $club->get_player( $player_options['walkover']['female'] );
		$player['noplayer']['male']['home']   = $club->get_player( $player_options['noplayer']['male'] );
		$player['noplayer']['female']['home'] = $club->get_player( $player_options['noplayer']['female'] );
		$player['share']['male']['home']      = $club->get_player( $player_options['share']['male'] );
		$player['share']['female']['home']    = $club->get_player( $player_options['share']['female'] );
		$club                                 = get_club( $match->teams['away']->affiliatedclub );
		$player['walkover']['male']['away']   = $club->get_player( $player_options['walkover']['male'] );
		$player['walkover']['female']['away'] = $club->get_player( $player_options['walkover']['female'] );
		$player['noplayer']['male']['away']   = $club->get_player( $player_options['noplayer']['male'] );
		$player['noplayer']['female']['away'] = $club->get_player( $player_options['noplayer']['female'] );
		$player['share']['male']['away']      = $club->get_player( $player_options['share']['male'] );
		$player['share']['female']['away']    = $club->get_player( $player_options['share']['female'] );
		$updated_rubbers                      = array();

		$match              = get_match( $match );
		$match->home_points = 0;
		$match->away_points = 0;
		$match->delete_result_check();
		$stats                    = array();
		$stats['rubbers']['home'] = 0;
		$stats['rubbers']['away'] = 0;
		$stats['sets']['home']    = 0;
		$stats['sets']['away']    = 0;
		$stats['games']['home']   = 0;
		$stats['games']['away']   = 0;

		for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$rubber_id    = isset( $_POST['id'][ $ix ] ) ? intval( $_POST['id'][ $ix ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$rubber_type  = isset( $_POST['type'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['type'][ $ix ] ) ) : null;
			$walkover     = '';
			$share        = false;
			$players      = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$sets         = isset( $_POST['sets'][ $ix ] ) ? ( wp_unslash( $_POST['sets'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$match_status = isset( $_POST['match_status'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'][ $ix ] ) ) : null;
			// phpcs:enable WordPress.Security.NonceVerification.Missing

			$winner    = '';
			$loser     = '';
			$opponents = array( 'home', 'away' );
			if ( 'D' === substr( $rubber_type, 1, 1 ) ) {
				$player_numbers = array( '1', '2' );
			} else {
				$player_numbers = array( '1' );
			}
			$sets_shared    = 0;
			$homescore      = 0;
			$awayscore      = 0;
			$set_prefix     = 'set_' . $ix . '_';
			$validate_match = true;
			$playoff        = false;
			$share          = null;
			$walkover       = null;
			$retired        = null;
			switch ( $match_status ) {
				case 'share':
					$share = true;
					if ( 'MD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['male']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['share']['male']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'WD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['female']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['share']['female']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'XD' === $match->league->type ) {
						$players['home']['1'] = $player['share']['male']['home']->roster_id;
						$players['home']['2'] = $player['share']['female']['home']->roster_id;
						$players['away']['1'] = $player['share']['male']['away']->roster_id;
						$players['away']['2'] = $player['share']['female']['away']->roster_id;
					}
					break;
				case 'walkover_player1':
					$walkover = 'home';
					if ( 'MD' === $match->league->type ) {
						$players['home']['1'] = $player['walkover']['male']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'WD' === $match->league->type ) {
						$players['home']['1'] = $player['walkover']['female']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['noplayer']['female']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'XD' === $match->league->type ) {
						$players['home']['1'] = $player['walkover']['male']['home']->roster_id;
						$players['home']['2'] = $player['walkover']['female']['home']->roster_id;
						$players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
						$players['away']['2'] = $player['noplayer']['female']['away']->roster_id;
					}
					break;
				case 'walkover_player2':
					$walkover = 'away';
					if ( 'MD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['walkover']['male']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'WD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['female']['home']->roster_id;
						$players['home']['2'] = $players['home']['1'];
						$players['away']['1'] = $player['walkover']['female']['away']->roster_id;
						$players['away']['2'] = $players['away']['1'];
					} elseif ( 'XD' === $match->league->type ) {
						$players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
						$players['home']['2'] = $player['noplayer']['female']['home']->roster_id;
						$players['home']['1'] = $player['walkover']['male']['away']->roster_id;
						$players['away']['2'] = $player['walkover']['female']['away']->roster_id;
					}
					break;
				case 'retired_player1':
					$retired = 'home';
					break;
				case 'retired_player2':
					$retired = 'away';
					break;
				default:
					break;
			}
			if ( isset( $match->league->scoring ) && ( 'TP' === $match->league->scoring || 'MP' === $match->league->scoring || 'MPL' === $match->league->scoring ) && intval( $match->num_rubbers ) === $ix && intval( $match->num_rubbers ) > $match->league->num_rubbers ) {
				if ( empty( $match->leg ) || '2' !== $match->leg ) {
					if ( $home_team_score !== $away_team_score ) {
						$validate_match = false;
					} else {
						$playoff = true;
					}
				} elseif ( $home_team_score_tie !== $away_team_score_tie ) {
						$validate_match = false;
				} else {
					$playoff = true;
				}
			}
			if ( $validate_match ) {
				if ( empty( $share ) && empty( $walkover ) ) {
					foreach ( $opponents as $opponent ) {
						$team_players = isset( $players[ $opponent ] ) ? $players[ $opponent ] : array();
						foreach ( $player_numbers as $player_number ) {
							if ( empty( $team_players[ $player_number ] ) ) {
								$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
								$err_msg[]   = __( 'Player not selected', 'racketmanager' );
								$error       = true;
							} else {
								$player_ref  = $team_players[ $player_number ];
								$club_player = $racketmanager->get_club_player( $player_ref );
								if ( ! $club_player->system_record ) {
									$player_found = array_search( $player_ref, $match_players, true );
									if ( false === $player_found ) {
										if ( $playoff ) {
											$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
											$err_msg[]   = __( 'Player for playoff must have played', 'racketmanager' );
											$error       = true;
										} else {
											$match_players[] = $player_ref;
										}
									} elseif ( ! $playoff ) {
										$err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
										$err_msg[]   = __( 'Player already selected', 'racketmanager' );
										$error       = true;
									}
								}
							}
						}
					}
				}
				$status              = null;
				$rubber_number       = $ix;
				$errors['err_msg']   = $err_msg;
				$errors['err_field'] = $err_field;
				$match_validate      = $this->validate_match_score( $match, $sets, $set_prefix, $errors, $rubber_number, $match_status );
				$error               = $match_validate[0];
				$err_msg             = $match_validate[1];
				$err_field           = $match_validate[2];
				$homescore           = $match_validate[3];
				$awayscore           = $match_validate[4];
				$sets                = $match_validate[5];
				$match_stats         = $match_validate[6];
				$points              = $match_validate[7];
				if ( ! $error ) {
					$custom         = array();
					$custom['sets'] = $sets;
					if ( $walkover ) {
						$status             = 1;
						$custom['walkover'] = $walkover;
					}
					if ( $share ) {
						$status          = 3;
						$custom['share'] = true;
					}
					if ( $retired ) {
						$status            = 2;
						$custom['retired'] = $retired;
					}
					if ( empty( $status ) ) {
						$status = 0;
					}
					$stats['sets']['home']  += $match_stats['sets']['home'];
					$stats['sets']['away']  += $match_stats['sets']['away'];
					$stats['games']['home'] += $match_stats['games']['home'];
					$stats['games']['away'] += $match_stats['games']['away'];
					$rubber                  = get_rubber( $rubber_id );
					$points['home']['team']  = $match->home_team;
					$points['away']['team']  = $match->away_team;
					$result                  = $rubber->calculate_result( $points );
					$homescore               = $result->home;
					$awayscore               = $result->away;
					$winner                  = $result->winner;
					$loser                   = $result->loser;
					if ( is_numeric( $homescore ) ) {
						$home_team_score     += $homescore;
						$home_team_score_tie += $homescore;
					}
					if ( is_numeric( $awayscore ) ) {
						$away_team_score     += $awayscore;
						$away_team_score_tie += $awayscore;
					}
					if ( $winner === $match->home_team ) {
						++$stats['rubbers']['home'];
					} elseif ( $winner === $match->away_team ) {
						++$stats['rubbers']['away'];
					} else {
						$stats['rubbers']['home'] += 0.5;
						$stats['rubbers']['away'] += 0.5;
					}
					if ( ! empty( $homescore ) || ! empty( $awayscore ) ) {
						$homescore                                   = ! empty( $homescore ) ? $homescore : 0;
						$awayscore                                   = ! empty( $awayscore ) ? $awayscore : 0;
						$updated_rubbers['homepoints'][ $rubber_id ] = $homescore;
						$updated_rubbers['awaypoints'][ $rubber_id ] = $awayscore;
						$match->home_points                         += $homescore;
						$match->away_points                         += $awayscore;

						$rubber->players = $players;
						$rubber->set_players();
						$rubber->home_points = $homescore;
						$rubber->away_points = $awayscore;
						$rubber->winner_id   = $winner;
						$rubber->loser_id    = $loser;
						$rubber->custom      = $custom;
						$rubber->status      = $status;
						$rubber->update_result();
						$match_confirmed = 'P';
						$check_options   = $options['checks'];
						$player_options  = $options['player'];
						foreach ( $opponents as $opponent ) {
							$team           = $match->teams[ $opponent ]->id;
							$rubber_players = array();
							foreach ( $player_numbers as $player_number ) {
								$rubber_players[]                                        = $players[ $opponent ][ $player_number ];
								$updated_rubbers[ $rubber_id ]['players'][ $opponent ][] = $players[ $opponent ][ $player_number ];
							}
							$this->check_team_players( $team, $rubber_players, $match, $check_options, $player_options );
						}
						$updated_rubbers[ $rubber_id ]['sets']   = $sets;
						$updated_rubbers[ $rubber_id ]['winner'] = $winner;
					}
				}
			}
		}
		if ( ! $error ) {
			$match_custom['stats'] = $stats;
			$match->update_result( $home_team_score, $away_team_score, $match_custom, $match_confirmed );
		}
		array_push( $return, $error, $match_confirmed, $err_msg, $err_field, $updated_rubbers );
		return $return;
	}
	/**
	 * Check team players
	 *
	 * @param array  $team team to check.
	 * @param array  $players players within team.
	 * @param object $match match details.
	 * @param array  $check_options check option details.
	 * @param array  $player_options player option details.
	 */
	private function check_team_players( $team, $players, $match, $check_options, $player_options ) {
		foreach ( $players as $player_ref ) {
			if ( ! empty( $player_ref ) ) {
				$this->check_player_result( $match, $player_ref, $team, $check_options, $player_options );
			}
		}
	}

	/**
	 * Validate Match Score
	 *
	 * @param object $match match details.
	 * @param array  $sets set details.
	 * @param string $set_prefix_start set prefix.
	 * @param array  $errors array of error messages and error fields.
	 * @param int    $rubber_number optional rubber number.
	 * @param string $match_status match_status setting.
	 */
	public function validate_match_score( $match, $sets, $set_prefix_start, $errors, $rubber_number = false, $match_status = false ) {
		global $racketmanager;
		$num_sets_to_win        = intval( $match->league->num_sets_to_win );
		$return                 = array();
		$homescore              = 0;
		$awayscore              = 0;
		$error                  = false;
		$scoring                = isset( $match->league->scoring ) ? $match->league->scoring : 'TB';
		$sets_updated           = array();
		$s                      = 1;
		$stats                  = array();
		$stats['sets']['home']  = 0;
		$stats['sets']['away']  = 0;
		$stats['games']['home'] = 0;
		$stats['games']['away'] = 0;

		$points['home']['sets']   = 0;
		$points['away']['sets']   = 0;
		$points['shared']['sets'] = 0;
		$points['split']['sets']  = 0;
		if ( ! empty( $sets ) ) {
			$num_sets    = count( $sets );
			$set_retired = null;
			if ( 'retired_player1' === $match_status || 'retired_player2' === $match_status ) {
				for ( $s1 = $num_sets - 1; $s1 >= 0; $s1-- ) {
					if ( null !== $sets[ $s1 ]['player1'] || null !== $sets[ $s1 ]['player2'] ) {
						$set_retired = $s1;
						break;
					}
				}
			}
			foreach ( $sets as $set ) {
				$set_prefix = $set_prefix_start . $s . '_';
				$set_type   = Racketmanager_Util::get_set_type( $scoring, $match->final_round, $match->league->num_sets, $s, $rubber_number, $match->num_rubbers, $match->leg );
				if ( ( $s > $num_sets_to_win ) && $homescore === $num_sets_to_win || $awayscore === $num_sets_to_win ) {
					$set_type = 'null';
				}
				$set_status = null;
				if ( 'retired_player1' === $match_status || 'retired_player2' === $match_status ) {
					if ( $set_retired === $s ) {
						$set_status = $match_status;
					} elseif ( $s > $set_retired ) {
						$set_type = 'null';
					}
				} else {
					$set_status = $match_status;
				}
				$set_validate        = $this->validate_set( $set, $set_prefix, $errors['err_msg'], $errors['err_field'], $set_type, $set_status );
				$set                 = $set_validate[2];
				$errors['err_msg']   = $set_validate[0];
				$errors['err_field'] = $set_validate[1];
				if ( $errors['err_msg'] ) {
					$error = true;
				}
				$set_player_1 = strtoupper( $set['player1'] );
				$set_player_2 = strtoupper( $set['player2'] );
				if ( null !== $set_player_1 && null !== $set_player_2 ) {
					if ( ( $set_player_1 > $set_player_2 && empty( $set_status ) ) || ( 'retired_player2' ) === $set_status ) {
						++$points['home']['sets'];
						++$stats['sets']['home'];
						++$homescore;
						if ( 'MTB' === $set['settype'] ) {
							++$stats['games']['home'];
						}
					} elseif ( ( $set_player_1 < $set_player_2 && empty( $set_status ) ) || ( 'retired_player1' ) === $set_status ) {
						++$points['away']['sets'];
						++$stats['sets']['away'];
						++$awayscore;
						if ( 'MTB' === $set['settype'] ) {
							++$stats['games']['away'];
						}
					} elseif ( 'S' === $set_player_1 ) {
						++$points['shared']['sets'];
						$stats['sets']['home'] += 0.5;
						$stats['sets']['away'] += 0.5;
						$homescore             += 0.5;
						$awayscore             += 0.5;
					}
				}
				if ( is_numeric( $set_player_1 ) && 'MTB' !== $set['settype'] ) {
					$stats['games']['home'] += $set_player_1;
				}
				if ( is_numeric( $set_player_2 ) && 'MTB' !== $set['settype'] ) {
					$stats['games']['away'] += $set_player_2;
				}
				$sets_updated[ $s ] = $set;
				++$s;
			}
			if ( ! empty( $homescore ) && ! empty( $awayscore ) ) {
				++$points['split']['sets'];
			}
		}
		if ( 'league' === $match->league->event->competition->type ) {
			$player_options          = $racketmanager->get_options( 'player' );
			$walkover_rubber_penalty = ! empty( $player_options['walkover']['rubber'] ) ? $player_options['walkover']['rubber'] : 0;
		} else {
			$walkover_rubber_penalty = 0;
		}
		if ( 'walkover_player1' === $match_status ) {
			$stats['sets']['home']     += $num_sets_to_win;
			$points['home']['sets']    += $num_sets_to_win;
			$points['away']['walkover'] = true;
			$homescore                 += $num_sets_to_win;
			$awayscore                 -= $walkover_rubber_penalty;
			$stats['games']['home']    += $num_sets_to_win * 6;
		} elseif ( 'walkover_player2' === $match_status ) {
			$stats['sets']['away']     += $num_sets_to_win;
			$points['away']['sets']    += $num_sets_to_win;
			$points['home']['walkover'] = true;
			$awayscore                 += $num_sets_to_win;
			$homescore                 -= $walkover_rubber_penalty;
			$stats['games']['away']    += $num_sets_to_win * 6;
		} elseif ( 'retired_player1' === $match_status ) {
			$points['home']['retired'] = true;
			$points['away']['sets']    = $num_sets_to_win;
			$stats['sets']['away']     = $num_sets_to_win;
			$awayscore                 = $num_sets_to_win;
		} elseif ( 'retired_player2' === $match_status ) {
			$points['away']['retired'] = true;
			$points['home']['sets']    = $num_sets_to_win;
			$stats['sets']['home']     = $num_sets_to_win;
			$homescore                 = $num_sets_to_win;
		} elseif ( 'share' === $match_status ) {
			$shared_sets              = $match->league->num_sets / 2;
			$points['shared']['sets'] = $match->league->num_sets;
			$homescore               += $shared_sets;
			$awayscore               += $shared_sets;
		}
		array_push( $return, $error, $errors['err_msg'], $errors['err_field'], $homescore, $awayscore, $sets_updated, $stats, $points );
		return $return;
	}

	/**
	 * Validate set
	 *
	 * @param array  $set set information.
	 * @param string $set_prefix sert prefix.
	 * @param array  $err_msg error messages.
	 * @param array  $err_field error fields.
	 * @param string $set_type type of set.
	 * @param string $match_status match_status setting.
	 */
	public function validate_set( $set, $set_prefix, $err_msg, $err_field, $set_type, $match_status ) {
		$return         = array();
		$set_info       = Racketmanager_Util::get_set_info( $set_type );
		$max_win        = $set_info->max_win;
		$min_win        = $set_info->min_win;
		$max_loss       = $set_info->max_loss;
		$set['player1'] = strtoupper( $set['player1'] );
		$set['player2'] = strtoupper( $set['player2'] );
		if ( 'walkover_player1' === $match_status || 'walkover_player2' === $match_status ) {
			if ( 'null' === $set_type ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			} else {
				$set['player1']  = null;
				$set['player2']  = null;
				$set['tiebreak'] = '';
			}
		} elseif ( 'retired_player1' === $match_status || 'retired_player2' === $match_status ) {
			if ( 'null' === $set_type ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			}
		} elseif ( null !== $set['player1'] && null !== $set['player2'] ) {
			if ( 'null' === $set_type ) {
				if ( '' !== $set['player1'] ) {
					$err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
				}
				if ( '' !== $set['player2'] ) {
					$err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'player2';
				}
				if ( '' !== $set['tiebreak'] ) {
					$err_msg[]   = __( 'Tie break should be empty', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				}
			} elseif ( 'share' === $match_status ) {
				$set['player1']  = '';
				$set['player2']  = '';
				$set['tiebreak'] = '';
			} elseif ( 'S' === $set['player1'] || 'S' === $set['player2'] ) {
				if ( 'S' !== $set['player1'] ) {
					$err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
				}
				if ( 'S' !== $set['player2'] ) {
					$err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
					$err_field[] = $set_prefix . 'player2';
				}
			} elseif ( $set['player1'] === $set['player2'] ) {
				if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status ) {
					$err_msg[]   = __( 'Set scores must be different', 'racketmanager' );
					$err_field[] = $set_prefix . 'player1';
					$err_field[] = $set_prefix . 'player2';
				}
			} elseif ( $set['player1'] > $set['player2'] ) {
				$set_data        = new \stdClass();
				$set_data->msg   = $err_msg;
				$set_data->field = $err_field;
				$set_data        = $this->validate_set_score( $set, $set_prefix, 'player1', 'player2', $set_data, $set_info, $match_status );
				$err_msg         = $set_data->msg;
				$err_field       = $set_data->field;
			} elseif ( $set['player1'] < $set['player2'] ) {
				$set_data        = new \stdClass();
				$set_data->msg   = $err_msg;
				$set_data->field = $err_field;
				$set_data        = $this->validate_set_score( $set, $set_prefix, 'player2', 'player1', $set_data, $set_info, $match_status );
				$err_msg         = $set_data->msg;
				$err_field       = $set_data->field;
			} elseif ( '' === $set['player1'] || '' === $set['player2'] ) {
				if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status ) {
					$err_msg[] = __( 'Set score not entered', 'racketmanager' );
					if ( '' === $set['player1'] ) {
						$err_field[] = $set_prefix . 'player1';
					}
					if ( '' === $set['player2'] ) {
						$err_field[] = $set_prefix . 'player2';
					}
				}
			}
		}
		$set['settype'] = $set_type;
		array_push( $return, $err_msg, $err_field, $set );
		return $return;
	}
	/**
	 * Validate set score function
	 *
	 * @param array  $set set details.
	 * @param string $set_prefix ste prefix.
	 * @param string $team_1 team 1.
	 * @param string $team_2 team 2.
	 * @param object $return_data return data.
	 * @param object $set_info set info.
	 * @param string $match_status match status.
	 * @return object
	 */
	private function validate_set_score( $set, $set_prefix, $team_1, $team_2, $return_data, $set_info, $match_status = null ) {
		$tiebreak_allowed  = $set_info->tiebreak_allowed;
		$tiebreak_required = $set_info->tiebreak_required;
		$max_win           = $set_info->max_win;
		$min_win           = $set_info->min_win;
		$max_loss          = $set_info->max_loss;
		$min_loss          = $set_info->min_loss;
		$err_msg           = $return_data->msg;
		$err_field         = $return_data->field;
		$retired_player    = 'retired_' . $team_2;
		if ( $set[ $team_1 ] < $min_win && $match_status !== $retired_player ) {
			$err_msg[]   = __( 'Winning set score too low', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
		} elseif ( $set[ $team_1 ] > $max_win ) {
			$err_msg[]   = __( 'Winning set score too high', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
		} elseif ( $set[ $team_1 ] === $min_win && $set[ $team_2 ] > $min_loss && $match_status !== $retired_player ) {
			$err_msg[]   = __( 'Games difference must be at least 2', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
			$err_field[] = $set_prefix . $team_2;
		} elseif ( intval( $set[ $team_1 ] ) === $max_win ) {
			if ( $set[ $team_2 ] < $max_loss ) {
				$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
				$err_field[] = $set_prefix . $team_1;
				$err_field[] = $set_prefix . $team_2;
			} elseif ( $tiebreak_allowed && $set[ $team_2 ] > $max_loss ) {
				if ( ! $set['tiebreak'] > '' ) {
					$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				} elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
					$err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
					$err_field[] = $set_prefix . 'tiebreak';
				}
			} elseif ( $tiebreak_required && '' === $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		} elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] < $min_loss ) {
			$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
			$err_field[] = $set_prefix . $team_1;
			$err_field[] = $set_prefix . $team_2;
		} elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] > $min_loss && ( $set[ $team_1 ] - 2 ) !== intval( $set[ $team_2 ] ) ) {
			$err_msg[]   = __( 'Games difference incorrect', 'racketmanager' );
			$err_field[] = $set_prefix . $team_2;
		} elseif ( $set['tiebreak'] > '' ) {
			if ( ! $tiebreak_required ) {
				$err_msg[]   = __( 'Tie break score should be empty', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		} elseif ( $tiebreak_required ) {
			if ( '' === $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score required', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			} elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
				$err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
				$err_field[] = $set_prefix . 'tiebreak';
			}
		}
		$return_data->msg   = $err_msg;
		$return_data->field = $err_field;
		return $return_data;
	}
	/**
	 * Result notification
	 *
	 * @param string $match_status match status.
	 * @param string $match_message match message.
	 * @param object $match match object.
	 * @param string $match_updated_by match updated by value.
	 */
	public function result_notification( $match_status, $match_message, $match, $match_updated_by = false ) {
		global $racketmanager;
		$admin_email         = $racketmanager->get_confirmation_email( $match->league->event->competition->type );
		$rm_options          = $racketmanager->get_options();
		$result_notification = $rm_options[ $match->league->event->competition->type ]['resultNotification'];

		if ( $admin_email > '' ) {
			$message_args               = array();
			$message_args['email_from'] = $admin_email;
			$message_args['league']     = $match->league->id;
			if ( $match->league->is_championship ) {
				$message_args['round'] = $match->final_round;
			} else {
				$message_args['matchday'] = $match->match_day;
			}
			$headers            = array();
			$confirmation_email = '';
			if ( 'P' === $match_status ) {
				if ( 'home' === $match_updated_by ) {
					if ( 'captain' === $result_notification ) {
						$confirmation_email = $match->teams['away']->contactemail;
					} elseif ( 'secretary' === $result_notification ) {
						$club               = get_club( $match->teams['away']->affiliatedclub );
						$confirmation_email = isset( $club->match_secretary_email ) ? $club->match_secretary_email : '';
					}
				} elseif ( 'captain' === $result_notification ) {
					$confirmation_email = $match->teams['home']->contactemail;
				} elseif ( 'secretary' === $result_notification ) {
					$club               = get_club( $match->teams['away']->affiliatedclub );
					$confirmation_email = isset( $club->match_secretary_email ) ? $club->match_secretary_email : '';
				}
			}
			if ( $confirmation_email ) {
				$email_to  = $confirmation_email;
				$headers[] = $racketmanager->get_from_user_email();
				$headers[] = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $admin_email . '>';
				$subject   = $racketmanager->site_name . ' - ' . $match->league->title . ' - ' . $match->match_title . ' - Result confirmation required';
				$message   = racketmanager_captain_result_notification( $match->id, $message_args );
			} else {
				$email_to  = $admin_email;
				$headers[] = $racketmanager->get_from_user_email();
				$subject   = $racketmanager->site_name . ' - ' . $match->league->title . ' - ' . $match->match_title . ' - ' . $match_message;
				if ( 'Y' === $match_status ) {
					$match = get_match( $match->id );
					if ( $match->has_result_check() ) {
						$message_args['errors'] = true;
						$subject               .= ' - ' . __( 'Check results', 'racketmanager' );
					} else {
						$message_args['complete'] = true;
						$subject                 .= ' - ' . __( 'Match complete', 'racketmanager' );
					}
				}
				$message = racketmanager_result_notification( $match->id, $message_args );
			}
			wp_mail( $email_to, $subject, $message, $headers );
		}
	}

	/**
	 * Confirm results of rubbers
	 *
	 * @param string $result_confirm result confirmation.
	 */
	public function confirm_rubber_results( $result_confirm ) {
		$match_confirmed = '';
		switch ( $result_confirm ) {
			case 'confirm':
				$match_confirmed = 'A';
				break;
			case 'challenge':
				$match_confirmed = 'C';
				break;
			default:
				$match_confirmed = '';
		}

		return $match_confirmed;
	}

	/**
	 * Update match results and automatically calculate score
	 *
	 * @param object $match match object.
	 * @param int    $roster_id roster id.
	 * @param int    $team team id.
	 * @param array  $options options.
	 * @param array  $player_options player options.
	 * @return none
	 */
	public function check_player_result( $match, $roster_id, $team, $options, $player_options ) {
		global $wpdb, $racketmanager, $match;

		$match  = get_match( $match->id );
		$player = $racketmanager->get_club_player( $roster_id, $team );
		if ( ! empty( $player->system_record ) ) {
			if ( 'M' === $player->gender ) {
				$gender = 'male';
			} elseif ( 'F' === $player->gender ) {
				$gender = 'female';
			} else {
				$gender = 'unknown';
			}
			if ( isset( $player_options['unregistered'][ $gender ] ) && $player->player_id === $player_options['unregistered'][ $gender ] ) {
				$error = __( 'Unregistered player', 'racketmanager' );
				$match->add_result_check( $team, $player->player_id, $error );
			}
			return;
		}

		$team_name           = get_team( $team )->title;
		$current_team_number = substr( $team_name, -1 );

		if ( ! is_numeric( $roster_id ) ) {
			$error = __( 'Player not selected', 'racketmanager' );
			$match->add_result_check( $team, 0, $error );
		}

		if ( $player ) {
			if ( isset( $options['rosterLeadTime'] ) && isset( $player->created_date ) ) {
				$match_date  = new \DateTime( $match->date );
				$roster_date = new \DateTime( $player->created_date );
				$interval    = $roster_date->diff( $match_date );
				if ( $interval->days < intval( $options['rosterLeadTime'] ) ) {
					/* translators: %d: number of days */
					$error = sprintf( __( 'registered with club only %d days before match', 'racketmanager' ), $interval->days );
					$match->add_result_check( $team, $player->player_id, $error );
				} elseif ( $interval->invert ) {
					/* translators: %d: number of days */
					$error = sprintf( __( 'registered with club %d days after match', 'racketmanager' ), $interval->days );
					$match->add_result_check( $team, $player->player_id, $error );
				}
			}
			if ( ! empty( $player->locked ) ) {
				$error = __( 'locked', 'racketmanager' );
				$match->add_result_check( $team, $player->player_id, $error );
			}

			if ( isset( $match->match_day ) ) {
				$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_rubber_players} rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} c WHERE l.`event_id` = (SELECT `event_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND rp.`club_player_id` = %d",
						$match->season,
						$match->match_day,
						$match->league_id,
						$match->league_id,
						$roster_id,
					)
				);
				if ( $count > 0 ) {
					/* translators: %d: match day */
					$error = sprintf( __( 'already played on match day %d', 'racketmanager' ), $match->match_day );
					$match->add_result_check( $team, $player->player_id, $error );
				}

				if ( isset( $options['playedRounds'] ) ) {
					$league         = get_league( $match->league_id );
					$num_match_days = $league->seasons[ $match->season ]['num_match_days'];
					if ( $match->match_day > ( $num_match_days - $options['playedRounds'] ) ) {
						$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT count(*) FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager_rubbers} r, {$wpdb->racketmanager_rubber_players} rp WHERE m.`id` = r.`match_id` AND r.`id` = rp.`rubber_id` AND m.`season` = %s AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_events} e WHERE l.`event_id` = (SELECT `event_id` FROM {$wpdb->racketmanager} WHERE `id` = %d)) AND rp.`club_player_id` = %d",
								$match->season,
								$match->match_day,
								$match->league_id,
								$roster_id
							)
						);
						if ( 0 === intval( $count ) ) {
							/* translators: %d: number of played rounds */
							$error = sprintf( __( 'not played before the final %d match days', 'racketmanager' ), $options['playedRounds'] );
							$match->add_result_check( $team, $player->player_id, $error );
						}
					}
				}
				if ( isset( $options['playerLocked'] ) ) {
					$event        = get_event( $match->league->event_id );
					$player_stats = $event->get_player_stats(
						array(
							'season' => $match->season,
							'player' => $roster_id,
						)
					);
					$teamplay     = array();
					foreach ( $player_stats as $player_stat ) {
						foreach ( $player_stat->matchdays as $match_day ) {
							$team_num = substr( $match_day->team_title, -1 );
							if ( isset( $teamplay[ $team_num ] ) ) {
								++$teamplay[ $team_num ];
							} else {
								$teamplay[ $team_num ] = 1;
							}
						}
						foreach ( $teamplay as $team_num => $played ) {
							if ( $team_num < $current_team_number && $played > $options['playerLocked'] ) {
								/* translators: %d: team number */
								$error = sprintf( __( 'locked to team %d', 'racketmanager' ), $team_num );
								$match->add_result_check( $team, $player->player_id, $error );
							}
						}
					}
				}
			}
		}
	}
}
