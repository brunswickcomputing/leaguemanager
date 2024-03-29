<?php
/**
 * RacketManager API: RacketManager class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

/**
 * Main class to implement RacketManager
 */
class RacketManager {
	/**
	 * The array of templates that this plugin tracks.
	 *
	 * @var $template
	 */
	protected $templates;
	/**
	 * Site name.
	 *
	 * @var $site_name
	 */
	public $site_name;
	/**
	 * Message.
	 *
	 * @var $message
	 */
	public $message;
	/**
	 * Error.
	 *
	 * @var $error
	 */
	public $error = false;
	/**
	 * Options.
	 *
	 * @var $options
	 */
	public $options;
	/**
	 * Date format.
	 *
	 * @var $date_format
	 */
	public $date_format;
	/**
	 * Time format.
	 *
	 * @var $time_format
	 */
	public $time_format;
	/**
	 * Admin email.
	 *
	 * @var $admin_email
	 */
	public $admin_email;
	/**
	 * Site url.
	 *
	 * @var $site_url
	 */
	public $site_url;
	/**
	 * Seasons.
	 *
	 * @var $seasons
	 */
	public $seasons;
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		global $wpdb;

		$wpdb->show_errors();
		$this->load_options();
		$this->load_libraries();

		add_action( 'widgets_init', array( &$this, 'register_widget' ) );
		add_action( 'init', array( &$this, 'racketmanager_rewrites' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_styles' ), 5 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts' ) );
		add_action( 'rm_resultPending', array( &$this, 'chase_pending_results' ), 1 );
		add_action( 'rm_confirmationPending', array( &$this, 'chase_pending_approvals' ), 1 );
		add_action( 'wp_loaded', array( &$this, 'add_racketmanager_templates' ) );
		add_action( 'template_redirect', array( &$this, 'redirect_to_login' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( &$this, 'racketmanager_register_exporter' ) );
		add_filter( 'wp_mail', array( &$this, 'racketmanager_mail' ) );
		add_filter( 'email_change_email', array( &$this, 'racketmanager_change_email_address' ), 10, 3 );
		add_filter( 'pre_get_document_title', array( &$this, 'set_page_title' ), 999, 1 );
	}
	/**
	 * Set page title function
	 *
	 * @param string $title title.
	 * @return $title new title
	 */
	public function set_page_title( $title ) {
		global $wp;
		$slug      = get_post_field( 'post_name' );
		$site_name = $this->site_name;
		if ( 'event' === $slug ) {
			$event  = un_seo_url( isset( $wp->query_vars['event'] ) ? $wp->query_vars['event'] : '' );
			$season = un_seo_url( isset( $wp->query_vars['season'] ) ? $wp->query_vars['season'] : '' );
			$club   = un_seo_url( isset( $wp->query_vars['club_name'] ) ? $wp->query_vars['club_name'] : '' );
			$player = un_seo_url( isset( $wp->query_vars['player_id'] ) ? $wp->query_vars['player_id'] : '' );
			if ( $season ) {
				$event .= ' ' . $season;
			}
			if ( $player ) {
				$title = $player . ' - ' . $event . ' - ' . $site_name;
			} elseif ( $club ) {
				$title = $club . ' - ' . $event . ' - ' . $site_name;
			} else {
				$title = $event . ' - ' . $site_name;
			}
		}
		if ( 'cup' === $slug ) {
			$event  = un_seo_url( isset( $wp->query_vars['event'] ) ? $wp->query_vars['event'] : '' );
			$season = un_seo_url( isset( $wp->query_vars['season'] ) ? $wp->query_vars['season'] : '' );
			$title  = $event;
			if ( $season ) {
				$title .= ' - ' . $season;
			}
			$title .= ' - ' . $site_name;
		}
		if ( 'league' === $slug ) {
			$league = un_seo_url( isset( $wp->query_vars['league_name'] ) ? $wp->query_vars['league_name'] : '' );
			$season = un_seo_url( isset( $wp->query_vars['season'] ) ? $wp->query_vars['season'] : '' );
			$team   = un_seo_url( isset( $wp->query_vars['team'] ) ? $wp->query_vars['team'] : '' );
			if ( $season ) {
				$league .= ' - ' . $season;
			}
			if ( $team ) {
				$title = $team . ' - ' . $league . ' - ' . $site_name;
			} else {
				$title = $league . ' - ' . $site_name;
			}
		}
		if ( 'match' === $slug ) {
			$league    = un_seo_url( isset( $wp->query_vars['league_name'] ) ? $wp->query_vars['league_name'] : '' );
			$season    = un_seo_url( isset( $wp->query_vars['season'] ) ? $wp->query_vars['season'] : '' );
			$team_home = un_seo_url( isset( $wp->query_vars['teamHome'] ) ? $wp->query_vars['teamHome'] : '' );
			$team_away = un_seo_url( isset( $wp->query_vars['teamAway'] ) ? $wp->query_vars['teamAway'] : '' );
			if ( $season ) {
				$league .= ' - ' . $season;
			}
			if ( $team_home && $team_away ) {
				$title = $team_home . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $team_away . ' - ' . $league . ' - ' . $site_name;
			} else {
				$title = __( 'Match', 'racketmanager' ) . ' - ' . $league . ' - ' . $site_name;
			}
		}
		if ( 'league-entry' === $slug || 'cup-entry' === $slug ) {
			$competition_name = un_seo_url( isset( $wp->query_vars['competition_name'] ) ? ucwords( $wp->query_vars['competition_name'] ) : '' );
			$season           = un_seo_url( isset( $wp->query_vars['season'] ) ? $wp->query_vars['season'] : '' );
			$club             = un_seo_url( isset( $wp->query_vars['club_name'] ) ? $wp->query_vars['club_name'] : '' );
			$title            = $competition_name;
			if ( $season ) {
				$title .= ' - ' . $season;
			}
			$title .= ' - ' . __( 'Entry Form', 'racketmanager' );
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}
		if ( 'tournament-entry' === $slug ) {
			$tournament  = un_seo_url( isset( $wp->query_vars['tournament'] ) ? ucwords( $wp->query_vars['tournament'] ) : '' );
			$competition = un_seo_url( isset( $wp->query_vars['competition_name'] ) ? ucwords( $wp->query_vars['competition_name'] ) : '' );
			if ( $tournament ) {
				$title = $tournament;
			} else {
				$title = $competition;
			}
			$title .= ' - ' . __( 'Tournament Entry Form', 'racketmanager' ) . ' - ' . $site_name;
		}
		if ( 'tournament' === $slug ) {
			$tournament = un_seo_url( isset( $wp->query_vars['tournament'] ) ? ucwords( $wp->query_vars['tournament'] ) : __( 'Latest', 'racketmanager' ) );
			$draw       = un_seo_url( isset( $wp->query_vars['draw'] ) ? $wp->query_vars['draw'] : '' );
			$event      = un_seo_url( isset( $wp->query_vars['event'] ) ? $wp->query_vars['event'] : '' );
			$player     = un_seo_url( isset( $wp->query_vars['player'] ) ? $wp->query_vars['player'] : '' );
			$tab        = un_seo_url( isset( $wp->query_vars['tab'] ) ? $wp->query_vars['tab'] : '' );
			$title      = '';
			if ( $player ) {
				$title .= $player . ' - ' . __( 'Player', 'racketmanager' ) . ' - ';
			}
			if ( $draw ) {
				$title .= $draw . ' ' . __( 'Draw', 'racketmanager' ) . ' - ';
			}
			if ( $event ) {
				$title .= $event . ' ' . __( 'Event', 'racketmanager' ) . ' - ';
			}
			if ( 'matches' === $tab ) {
				$title .= __( 'Matches', 'racketmanager' ) . ' - ';
			}
			$title .= $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
			$title .= ' - ' . $site_name;
		}
		if ( 'club' === $slug ) {
			$club  = un_seo_url( isset( $wp->query_vars['club_name'] ) ? $wp->query_vars['club_name'] : '' );
			$title = '';
			if ( $club ) {
				$title = $club;
			} else {
				$title = __( 'Clubs', 'racketmanager' );
			}
			$title .= ' - ' . $site_name;
		}
		return $title;
	}
	/**
	 * Chase pending results
	 *
	 * @param int $competition Competiton id.
	 * @return void
	 */
	public function chase_pending_results( $competition ) {
		$result_pending                 = $this->get_options( $competition )['resultPending'];
		$match_args                     = array();
		$match_args['time']             = 'outstanding';
		$match_args['competition_type'] = 'league';
		$match_args['orderby']          = array(
			'date' => 'ASC',
			'id'   => 'ASC',
		);
		$match_args['timeOffset']       = $result_pending;
		$matches                        = $this->get_matches( $match_args );
		foreach ( $matches as $match ) {
			$this->chase_match_result( $match->id, $result_pending );
		}
	}

	/**
	 * Chase match results
	 *
	 * @param int $match_id Match id.
	 * @param int $time_period time Period that result is overdue.
	 * @return boolean $message_sent Indicator to show if message was sent.
	 */
	public function chase_match_result( $match_id, $time_period = false ) {
		global $racketmanager;
		$match                       = get_match( $match_id );
		$message_sent                = false;
		$headers                     = array();
		$from_email                  = $this->get_confirmation_email( $match->league->event->competition->type );
		$headers[]                   = 'From: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$headers[]                   = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$message_args                = array();
		$message_args['time_period'] = $time_period;
		$message_args['from_email']  = $from_email;

		$email_subject = $racketmanager->site_name . ' - ' . $match->league->title . ' - ' . $match->get_title() . ' Match result pending';
		$email_to      = '';
		if ( isset( $match->teams['home']->contactemail ) ) {
			$email_to = $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
			$club     = get_club( $match->teams['home']->affiliatedclub );
			if ( isset( $club->match_secretary_email ) ) {
				$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
			}
			$email_message = racketmanager_result_outstanding_notification( $match->id, $message_args );
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			$message_sent = true;
		}
		return $message_sent;
	}

	/**
	 * Chase pending approvals
	 *
	 * @param int $competition Competiton id.
	 * @return void
	 */
	public function chase_pending_approvals( $competition ) {
		$confirmation_timeout           = $this->get_options( $competition )['confirmationTimeout'];
		$match_args                     = array();
		$match_args['confirmed']        = 'true';
		$match_args['competition_type'] = 'league';
		$match_args['orderby']          = array(
			'date' => 'ASC',
			'id'   => 'ASC',
		);
		$match_args['timeOffset']       = $confirmation_timeout;
		$matches                        = $this->get_matches( $match_args );
		foreach ( $matches as $match ) {
			$this->complete_match_result( $match, $confirmation_timeout );
		}
		$confirmation_pending           = $this->get_options( $competition )['confirmationPending'];
		$match_args                     = array();
		$match_args['confirmed']        = 'true';
		$match_args['competition_type'] = 'league';
		$match_args['orderby']          = array(
			'updated' => 'ASC',
			'id'      => 'ASC',
		);
		$match_args['timeOffset']       = $confirmation_pending;
		$matches                        = $this->get_matches( $match_args );
		foreach ( $matches as $match ) {
			$this->chase_match_approval( $match->id, $confirmation_pending );
		}
	}

	/**
	 * Complete match result
	 *
	 * @param object $match Match id.
	 * @param int    $confirmation_timeout time Period that match result confirmation is overdue.
	 * @return int number of matches completed.
	 */
	public function complete_match_result( $match, $confirmation_timeout ) {
		$this->chase_match_approval( $match->id, $confirmation_timeout, 'override' );
		$league = get_league( $match->league_id );
		$final  = false;
		$league->set_finals( $final );
		$result_matches               = array();
		$home_points                  = array();
		$away_points                  = array();
		$home_team                    = array();
		$away_team                    = array();
		$custom                       = array();
		$result_matches[ $match->id ] = $match->id;
		$home_points[ $match->id ]    = $match->home_points;
		$away_points[ $match->id ]    = $match->away_points;
		$home_team[ $match->id ]      = $match->home_team;
		$away_team[ $match->id ]      = $match->away_team;
		$custom[ $match->id ]         = $match->custom;
		$season                       = $match->season;
		return $league->update_match_results( $result_matches, $home_points, $away_points, $custom, $season, $final );
	}

	/**
	 * Chase match approval
	 *
	 * @param object  $match_id Match id.
	 * @param int     $time_period time Period that match result confirmation is overdue.
	 * @param boolean $override Override indicator.
	 * @return boolean $message_sent Indicator to show if message was sent.
	 */
	public function chase_match_approval( $match_id, $time_period = false, $override = false ) {
		global $racketmanager;
		$match                       = get_match( $match_id );
		$message_sent                = false;
		$headers                     = array();
		$from_email                  = $this->get_confirmation_email( $match->league->event->competition->type );
		$headers[]                   = 'From: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$headers[]                   = 'cc: ' . ucfirst( $match->league->event->competition->type ) . ' Secretary <' . $from_email . '>';
		$message_args                = array();
		$message_args['outstanding'] = true;
		$message_args['time_period'] = $time_period;
		$message_args['override']    = $override;
		$message_args['from_email']  = $from_email;
		$msg_end                     = 'approval pending';
		if ( $override ) {
			$msg_end = 'complete';
		}
		$email_subject = $racketmanager->site_name . ' - ' . $match->league->title . ' - ' . $match->get_title() . ' ' . $msg_end;
		$email_to      = '';
		if ( isset( $match->home_captain ) ) {
			if ( isset( $match->teams['away']->contactemail ) ) {
				$email_to = $match->teams['away']->captain . ' <' . $match->teams['away']->contactemail . '>';
				$club     = get_club( $match->teams['away']->affiliatedclub );
				if ( isset( $club->match_secretary_email ) ) {
					$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
				}
			}
		} elseif ( isset( $match->away_captain ) ) {
			if ( isset( $match->teams['home']->contactemail ) ) {
				$email_to = $match->teams['home']->captain . ' <' . $match->teams['home']->contactemail . '>';
				$club     = get_club( $match->teams['home']->affiliatedclub );
				if ( isset( $club->match_secretary_email ) ) {
					$headers[] = 'cc: ' . $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
				}
			}
		}
		if ( ! empty( $email_to ) ) {
			$email_message = racketmanager_captain_result_notification( $match->id, $message_args );
			wp_mail( $email_to, $email_subject, $email_message, $headers );
			$message_sent = true;
		}
		return $message_sent;
	}

	/**
	 * Adds our templates
	 */
	public function add_racketmanager_templates() {
		// Add your templates to this array.
		$this->templates = array(
			'templates/page_template/template_notitle.php' => 'No Title',
			'templates/page_template/template_member_account.php' => 'Member Account',
		);

		// Add a filter to the wp 4.7 version attributes metabox.
		add_filter( 'theme_page_templates', array( $this, 'racketmanager_templates_as_option' ) );

		// Add a filter to the save post to inject our template into the page cache.
		add_filter( 'wp_insert_post_data', array( $this, 'register_racketmanager_templates' ) );

		// Add a filter to the template include to determine if the page has our.
		// template assigned and return it's path.
		add_filter( 'template_include', array( $this, 'racketmanager_load_template' ) );

		add_filter( 'archive_template', array( $this, 'racketmanager_archive_template' ) );
	}

	/**
	 * Adds our templates to the page dropdown
	 *
	 * @param array $posts_templates array of post templates.
	 */
	public function racketmanager_templates_as_option( $posts_templates ) {
		return array_merge( $posts_templates, $this->templates );
	}

	/**
	 * Adds our templates to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 *
	 * @param array $atts array of attributes.
	 */
	public function register_racketmanager_templates( $atts ) {

		// Create the key used for the themes cache.
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array.
		$page_templates = wp_get_theme()->get_page_templates();
		if ( empty( $page_templates ) ) {
			$page_templates = array();
		}

		// New cache, therefore remove the old one.
		wp_cache_delete( $cache_key, 'themes' );

		// Now add our template to the list of templates by merging our templates.
		// with the existing templates array from the cache.
		$page_templates = array_merge( $page_templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing available templates.
		wp_cache_add( $cache_key, $page_templates, 'themes', 1800 );

		return $atts;
	}

	/**
	 * Checks if the template is assigned to the page
	 *
	 * @param string $template template.
	 */
	public function racketmanager_load_template( $template ) {

		// Get global post.
		global $post;

		// Return template if post is empty or if we don't have a custom one defined.
		if ( ! $post || ! isset( $this->templates[ get_post_meta( $post->ID, '_wp_page_template', true ) ] ) ) {
			return $template;
		}

		$file = RACKETMANAGER_PATH . get_post_meta( $post->ID, '_wp_page_template', true );

		// Just to be safe, we check if the file exist first.
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo esc_html( $file );
		}

		// Return template.
		return $template;
	}

	/**
	 * Load specific archive templates
	 *
	 * @param string $template template.
	 */
	public function racketmanager_archive_template( $template ) {
		global $post;

		if ( is_category( 'rules' ) ) {
			$template = RACKETMANAGER_PATH . 'templates/pages/category-rules.php';
		}
		if ( is_category( 'how-to' ) ) {
			$template = RACKETMANAGER_PATH . 'templates/pages/category-how-to.php';
		}
		return $template;
	}

	/**
	 * Register exporter array
	 *
	 * @param array $exporters_array template.
	 */
	public function racketmanager_register_exporter( $exporters_array ) {
		$exporters_array['racketmanager_exporter'] = array(
			'exporter_friendly_name' => 'Racketmanager exporter',
			'callback'               => array( &$this, 'racketmanager_privacy_exporter' ),
		);
		return $exporters_array;
	}

	/**
	 * Run privacy exporter
	 *
	 * @param string $email_address email address to send report.
	 * @param int    $page how many pages.
	 */
	public function racketmanager_privacy_exporter( $email_address, $page = 1 ) {
		$page = (int) $page;

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$user_meta = get_user_meta( $user->ID );

		$user_prop_to_export = array(
			'gender'        => __( 'Gender', 'racketmanager' ),
			'year_of_birth' => __( 'Year of birth', 'racketmanager' ),
			'btm'           => __( 'LTA Tennis Number', 'racketmanager' ),
			'remove_date'   => __( 'User Removed Date', 'racketmanager' ),
			'contactno'     => __( 'Telephone Number', 'racketmanager' ),
		);

		$user_data_to_export = array();

		foreach ( $user_prop_to_export as $key => $name ) {
			switch ( $key ) {
				case 'gender':
				case 'btm':
				case 'year_of_birth':
				case 'remove_date':
				case 'contactno':
					$value = isset( $user_meta[ $key ][0] ) ? $user_meta[ $key ][0] : '';
					break;
				default:
					$value = '';
			}

			if ( ! empty( $value ) ) {
				$user_data_to_export[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		$data_to_export[] = array(
			'group_id'    => 'user',
			'group_label' => __( 'User', 'racketmanager' ),
			'item_id'     => "user-{$user->ID}",
			'data'        => $user_data_to_export,
		);

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * Register Widget
	 */
	public function register_widget() {
		register_widget( 'Racketmanager\RacketManager_Widget' );
	}

	/**
	 * Load libraries
	 */
	private function load_libraries() {
		global $racketmanager_shortcodes, $racketmanager_login, $racketmanager_ajax_frontend;

		// Objects.
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-charges.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-invoice.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-club.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-championship.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-competition.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-event.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-league.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-league-team.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-match.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-rubber.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-svg-icons.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-team.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-player.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-tournament.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-validator.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-entry-form-validator.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-exporter.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-results-report.php';

		/*
		* load sports libraries
		*/
		// First read files in racketmanager sports directory, then overwrite with sports files in user stylesheet directory.
		$files = array_merge( $this->read_directory( RACKETMANAGER_PATH . 'sports' ), $this->read_directory( get_stylesheet_directory() . '/sports' ) );

		// load files.
		foreach ( $files as $file ) {
			require_once $file;
		}

		// Global libraries.
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-ajax.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-ajax-frontend.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-shortcodes.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-shortcodes-competition.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-shortcodes-email.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-shortcodes-tournament.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-login.php';
		require_once RACKETMANAGER_PATH . 'include/class-racketmanager-widget.php';

		// template tags & functions.
		require_once RACKETMANAGER_PATH . '/template-tags.php';
		require_once RACKETMANAGER_PATH . '/functions.php';

		$racketmanager_ajax_frontend = new RacketManager_Ajax_Frontend();

		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		$racketmanager_shortcodes_competition = new Racketmanager_Shortcodes_Competition();
		$racketmanager_shortcodes_emails      = new Racketmanager_Shortcodes_Email();
		$racketmanager_shortcodes_tournament  = new Racketmanager_Shortcodes_Tournament();
		$racketmanager_shortcodes             = new Racketmanager_Shortcodes();
		$racketmanager_login                  = new RacketManager_Login();
	}

	/**
	 * Get standings display options
	 *
	 * @return array
	 */
	public function get_standings_display_options() {
		$options = array(
			'status'     => __( 'Team Status', 'racketmanager' ),
			'pld'        => __( 'Played Games', 'racketmanager' ),
			'won'        => __( 'Won Games', 'racketmanager' ),
			'tie'        => __( 'Tie Games', 'racketmanager' ),
			'lost'       => __( 'Lost Games', 'racketmanager' ),
			'winPercent' => __( 'Win Percentage', 'racketmanager' ),
			'last5'      => __( 'Last 5 Matches', 'racketmanager' ),
			'sets'       => __( 'Sets', 'racketmanager' ),
			'games'      => __( 'Games', 'racketmanager' ),
		);

		/**
		* Fires when standings options are generated
		*
		* @param array $options
		* @return array
		* @category wp-filter
		*/
		return apply_filters( 'racketmanager_competition_standings_options', $options );
	}

	/**
	 * Read files in directory
	 *
	 * @param string $dir directory name.
	 * @return array
	 */
	public function read_directory( $dir ) {
		$files = array();

		if ( file_exists( $dir ) ) {
			$handle = opendir( $dir );
			do {
				$file      = readdir( $handle );
				$file_info = pathinfo( $dir . '/' . $file );
				$file_type = ( isset( $file_info['extension'] ) ) ? $file_info['extension'] : '';
				if ( '.' !== $file && '..' !== $file && ! is_dir( $file ) && substr( $file, 0, 1 ) !== '.' && 'php' === $file_type ) {
					$files[ $file ] = $dir . '/' . $file;
				}
			} while ( false !== $file );
		}

		return $files;
	}

	/**
	 * Load options
	 */
	private function load_options() {
		$this->options     = get_option( 'racketmanager' );
		$this->date_format = get_option( 'date_format' );
		$this->time_format = get_option( 'time_format' );
		$this->site_name   = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$this->admin_email = get_option( 'admin_email' );
		$this->site_url    = get_option( 'siteurl' );
	}

	/**
	 * Get options
	 *
	 * @param boolean $index index lookup (optional).
	 */
	public function get_options( $index = false ) {
		if ( $index ) {
			return $this->options[ $index ];
		} else {
			return $this->options;
		}
	}

	/**
	 * Load Javascript
	 */
	public function load_scripts() {
		wp_register_script( 'racketmanager', RACKETMANAGER_URL . 'js/racketmanager.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'jquery-effects-core', 'jquery-effects-slide', 'thickbox' ), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );
		wp_enqueue_script( 'racketmanager' );
		wp_localize_script(
			'racketmanager',
			'ajax_var',
			array(
				'url'        => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'ajax-nonce' ),
			)
		);
		wp_enqueue_script( 'password-strength-meter' );
		wp_enqueue_script( 'password-strength-meter-mediator', RACKETMANAGER_URL . 'js/password-strength-meter-mediator.js', array( 'password-strength-meter' ), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );
		wp_localize_script(
			'password-strength-meter',
			'pwsL10n',
			array(
				'empty'    => __( 'Strength indicator', 'racketmanager' ),
				'short'    => __( 'Very weak', 'racketmanager' ),
				'bad'      => __( 'Weak', 'racketmanager' ),
				'good'     => __( 'Good', 'racketmanager' ),
				'strong'   => __( 'Strong', 'racketmanager' ),
				'mismatch' => __( 'Mismatch', 'racketmanager' ),
			)
		);
		?>
	<script type="text/javascript">
	//<![CDATA[
	RacketManagerAjaxL10n = {
		blogUrl: "<?php bloginfo( 'wpurl' ); ?>",
		pluginUrl: "<?php echo esc_url( RACKETMANAGER_URL ); ?>",
		requestUrl: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
		Edit: "<?php esc_html_e( 'Edit', 'racketmanager' ); ?>",
		Post: "<?php esc_html_e( 'Post', 'racketmanager' ); ?>",
		Save: "<?php esc_html_e( 'Save', 'racketmanager' ); ?>",
		Cancel: "<?php esc_html_e( 'Cancel', 'racketmanager' ); ?>",
		pleaseWait: "<?php esc_html_e( 'Please wait...', 'racketmanager' ); ?>",
		Revisions: "<?php esc_html_e( 'Page Revisions', 'racketmanager' ); ?>",
		Time: "<?php esc_html_e( 'Insert time', 'racketmanager' ); ?>",
		Options: "<?php esc_html_e( 'Options', 'racketmanager' ); ?>",
		Delete: "<?php esc_html_e( 'Delete', 'racketmanager' ); ?>"
	}
	//]]>
	</script>
		<?php
	}

	/**
	 * Load CSS styles
	 */
	public function load_styles() {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'racketmanager-print', RACKETMANAGER_URL . 'css/print.css', false, RACKETMANAGER_VERSION, 'print' );
		wp_enqueue_style( 'racketmanager-modal', RACKETMANAGER_URL . 'css/modal.css', false, RACKETMANAGER_VERSION, 'screen' );
		wp_enqueue_style( 'racketmanager', RACKETMANAGER_URL . 'css/style.css', false, RACKETMANAGER_VERSION, 'screen' );

		$jquery_ui_version = '1.13.2';
		wp_register_style( 'jquery-ui', RACKETMANAGER_URL . 'css/jquery/jquery-ui.min.css', false, $jquery_ui_version, 'all' );
		wp_register_style( 'jquery-ui-structure', RACKETMANAGER_URL . 'css/jquery/jquery-ui.structure.min.css', array( 'jquery-ui' ), $jquery_ui_version, 'all' );
		wp_register_style( 'jquery-ui-theme', RACKETMANAGER_URL . 'css/jquery/jquery-ui.theme.min.css', array( 'jquery-ui', 'jquery-ui-structure' ), $jquery_ui_version, 'all' );
		wp_register_style( 'jquery-ui-autocomplete', RACKETMANAGER_URL . 'css/jquery/jquery-ui.autocomplete.min.css', array( 'jquery-ui', 'jquery-ui-autocomplete' ), $jquery_ui_version, 'all' );

		wp_enqueue_style( 'jquery-ui-structure' );
		wp_enqueue_style( 'jquery-ui-theme' );

		ob_start();
		require_once RACKETMANAGER_PATH . 'css/colors.css.php';
		$css = ob_get_contents();
		ob_end_clean();

		wp_add_inline_style( 'racketmanager', $css );
	}
	/**
	 * Create formatted url
	 */
	public function racketmanager_rewrites() {
		$this->rewrite_tournament();
		// league type info.
		add_rewrite_rule(
			'leagues/(.+?)-news/?$',
			'index.php?pagename=leagues%2F$matches[1]',
			'top'
		);
		// daily matches - date.
		add_rewrite_rule(
			'leagues/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
			'index.php?pagename=leagues%2Fdaily-matches&match_date=$matches[1]-$matches[2]-$matches[3]',
			'top'
		);
		// daily matches.
		add_rewrite_rule(
			'leagues/daily-matches/?$',
			'index.php?pagename=leagues%2Fdaily-matches',
			'top'
		);
		// latest results.
		add_rewrite_rule(
			'leagues/latest-results/?$',
			'index.php?pagename=leagues%2Flatest-results',
			'top'
		);
		$this->rewrite_league();
		// cup - season - player.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup - season - player.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup - season - players.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// cup - season - players.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// cup - season - teams.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// cup - season - teams.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// cup - season - team.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// cup - season.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// cup - season.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// cup.
		add_rewrite_rule(
			'cup/(.+?)/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]',
			'top'
		);
		// cup - season (winners).
		add_rewrite_rule(
			'cups/(.+?)/winners/([0-9]{4})?$',
			'index.php?pagename=cups%2F$matches[1]%2Fwinners&season=$matches[2]',
			'top'
		);
		// cup - season.
		add_rewrite_rule(
			'cups/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
			'index.php?pagename=cups%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
			'top'
		);
		// cup entry form - type - season - club.
		add_rewrite_rule(
			'cups/(.+?)-entry/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=cups%2Fcup-entry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// cup.
		add_rewrite_rule(
			'cups/(.+?)/?$',
			'index.php?pagename=cups%2Fcup&event=$matches[1]',
			'top'
		);
		// player.
		add_rewrite_rule(
			'clubs/(.+?)/(.+?)/?$',
			'index.php?pagename=club%2Fplayer&club_name=$matches[1]&player_id=$matches[2]',
			'top'
		);
		// club.
		add_rewrite_rule(
			'clubs\/(.+?)\/?$',
			'index.php?pagename=club&club_name=$matches[1]',
			'top'
		);
		// invoice.
		add_rewrite_rule(
			'invoice\/(.+?)\/?$',
			'index.php?pagename=invoice&id=$matches[1]',
			'top'
		);
	}
	/**
	 * Rewrite league urls function
	 *
	 * @return void
	 */
	private function rewrite_league() {
		// league entry form - competition - season - club.
		add_rewrite_rule(
			'leagues/(.+?)-entry/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=leagues%2Fleague-entry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// league event - season.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// league event - season - club.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/club/(.+?)/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]&club_name=$matches[3]',
			'top'
		);
		// league event - season - clubs.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/clubs/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]&tab=clubs',
			'top'
		);
		// league event - season - team.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// league event - season - player.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// league event - season - players.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// league event.
		add_rewrite_rule(
			'leagues/(.+?)/?$',
			'index.php?pagename=leagues%2Fevent&event=$matches[1]',
			'top'
		);

		// league - season - matchday - team.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/day([0-9]{1,2})/(.+?)/?$',
			'index.php?pagename=leagues%2Fleague&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&team=$matches[4]',
			'top'
		);
		// league - season - matchday.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/day([0-9]{1,2})/?$',
			'index.php?pagename=leagues%2Fleague&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]',
			'top'
		);
		// league - season - player.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=leagues%2Fleague&league_name=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// league - season - players.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=leagues%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// league - season - teams.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=leagues%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// league - season - team.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=leagues%2Fleague&&league_name=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// league - season.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})\/?$',
			'index.php?pagename=leagues%2Fleague&&league_name=$matches[1]&season=$matches[2]',
			'top'
		);
		// league.
		add_rewrite_rule(
			'league/(.+?)/?$',
			'index.php?pagename=leagues%2Fleague&&league_name=$matches[1]',
			'top'
		);

		// competition - season.
		add_rewrite_rule(
			'leagues/(.+?)/(.+?)-competition/([0-9]{4})?$',
			'index.php?pagename=leagues/$matches[1]/$matches[2]-competition&season=$matches[3]',
			'top'
		);
		// competition.
		add_rewrite_rule(
			'leagues/(.+?)/(.+?)-competition/?$',
			'index.php?pagename=leagues/$matches[1]/$matches[2]-competition',
			'top'
		);
		// league - season - matchday - team.
		add_rewrite_rule(
			'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/(.+?)/?$',
			'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]&team=$matches[6]',
			'top'
		);
		// league - season - matchday.
		add_rewrite_rule(
			'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/?$',
			'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]',
			'top'
		);
		// league - season - team.
		add_rewrite_rule(
			'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&team=$matches[5]',
			'top'
		);
		// league.
		add_rewrite_rule(
			'leagues/(.+?)-(.+?)-([0-9]{1})/?$',
			'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]%20$matches[2]%20$matches[3]',
			'top'
		);
		// league - season.
		add_rewrite_rule(
			'leagues\/([a-z]+?)-([a-z]+?)-([0-9]{1})\/([0-9]{4})\/?$',
			'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]',
			'top'
		);
		// league - season - round - match - leg.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/leg-([0-9]{1})/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]&leg=$matches[6]',
			'top'
		);
		// league - season - matchday - match.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/day([0-9]{1,2})/(.+?)-vs-(.+?)/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]',
			'top'
		);
		// league - season - round - match.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]',
			'top'
		);
	}
	/**
	 * Rewrite tournament urls function
	 *
	 * @return void
	 */
	private function rewrite_tournament() {
		// tournament - match.
		add_rewrite_rule(
			'tournament/(.+?)/match/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament%2Fmatch&tournament=$matches[1]&match_id=$matches[2]',
			'top'
		);
		// tournament - matches - match date.
		add_rewrite_rule(
			'tournament/(.+?)/matches/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&match_date=$matches[2]&tab=matches',
			'top'
		);
		// tournament - matches.
		add_rewrite_rule(
			'tournament/(.+?)/matches/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=matches',
			'top'
		);
		// tournament - name - winners.
		add_rewrite_rule(
			'tournament/(.+?)/winners/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=winners',
			'top'
		);
		// tournament - name - player.
		add_rewrite_rule(
			'tournament/(.+?)/players/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&player=$matches[2]&tab=players',
			'top'
		);
		// tournament - name - players.
		add_rewrite_rule(
			'tournament/(.+?)/players/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=players',
			'top'
		);
		// tournament - name - draws.
		add_rewrite_rule(
			'tournament/(.+?)/draws/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=draws',
			'top'
		);
		// tournament - name - draw.
		add_rewrite_rule(
			'tournament/(.+?)/draw/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&draw=$matches[2]&tab=draws',
			'top'
		);
		// tournament - name - events.
		add_rewrite_rule(
			'tournament/(.+?)/events/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=events',
			'top'
		);
		// tournament - name - event.
		add_rewrite_rule(
			'tournament/(.+?)/event/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&event=$matches[2]&tab=events',
			'top'
		);
		// tournament - name.
		add_rewrite_rule(
			'tournament/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]',
			'top'
		);
		// tournament.
		add_rewrite_rule(
			'tournament/?$',
			'index.php?pagename=tournaments%2Ftournament',
			'top'
		);
		// tournament entry form - season - club.
		add_rewrite_rule(
			'tournaments/(.+?)-entry/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament-entry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// tournament entry form - season.
		add_rewrite_rule(
			'tournaments/(.+?)-entry/([0-9]{4})/?$',
			'index.php?pagename=tournaments%2Ftournament-entry&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// tournament entry form - name - club.
		add_rewrite_rule(
			'tournaments/entry-form/(.+?)/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament-entry&tournament=$matches[1]&club=$matches[2]',
			'top'
		);
		// tournament entry form - name.
		add_rewrite_rule(
			'tournaments/entry-form/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament-entry&&tournament=$matches[1]',
			'top'
		);
		// tournament winners - type - season - tournament.
		add_rewrite_rule(
			'tournaments/(.+?)/winners/(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&tournament=$matches[2]&type=$matches[1]',
			'top'
		);
		// tournament winners - type - season.
		add_rewrite_rule(
			'tournaments/(.+?)/winners/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&type=$matches[1]',
			'top'
		);
		// tournament order of play - type - season - tournament.
		add_rewrite_rule(
			'tournaments/(.+?)/order-of-play/(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&tournament=$matches[2]&type=$matches[1]',
			'top'
		);
		// tournament order of play - type - season.
		add_rewrite_rule(
			'tournaments/(.+?)/order-of-play/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&type=$matches[1]',
			'top'
		);
		// tournament event - season - players.
		add_rewrite_rule(
			'tournaments/(.+?)/([0-9]{4})/players?$',
			'index.php?pagename=tournaments%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// tournament event - season - player.
		add_rewrite_rule(
			'tournaments/(.+?)/([0-9]{4})/player/(.+?)?$',
			'index.php?pagename=tournaments%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// tournament event - season.
		add_rewrite_rule(
			'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
			'top'
		);
		// tournament event.
		add_rewrite_rule(
			'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]',
			'top'
		);
	}
	/**
	 * Add html content type to mail header
	 *
	 * @param array $args arguments for mail message.
	 * @return args
	 */
	public function racketmanager_mail( $args ) {
		$headers = $args['headers'];
		if ( ! $headers ) {
			$headers = array();
		}
		$headers[]       = 'Content-Type: text/html; charset=UTF-8';
		$args['headers'] = $headers;
		return $args;
	}

	/**
	 * Change email address
	 *
	 * @param array $email_change email change message.
	 * @param array $user original user details (not used).
	 * @param array $user_data new user details.
	 * @return args
	 */
	public function racketmanager_change_email_address( $email_change, $user, $user_data ) {
		global $racketmanager_shortcodes, $racketmanager;

		$vars['site_name']       = $racketmanager->site_name;
		$vars['site_url']        = $racketmanager->site_url;
		$vars['user_login']      = $user_data['user_login'];
		$vars['display_name']    = $user['display_name'];
		$vars['email_link']      = $racketmanager->admin_email;
		$email_change['message'] = $racketmanager_shortcodes->load_template( 'email-email-change', $vars, 'email' );
		return $email_change;
	}
	/**
	 * Redirect users on certain pages to login function
	 */
	public function redirect_to_login() {
		global $wp_query;
		if ( ! is_user_logged_in() ) {
			$redirect_page = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$slug          = get_post_field( 'post_name' );
			switch ( $slug ) {
				case 'tournament-entry':
				case 'league-entry':
				case 'cup-entry':
					wp_safe_redirect( wp_login_url( $redirect_page ) );
					exit;
				default:
			}
		}
	}
	/**
	 * Delete page
	 *
	 * @param string $page_name page name.
	 */
	public function delete_racketmanager_page( $page_name ) {
		$option  = 'racketmanager_page_' . $page_name . '_id';
		$page_id = intval( get_option( $option ) );

		// Force delete this so the Title/slug "Menu" can be used again.
		if ( $page_id ) {
			wp_delete_post( $page_id, true );
			delete_option( $option );
		}
	}

	/**
	 * Set message
	 *
	 * @param string  $message message.
	 * @param boolean $error triggers error message if true.
	 */
	public function set_message( $message, $error = false ) {
		$this->error   = $error;
		$this->message = $message;
	}

	/**
	 * Get league types
	 *
	 * @return array
	 */
	public function get_league_types() {
		$types = array( 'default' => __( 'Default', 'racketmanager' ) );
		/**
		* Add custom league types
		*
		* @param array $types
		* @return array
		* @category wp-filter
		*/
		$types = apply_filters( 'racketmanager_sports', $types );
		asort( $types );

		return $types;
	}

	/**
	 * Get seasons
	 *
	 * @param string $order sort order.
	 * @return array
	 */
	public function get_seasons( $order = 'ASC' ) {
		global $wpdb;

		$order_by_string = '`name` ' . $order;
		$order_by        = $order_by_string;
		$seasons         = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `name`, `id` FROM {$wpdb->racketmanager_seasons} ORDER BY $order_by"
		);
		$i = 0;
		foreach ( $seasons as $season ) {
			$seasons[ $i ]->id   = $season->id;
			$seasons[ $i ]->name = stripslashes( $season->name );

			$this->seasons[ $season->id ] = $seasons[ $i ];
			++$i;
		}
		return $seasons;
	}

	/**
	 * Get season
	 *
	 * @param array $args query arguments.
	 * @return array
	 */
	public function get_season( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'id'   => false,
			'name' => false,
		);
		$args     = array_merge( $defaults, $args );
		$id       = $args['id'];
		$name     = $args['name'];

		$search_terms = array();
		if ( $id ) {
			$search_terms[] = $wpdb->prepare( '`id` = %d', intval( $id ) );
		}
		if ( $name ) {
			$search_terms[] = $wpdb->prepare( '`name` = %s', $name );
		}
		$search = '';

		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}

		$sql = "SELECT `id`, `name` FROM {$wpdb->racketmanager_seasons} $search ORDER BY `name`";

		$season = wp_cache_get( md5( $sql ), 'seasons' );
		if ( ! $season ) {
			$season = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $season, 'seasons' );
		}

		if ( ! isset( $season[0] ) ) {
			return false;
		}

		return $season[0];
	}

	/**
	 * Get tournaments from database
	 *
	 * @param array $args query arguments.
	 * @return array
	 */
	public function get_tournaments( $args = array() ) {
		global $wpdb;
		$defaults       = array(
			'offset'         => 0,
			'limit'          => 99999999,
			'competition_id' => false,
			'season'         => false,
			'name'           => false,
			'entryopen'      => false,
			'open'           => false,
			'orderby'        => array( 'name' => 'DESC' ),
		);
		$args           = array_merge( $defaults, $args );
		$offset         = $args['offset'];
		$limit          = $args['limit'];
		$competition_id = $args['competition_id'];
		$season         = $args['season'];
		$entry_open     = $args['entryopen'];
		$open           = $args['open'];
		$orderby        = $args['orderby'];

		$search_terms = array();

		if ( $competition_id ) {
			$search_terms[] = $wpdb->prepare( '`competition_id` = %s', $competition_id );
		}
		if ( $season ) {
			$search_terms[] = $wpdb->prepare( '`season` = %s', $season );
		}
		if ( $entry_open ) {
			$search_terms[] = '`closingdate` >= CURDATE()';
		}

		if ( $open ) {
			$search_terms[] = "(`date` >= CURDATE() OR `date` = '0000-00-00')";
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `id` FROM {$wpdb->racketmanager_tournaments} $search ORDER BY $orderby LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$tournaments = wp_cache_get( md5( $sql ), 'tournaments' );
		if ( ! $tournaments ) {
			$tournaments = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $tournaments, 'tournaments' );
		}

		$i = 0;
		foreach ( $tournaments as $i => $tournament ) {
			$tournament = get_tournament( $tournament->id );

			$tournaments[ $i ] = $tournament;
		}

		return $tournaments;
	}

	/**
	 * Get clubs from database
	 *
	 * @param array $args query arguments.
	 * @return object
	 */
	public function get_clubs( $args = array() ) {
		global $wpdb;
		$defaults     = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'type'    => false,
			'name'    => false,
			'orderby' => 'asc',
		);
		$args         = array_merge( $defaults, $args );
		$offset       = $args['offset'];
		$limit        = $args['limit'];
		$type         = $args['type'];
		$orderby      = $args['orderby'];
		$search_terms = array();
		if ( $type && 'all' !== $type ) {
			if ( 'current' === $type ) {
				$search_terms[] = "`type` != 'past'";
			} else {
				$search_terms[] = $wpdb->prepare(
					'`type` = %s',
					$type
				);
			}
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}
		$order = '';
		if ( $orderby ) {
			if ( 'asc' === $orderby ) {
				$order = '`name` ASC';
			} elseif ( 'desc' === $orderby ) {
				$order = '`name` DESC';
			} elseif ( 'rand' === $orderby ) {
				$order = 'RAND()';
			} elseif ( 'menu_order' === $orderby ) {
				$order = '`id` ASC';
			}
		}
		if ( ! empty( $order ) ) {
			$order = 'ORDER BY ' . $order;
		}
		if ( $limit && -1 === $limit ) {
			$limit = 99999999;
		}

		$sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} $search $order LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);

		$clubs = wp_cache_get( md5( $sql ), 'clubs' );
		if ( ! $clubs ) {
			$clubs = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $clubs, 'clubs' );
		}

		$i = 0;
		foreach ( $clubs as $i => $club ) {
			$club = get_club( $club );

			$clubs[ $i ] = $club;
		}

		return $clubs;
	}

	/**
	 * Get competitions from database
	 *
	 * @param array $args query arguements.
	 * @return object
	 */
	public function get_competitions( $args = array() ) {
		global $wpdb;

		$defaults     = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'type'    => false,
			'name'    => false,
			'season'  => false,
			'orderby' => array( 'name' => 'ASC' ),
		);
		$args         = array_merge( $defaults, $args );
		$offset       = $args['offset'];
		$limit        = $args['limit'];
		$type         = $args['type'];
		$name         = $args['name'];
		$season       = $args['season'];
		$orderby      = $args['orderby'];
		$search_terms = array();
		if ( $name ) {
			$name           = $wpdb->esc_like( stripslashes( $name ) ) . '%';
			$search_terms[] = $wpdb->prepare( '`name` like %s', $name );
		}
		if ( $type ) {
			$search_terms[] = $wpdb->prepare( '`type` = %s', $type );
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}
		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;
		$sql     = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `name`, `id`, `type`, `settings`, `seasons` FROM {$wpdb->racketmanager_competitions} $search ORDER BY $orderby LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$competitions = wp_cache_get( md5( $sql ), 'competitions' );
		if ( ! $competitions ) {
			$competitions = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $competitions, 'competitions' );
		}

		$i = 0;
		foreach ( $competitions as $i => $competition ) {
			$competition->name     = stripslashes( $competition->name );
			$competition->seasons  = maybe_unserialize( $competition->seasons );
			$competition->settings = maybe_unserialize( $competition->settings );

			$competition = (object) array_merge( (array) $competition, $competition->settings );

			if ( $season ) {
				if ( array_search( $season, array_column( $competition->seasons, 'name' ), false ) ) {
					$competitions[ $i ] = $competition;
				} else {
					unset( $competitions[ $i ] );
				}
			} else {
				$competitions[ $i ] = $competition;
			}
		}
		return $competitions;
	}
	/**
	 * Get events from database
	 *
	 * @param array $args query arguements.
	 * @return object
	 */
	public function get_events( $args = array() ) {
		global $wpdb;

		$defaults         = array(
			'offset'           => 0,
			'limit'            => 99999999,
			'competition_type' => false,
			'name'             => false,
			'season'           => false,
			'orderby'          => array( 'name' => 'ASC' ),
		);
		$args             = array_merge( $defaults, $args );
		$offset           = $args['offset'];
		$limit            = $args['limit'];
		$competition_type = $args['competition_type'];
		$name             = $args['name'];
		$season           = $args['season'];
		$orderby          = $args['orderby'];
		$search_terms     = array();
		if ( $name ) {
			$name           = $wpdb->esc_like( stripslashes( $name ) ) . '%';
			$search_terms[] = $wpdb->prepare( '`name` like %s', $name );
		}
		if ( $competition_type ) {
			$search_terms[] = $wpdb->prepare( "`competition_id` in (select `id` from {$wpdb->racketmanager_competitions} WHERE `type` = %s)", $competition_type );
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search  = ' WHERE ';
			$search .= implode( ' AND ', $search_terms );
		}
		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$orderby = $orderby_string;
		$sql     = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `name`, `id`, `type`, `settings`, `seasons` FROM {$wpdb->racketmanager_events} $search ORDER BY $orderby LIMIT %d, %d",
			intval( $offset ),
			intval( $limit )
		);
		$events = wp_cache_get( md5( $sql ), 'events' );
		if ( ! $events ) {
			$events = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $events, 'events' );
		}

		$i = 0;
		foreach ( $events as $i => $event ) {
			$event->name     = stripslashes( $event->name );
			$event->seasons  = maybe_unserialize( $event->seasons );
			$event->settings = maybe_unserialize( $event->settings );

			$event = (object) array_merge( (array) $event, $event->settings );

			if ( $season ) {
				if ( array_search( $season, array_column( $event->seasons, 'name' ), false ) ) {
					$events[ $i ] = $event;
				} else {
					unset( $events[ $i ] );
				}
			} else {
				$events[ $i ] = $event;
			}
		}
		return $events;
	}
	/**
	 * Get Team ID for given string
	 *
	 * @param string $title title.
	 * @return int
	 */
	public function getteam_id( $title ) {
		global $wpdb;

		$team = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` = %s",
				$title
			)
		);
		if ( ! isset( $team[0] ) ) {
			return 0;
		} else {
			return $team[0]->id;
		}
	}

	/**
	 * Get club players from database
	 *
	 * @param array $args query arguements.
	 * @return array
	 */
	public function get_club_players( $args ) {
		global $wpdb;

		$defaults = array(
			'count'   => false,
			'team'    => false,
			'club'    => false,
			'player'  => false,
			'gender'  => false,
			'active'  => false,
			'type'    => false,
			'orderby' => array( 'display_name' => 'ASC' ),
		);
		$args     = array_merge( $defaults, (array) $args );
		$count    = $args['count'];
		$team     = $args['team'];
		$type     = $args['type'];
		$club     = $args['club'];
		$player   = $args['player'];
		$gender   = $args['gender'];
		$active   = $args['active'];
		$orderby  = $args['orderby'];

		$search_terms = array();
		if ( $team ) {
			$search_terms[] = $wpdb->prepare( '`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = %d)', intval( $team ) );
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare( '`affiliatedclub` = %d', intval( $club ) );
		}

		if ( $player ) {
			$search_terms[] = $wpdb->prepare( '`player_id` = %d', intval( $player ) );
		}

		if ( $type ) {
			$search_terms[] = '`system_record` IS NULL';
		}

		if ( $active ) {
			$search_terms[] = '`removed_date` IS NULL';
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search = implode( ' AND ', $search_terms );
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$order = $orderby_string;

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_players}";
			if ( '' !== $search ) {
				$sql .= " WHERE $search";
			}
			return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
		}

		$sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID`";
		if ( '' !== $search ) {
			$sql .= " WHERE $search";
		}
		if ( '' !== $order ) {
			$sql .= " ORDER BY $order";
		}

		$club_players = wp_cache_get( md5( $sql ), 'club_players' );
		if ( ! $club_players ) {
			$club_players = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $club_players, 'club_players' );
		}

		$i     = 0;
		$class = '';
		foreach ( $club_players as $club_player ) {
			$class                     = ( 'alternate' === $class ) ? '' : 'alternate';
			$club_players[ $i ]->class = $class;

			$club_players[ $i ] = (object) (array) $club_player;

			$club_players[ $i ]->affiliatedclub = $club_player->affiliatedclub;
			$club_players[ $i ]->roster_id      = $club_player->roster_id;
			$club_players[ $i ]->player_id      = $club_player->player_id;
			$club_players[ $i ]->fullname       = $club_player->fullname;
			$club_players[ $i ]->gender         = get_user_meta( $club_player->player_id, 'gender', true );
			$club_players[ $i ]->type           = get_user_meta( $club_player->player_id, 'racketmanager_type', true );
			$club_players[ $i ]->locked         = get_user_meta( $club_player->player_id, 'locked', true );
			$club_players[ $i ]->locked_date    = get_user_meta( $club_player->player_id, 'locked_date', true );
			$club_players[ $i ]->locked_user    = get_user_meta( $club_player->player_id, 'locked_user', true );
			if ( $club_players[ $i ]->locked_user ) {
				$club_players[ $i ]->locked_user_name = get_userdata( $club_players[ $i ]->locked_user )->display_name;
			} else {
				$club_players[ $i ]->locked_user_name = '';
			}
			$club_players[ $i ]->removed_date = $club_player->removed_date;
			$club_players[ $i ]->removed_user = $club_player->removed_user;
			if ( $club_player->removed_user ) {
				$club_players[ $i ]->removed_user_name = get_userdata( $club_player->removed_user )->display_name;
			} else {
				$club_players[ $i ]->removed_user_name = '';
			}
			$club_players[ $i ]->btm           = get_user_meta( $club_player->player_id, 'btm', true );
			$club_players[ $i ]->year_of_birth = get_user_meta( $club_player->player_id, 'year_of_birth', true );
			$club_players[ $i ]->created_date  = $club_player->created_date;
			$club_players[ $i ]->created_user  = $club_player->created_user;
			if ( $club_player->created_user ) {
				$club_players[ $i ]->created_user_name = get_userdata( $club_player->created_user )->display_name;
			} else {
				$club_players[ $i ]->created_user_name = '';
			}
			if ( $gender && $gender !== $club_players[ $i ]->gender ) {
				unset( $club_players[ $i ] );
			}

			++$i;
		}

		return $club_players;
	}

	/**
	 * Gets single club player entry from database
	 *
	 * @param int     $club_player_id club player ref.
	 * @param boolean $cache use cache flag.
	 * @return array
	 */
	public function get_club_player( $club_player_id, $cache = true ) {
		global $wpdb;

		$sql = "SELECT A.`player_id` as `player_id`, A.`system_record`, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A WHERE A.`id`= '" . intval( $club_player_id ) . "'";

		$club_player = wp_cache_get( md5( $sql ), 'clubplayer' );
		if ( ! $club_player || ! $cache ) {
			$club_player = $wpdb->get_row( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $club_player, 'clubplayer' );
		}
		if ( $club_player ) {
			$club_player->id            = $club_player_id;
			$player                     = get_userdata( $club_player->player_id );
			$club_player->fullname      = $player->display_name;
			$club_player->email         = $player->user_email;
			$player                     = get_user_meta( $club_player->player_id );
			$club_player->firstname     = $player['first_name'][0];
			$club_player->surname       = $player['last_name'][0];
			$club_player->gender        = isset( $player['gender'] ) ? $player['gender'][0] : '';
			$club_player->btm           = isset( $player['btm'] ) ? $player['btm'][0] : '';
			$club_player->year_of_birth = isset( $player['year_of_birth'] ) ? $player['year_of_birth'][0] : '';
			$club_player->locked        = isset( $player['locked'] ) ? $player['locked'][0] : '';
			$club_player->locked_date   = isset( $player['locked_date'] ) ? $player['locked_date'][0] : '';
			$club_player->locked_user   = isset( $player['locked_user'] ) ? $player['locked_user'][0] : '';
		}

		return $club_player;
	}

	/**
	 * Delete Club Player
	 *
	 * @param int $roster_id id of player to be removed from club.
	 * @return boolean
	 */
	public function delete_club_player( $roster_id ) {
		global $wpdb;
		$userid = get_current_user_id();
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_club_players} SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = %d",
				$userid,
				$roster_id
			)
		);
		$this->set_message( __( 'Player removed from club', 'racketmanager' ) );

		return true;
	}

	/**
	 * Get list of players
	 *
	 * @param array $args query arguments.
	 * @return array
	 */
	public function get_all_players( $args ) {
		$defaults = array(
			'name' => false,
		);
		$args     = array_merge( $defaults, (array) $args );
		$name     = $args['name'];
		$search   = '';
		if ( $name ) {
			$search       = '*' . $name . '*';
			$search_terms = 'display_name';
		}

		$orderby_string = 'display_name';
		$order          = 'ASC';

		$user_fields               = array( 'ID', 'display_name' );
		$user_args                 = array();
		$user_args['meta_key']     = 'gender'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$user_args['meta_value']   = 'M,F'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$user_args['meta_compare'] = 'IN';
		$user_args['orderby']      = $orderby_string;
		$user_args['order']        = $order;
		if ( $search ) {
			$user_args['search']         = $search;
			$user_args['search_columns'] = array( $search_terms );
		}
		$user_search = wp_json_encode( $user_args );
		$players     = wp_cache_get( md5( $user_search ), 'players' );
		if ( ! $players ) {
			$user_args['fields'] = $user_fields;
			$players             = get_users( $user_args );
			if ( $players ) {
				$i = 0;
				foreach ( $players as $player ) {
					$player        = get_player( $player->ID );
					$players[ $i ] = $player;
					++$i;
				}
			}
			wp_cache_set( md5( $user_search ), $players, 'players' );
		}
		return $players;
	}

	/**
	 * Get player name
	 *
	 * @param int $player_id player id.
	 * @return string | false
	 */
	public function get_player_name( $player_id ) {
		$player = get_player( $player_id );
		if ( ! $player ) {
			return false;
		}

		return $player->display_name;
	}

	/**
	 * Match query arguments
	 *
	 * @var array
	 */
	private $match_query_args = array(
		'leagueId'            => false,
		'season'              => false,
		'final'               => false,
		'competitiontype'     => false,
		'competitionseason'   => false,
		'orderby'             => array(
			'league_id' => 'ASC',
			'id'        => 'ASC',
		),
		'competition_id'      => false,
		'event_id'            => false,
		'confirmed'           => false,
		'match_date'          => false,
		'competition_type'    => false,
		'time'                => false,
		'timeOffset'          => false,
		'history'             => false,
		'affiliatedClub'      => false,
		'league_name'         => false,
		'team_name'           => false,
		'home_team'           => false,
		'away_team'           => false,
		'match_day'           => false,
		'competition_name'    => false,
		'home_club'           => false,
		'count'               => false,
		'confirmationPending' => false,
		'resultPending'       => false,
		'status'              => false,
		'team'                => false,
	);

	/**
	 * Get matches without using league object
	 *
	 * @param array $match_args query arguments.
	 * @return array $matches
	 */
	public function get_matches( $match_args ) {
		global $wpdb;

		$match_args           = array_merge( $this->match_query_args, (array) $match_args );
		$league_id            = $match_args['leagueId'];
		$season               = $match_args['season'];
		$final                = $match_args['final'];
		$competitiontype      = $match_args['competition_type'];
		$orderby              = $match_args['orderby'];
		$competition_id       = $match_args['competition_id'];
		$event_id             = $match_args['event_id'];
		$confirmed            = $match_args['confirmed'];
		$match_date           = $match_args['match_date'];
		$time                 = $match_args['time'];
		$time_offset          = $match_args['timeOffset'];
		$history              = $match_args['history'];
		$club                 = $match_args['affiliatedClub'];
		$league_name          = $match_args['league_name'];
		$team                 = $match_args['team'];
		$team_name            = $match_args['team_name'];
		$home_team            = $match_args['home_team'];
		$home_club            = $match_args['home_club'];
		$away_team            = $match_args['away_team'];
		$match_day            = $match_args['match_day'];
		$competition_name     = $match_args['competition_name'];
		$count                = $match_args['count'];
		$confirmation_pending = $match_args['confirmationPending'];
		$result_pending       = $match_args['resultPending'];
		$status               = $match_args['status'];
		if ( $count ) {
			$sql = "SELECT COUNT(*) FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";
		} else {
			$sql_fields = "SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, l.`id` AS `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, m.`id` AS `id`, `custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`, `event_id`";
			$sql        = " FROM {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager} AS l WHERE m.`league_id` = l.`id`";
		}

		if ( $match_date ) {
			$sql .= " AND DATEDIFF('" . htmlspecialchars( wp_strip_all_tags( $match_date ) ) . "', `date`) = 0";
		}
		if ( $competition_name ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `event_id` in (SELECT e.`id` FROM {$wpdb->racketmanager_events} e, {$wpdb->racketmanager_competitions} c WHERE c.`name` = '" . $competition_name . "' AND e.`competition_id` = c.`id`))";
		}
		if ( $competition_id ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `event_id` IN (select `id` from {$wpdb->racketmanager_events} WHERE `competition_id` = '" . $competition_id . "') )";
		}
		if ( $event_id ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `event_id` = '" . $event_id . "')";
		}
		if ( $league_id ) {
			$sql .= " AND `league_id`  = '" . $league_id . "'";
		}
		if ( $league_name ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `title` = '" . $league_name . "')";
		}
		if ( $season ) {
			$sql .= " AND `season`  = '" . $season . "'";
		}
		if ( $final ) {
			if ( 'all' === $final ) {
				$sql .= " AND `final` != ''";
			} else {
				$sql .= " AND `final`  = '" . $final . "'";
			}
		}
		if ( $competitiontype ) {
			$sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `event_id` in (select e.`id` from {$wpdb->racketmanager_events} e, {$wpdb->racketmanager_competitions} c WHERE e.`competition_id` = c.`id` AND c.`type` = '" . $competitiontype . "'))";
		}

		if ( $time_offset ) {
			$time_offset = intval( $time_offset ) . ':00:00';
		} else {
			$time_offset = '00:00:00';
		}
		if ( $status ) {
			$sql .= " AND `confirmed` = '" . $status . "'";
		}
		if ( $confirmed ) {
			$sql .= " AND `confirmed` in ('P','A','C')";
			if ( $time_offset ) {
				$sql .= " AND ADDTIME(`updated`,'" . $time_offset . "') <= NOW()";
			}
		}
		if ( $confirmation_pending ) {
			$confirmation_pending = intval( $confirmation_pending ) . ':00:00';
			$sql_fields          .= ",ADDTIME(`updated`,'" . $confirmation_pending . "') as confirmation_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'" . $confirmation_pending . "')), '%H')/24 as overdue_time";
		}
		if ( $result_pending ) {
			$result_pending = intval( $result_pending ) . ':00:00';
			$sql_fields    .= ",ADDTIME(`date`,'" . $result_pending . "') as result_overdue_date, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'" . $result_pending . "')), '%H')/24 as overdue_time";
		}
		if ( 'latest' === $time ) { // get only finished matches with score for time 'latest'.
			$sql .= " AND (`home_points` != '' OR `away_points` != '')";
		}
		if ( 'outstanding' === $time ) {
			$sql .= " AND ADDTIME(`date`,'" . $time_offset . "') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
		}
		if ( $history ) { // get only updated matches in specified period for history.
			$sql .= ' AND `updated` >= NOW() - INTERVAL ' . $history . ' DAY';
		}

		if ( $club ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . ") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $club . '))';
		}
		if ( $team ) {
			$sql .= ' AND (`home_team` = ' . $team . ' OR `away_team` = ' . $team . ')';
		}
		if ( $home_club ) {
			$sql .= " AND `home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = " . $home_club . ')';
		}
		if ( ! empty( $home_team ) ) {
			$sql .= ' AND `home_team` = ' . $home_team . ' ';
		}
		if ( ! empty( $away_team ) ) {
			$sql .= ' AND `away_team` = ' . $away_team . ' ';
		}
		if ( ! empty( $team_name ) ) {
			$sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` LIKE '%" . $team_name . "%') OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` LIKE '%" . $team_name . "%'))";
		}
		if ( $match_day && intval( $match_day ) > 0 ) {
			$sql .= ' AND `match_day` = ' . $match_day . ' ';
		}

		if ( $count ) {
			return intval(
				$wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				)
			);
		}
		$orderby_string = '';
		$i              = 0;
		if ( is_array( $orderby ) ) {
			foreach ( $orderby as $order => $direction ) {
				$orderby_string .= '`' . $order . '` ' . $direction;
				if ( $i < ( count( $orderby ) - 1 ) ) {
					$orderby_string .= ',';
				}
				++$i;
			}
		}
		$sql     = $sql_fields . $sql . ' ORDER BY ' . $orderby_string;
		$matches = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql
		);
		$class = '';

		foreach ( $matches as $i => $match ) {
			$class = ( 'alternate' === $class ) ? '' : 'alternate';
			$match = get_match( $match );
			if ( 'final' === $match->final_round ) {
				if ( ! is_numeric( $match->home_team ) ) {
					$match->prev_home_match = $this->get_prev_round_matches( $match->home_team, $match->season, $match->league );
				}
				if ( ! is_numeric( $match->away_team ) ) {
					$match->prev_away_match = $this->get_prev_round_matches( $match->away_team, $match->season, $match->league );
				}
			}
			$match->class  = $class;
			$matches[ $i ] = $match;
		}
		return $matches;
	}

	/**
	 * Get details of previous round match
	 *
	 * @param string $team_ref round and team position.
	 * @param string $season season.
	 * @param string $league_id league.
	 * @return array $prev_match previous match.
	 */
	public function get_prev_round_matches( $team_ref, $season, $league_id ) {
		$team         = explode( '_', $team_ref );
		$league       = get_league( $league_id );
		$prev_matches = $league->get_matches(
			array(
				'final'   => $team[1],
				'season'  => $season,
				'orderby' => array( 'id' => 'ASC' ),
			)
		);
		if ( $prev_matches ) {
			$match_ref = $team[2] - 1;
			return $prev_matches[ $match_ref ];
		} else {
			return false;
		}
	}

	/**
	 * Show winners
	 *
	 * @param string  $season season.
	 * @param string  $competition_id competition id.
	 * @param string  $competition_type competition type.
	 * @param boolean $group_by group by type.
	 * @return array of winners|false.
	 */
	public function get_winners( $season, $competition_id, $competition_type = 'tournament', $group_by = false ) {
		global $wpdb;

		$winners = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winner_id`, m.`loser_id` AS `loser_id`, e.`type`, e.`name` AS `event_name`, e.`id` AS `event_id`, c.`name` AS `competition_name`, c.`id` AS `competition_id`, wt.`roster` AS `winner_roster`, lt.`roster` AS `loser_roster`, wt.`status` AS `team_type`  FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_teams} lt, {$wpdb->racketmanager_events} e WHERE `league_id` = l.`id` AND l.`event_id` = e.`id` AND e.`competition_id` = c.`id` AND c.`type` = %s AND c.`id` = %d AND m.`final` = 'FINAL' AND m.`season` = %d AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by c.`type`, l.`title`",
				$competition_type,
				$competition_id,
				$season
			)
		);

		if ( ! $winners ) {
			return false;
		}

		$return = array();
		foreach ( $winners as $winner ) {
			$winner->winner_roster = maybe_unserialize( $winner->winner_roster );
			$winner->loser_roster  = maybe_unserialize( $winner->loser_roster );
			$match                 = get_match( $winner->id );
			if ( $winner->winner_id === $winner->home_team ) {
				$winner_club = $match->teams['home']->club->shortcode;
			} else {
				$winner_club = $match->teams['away']->club->shortcode;
			}
			if ( $winner->loser_id === $winner->home_team ) {
				$loser_club = $match->teams['home']->club->shortcode;
			} else {
				$loser_club = $match->teams['away']->club->shortcode;
			}
			$winner->league      = $winner->title;
			$winner->winner_club = $winner_club;
			$winner->loser_club  = $loser_club;
			if ( 'P' === $winner->team_type ) {
				if ( ! empty( $winner->winner_roster ) ) {
					$rosters['winner'] = $winner->winner_roster;
				}
				if ( ! empty( $winner->loser_roster ) ) {
					$rosters['loser'] = $winner->loser_roster;
				}
				foreach ( $rosters as $key => $roster ) {
					$p = 1;
					foreach ( $roster as $player ) {
						$teamplayer                      = get_player( $player );
						$winner->player[ $key ][ $p ]    = $teamplayer->fullname;
						$winner->player_id[ $key ][ $p ] = $player;
						++$p;
					}
				}
			}
			if ( $group_by ) {
				$key = strtoupper( $winner->type );
				if ( false === array_key_exists( $key, $return ) ) {
					$return[ $key ] = array();
				}
				// now just add the row data.
				$return[ $key ][] = $winner;
			} else {
				$return[] = $winner;
			}
		}

		return $return;
	}

	/**
	 * Get confirmation email
	 *
	 * @param string $type type of confirmation email.
	 * @return string $email
	 */
	public function get_confirmation_email( $type ) {
		global $racketmanager;
		$options = $racketmanager->get_options();
		return isset( $options[ $type ]['resultConfirmationEmail'] ) ? $options[ $type ]['resultConfirmationEmail'] : '';
	}

	/**
	 * Check user allowed to update match
	 *
	 * @param object $home_team home team.
	 * @param object $away_team away team.
	 * @param string $competition_type competition type.
	 * @param string $match_status match status.
	 * @return boolean
	 */
	public function is_match_update_allowed( $home_team, $away_team, $competition_type, $match_status ) {
		$user_can_update = false;
		$return          = array();
		$user_type       = '';
		$user_team       = '';
		$message         = '';
		if ( is_user_logged_in() ) {
			$options          = $this->get_options();
			$userid           = get_current_user_id();
			$match_capability = $options[ $competition_type ]['matchCapability'];
			$result_entry     = $options[ $competition_type ]['resultEntry'];
			if ( isset( $home_team ) && isset( $away_team ) && isset( $home_team->affiliatedclub ) && isset( $away_team->affiliatedclub ) ) {
				if ( $userid ) {
					if ( current_user_can( 'manage_racketmanager' ) ) {
						$user_type       = 'admin';
						$user_can_update = true;
					} else {
						if ( isset( $home_team->captain_id ) && $userid === $home_team->captain_id ) {
							$user_type = 'captain';
							$user_team = 'home';
						} elseif ( isset( $away_team->captain_id ) && $userid === $away_team->captain_id ) {
							$user_type = 'captain';
							$user_team = 'away';
						} else {
							$message = 'notCaptain';
						}
						if ( 'none' === $match_capability ) {
								$message = 'noMatchCapability';
						} elseif ( 'captain' === $match_capability || 'captain' === $user_type ) {
							if ( 'captain' === $user_type ) {
								if ( 'home' === $user_team ) {
									$user_can_update = true;
								} elseif ( 'home' === $result_entry ) {
									if ( 'P' === $match_status ) {
										$user_can_update = true;
									}
								} elseif ( 'either' === $result_entry ) {
									$user_can_update = true;
								}
							}
						} elseif ( 'player' === $match_capability ) {
							$club             = get_club( $home_team->affiliatedclub );
							$home_club_player = $club->get_players(
								array(
									'count'  => true,
									'player' => $userid,
									'active' => true,
								)
							);
							if ( $home_club_player ) {
								if ( $club->matchsecretary === $userid ) {
									$user_type = 'matchsecretary';
								} else {
									$user_type = 'player';
								}
								$user_team = 'home';
							}
							$club             = get_club( $away_team->affiliatedclub );
							$away_club_player = $club->get_players(
								array(
									'count'  => true,
									'player' => $userid,
									'active' => true,
								)
							);
							if ( $away_club_player ) {
								if ( $club->matchsecretary === $userid ) {
									$user_type = 'matchsecretary';
								} else {
									$user_type = 'player';
								}
								if ( 'home' === $user_team ) {
									$user_team = 'both';
								} else {
									$user_team = 'away';
								}
							}
							if ( 'home' === $result_entry ) {
								if ( in_array( $user_team, array( 'home', 'both' ), true ) ) {
									if ( '' === $match_status ) {
										$user_can_update = true;
									}
								} elseif ( 'away' === $user_team ) {
									if ( 'P' === $match_status ) {
										$user_can_update = true;
									}
								}
							} elseif ( 'either' === $result_entry ) {
								if ( $user_team && ( empty( $match_status ) || 'P' === $match_status ) ) {
									$user_can_update = true;
								}
							}
							if ( ! $user_team ) {
								$message = 'notTeamPlayer';
							}
						}
					}
				} else {
					$message = 'notLoggedIn';
				}
			} else {
				$message = 'notTeamSet';
			}
		} else {
			$message = 'notLoggedIn';
		}
		array_push( $return, $user_can_update, $user_type, $user_team, $message );
		return $return;
	}

	/**
	 * Get from line for email
	 *
	 * @return string from line
	 */
	public function get_from_user_email() {
		return 'From: ' . wp_get_current_user()->display_name . ' <' . $this->admin_email . '>';
	}

	/**
	 * Notify clubs entries open
	 *
	 * @param string $competition_type competition type.
	 * @param string $season season.
	 * @param string $competition_id competition id.
	 * @return object notifivation status
	 */
	public function notify_entry_open( $competition_type, $season, $competition_id ) {
		global $racketmanager_shortcodes;

		$return = new \stdClass();
		$msg    = array();
		if ( ! $competition_type ) {
			$return->error = true;
			$msg[]         = __( 'Competition type not set', 'racketmanager' );
		}
		if ( ! $season ) {
			$return->error = true;
			$msg[]         = __( 'Season not set', 'racketmanager' );
		}
		if ( ! $competition_id ) {
			$return->error = true;
			$msg[]         = __( 'Competition not set', 'racketmanager' );
		}
		if ( empty( $return->error ) ) {
			$competition = get_competition( $competition_id );
			if ( $competition ) {
				if ( empty( $competition->seasons[ $season ] ) ) {
					$return->error = true;
					$msg[]         = __( 'Season not set for competition', 'racketmanager' );
				}
				if ( 'league' === $competition_type ) {
					$events = $competition->get_events();
					foreach ( $events as $event ) {
						if ( empty( $event->get_leagues() ) ) {
							$return->error = true;
							$msg[]         = __( 'No leagues found for event', 'racketmanager' ) . ' ' . $event->name;
						} else {
							$constitution = $event->get_constitution(
								array(
									'season' => $season,
									'count'  => true,
								)
							);
							if ( ! $constitution ) {
								$return->error = true;
								$msg[]         = __( 'Constitution not set', 'racketmanager' ) . ' ' . $event->name;
							}
						}
					}
				}
			} else {
				$return->error = true;
				$msg[]         = __( 'Competition not found', 'racketmanager' );
			}
		}
		if ( empty( $return->error ) ) {
			$clubs = $this->get_clubs(
				array(
					'type' => 'affiliated',
				)
			);

			$headers    = array();
			$from_email = $this->get_confirmation_email( $competition->type );
			if ( $from_email ) {
				$headers[]         = 'From: ' . ucfirst( $competition->type ) . 'Secretary <' . $from_email . '>';
				$headers[]         = 'cc: ' . ucfirst( $competition->type ) . 'Secretary <' . $from_email . '>';
				$organisation_name = $this->site_name;

				foreach ( $clubs as $club ) {
					$email_subject = $this->site_name . ' - ' . ucwords( $competition->name ) . ' ' . $season . ' ' . __( 'Entry Open', 'racketmanager' ) . ' - ' . $club->name;
					$email_to      = $club->match_secretary_name . ' <' . $club->match_secretary_email . '>';
					$action_url    = $this->site_url . '/' . $competition->type . 's/' . seo_url( $competition->name ) . '-entry/' . $season . '/' . seo_url( $club->shortcode );
					$email_message = $racketmanager_shortcodes->load_template(
						'competition-entry-open',
						array(
							'email_subject'    => $email_subject,
							'from_email'       => $from_email,
							'action_url'       => $action_url,
							'organisation'     => $organisation_name,
							'season'           => $season,
							'competition_name' => $competition->name,
							'club'             => $club,
						),
						'email'
					);
					wp_mail( $email_to, $email_subject, $email_message, $headers );
					$message_sent = true;
				}
				if ( $message_sent ) {
					$return->msg = __( 'Match secretaries notified', 'racketmanager' );
				} else {
					$return->error = true;
					$msg[]         = __( 'No notification', 'racketmanager' );
				}
			} else {
				$return->error = true;
				$msg[]         = __( 'No secretary email', 'racketmanager' );
			}
		}
		if ( ! empty( $return->error ) ) {
			$return->msg = __( 'Notification error', 'racketmanager' );
			foreach ( $msg as $error ) {
				$return->msg .= '<br>' . $error;
			}
		}
		return $return;
	}

	/**
	 * User favourite
	 *
	 * @param string $type type of favourite.
	 * @param int    $id id of favourite.
	 * @return boolean true/false
	 */
	public function is_user_favourite( $type, $id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$user_id         = get_current_user_id();
		$meta_key        = 'favourite-' . $type;
		$favourites      = get_user_meta( $user_id, $meta_key );
		$favourite_found = ( array_search( $id, $favourites, true ) );
		if ( is_numeric( $favourite_found ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Show match header
	 *
	 * @param object $match match object.
	 * @return string
	 */
	public function show_match_header( $match ) {
		global $racketmanager_shortcodes;
		$match_args['match'] = $match;
		$template            = 'match-header';
		return $racketmanager_shortcodes->load_template(
			$template,
			$match_args,
			'includes'
		);
	}

	/**
	 * Show match screen
	 *
	 * @param object  $match match object.
	 * @param boolean $is_edit_mode flag to indicate screen should be editable.
	 * @param string  $player optional indicator.
	 * @return string
	 */
	public function show_match_screen( $match, $is_edit_mode = true, $player = false ) {
		global $racketmanager_shortcodes;
		if ( '' === $match->final_round ) {
			$match->round = '';
			$match->type  = 'league';
		} else {
			$match->round = $match->final_round;
			$match->type  = 'tournament';
		}
		$user_can_update_array               = $this->is_match_update_allowed( $match->teams['home'], $match->teams['away'], $match->league->event->competition->type, $match->confirmed );
		$match_args['match']                 = $match;
		$match_args['user_can_update_array'] = $user_can_update_array;
		$match_args['is_edit_mode']          = $is_edit_mode;
		if ( $player ) {
			$match_args['match_player'] = $player;
		}
		if ( ! empty( $match->league->num_rubbers ) ) {
			$template  = 'match-rubber-input';
			$home_club = get_club( $match->teams['home']->affiliatedclub );
			$away_club = get_club( $match->teams['away']->affiliatedclub );
			switch ( $match->league->type ) {
				case 'MD':
					$home_club_player['m'] = $home_club->get_players( array( 'gender' => 'M' ) );
					$away_club_player['m'] = $away_club->get_players( array( 'gender' => 'M' ) );
					break;
				case 'WD':
					$home_club_player['f'] = $home_club->get_players( array( 'gender' => 'F' ) );
					$away_club_player['f'] = $away_club->get_players( array( 'gender' => 'F' ) );
					break;
				case 'XD':
				case 'LD':
					$home_club_player['m'] = $home_club->get_players( array( 'gender' => 'M' ) );
					$home_club_player['f'] = $home_club->get_players( array( 'gender' => 'F' ) );
					$away_club_player['m'] = $away_club->get_players( array( 'gender' => 'M' ) );
					$away_club_player['f'] = $away_club->get_players( array( 'gender' => 'F' ) );
					break;
				default:
					$home_club_player['m'] = array();
					$home_club_player['f'] = array();
					$away_club_player['m'] = array();
					$away_club_player['f'] = array();
			}
			$match_args['home_club_player'] = $home_club_player;
			$match_args['away_club_player'] = $away_club_player;
		} else {
			$template = 'match-input';
		}
		if ( $is_edit_mode ) {
			return $racketmanager_shortcodes->load_template(
				$template,
				$match_args,
				'form'
			);
		} else {
			$template = 'match-teams-scores';
			return $racketmanager_shortcodes->load_template(
				$template,
				$match_args,
			);
		}
	}

	/**
	 * Email entry form
	 *
	 * @param string $template email template to use.
	 * @param array  $template_args template arguments.
	 * @param string $email_to email address to send.
	 * @param string $email_subject email subject.
	 * @param array  $headers email headers.
	 */
	public function email_entry_form( $template, $template_args, $email_to, $email_subject, $headers ) {
		global $racketmanager_shortcodes;
		$email_message = $racketmanager_shortcodes->load_template(
			$template,
			$template_args,
			'email'
		);
		wp_mail( $email_to, $email_subject, $email_message, $headers );
	}
}
