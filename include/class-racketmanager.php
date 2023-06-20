<?php
/**
* Main class to implement RacketManager
*
*/
class RacketManager {

  /**
  * The array of templates that this plugin tracks.
  */
  protected $templates;
  public $site_name;
  public $message;
  public $error = false;
  public $options;
  public $date_format;
  public $time_format;
  public $admin_email;
  public $site_url;
  public $seasons;
  public $num_players;
  /**
  * constructor
  *
  * @param none
  * @return void
  */
  public function __construct() {
    global $wpdb;

    $wpdb->show_errors();
    $this->loadOptions();
    $this->loadLibraries();

    add_action( 'widgets_init', array(&$this, 'registerWidget') );
    add_action( 'init', array(&$this, 'racketmanagerRewrites') );
    add_action('wp_enqueue_scripts', array(&$this, 'loadStyles'), 5 );
    add_action('wp_enqueue_scripts', array(&$this, 'loadScripts') );
    add_action( 'rm_resultPending', array(&$this, 'chasePendingResults'), 1);
    add_action( 'rm_confirmationPending', array(&$this, 'chasePendingApprovals'), 1);
    add_action( 'wp_loaded', array(&$this, 'addRacketmanagerTemplates') );
    add_filter( 'wp_privacy_personal_data_exporters', array(&$this, 'racketmanagerRegisterExporter') );
    add_filter( 'wp_mail', array(&$this, 'racketmanagerMail') );
    add_filter( 'email_change_email', array(&$this, 'racketmanagerChangeEmailAddress'), 10, 3 );

  }

  public function chasePendingResults($competition) {
    $resultPending = $this->getOptions($competition)['resultPending'];
    $matchArgs = array();
    $matchArgs['time'] = 'outstanding';
    $matchArgs['competitiontype'] = 'league';
    $matchArgs['orderby'] = array( 'date' => 'ASC', 'id' => 'ASC');
    $matchArgs['timeOffset'] = $resultPending;
    $matches = $this->getMatches( $matchArgs );
    foreach($matches as $match) {
      $this->_chaseMatchResult($match->id, $resultPending);
    }
	}
  
	public function _chaseMatchResult($matchId, $timePeriod = false) {
		global $racketmanager, $match;
		$match = get_match($matchId);
		$messageSent = false;
		$headers = array();
		$fromEmail = $this->getConfirmationEmail($match->league->competitionType);
		$headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$messageArgs = array();
    $messageArgs['timeperiod'] = $timePeriod;

		$emailSubject = $racketmanager->site_name." - ".$match->league->title." - ".$match->getTitle()." Match result pending";
		$emailTo = '';
		if ( isset($match->teams['home']->contactemail) ) {
			$emailTo = $match->teams['home']->captain.' <'.$match->teams['home']->contactemail.'>';
			$club = get_club($match->teams['home']->affiliatedclub);
			if ( isset($club->matchSecretaryEmail) ) {
				$headers[] = 'cc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
			}
			$emailMessage = racketmanager_result_outstanding_notification($match->id, $messageArgs );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
			$messageSent = true;
		}
		return $messageSent;
	}

  public function chasePendingApprovals($competition) {
    $confirmationTimeout = $this->getOptions($competition)['confirmationTimeout'];
    $matchArgs = array();
    $matchArgs['confirmed'] = 'true';
    $matchArgs['competitiontype'] = 'league';
    $matchArgs['orderby'] = array( 'date' => 'ASC', 'id' => 'ASC');
    $matchArgs['timeOffset'] = $confirmationTimeout;
    $matches = $this->getMatches( $matchArgs );
    foreach($matches as $match) {
      $this->completeMatchResult($match, $confirmationTimeout);
    }
    $confirmationPending = $this->getOptions($competition)['confirmationPending'];
    $matchArgs = array();
    $matchArgs['confirmed'] = 'true';
    $matchArgs['competitiontype'] = 'league';
    $matchArgs['orderby'] = array( 'updated' => 'ASC', 'id' => 'ASC');
    $matchArgs['timeOffset'] = $confirmationPending;
    $matches = $this->getMatches( $matchArgs );
    foreach($matches as $match) {
      $this->_chaseMatchApproval($match->id, $confirmationPending);
    }
	}
  
  public function completeMatchResult($match, $confirmationTimeout) {
    global $league;
    $this->_chaseMatchApproval($match->id, $confirmationTimeout, 'override');
    $league = get_league($match->league_id);
    $final = false;
    $league->setFinals($final);
    $resultMatches = array();
    $home_points = array();
    $away_points = array();
    $home_team = array();
    $away_team = array();
    $custom = array();
    $resultMatches[$match->id] = $match->id;
    $home_points[$match->id] = $match->home_points;
    $away_points[$match->id] = $match->away_points;
    $home_team[$match->id] = $match->home_team;
    $away_team[$match->id] = $match->away_team;
    $custom[$match->id] = $match->custom;
    $season = $match->season;
    return $league->_updateResults( $resultMatches, $home_points, $away_points, $custom, $season, $final );
	}
  
	public function _chaseMatchApproval($matchId, $timePeriod = false, $override = false) {
		global $racketmanager, $match;
		$match = get_match($matchId);
		$messageSent = false;
		$headers = array();
		$fromEmail = $this->getConfirmationEmail($match->league->competitionType);
		$headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$fromEmail.'>';
		$messageArgs = array();
		$messageArgs['outstanding'] = true;
    $messageArgs['timeperiod'] = $timePeriod;
    $messageArgs['override'] = $override;
    $title = "approval pending";
    if ($override) {
      $title = "complete";
    }
		$emailSubject = $racketmanager->site_name." - ".$match->league->title." - ".$match->getTitle()." ".$title;
		$emailTo = '';
		if ( isset($match->home_captain) ) {
			if ( isset($match->teams['away']->contactemail) ) {
				$emailTo = $match->teams['away']->captain.' <'.$match->teams['away']->contactemail.'>';
				$club = get_club($match->teams['away']->affiliatedclub);
				if ( isset($club->matchSecretaryEmail) ) {
					$headers[] = 'cc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
				}
			}
		} elseif ( isset($match->away_captain) ) {
			if ( isset($match->teams['home']->contactemail) ) {
				$emailTo = $match->teams['home']->captain.' <'.$match->teams['home']->contactemail.'>';
				$club = get_club($match->teams['home']->affiliatedclub);
				if ( isset($club->matchSecretaryEmail) ) {
					$headers[] = 'cc: '.$club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
				}
			}
		}
		if ( !empty($emailTo) ) {
			$emailMessage = racketmanager_captain_result_notification($match->id, $messageArgs );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
			$messageSent = true;
		}
		return $messageSent;
	}

  public function addRacketmanagerTemplates() {
    // Add your templates to this array.
    $this->templates = array(
      'templates/page_template/template_notitle.php' => 'No Title',
      'templates/page_template/template_member_account.php' => 'Member Account'
    );

    // Add a filter to the wp 4.7 version attributes metabox
    add_filter( 'theme_page_templates', array( $this, 'racketmanagerTemplatesAsOption' ) );

    // Add a filter to the save post to inject our template into the page cache
    add_filter( 'wp_insert_post_data', array( $this, 'registerRacketmanagerTemplates' ) );

    // Add a filter to the template include to determine if the page has our
    // template assigned and return it's path
    add_filter(	'template_include',	array( $this, 'racketmanagerLoadTemplate') );

    add_filter( 'archive_template', array( $this, 'racketmanagerArchiveTemplate') );

  }

  /**
  * Adds our templates to the page dropdown
  *
  */
  public function racketmanagerTemplatesAsOption( $postsTemplates ) {
    return array_merge( $postsTemplates, $this->templates );
  }

  /**
  * Adds our templates to the pages cache in order to trick WordPress
  * into thinking the template file exists where it doens't really exist.
  */
  public function registerRacketmanagerTemplates( $atts ) {

    // Create the key used for the themes cache
    $cacheKey = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

    // Retrieve the cache list.
    // If it doesn't exist, or it's empty prepare an array
    $pageTemplates = wp_get_theme()->get_page_templates();
    if ( empty( $pageTemplates ) ) {
      $pageTemplates = array();
    }

    // New cache, therefore remove the old one
    wp_cache_delete( $cacheKey , 'themes');

    // Now add our template to the list of templates by merging our templates
    // with the existing templates array from the cache.
    $pageTemplates = array_merge( $pageTemplates, $this->templates );

    // Add the modified cache to allow WordPress to pick it up for listing
    // available templates
    wp_cache_add( $cacheKey, $pageTemplates, 'themes', 1800 );

    return $atts;

  }

  /**
  * Checks if the template is assigned to the page
  */
  public function racketmanagerLoadTemplate( $template ) {

    // Get global post
    global $post;

    // Return template if post is empty or if we don't have a custom one defined
    if ( ! $post || ! isset( $this->templates[get_post_meta($post->ID, '_wp_page_template', true)] ) ) {
      return $template;
    }

    $file = RACKETMANAGER_PATH. get_post_meta($post->ID, '_wp_page_template', true);

    // Just to be safe, we check if the file exist first
    if ( file_exists( $file ) ) {
      return $file;
    } else {
      echo $file;
    }

    // Return template
    return $template;

  }

  /**
  * load specific archive templates
  */
  public function racketmanagerArchiveTemplate( $template ) {
    global $post;

    if ( is_category('rules') ) {
      $template = RACKETMANAGER_PATH.'templates/pages/category-rules.php';
    }
    if ( is_category('how-to') ) {
      $template = RACKETMANAGER_PATH.'templates/pages/category-how-to.php';
    }
    return $template;
  }

  public function racketmanagerRegisterExporter( $exportersArray ) {
    $exportersArray['racketmanager_exporter'] = array(
      'exporter_friendly_name' => 'Racketmanager exporter',
      'callback' => array(&$this, 'racketmanagerPrivacyExporter')
    );
    return $exportersArray;

  }

  public function racketmanagerPrivacyExporter( $emailAddress, $page = 1 ) {
    $page = (int) $page;

    $dataToExport = array();

    $user = get_user_by( 'email', $emailAddress );
    if ( ! $user ) {
      return array(
        'data' => array(),
        'done' => true,
      );
    }

    $userMeta = get_user_meta( $user->ID );

    $userPropToExport = array(
      'gender'           => __( 'User Gender' ),
      'LTA Tennis Number'              => __( 'User LTA Tennis Number' ),
      'remove_date'      => __( 'User Removed Date' ),
      'contactno'        => __( 'User Contact Number' ),
    );

    $userDataToExport = array();

    foreach ( $userPropToExport as $key => $name ) {

      switch ( $key ) {
        case 'gender':
        case 'LTA Tennis Number':
        case 'remove_date':
        case 'contactno':
        $value = isset($userMeta[ $key ][0]) ? $userMeta[ $key ][0] : '';
        break;
        default:
        $value = '';
      }

      if ( ! empty( $value ) ) {
        $userDataToExport[] = array(
          'name'  => $name,
          'value' => $value,
        );
      }
    }

    $dataToExport[] = array(
      'group_id'    => 'user',
      'group_label' => __( 'User' ),
      'item_id'     => "user-{$user->ID}",
      'data'        => $userDataToExport,
    );

    return array(
      'data' => $dataToExport,
      'done' => true,
    );
  }

  /**
  * register Widget
  */
  public function registerWidget() {
    register_widget('RacketManagerWidget');
  }

  /**
  * load libraries
  *
  */
  private function loadLibraries() {
    global $racketmanager_shortcodes, $racketmanager_login;

    // Objects
    require_once RACKETMANAGER_PATH . '/include/class-charges.php';
    require_once RACKETMANAGER_PATH . '/include/class-invoice.php';
    require_once RACKETMANAGER_PATH . '/lib/club.php';
    require_once RACKETMANAGER_PATH . '/lib/championship.php';
    require_once RACKETMANAGER_PATH . '/lib/competition.php';
    require_once RACKETMANAGER_PATH . '/lib/league.php';
    require_once RACKETMANAGER_PATH . '/lib/leagueteam.php';
    require_once RACKETMANAGER_PATH . '/include/class-match.php';
    require_once RACKETMANAGER_PATH . '/lib/svg-icons.php';
    require_once RACKETMANAGER_PATH . 'include/class-team.php';
    require_once RACKETMANAGER_PATH . '/include/class-player.php';
    require_once RACKETMANAGER_PATH . '/include/class-tournament.php';

    /*
    * load sports libraries
    */
    // First read files in racketmanager sports directory, then overwrite with sports files in user stylesheet directory
    $files = array_merge($this->readDirectory(RACKETMANAGER_PATH."sports"), $this->readDirectory(get_stylesheet_directory() . "/sports"));

    // load files
    foreach ( $files as $file ) {
      require_once $file;
    }

    // Global libraries
    require_once RACKETMANAGER_PATH . '/lib/ajax.php';
    require_once RACKETMANAGER_PATH . '/lib/login.php';
    require_once RACKETMANAGER_PATH . '/lib/shortcodes.php';
    require_once RACKETMANAGER_PATH . '/lib/widget.php';

    // template tags & functions
    require_once RACKETMANAGER_PATH . '/template-tags.php';
    require_once RACKETMANAGER_PATH . '/functions.php';

    $racketmanager_ajax = new RacketManagerAJAX();

    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    $racketmanager_shortcodes = new RacketManagerShortcodes();
    $racketmanager_login = new RacketManagerLogin();
  }

  /**
  * get standings display options
  *
  * @return array
  */
  public function getStandingsDisplayOptions() {
    $options = array(
      'status' => __( 'Team Status', 'racketmanager' ),
      'pld' => __( 'Played Games', 'racketmanager' ),
      'won' => __( 'Won Games', 'racketmanager' ),
      'tie' => __('Tie Games', 'racketmanager' ),
      'lost' => __( 'Lost Games', 'racketmanager' ),
      'winPercent' => __( 'Win Percentage', 'racketmanager' ),
      'last5' => __( 'Last 5 Matches', 'racketmanager' )
    );

    /**
    * Fires when standings options are generated
    *
    * @param array $options
    * @return array
    * @category wp-filter
    */
    return apply_filters('competition_standings_options', $options);

  }

  /**
  * read files in directory
  *
  * @param string $dir
  * @return array
  */
  public function readDirectory($dir) {
    $files = array();

    if ( file_exists($dir)  && ( $handle = opendir($dir) ) ) {
      while ( false !== ($file = readdir($handle)) ) {
        $fileInfo = pathinfo($dir.'/'.$file);
        $fileType = (isset($fileInfo['extension'])) ? $fileInfo['extension'] : '';
        if ( $file != "." && $file != ".." && !is_dir($file) && substr($file, 0,1) != "."  && $fileType == 'php' )  {
          $files[$file] = $dir.'/'.$file;
        }
      }
    }

    return $files;
  }

  /**
  * load options
  *
  */
  private function loadOptions() {
    $this->options = get_option('leaguemanager');
    $this->date_format = get_option('date_format');
    $this->time_format = get_option('time_format');
    $this->site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    $this->admin_email = get_option('admin_email');
    $this->site_url = get_option('siteurl');
  }

  /**
  * get options
  *
  * @param boolean $index (optional)
  */
  public function getOptions($index = false) {
    if ( $index ) {
      return $this->options[$index];
    } else {
      return $this->options;
    }
  }

  /**
  * load Javascript
  *
  */
  public function loadScripts() {
    wp_register_script( 'datatables', 'https://cdn.datatables.net/v/ju/dt-1.11.3/fh-3.2.0/datatables.min.js', array('jquery') );
    wp_register_script( 'racketmanager', RACKETMANAGER_URL.'js/racketmanager.js', array('jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'jquery-effects-core', 'jquery-effects-slide', 'sack', 'thickbox'), RACKETMANAGER_VERSION );
    wp_enqueue_script('racketmanager');
    wp_enqueue_script( 'password-strength-meter' );
    wp_enqueue_script( 'password-strength-meter-mediator', RACKETMANAGER_URL . 'js/password-strength-meter-mediator.js', array('password-strength-meter'));
    wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
      'empty' => __( 'Strength indicator' ),
      'short' => __( 'Very weak' ),
      'bad' => __( 'Weak' ),
      'good' => _x( 'Good', 'password strength' ),
      'strong' => __( 'Strong' ),
      'mismatch' => __( 'Mismatch' )
    ) );
    ?>
    <script type="text/javascript">
    //<![CDATA[
    RacketManagerAjaxL10n = {
      blogUrl: "<?php bloginfo( 'wpurl' ); ?>",
      pluginUrl: "<?php echo RACKETMANAGER_URL; ?>",
      requestUrl: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
      Edit: "<?php _e("Edit"); ?>",
      Post: "<?php _e("Post"); ?>",
      Save: "<?php _e("Save"); ?>",
      Cancel: "<?php _e("Cancel"); ?>",
      pleaseWait: "<?php _e("Please wait..."); ?>",
      Revisions: "<?php _e("Page Revisions"); ?>",
      Time: "<?php _e("Insert time"); ?>",
      Options: "<?php _e("Options") ?>",
      Delete: "<?php _e('Delete') ?>"
    }
    //]]>
    </script>
    <?php
  }

  /**
  * load CSS styles
  *
  */
  public function loadStyles() {
    wp_enqueue_style('thickbox');
    wp_enqueue_style('racketmanager-print', RACKETMANAGER_URL . "css/print.css", false, RACKETMANAGER_VERSION, 'print');
    wp_enqueue_style('racketmanager-modal', RACKETMANAGER_URL . "css/modal.css", false, RACKETMANAGER_VERSION, 'screen');
    wp_enqueue_style('racketmanager', RACKETMANAGER_URL . "css/style.css", false, RACKETMANAGER_VERSION, 'screen');

    wp_register_style('jquery-ui', RACKETMANAGER_URL . "css/jquery/jquery-ui.min.css", false, '1.11.4', 'all');
    wp_register_style('jquery-ui-structure', RACKETMANAGER_URL . "css/jquery/jquery-ui.structure.min.css", array('jquery-ui'), '1.11.4', 'all');
    wp_register_style('jquery-ui-theme', RACKETMANAGER_URL . "css/jquery/jquery-ui.theme.min.css", array('jquery-ui', 'jquery-ui-structure'), '1.11.4', 'all');
    wp_register_style('jquery-ui-autocomplete', RACKETMANAGER_URL . "css/jquery/jquery-ui.autocomplete.min.css", array('jquery-ui', 'jquery-ui-autocomplete'), '1.11.4', 'all');
    wp_register_style('datatables-style', 'https://cdn.datatables.net/v/ju/dt-1.11.3/fh-3.2.0/datatables.min.css');

    wp_enqueue_style('jquery-ui-structure');
    wp_enqueue_style('jquery-ui-theme');

    ob_start();
    require_once RACKETMANAGER_PATH.'css/colors.css.php';
    $css = ob_get_contents();
    ob_end_clean();

    wp_add_inline_style( 'racketmanager', $css );
  }

  /*
  * Create formatted url
  */
  public function racketmanagerRewrites() {
    // daily matches - date
    add_rewrite_rule(
      'leagues/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
      'index.php?pagename=leagues/daily-matches&match_date=$matches[1]-$matches[2]-$matches[3]',
      'top'
    );
    // competition - season
    add_rewrite_rule(
      'leagues/(.+?)/(.+?)-competition/([0-9]{4})?$',
      'index.php?pagename=leagues/$matches[1]/$matches[2]-competition&season=$matches[3]',
      'top'
    );
    // competition
    add_rewrite_rule(
      'leagues/(.+?)/(.+?)-competition/?$',
      'index.php?pagename=leagues/$matches[1]/$matches[2]-competition',
      'top'
    );
    // league - season - matchday - team
    add_rewrite_rule(
      'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/(.+?)/?$',
      'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]&team=$matches[6]',
      'top'
    );
    // league - season - matchday
    add_rewrite_rule(
      'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/?$',
      'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]',
      'top'
    );
    // league - season - team
    add_rewrite_rule(
      'leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/(.+?)/?$',
      'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&team=$matches[5]',
      'top'
    );
    // league
    add_rewrite_rule(
      'leagues/(.+?)-(.+?)-([0-9]{1})/?$',
      'index.php?pagename=leagues%2F$matches[1]%2F$matches[2]&league_name=$matches[1]%20$matches[2]%20$matches[3]',
      'top'
    );
    // league - season
    add_rewrite_rule(
      'leagues\/([a-z]+?)-([a-z]+?)-([0-9]{1})\/([0-9]{4})\/?$',
      'index.php?pagename=leagues/$matches[1]/$matches[2]&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]',
      'top'
    );
    // league entry form - type - season - club
    add_rewrite_rule(
      'leagues/(.+?)-entry/([0-9]{4})/(.+?)/?$',
      'index.php?pagename=leagues%2Fentry-form&club_name=$matches[3]&season=$matches[2]&type=$matches[1]',
      'top'
    );
    // league - season - matchday - match
    add_rewrite_rule(
      'match/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})/day([0-9]{1,2})/(.+?)-vs-(.+?)/?$',
      'index.php?pagename=match%2F&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&match_day=$matches[5]&teamHome=$matches[6]&teamAway=$matches[7]',
      'top'
    );
    // league - season - matchday - match
    add_rewrite_rule(
      'match/(.+?)-(.+?)-(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/?$',
      'index.php?pagename=match%2F&league_name=$matches[1]-$matches[2]-$matches[3]&season=$matches[4]&round=$matches[5]&teamHome=$matches[6]&teamAway=$matches[7]',
      'top'
    );
    // tournament entry form - type - season - club
    add_rewrite_rule(
      'tournaments/(.+?)-entry/([0-9]{4})/(.+?)/?$',
      'index.php?pagename=tournaments%2F$matches[1]%2Fentry-form',
      'top'
    );
    // tournament winners - type - season - tournament
    add_rewrite_rule(
      'tournaments/(.+?)/winners/(.+?)/?$',
      'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&tournament=$matches[2]&type=$matches[1]',
      'top'
    );
    // tournament winners - type - season
    add_rewrite_rule(
      'tournaments/(.+?)/winners/?$',
      'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&type=$matches[1]',
      'top'
    );
    // tournament order of play - type - season - tournament
    add_rewrite_rule(
      'tournaments/(.+?)/order-of-play/(.+?)/?$',
      'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&tournament=$matches[2]&type=$matches[1]',
      'top'
    );
    // tournament order of play - type - season
    add_rewrite_rule(
      'tournaments/(.+?)/order-of-play/?$',
      'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&type=$matches[1]',
      'top'
    );
    // tournament - season
    add_rewrite_rule(
      'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
      'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
      'top'
    );
    // cup - season (winners)
    add_rewrite_rule(
      'cups/(.+?)/winners/([0-9]{4})?$',
      'index.php?pagename=cups%2F$matches[1]%2Fwinners&season=$matches[2]',
      'top'
    );
    // cup - season
    add_rewrite_rule(
      'cups/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
      'index.php?pagename=cups%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
      'top'
    );
    // cup entry form - type - season - club
    add_rewrite_rule(
      'cups/(.+?)-entry/([0-9]{4})/(.+?)/?$',
      'index.php?pagename=cups%2Fentry-form&club_name=$matches[3]&season=$matches[2]&type=$matches[1]',
      'top'
    );
    // player
    add_rewrite_rule(
      'clubs/(.+?)/(.+?)/?$','index.php?pagename=club%2Fplayer&club_name=$matches[1]&player_id=$matches[2]','top'
    );
    // club
    add_rewrite_rule(
      'clubs\/(.+?)\/?$','index.php?pagename=club&club_name=$matches[1]','top'
    );
    // invoice
    add_rewrite_rule(
      'invoice\/(.+?)\/?$','index.php?pagename=invoice&id=$matches[1]','top'
    );
  }

  /**
  * add html content type to mail header
  *
  * @param array $args
  * @return args
  */
  public function racketmanagerMail($args) {
    $headers = $args['headers'];
    if ( !$headers ) {
      $headers = array();
    }
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $args['headers'] = $headers;
    return $args;
  }

  public function racketmanagerChangeEmailAddress($emailChange, $user, $userData) {
    global $racketmanager_shortcodes, $racketmanager;

    $vars['site_name'] = $racketmanager->site_name;
    $vars['site_url'] = $racketmanager->site_url;
    $vars['user_login'] = $userData['user_login'];
    $vars['display_name'] = $userData['display_name'];
    $vars['email_link'] = $racketmanager->admin_email;
    $emailChange['message'] = $racketmanager_shortcodes->loadTemplate( 'email-email-change', $vars, 'email' );
    return $emailChange;

  }

  /**
  * deleteRacketmanagerPage
  *
  * @pageName string $name
  * @return none
  */
  public function deleteRacketmanagerPage( $pageName ) {

    $option = 'racketmanager_page_'.$pageName.'_id';
    $pageId = intval( get_option( $option ) );

    // Force delete this so the Title/slug "Menu" can be used again.
    if ( $pageId ) {
      wp_delete_post( $pageId, true );
      delete_option($option);
    }

  }


  /**
  * set message
  *
  * @param string $message
  * @param boolean $error triggers error message if true
  */
  public function setMessage( $message, $error = false ) {
    $this->error = $error;
    $this->message = $message;
  }

  /**
  * get league types
  *
  * @param none
  * @return array
  */
  public function getLeagueTypes() {
    $types = array( 'default' => __('Default', 'racketmanager') );
    /**
    * Add custom league types
    *
    * @param array $types
    * @return array
    * @category wp-filter
    */
    $types = apply_filters('racketmanager_sports', $types);
    asort($types);

    return $types;
  }

  /**
  * get seasons
  *
  * @return array
  */
  public function getSeasons( $order = "ASC" ) {
    global $wpdb;

    $orderByString = "`name` ".$order;
    $orderBy = $orderByString;
    $seasons = $wpdb->get_results("SELECT `name`, `id` FROM {$wpdb->racketmanager_seasons} ORDER BY $orderBy" );
    $i = 0;
    foreach ( $seasons as $season ) {
      $seasons[$i]->id = $season->id;
      $seasons[$i]->name = stripslashes($season->name);

      $this->seasons[$season->id] = $seasons[$i];
      $i++;
    }
    return $seasons;
  }

  /**
  * get season
  *
  * @return array
  */
  public function getSeasonDB( $args = array() ) {
    global $wpdb;

    $defaults = array( 'id' => false, 'name' => false );
    $args = array_merge($defaults, $args);
    extract($args, EXTR_SKIP);

    $searchTerms = array();
    if ( $id ) {
      $searchTerms[] = $wpdb->prepare("`id` = '%d'", intval($id));
    }
    if ( $name ) {
      $searchTerms[] = $wpdb->prepare("`name` = '%s'", $name);
    }
    $search = "";

    if ( !empty($searchTerms) ) {
      $search = " WHERE ";
      $search .= implode(" AND ", $searchTerms);
    }

    $sql = "SELECT `id`, `name` FROM {$wpdb->racketmanager_seasons} $search ORDER BY `name`";

    $season = wp_cache_get( md5($sql), 'seasons' );
    if ( !$season ) {
      $season = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $season, 'seasons' );
    }

    if (!isset($season[0])) {
      return false;
    }

    return $season[0];

  }

  /**
  * get tournaments from database
  *
  * @param none
  * @param string $search
  * @return array
  */
  public function getTournaments( $args = array() ) {
    global $wpdb;
    $defaults = array( 'offset' => 0, 'limit' => 99999999, 'type' => false, 'name' => false, 'entryopen' => false, 'open' => false, 'orderby' => array("name" => "DESC") );
    $args = array_merge($defaults, $args);
    extract($args, EXTR_SKIP);

    $searchTerms = array();

    if ( $type ) {
      $searchTerms[] = $wpdb->prepare("`type` = '%s'", $type);
    }

    if ( $entryopen ) {
      $searchTerms[] = "`closingdate` >= CURDATE()";
    }

    if ( $open ) {
      $searchTerms[] = "(`date` >= CURDATE() OR `date` = '0000-00-00')";
    }

    $search = "";
    if (!empty($searchTerms)) {
      $search = " WHERE ";
      $search .= implode(" AND ", $searchTerms);
    }

    $orderbyString = ""; $i = 0;
    foreach ($orderby as $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
        $direction = "ASC";
      }
      $orderbyString .= "`".$order."` ".$direction;
      if ($i < (count($orderby)-1)) {
        $orderbyString .= ",";
      }
      $i++;
    }
    $orderby = $orderbyString;

    $sql = $wpdb->prepare( "SELECT `id` FROM {$wpdb->racketmanager_tournaments} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) );

    $tournaments = wp_cache_get( md5($sql), 'tournaments' );
    if ( !$tournaments ) {
      $tournaments = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $tournaments, 'tournaments' );
    }

    $i = 0;
    foreach ( $tournaments as $i => $tournament ) {

      $tournament = get_tournament($tournament->id);

      $tournaments[$i] = $tournament;
    }

    return $tournaments;
  }

  /**
  * get clubs from database
  *
  * @param none
  * @param string $search
  * @return array
  */
  public function getClubs( $offset=0, $limit=99999999 ) {
    global $wpdb;

    $sql = $wpdb->prepare( "SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} ORDER BY `name` ASC LIMIT %d, %d",  intval($offset), intval($limit) );

    $clubs = wp_cache_get( md5($sql), 'clubs' );
    if ( !$clubs ) {
      $clubs = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $clubs, 'clubs' );
    }

    $i = 0;
    foreach ( $clubs as $i => $club ) {
      $club = get_club($club);

      $clubs[$i] = $club;
    }

    return $clubs;
  }

  /**
  * get competitions from database
  *
  * @param int $competitionId (default: false)
  * @param string $search
  * @return array
  */
  public function getCompetitions( $args = array() ) {
    global $wpdb;

    $defaults = array( 'offset' => 0, 'limit' => 99999999, 'type' => false, 'name' => false, 'season' => false, 'orderby' => array("name" => "ASC") );
    $args = array_merge($defaults, $args);
    extract($args, EXTR_SKIP);

    $searchTerms = array();
    if ( $name ) {
      $name = $wpdb->esc_like(stripslashes($name)).'%';
      $searchTerms[] = $wpdb->prepare("`name` like '%s'", $name);
    }

    if ( $type ) {
      $searchTerms[] = $wpdb->prepare("`competitiontype` = '%s'", $type);
    }

    $search = "";
    if (!empty($searchTerms)) {
      $search = " WHERE ";
      $search .= implode(" AND ", $searchTerms);
    }

    $orderbyString = ""; $i = 0;
    foreach ($orderby as $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
        $direction = "ASC";
      }
      $orderbyString .= "`".$order."` ".$direction;
      if ($i < (count($orderby)-1)) {
        $orderbyString .= ",";
      }
      $i++;
    }
    $orderby = $orderbyString;
    $sql = $wpdb->prepare( "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competitiontype` FROM {$wpdb->racketmanager_competitions} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) );

    $competitions = wp_cache_get( md5($sql), 'competitions' );
    if ( !$competitions ) {
      $competitions = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $competitions, 'competitions' );
    }

    $i = 0;
    foreach ( $competitions as $i => $competition ) {
      $competition->name = stripslashes($competition->name);
      $competition->seasons = maybe_unserialize($competition->seasons);
      $competition->settings = maybe_unserialize($competition->settings);

      $competition = (object)array_merge((array)$competition, $competition->settings);

      if ( $season ) {
        if ( array_search($season,array_column($competition->seasons, 'name') ,false) ) {
          $competitions[$i] = $competition;
        } else {
          unset($competitions[$i]);
        }
      } else {
        $competitions[$i] = $competition;
      }
    }
    return $competitions;
  }

  /**
  * get Team ID for given string
  *
  * @param string $title
  * @return int
  */
  public function getTeamID( $title ) {
    global $wpdb;

    $team = $wpdb->get_results( $wpdb->prepare("SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `title` = '%s'", $title) );
    if (!isset($team[0])) {
      return 0;
    } else {
      return $team[0]->id;
    }
  }

  /**
  * gets club players from database
  *
  * @param array $query_args
  * @return array
  */
  public function getClubPlayers( $args ) {
    global $wpdb;

    $defaults = array( 'count' => false, 'team' => false, 'club' => false, 'player' => false, 'gender' => false, 'active' => false, 'cache' => true, 'type' => false, 'orderby' => array("display_name" => "ASC" ));
    $args = array_merge($defaults, (array)$args);
    extract($args, EXTR_SKIP);

    $searchTerms = array();
    if ($team) {
      $searchTerms[] = $wpdb->prepare("`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = '%d')", intval($team));
    }

    if ($club) {
      $searchTerms[] = $wpdb->prepare("`affiliatedclub` = '%d'", intval($club));
    }

    if ($player) {
      $searchTerms[] = $wpdb->prepare("`player_id` = '%d'", intval($player));
    }

    if ($type) {
      $searchTerms[] = "`system_record` IS NULL";
    }

    if ($active) {
      $searchTerms[] = "`removed_date` IS NULL";
    }

    $search = "";
    if (!empty($searchTerms)) {
      $search = implode(" AND ", $searchTerms);
    }

    $orderbyString = "";
    $i = 0;
    foreach ($orderby as $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
        $direction = "ASC";
      }
      $orderbyString .= "`".$order."` ".$direction;
      if ($i < (count($orderby)-1)) {
        $orderbyString .= ",";
      }
      $i++;
    }
    $order = $orderbyString;

    if ( $count ) {
      $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_players}";
      if ( $search != "") {
        $sql .= " WHERE $search";
      }
      $cachekey = md5($sql);
      if ( isset($this->num_players[$cachekey]) && $cache && $count ) {
        return intval($this->num_players[$cachekey]);
      }

      $this->num_players[$cachekey] = $wpdb->get_var($sql);
      return $this->num_players[$cachekey];
    }

    $sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID`" ;
    if ( $search != "") {
      $sql .= " WHERE $search";
    }
    if ( $order != "") {
      $sql .= " ORDER BY $order";
    }

    $clubPlayers = wp_cache_get( md5($sql), 'clubPlayers' );
    if ( !$clubPlayers ) {
      $clubPlayers = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $clubPlayers, 'clubPlayers' );
    }

    $i = 0;
    $class = '';
    foreach ( $clubPlayers as $clubPlayer ) {
      $class = ( 'alternate' == $class ) ? '' : 'alternate';
      $clubPlayers[$i]->class = $class;

      $clubPlayers[$i] = (object)(array)$clubPlayer;

      $clubPlayers[$i]->affiliatedclub = $clubPlayer->affiliatedclub;
      $clubPlayers[$i]->roster_id = $clubPlayer->roster_id;
      $clubPlayers[$i]->player_id = $clubPlayer->player_id;
      $clubPlayers[$i]->fullname = $clubPlayer->fullname;
      $clubPlayers[$i]->gender = get_user_meta($clubPlayer->player_id, 'gender', true );
      $clubPlayers[$i]->type = get_user_meta($clubPlayer->player_id, 'racketmanager_type', true );
      $clubPlayers[$i]->locked = get_user_meta($clubPlayer->player_id, 'locked', true );
      $clubPlayers[$i]->locked_date = get_user_meta($clubPlayer->player_id, 'locked_date', true );
      $clubPlayers[$i]->locked_user = get_user_meta($clubPlayer->player_id, 'locked_user', true );
      if ( $clubPlayers[$i]->locked_user ) {
        $clubPlayers[$i]->lockedUserName = get_userdata($clubPlayers[$i]->locked_user)->display_name;
      } else {
        $clubPlayers[$i]->lockedUserName = '';
      }
      $clubPlayers[$i]->removed_date = $clubPlayer->removed_date;
      $clubPlayers[$i]->removed_user = $clubPlayer->removed_user;
      if ( $clubPlayer->removed_user ) {
        $clubPlayers[$i]->removedUserName = get_userdata($clubPlayer->removed_user)->display_name;
      } else {
        $clubPlayers[$i]->removedUserName = '';
      }
      $clubPlayers[$i]->btm = get_user_meta($clubPlayer->player_id, 'btm', true );
      $clubPlayers[$i]->created_date = $clubPlayer->created_date;
      $clubPlayers[$i]->created_user = $clubPlayer->created_user;
      if ( $clubPlayer->created_user ) {
        $clubPlayers[$i]->createdUserName = get_userdata($clubPlayer->created_user)->display_name;
      } else {
        $clubPlayers[$i]->createdUserName = '';
      }
      if ( $gender && $gender != $clubPlayers[$i]->gender ) {
        unset($clubPlayers[$i]);
      }

      $i++;
    }

    return $clubPlayers;
  }

  /**
  * gets single club player entry from database
  *
  * @param array $query_args
  * @return array
  */
  public function getClubPlayer( $rosterId, $cache = true ) {
    global $wpdb;

    $sql = "SELECT A.`player_id` as `player_id`, A.`system_record`, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A WHERE A.`id`= '".intval($rosterId)."'";

    $clubplayer = wp_cache_get( md5($sql), 'clubplayer' );
    if ( !$clubplayer || !$cache ) {
      $clubplayer = $wpdb->get_row( $sql );
      wp_cache_set( md5($sql), $clubplayer, 'clubplayer' );
    }
    $clubplayer->id = $rosterId;
    $player = get_userdata($clubplayer->player_id);
    $clubplayer->fullname = $player->display_name;
    $clubplayer->email = $player->user_email;
    $player = get_user_meta($clubplayer->player_id);
    $clubplayer->firstname = $player['first_name'][0];
    $clubplayer->surname = $player['last_name'][0];
    $clubplayer->gender = isset($player['gender']) ? $player['gender'][0] : '';
    $clubplayer->btm = isset($player['btm']) ? $player['btm'][0] : '';
    $clubplayer->locked = isset($player['locked']) ? $player['locked'][0] : '';
    $clubplayer->locked_date = isset($player['locked_date']) ? $player['locked_date'][0] : '';
    $clubplayer->locked_user = isset($player['locked_user']) ? $player['locked_user'][0] : '';

    return $clubplayer;
  }

  /**
  * delete Club Player
  *
  * @param int $teamId
  * @return boolean
  */
  public function delClubPlayer( $rosterId ) {
    global $wpdb;

    $userid = get_current_user_id();
    $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->racketmanager_club_players} SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = '%d'", $userid, $rosterId) );
    $this->setMessage( __('Player removed from club', 'racketmanager') );

    return true;
  }

  /**
  * get list of players
  *
  * @param array $query_args
  * @return array
  */
  public function getPlayers( $args ) {
    $defaults = array( 'name' => false, 'cache' => true );
    $args = array_merge($defaults, (array)$args);
    extract($args, EXTR_SKIP);
    $search = '';
    if ($name) {
      $search = '*'.$name.'*';
      $searchTerms = 'display_name';
    }

    $orderbyString = 'display_name';
    $order = 'ASC';

    $userFields = array('ID','display_name');
    $userArgs = array();
    $userArgs['meta_key'] = 'gender';
    $userArgs['meta_value'] = 'M,F';
    $userArgs['meta_compare'] = 'IN';
    $userArgs['orderby'] = $orderbyString;
    $userArgs['order'] = $order;
    if ( $search ) {
      $userArgs['search'] = $search;
      $userArgs['search_columns'] = array($searchTerms);
    }
    $userSearch = json_encode($userArgs);
    $players = wp_cache_get( md5($userSearch), 'players' );
    if ( !$players ) {
      $userArgs['fields'] = $userFields;
      $players = get_users( $userArgs);
      if ( $players ) {
        $i = 0;
        foreach ( $players as $player ) {
          $player = get_player($player->ID);
          $players[$i] = $player;
          $i++;
        }
          }
      wp_cache_set( md5($userSearch), $players, 'players' );
    }
    return $players;
  }

  /**
  * get player name
  *
  * @param int $playerId
  * @return string | false
  */
  public function getPlayerName( $playerId ) {
    $player = get_player( $playerId );
    if ( !$player ) {
      return false;
    }

    return $player->display_name;
  }

  /**
  * add new player
  *
  * @param string $firstname
  * @param string $surname
  * @param string $gender
  * @param int $btm
  * @param boolean $message (optional)
  * @param string $email (optional)
  * @return int | false
  */
  public function addPlayer( $firstname, $surname, $gender, $btm, $email = false, $message = true ) {

    $userdata = array();
    $userdata['first_name'] = $firstname;
    $userdata['last_name'] = $surname;
    $userdata['display_name'] = $firstname.' '.$surname;
    $userdata['user_login'] = $firstname.'.'.$surname;
    $userdata['user_pass'] = $userdata['user_login'].'1';
    if ( $email ) {
      $userdata['user_email'] = $email;
    }
    $userId = wp_insert_user( $userdata );
    if ( ! is_wp_error( $userId ) ) {
      update_user_meta($userId, 'show_admin_bar_front', false );
      update_user_meta($userId, 'gender', $gender);
      if ( isset($btm) ) {
        update_user_meta($userId, 'btm', $btm);
      }
    }

    if ( $message ) {
      $this->setMessage( __('Player added', 'racketmanager') );
    }

    return $userId;
  }

  /**
  * get matches without using league object
  *
  * @param array $matchArgs
  * @return array $matches
  */
  public function getMatches( $matchArgs ) {
    global $wpdb;

    $defaults = array( 'leagueId' => false, 'season' => false, 'final' => false, 'competitiontype' => false, 'competitionseason' => false, 'orderby' => array('league_id' => 'ASC', 'id' => 'ASC'), 'competitionId' => false, 'confirmed' => false, 'match_date' => false, 'competition_type' => false, 'time' => false, 'timeOffset' => false, 'history' => false, 'affiliatedClub' => false, 'league_name' => false, 'homeTeam' => false, 'awayTeam' => false, 'matchDay' => false, 'competition_name' => false, 'homeAffiliatedClub' => false, 'count' => false, 'confirmationPending' => false, 'resultPending' => false );
    $matchArgs = array_merge($defaults, (array)$matchArgs);
    extract($matchArgs, EXTR_SKIP);

    if ( $count ) {
      $sql = "SELECT COUNT(*) FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";
    } else {
      $sqlFields = "SELECT `final` AS final_round, `group`, `home_team`, `away_team`, DATE_FORMAT(`date`, '%Y-%m-%d %H:%i') AS date, DATE_FORMAT(`date`, '%e') AS day, DATE_FORMAT(`date`, '%c') AS month, DATE_FORMAT(`date`, '%Y') AS year, DATE_FORMAT(`date`, '%H') AS `hour`, DATE_FORMAT(`date`, '%i') AS `minutes`, `match_day`, `location`, `league_id`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `season`, `id`, `custom`, `confirmed`, `home_captain`, `away_captain`, `comments`, `updated`";
      $sql = " FROM {$wpdb->racketmanager_matches} WHERE 1 = 1";
    }

    if ( $match_date ) {
      $sql .= " AND DATEDIFF('". htmlspecialchars(strip_tags($match_date))."', `date`) = 0";
    }
    if ( $competition_type ) {
      $sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = '".$competition_type."'))";
    }
    if ( $competition_name ) {
      $sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `name` = '".$competition_name."'))";
    }

    if ( $competitionId ) {
      $sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `competition_id` = '".$competitionId."')";
    }

    if ( $leagueId ) {
      $sql .= " AND `league_id`  = '".$leagueId."'";
    }
    if ( $league_name ) {
      $sql .= " AND `league_id` in (select `id` from {$wpdb->racketmanager} WHERE `title` = '".$league_name."')";
    }
    if ( $season ) {
      $sql .= " AND `season`  = '".$season."'";
    }
    if ( $final ) {
      $sql .= " AND `final`  = '".$final."'";
    }
    if ( $competitiontype ) {
      $sql .= " AND `league_id` in (select l.`id` from {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c WHERE l.`competition_id` = c.`id` AND c.`competitiontype` = '".$competitiontype."'";
        if ( $competitionseason ) {
        $sql .= " AND c.`name` LIKE '".$competitionseason."%'";
        }
        $sql .= ")";
      }

      if ( $timeOffset ) {
        $timeOffset = intval($timeOffset).':00:00';
      } else {
        $timeOffset = '00:00:00';
      }

      if ( $confirmed ) {
        $sql .= " AND `confirmed` in ('P','A','C')";
        if ( $timeOffset ) {
          $sql .= " AND ADDTIME(`updated`,'".$timeOffset."') <= NOW()";
        }
      }
      if ( $confirmationPending ) {
        $confirmationPending = intval($confirmationPending).':00:00';
        $sqlFields .= ",ADDTIME(`updated`,'".$confirmationPending."') as confirmationOverdueDate, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`updated`,'".$confirmationPending."')), '%H')/24 as overdueTime";
      }
      if ( $resultPending ) {
        $resultPending = intval($resultPending).':00:00';
        $sqlFields .= ",ADDTIME(`date`,'".$resultPending."') as resultOverdueDate, TIME_FORMAT(TIMEDIFF(now(),ADDTIME(`date`,'".$resultPending."')), '%H')/24 as overdueTime";
      }

      // get only finished matches with score for time 'latest'
      if ( $time == 'latest' ) {
        $sql .= " AND (`home_points` != '' OR `away_points` != '')";
      }
      if ( $time == 'outstanding' ) {
        $sql .= " AND ADDTIME(`date`,'".$timeOffset."') <= NOW() AND `winner_id` = 0 AND `confirmed` IS NULL";
      }

      // get only updated matches in specified period for history
      if ( $history ) {
        $sql .= " AND `updated` >= NOW() - INTERVAL ".$history." DAY";
      }

      if ( $affiliatedClub ) {
        $sql .= " AND (`home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub.") OR `away_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$affiliatedClub."))";
      }
      if ( $homeAffiliatedClub ) {
        $sql .= " AND `home_team` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = ".$homeAffiliatedClub.")";
      }
      if (!empty($homeTeam)) {
        $sql .= " AND `home_team` = ".$homeTeam." ";
      }
      if (!empty($awayTeam)) {
        $sql .= " AND `away_team` = ".$awayTeam." ";
      }
      if ( $matchDay && intval($matchDay) > 0 ) {
        $sql .= " AND `match_day` = ".$matchDay." ";
      }

      if ( $count ) {
        $matches = intval($wpdb->get_var($sql));
      } else {
        $orderbyString = "";
        $i = 0;
        foreach ($orderby as $order => $direction) {
          $orderbyString .= "`".$order."` ".$direction;
          if ($i < (count($orderby)-1)) {
            $orderbyString .= ",";
          }
          $i++;
        }
        $sql .= " ORDER BY ".$orderbyString;
        $sql = $sqlFields.$sql;

        // get matches
        $matches = $wpdb->get_results($sql);
        $class = '';

        foreach ( $matches as $i => $match ) {

          $class = ( 'alternate' == $class ) ? '' : 'alternate';
          $match = get_match($match);
          if ( $match->final_round == 'final' ) {
            if ( !is_numeric($match->home_team) ) {
              $match->prevHomeMatch = $this->getPrevRoundMatches($match->home_team, $match->season, $match->league);
            }
            if ( !is_numeric($match->away_team) ) {
              $match->prevAwayMatch = $this->getPrevRoundMatches($match->away_team, $match->season, $match->league);
            }
          }
          $match->class = $class;
          $matches[$i] = $match;
        }
      }

      return $matches;
    }

    /**
    * get details of previous round match
    *
    * @param string $teamRef
    * @param string $season
    * @param string $leagueId
    * @return array $prevMatch
    */
    public function getPrevRoundMatches($teamRef, $season, $leagueId) {
      global $racketmanager;
      $team = explode("_", $teamRef);
      $league = get_league($leagueId);
      $prevMatches = $league->getMatches( array('final' => $team[1], 'season' => $season, "orderby" => array("id" => "ASC") ));
      if ( $prevMatches ) {
        $matchRef = $team[2] - 1;
        return $prevMatches[$matchRef];
      } else {
        return false;
      }
    }

    /**
    * show winners
    *
    * @param string $season
    * @param string $seasonType
    * @return void
    */
    public function getWinners( $season, $seasonType, $competitionType = 'tournament' ) {
      global $racketmanager, $wpdb;

      $seasonType = $wpdb->esc_like(stripslashes($seasonType)).'%';

      $sql = "SELECT l.`title` ,wt.`title` AS `winner` ,lt.`title` AS `loser`, m.`id`, m.`home_team`, m.`away_team`, m.`winner_id` AS `winnerId`, m.`loser_id` AS `loserId` FROM {$wpdb->racketmanager_matches} m, {$wpdb->racketmanager} l, {$wpdb->racketmanager_competitions} c, {$wpdb->racketmanager_teams} wt, {$wpdb->racketmanager_teams} lt WHERE `league_id` = l.`id` AND l.`competition_id` = c.`id` AND c.`competitiontype` = '%s' AND c.`name` like '%s' AND m.`final` = 'FINAL' AND m.`season` = '%d' AND m.`winner_id` = wt.`id` AND m.`loser_id` = lt.`id` order by 1";

      $sql = $wpdb->prepare($sql, $competitionType, $seasonType, $season);
      $winners = $wpdb->get_results($sql);

      if ( !$winners ) {
        return false;
      }

      $i = 0;
      foreach ( $winners as $winner ) {

        $match = get_match($winner->id);
        $winners[$i] = (object)(array)$winner;
        $winners[$i]->league = $winner->title;
        $winners[$i]->winner = $winner->winner;
        if ( $winner->winnerId == $winner->home_team ) {
          $winners[$i]->winnerClub = $match->teams['home']->affiliatedclubname;
        } else {
          $winners[$i]->winnerClub = $match->teams['away']->affiliatedclubname;
        }
        $winners[$i]->loser = $winner->loser;
        if ( $winner->loserId == $winner->home_team ) {
          $winners[$i]->loserClub = $match->teams['home']->affiliatedclubname;
        } else {
          $winners[$i]->loserClub = $match->teams['away']->affiliatedclubname;
        }

        $i++;
      }

      return $winners;

    }

    /**
    * get confirmation email
    *
    * @param boolean $championship
    * @param string $type
    * @return string $email
    */
    public function getConfirmationEmail($type) {
      global $racketmanager;
      $rmOptions = $racketmanager->getOptions();
      return isset($rmOptions[$type]['resultConfirmationEmail']) ? $rmOptions[$type]['resultConfirmationEmail'] : '';
    }

    /**
    * check if database column exists
    *
    * @param string $table
    * @param string $column
    * @return boolean
    */
    public function databaseColumnExists($table, $column) {
      global $wpdb;

      if ($table == "teams") { $table = $wpdb->racketmanager_teams; }
      elseif ($table == "table") { $table = $wpdb->racketmanager_table; }
      elseif ($table == "matches") { $table = $wpdb->racketmanager_matches; }
      elseif ($table == "club_players") { $table = $wpdb->racketmanager_club_players; }
      elseif ($table == "leagues") { $table = $wpdb->racketmanager; }
      elseif ($table == "seasons") { $table = $wpdb->racketmanager_seasons; }
      elseif ($table == "competititons") { $table = $wpdb->racketmanager_competititons; }
      else { return false; }

      $sql = $wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column);

      $res = wp_cache_get( md5($sql), 'racketmanager' );

      if ( !$res ) {
        $res = $wpdb->query( $sql );
        wp_cache_set( md5($sql), $res, 'racketmanager' );
      }
      return $res;
    }

    /**
    * check user allowed to update match
    *
    * @param array $home_team
    * @param array $away_team
    * @param string $competitionType
    * @param string $matchStatus
    * @return boolean
    */
    public function getMatchUpdateAllowed($homeTeam, $awayTeam, $competitionType, $matchStatus) {
      $userCanUpdate = false;
      $return = array();
      $userType = '';
      $userTeam = '';
      $message = '';
      if ( is_user_logged_in() ) {
        $options = $this->getOptions();
        $userid = get_current_user_id();
        $matchCapability = $options[$competitionType]['matchCapability'];
        $resultEntry = $options[$competitionType]['resultEntry'];
        if ( isset($homeTeam) && isset($awayTeam) && isset($homeTeam->affiliatedclub) && isset($awayTeam->affiliatedclub) ) {
          if ( $userid ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
              $userType = 'admin';
              $userCanUpdate = true;
            } else {
              if ( isset($homeTeam->captainId) && $userid == $homeTeam->captainId ) {
                $userType = 'captain';
                $userTeam = 'home';
              } elseif ( isset($awayTeam->captainId) && $userid == $awayTeam->captainId ) {
                $userType = 'captain';
                $userTeam = 'away';
              } else {
                $message = 'notCaptain';
              }
              if ($matchCapability == 'none') {
                  $message = 'noMatchCapability';
              } elseif ($matchCapability == 'captain' || $userType == 'captain') {
                if ( $userType = 'captain' ) {
                  if ( $userTeam == 'home' ) {
                    $userCanUpdate = true;
                  } else {
                    if ( $resultEntry == 'home') {
                      if ( $matchStatus == 'P' ) {
                        $userCanUpdate = true;
                      }
                    } elseif ( $resultEntry == 'either' ) {
                      $userCanUpdate = true;
                    }
                  }
                }
              } elseif ($matchCapability == 'player') {
                $club = get_club($homeTeam->affiliatedclub);
                $homeClubPlayer = $club->getPlayers( array( 'count' => true, 'player' => $userid, 'active' => true ) );
                if ( $homeClubPlayer ) {
                  if ( $club->matchsecretary == $userid ) {
                    $userType = 'matchsecretary';
                  } else {
                    $userType = 'player';
                  }
                  $userTeam = 'home';
                }
                $club = get_club($awayTeam->affiliatedclub);
                $awayClubPlayer = $club->getPlayers( array( 'count' => true, 'player' => $userid, 'active' => true ) );
                if ( $awayClubPlayer ) {
                  if ( $club->matchsecretary == $userid ) {
                    $userType = 'matchsecretary';
                  } else {
                    $userType = 'player';
                  }
                  if ($userTeam == 'home') {
                    $userTeam = 'both';
                  } else {
                    $userTeam = 'away';
                  }
                }
                if ( $resultEntry == 'home' ) {
                  if ( in_array($userTeam, array('home','both'), true )) {
                    if ($matchStatus == '' ) {
                      $userCanUpdate = true;
                    }
                  } elseif ($userTeam == 'away') {
                    if ($matchStatus == 'P') {
                      $userCanUpdate = true;
                    }
                  }
                } elseif ( $resultEntry == 'either' ) {
                  if ($userTeam) {
                    $userCanUpdate = true;
                  }
                }
                if (!$userTeam) {
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
      array_push($return,$userCanUpdate,$userType,$userTeam,$message);
      return $return;
    }

    /**
    * notify teams for next round
    *
    * @param object $match next match
    * @return void
    */
    public function notifyNextMatchTeams($match) {
      global $racketmanager;

      if ( !(isset($match->teams['home']->contactemail) && $match->teams['home']->contactemail > '') && !(isset($match->teams['away']->contactemail) && $match->teams['away']->contactemail > '' ) ) {
        return false;
      }
      if ( ( $match->teams['home']->id == -1 || $match->teams['away']->id == -1 ) || ( !isset($match->custom['host']) ) ) {
        return false ;
      }
      $to = array();
      if ( isset($match->teams['home']->contactemail) && $match->teams['home']->contactemail > '' ) { array_push($to, $match->teams['home']->contactemail); }
      if ( isset($match->teams['away']->contactemail) && $match->teams['away']->contactemail > '' ) { array_push($to, $match->teams['away']->contactemail); }
      $emailFrom = $racketmanager->getConfirmationEmail($match->league->competitionType);
      $organisationName = $racketmanager->site_name;
      $roundName = $match->league->championship->finals[$match->final_round]['name'];
      $messageArgs = array();
      $messageArgs['round'] = $roundName;
      $messageArgs['competitiontype'] = $match->league->competitionType;
      if ( $match->league->competitionType == 'tournament' ) {
        $leagueTitle = explode(" ", $match->league->title);
        $tournamentType = $leagueTitle[0];
        $tournaments = $racketmanager->getTournaments( array( 'type' => $tournamentType, 'open' => true ) );
        $tournament = $tournaments[0];
        $messageArgs['tournament'] = $tournament->id;
      } elseif ( $match->league->competitionType == 'cup' ) {
        $messageArgs['competition'] = $match->league->competitionName;
      }
      $messageArgs['emailfrom'] = $emailFrom;
      $emailMessage = racketmanager_match_notification($match->id, $messageArgs );
      $headers = array();
      $headers[] = 'From: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
      $headers[] = 'cc: '.ucfirst($match->league->competitionType).' Secretary <'.$emailFrom.'>';
      $subject = $organisationName." - ".$match->league->title." - ".$roundName." - Match Details";
      wp_mail($to, $subject, $emailMessage, $headers);
      return true;
    }

    /**
    * get from line for email
    *
    * @return string from line
    */
    public function getFromUserEmail() {
      return 'From: '.wp_get_current_user()->display_name.' <'.$this->admin_email.'>';
    }

    /**
    * notify clubs entries open
    *
    * @return string notifivation status
    */
    public function notifyEntryOpen($competitionType, $season, $competitionSeason) {
      global $racketmanager_shortcodes;

      $return = array();
      if ( !$competitionSeason ) {
        $return['error'] = true;
        $return['msg'] = __('Type not selected','racketmanager');
      } else {
        $clubs = $this->getClubs();

        $headers = array();
        $fromEmail = $this->getConfirmationEmail($competitionType);
        if ( $fromEmail ) {
          $headers[] = 'From: '.ucfirst($competitionType).'Secretary <'.$fromEmail.'>';
          $headers[] = 'cc: '.ucfirst($competitionType).'Secretary <'.$fromEmail.'>';
          $organisationName = $this->site_name;

          foreach ($clubs as $club) {
            $emailSubject = $this->site_name." - ".ucfirst($competitionSeason)." ".$season." ".ucfirst($competitionType)." Entry Open - ".$club->name;
            $emailTo = $club->matchSecretaryName.' <'.$club->matchSecretaryEmail.'>';
            $actionURL = $this->site_url.'/'.$competitionType.'s/'.$competitionSeason.'-entry/'.$season.'/'.seoUrl($club->shortcode);
            $emailMessage = $racketmanager_shortcodes->loadTemplate( 'competition-entry-open', array( 'emailSubject' => $emailSubject, 'fromEmail' => $fromEmail, 'actionURL' => $actionURL, 'organisationName' => $organisationName, 'season' => $season, 'competitionSeason' => $competitionSeason, 'competitionType' => $competitionType, 'club' => $club ), 'email' );
            wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
            $messageSent = true;
          }
          if ( $messageSent ) {
            $return['msg'] = __('Match secretaries notified','racketmanager');
          } else {
            $return['error'] = true;
            $return['msg'] = __('No notification','racketmanager');
          }
        } else {
          $return['error'] = true;
          $return['msg'] = __('No secretary email','racketmanager');
        }
      }
      return $return;
    }

    /**
    * user favourite
    *
    * @return boolean true/false
    */
    public function userFavourite($type, $id) {

      if ( !is_user_logged_in() ) {
        return false;
      }
      $userId = get_current_user_id();
      $metaKey = 'favourite-'.$type;
      $favourites = get_user_meta($userId, $metaKey);
      $favouriteFound = (array_search($id, $favourites,true));
      if ( is_numeric($favouriteFound) ) {
        return true;
      }
      return false;
    }

    /**
    * notify favourites
    *
    * @param object $users
    * @param object $matches
    * @return null
    */
    public function notifyFavourites($matches, $league) {
      $users = $this->getUsersForFavourite('league', $league->id);
      if ( $users ) {
        $favourite = $league->title;
        $this->notifyFavouritesEmail($favourite, $league, $users, $matches);
      }

      $clubs = array();
      foreach ($matches as $i => $match) {
        if ( isset($match->teams['home']->affiliatedclub) ) {
          $clubs[$i]['id'] = $match->teams['home']->affiliatedclub;
          $clubs[$i]['name'] = $match->teams['home']->affiliatedclubname;
          $clubs[$i]['matches'] = array();
          $clubs[$i]['matches'][] = $match;
        }
        if ( isset($match->teams['away']->affiliatedclub) && isset($match->teams['home']->affiliatedclub) && $match->teams['home']->affiliatedclub != $match->teams['away']->affiliatedclub ) {
          $clubs[$i]['id'] = $match->teams['away']->affiliatedclub;
          $clubs[$i]['name'] = $match->teams['away']->affiliatedclubname;
          $clubs[$i]['matches'] = array();
          $clubs[$i]['matches'][] = $match;
        }
      }
      $clubs = array_unique($clubs, SORT_REGULAR);
      foreach ($clubs as $club) {
        $users = $this->getUsersForFavourite('club', $club['id']);
        if ( $users ) {
          $favourite = $club['name'];
          $this->notifyFavouritesEmail($favourite, $league, $users, $club['matches']);
        }
      }
    }

    /**
    * get users for favourite
    *
    * @param string $type
    * @param string $key
    * @return array list of users
    */
    public function getUsersForFavourite($type, $key) {
      return get_users(array(
        'meta_key' => 'favourite-'.$type,
        'meta_value' => $key,
        'fields' => 'ids'
      ));
    }

    /**
    * send emails to users for favourite updates
    *
    * @param string $favourite
    * @param object $league
    * @param array 	$users
    * @param array 	$matches
    * @return null
    */
    public function notifyFavouritesEmail($favourite, $league, $users, $matches) {
      global $racketmanager_shortcodes;

      $headers = array();
      $fromEmail = $this->getConfirmationEmail($league->competitionType);
      $headers[] = 'From: '.ucfirst($league->competitionType).' Secretary <'.$fromEmail.'>';
      $organisationName = $this->site_name;
      $emailSubject = $this->site_name." - ".$league->title." Result Notification";
      $favouriteURL = $this->site_url.'/member-account/favourites';
      $matchURL = $this->site_url.'/'.$league->competitionType.'s/'.seoUrl($league->title).'/'.$league->current_season['name'].'/';

      foreach ( $users as $user ) {
        $userDtls = get_userdata($user);
        $emailTo = $userDtls->display_name.' <'.$userDtls->user_email.'>';
        $emailMessage = $racketmanager_shortcodes->loadTemplate( 'favourite-notification', array( 'emailSubject' => $emailSubject, 'fromEmail' => $fromEmail, 'matchURL' => $matchURL, 'favouriteURL' => $favouriteURL, 'favouriteTitle' => $favourite, 'organisationName' => $organisationName, 'user' => $userDtls, 'matches' => $matches ), 'email' );
        wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
      }
    }

    public function showMatchScreen($match) {
      global $racketmanager, $championship;

      $userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
      $userCanUpdate = $userCanUpdateArray[0];
      $userMessage = $userCanUpdateArray[3];
      if ( $match->final_round == '' ) {
        $match->round = '';
        $match->type = 'league';
      } else {
        $match->round = $match->final_round;
        $match->type = 'tournament';
      }
      $league = get_league($match->league_id);
      $numSets = $league->num_sets;
      $tabbase = 0;
      ?>
      <div id="matchrubbers">
        <div id="matchheader">
          <div class="row justify-content-between" id="match-header-1">
            <div class="col-auto leaguetitle"><?php echo $league->title ?></div>
            <div class="col-auto matchday">
              <?php if ( $league->mode == 'championship' ) {
                echo $league->championship->getFinalName($match->final_round);
              } else {
                echo 'Week'.$match->match_day;
              } ?>
            </div>
            <div class="col-auto matchdate"><?php echo substr($match->date,0,10) ?></div>
          </div>
          <div class="row justify-content-center" id="match-header-2">
            <?php if ( $league->mode != 'championship' ) { ?>
              <div class="col-auto matchtitle"><?php echo $match->match_title ?></div>
            <?php } ?>
          </div>
        </div>
        <form id="match-view" action="#" method="post" onsubmit="return checkSelect(this)">
          <?php wp_nonce_field( 'scores-match' ) ?>

          <input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo $match->league_id ?>" />
          <input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $match->id ?>" />
          <input type="hidden" name="current_season" id="current_season" value="<?php echo $match->season ?>" />
          <input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
          <input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
          <input type="hidden" name="match_type" value="<?php echo $match->type ?>" />
          <input type="hidden" name="match_round" value="<?php echo $match->round ?>" />

          <div class="row mb-3">
            <div class="col-4 text-center"><strong><?php _e( 'Team', 'racketmanager' ) ?></strong></div>
            <div class="col-4 text-center"><strong><?php _e('Sets', 'racketmanager' ) ?></strong></div>
            <div class="col-4 text-center"><strong><?php _e( 'Team', 'racketmanager' ) ?></strong></div>
          </div>
          <div class="row align-items-center mb-3">
            <div class="col-4 text-center">
              <?php echo $match->teams['home']->title ?>
            </div>
            <div class="col-4 align-self-center">
              <div class="row text-center mb-1">
                <?php for ( $i = 1; $i <= $numSets; $i++ ) {
                  if (!isset($match->sets[$i])) {
                    $match->sets[$i] = array('player1' => '', 'player2' => '');
                  }
                  $colspan = 12 / $numSets;
                  $tabindex = $tabbase + 10 + $i; ?>
                  <div class="col-<?php echo $colspan ?> col-sm-12 col-lg-<?php echo $colspan ?>">
                    <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $i ?>_player1" name="custom[sets][<?php echo $i ?>][player1]" value="<?php echo $match->sets[$i]['player1'] ?>" />
                    -
                    <?php $tabindex = $tabbase + 11 + $i; ?>
                    <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $i ?>_player2" name="custom[sets][<?php echo $i ?>][player2]" value="<?php echo $match->sets[$i]['player2'] ?>" />
                  </div>
                <?php } ?>
              </div>
            </div>
            <div class="col-4 text-center">
              <?php echo $match->teams['away']->title ?>
            </div>
          </div>
          <div class="row text-center mb-3">
            <div class="col-12">
              <input class="points" type="text" size="2" readonly id="home_points" name="home_points" value="<?php echo isset($match->home_points) ? $match->home_points : '' ?>" />
              <input class="points" type="text" size="2" readonly id="away_points" name="away_points[" value="<?php echo isset($match->away_points) ? $match->away_points : '' ?>" />
            </div>
          </div>
          <div class="form-floating">
            <textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"><?php echo $match->comments ?></textarea>
            <label for="resultConfirmComments"><?php _e( 'Comments', 'racketmanager' ) ?></label>
          </div>
          <div class="mb-3">
            <?php if ( isset($match->updated_user) ) { ?>
              <div class="row">
                <div class="col-auto">
                  Updated By:
                </div>
                <div class="col-auto">
                  <?php echo $racketmanager->getPlayerName($match->updated_user); ?>
                </div>
              </div>
              <?php if ( isset($match->updated) ) { ?>
                <div class="row">
                  <div class="col-auto">
                    On:
                  </div>
                  <div class="col-auto">
                    <?php echo $match->updated; ?>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
          <?php if ( $userCanUpdate ) {
            if (current_user_can( 'update_results' ) || $match->confirmed == 'P' || $match->confirmed == null) { ?>
              <div class="row mb-3">
                <div class="col-12">
                  <input type="hidden" name="updateMatch" id="updateMatch" value="results" />
                  <button tabindex="500" class="button button-primary" type="button" id="updateMatchResults" onclick="Racketmanager.updateMatchResults(this)">Update Result</button>
                </div>
              </div>
              <div class="row mb-3">
                <div id="updateResponse" class="updateResponse"></div>
              </div>
            <?php } else { ?>
              <div class="row mb-3">
                <div class="col-12 updateResponse message-error">
                  <?php _e('Updates not allowed', 'racketmanager') ?>
                </div>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="row mb-3 justify-content-center">
              <div class="col-auto">
                <?php if ( $userMessage == 'notLoggedIn' ) { ?>
                  You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to update the result.
                <?php } else {
                  _e('User not allowed to update result', 'racketmanager');
                } ?>
              </div>
            </div>
          <?php } ?>
        </form>
      </div>
    <?php	}

    public function showRubbersScreen($match) {
      global $racketmanager, $league, $match;
      if ( $match->final_round == '' ) {
        $match->round = '';
        $match->type = 'league';
      } else {
        $match->round = $match->final_round;
        $match->type = 'tournament';
      }
      $match->num_sets = $match->league->num_sets;
      $match->num_rubbers = $match->league->num_rubbers;
      $matchType = $match->league->type;
      $homeClub = get_club($match->teams['home']->affiliatedclub);
      $awayClub = get_club($match->teams['away']->affiliatedclub);
      switch ($matchType) {
        case 'MD':
        $homeClubPlayer['m'] = $homeClub->getPlayers(array('gender' => 'M'));
        $awayClubPlayer['m'] = $awayClub->getPlayers(array('gender' => 'M'));
        break;
        case 'WD':
        $homeClubPlayer['f'] = $homeClub->getPlayers(array('gender' => 'F'));
        $awayClubPlayer['f'] = $awayClub->getPlayers(array('gender' => 'F'));
        break;
        case 'XD':
        case 'LD':
        $homeClubPlayer['m'] = $homeClub->getPlayers(array('gender' => 'M'));
        $homeClubPlayer['f'] = $homeClub->getPlayers(array('gender' => 'F'));
        $awayClubPlayer['m'] = $awayClub->getPlayers(array('gender' => 'M'));
        $awayClubPlayer['f'] = $awayClub->getPlayers(array('gender' => 'F'));
        break;
        default:
        $homeClubPlayer['m'] = array();
        $homeClubPlayer['f'] = array();
        $awayClubPlayer['m'] = array();
        $awayClubPlayer['f'] = array();
      }
      $this->buildRubbersScreen($match, $homeClubPlayer, $awayClubPlayer);
    }
    /**
    * build screen to allow input of match rubber scores
    *
    */
    public function buildRubbersScreen($match, $homeClubPlayer, $awayClubPlayer) {
      global $racketmanager, $league, $match;
      $userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
      $userCanUpdate = $userCanUpdateArray[0];
      $userType = $userCanUpdateArray[1];
      $userTeam = $userCanUpdateArray[2];
      $userMessage = $userCanUpdateArray[3];
      $updatesAllowed = true;
      if ( $match->confirmed == 'P' && $userType != 'admin' ) {
        $updatesAllowed = false;
      }
      ?>
      <div id="matchrubbers" class="rubber-block">
        <div id="matchheader">
          <div class="row justify-content-between" id="match-header-1">
            <div class="col-auto leaguetitle"><?php echo $match->league->title ?></div>
            <?php if ( isset($match->match_day) && $match->match_day > 0 ) { ?>
              <div class="col-auto matchday">Week <?php echo $match->match_day ?></div>
            <?php } ?>
            <div class="col-auto matchdate"><?php echo substr($match->date,0,10) ?></div>
          </div>
          <div class="row justify-content-center" id="match-header-2">
            <div class="col-auto matchtitle"><?php echo $match->match_title ?></div>
          </div>
        </div>
        <form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
          <?php wp_nonce_field( 'rubbers-match' ) ?>

          <input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo $match->league_id ?>" />
          <input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $match->id ?>" />
          <input type="hidden" name="current_season" id="current_season" value="<?php echo $match->season ?>" />
          <input type="hidden" name="num_rubbers" value="<?php echo $match->num_rubbers ?>" />
          <input type="hidden" name="home_club" value="<?php echo $match->teams['home']->affiliatedclub ?>" />
          <input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
          <input type="hidden" name="away_club" value="<?php echo $match->teams['away']->affiliatedclub ?>" />
          <input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
          <input type="hidden" name="match_type" value="<?php echo $match->type ?>" />
          <input type="hidden" name="match_round" value="<?php echo $match->round ?>" />

          <div class="row">
            <div class="col-1 text-center"><strong><?php _e( 'Pair', 'racketmanager' ) ?></strong></div>
            <div class="col-3 text-center"><strong><?php _e( 'Home Team', 'racketmanager' ) ?></strong></div>
            <div class="col-5 text-center"><strong><?php _e('Sets', 'racketmanager' ) ?></strong></div>
            <div class="col-3 text-center"><strong><?php _e( 'Away Team', 'racketmanager' ) ?></strong></div>
          </div>

          <?php
          $rubbers = $match->getRubbers();
          $r = $tabbase = 0 ;
          $numPlayers = 2;

          foreach ($rubbers as $rubber) {
            $r = $rubber->rubber_number;
            if ( $match->league->type == 'MD' ) {
              $homeClubPlayer[$r][1]['players'] = $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['m'];
              $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'm';
              $awayClubPlayer[$r][1]['players'] = $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['m'];
            } elseif ( $match->league->type == 'WD' ) {
              $homeClubPlayer[$r][1]['players'] = $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['f'];
              $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'f';
              $awayClubPlayer[$r][1]['players'] = $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['f'];
            } elseif ( $match->league->type == 'XD' ) {
              $homeClubPlayer[$r][1]['players'] = $homeClubPlayer['m'];
              $awayClubPlayer[$r][1]['players'] = $awayClubPlayer['m'];
              $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = 'm';
              $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['f'];
              $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['f'];
              $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'f';
            } elseif ( $match->league->type == 'LD' ) {
              if ( $rubber->rubber_number == 1 ) {
                $homeClubPlayer[$r][1]['players'] = $homeClubPlayer['f'];
                $awayClubPlayer[$r][1]['players'] = $awayClubPlayer['f'];
                $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'f';
                $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['f'];
                $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['f'];
              } elseif ( $rubber->rubber_number == 2 ) {
                $homeClubPlayer[$r][1]['players'] = $homeClubPlayer['m'];
                $awayClubPlayer[$r][1]['players'] = $awayClubPlayer['m'];
                $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'm';
                $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['m'];
                $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['m'];
              } elseif ( $rubber->rubber_number == 3 ) {
                $homeClubPlayer[$r][1]['players'] = $homeClubPlayer['m'];
                $awayClubPlayer[$r][1]['players'] = $awayClubPlayer['m'];
                $homeClubPlayer[$r][1]['gender'] = $awayClubPlayer[$r][1]['gender'] = 'm';
                $homeClubPlayer[$r][2]['players'] = $homeClubPlayer['f'];
                $awayClubPlayer[$r][2]['players'] = $awayClubPlayer['f'];
                $homeClubPlayer[$r][2]['gender'] = $awayClubPlayer[$r][2]['gender'] = 'f';
              }
            }
            ?>
            <div class="row mb-3 border border-dark">
              <input type="hidden" name="id[<?php echo $r ?>]" value="<?php echo $rubber->id ?>" </>
              <div class="col-1 text-center align-self-center"><?php echo isset($rubber->rubber_number) ? $rubber->rubber_number : '' ?></div>
              <div class="col-11">
              <div class="row text-center mb-3">
                  <div class="col-4">
                    <label for="walkoverHome_<?php echo $r ?>"><?php _e('Home walkover', 'racketmanger') ?></label>
                    <input type="checkbox" class="checkbox" name="walkoverHome[<?php echo $r ?>]" id="walkoverHome_<?php echo $r ?>" value="walkoverHome" <?php if ( isset($rubber->walkover) && $rubber->walkover == 'home' ) { echo 'checked'; } ?> />
                  </div>
                  <div class="col-4">
                    <div class="col-12">
                      <label for="sharedRubber_<?php echo $r ?>"><?php _e('Share', 'racketmanger') ?></label>
                      <input type="checkbox" class="checkbox" name="sharedRubber[<?php echo $r ?>]" id="sharedRubber_<?php echo $r ?>" value="sharedRubber" <?php if ( isset($rubber->share) && $rubber->share ) { echo 'checked'; } ?> />
                    </div>
                  </div>
                  <div class="col-4">
                    <label for="walkoverAway_<?php echo $r ?>"><?php _e('Away walkover', 'racketmanger') ?></label>
                    <input type="checkbox" class="checkbox" name="walkoverAway[<?php echo $r ?>]" id="walkoverAway_<?php echo $r ?>" value="walkoverAway" <?php if ( isset($rubber->walkover) && $rubber->walkover == 'away' ) { echo 'checked'; } ?> />
                  </div>
                </div>
                <div class="row mb-1">
                  <div class="col-6 col-sm-4">
                    <div class="row">
                      <?php for ($p=1; $p <= $numPlayers ; $p++) { ?>
                        <div class="col-12">
                          <div class="form-group mb-2">
                            <?php $tabindex = $tabbase + 1; ?>
                            <select class="form-select" tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer<?php echo $p ?>[<?php echo $r ?>]" id="homeplayer<?php echo $p ?>_<?php echo $r ?>" <?php if ( !$updatesAllowed ) { echo 'disabled';} ?>>
                              <?php if ($homeClubPlayer[$r][$p]['gender'] == 'm') { $select = 'Select male player'; } else { $select = 'Select female player'; } ?>
                              <option value="0"><?php _e( $select, 'racketmanager' ) ?></option>
                              <?php foreach ( $homeClubPlayer[$r][$p]['players'] as $clubPlayer ) {
                                if ( isset($clubPlayer->removed_date) && $clubPlayer->removed_date != '' ) { $disabled = 'disabled'; } else { $disabled = ''; } ?>
                                <option value="<?php echo $clubPlayer->roster_id ?>"<?php $player = 'home_player_'.$p; if (isset($rubber->$player)) { selected($clubPlayer->roster_id, $rubber->$player ); } echo $disabled; ?>>
                                  <?php echo $clubPlayer->fullname ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                  <div class="col-12 col-sm-4 align-self-center order-3 order-sm-2">
                    <div class="row text-center">
                      <?php for ( $i = 1; $i <= $match->num_sets; $i++ ) {
                        if (!isset($rubber->sets[$i])) {
                          $rubber->sets[$i] = array('player1' => '', 'player2' => '');
                        }
                        $colspan = ceil(12 / $match->num_sets);
                        $tabindex = $tabbase + 10 + $i; ?>
                        <div class="col-<?php echo $colspan ?> col-sm-12 col-lg-<?php echo $colspan ?>">
                          <input tabindex="<?php echo $tabindex ?>" class="points" type="text" <?php if ( !$updatesAllowed ) { echo 'readonly';} ?> size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" value="<?php echo $rubber->sets[$i]['player1'] ?>" />
                          -
                          <?php $tabindex = $tabbase + 11 + $i; ?>
                          <input tabindex="<?php echo $tabindex ?>" class="points" type="text" <?php if ( !$updatesAllowed ) { echo 'readonly';} ?> size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" value="<?php echo $rubber->sets[$i]['player2'] ?>" />
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                  <div class="col-6 col-sm-4 order-2 order-sm-3">
                    <div class="row">
                      <?php for ($p=1; $p <= $numPlayers ; $p++) { ?>
                        <div class="col-12">
                          <div class="form-group mb-2">
                            <?php $tabindex = $tabbase + 3; ?>
                            <select class="form-select" tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer<?php echo $p ?>[<?php echo $r ?>]" id="awayplayer<?php echo $p ?>_<?php echo $r ?>" <?php if ( !$updatesAllowed ) { echo 'disabled';} ?>>
                              <?php if ($awayClubPlayer[$r][$p]['gender'] == 'm') { $select = 'Select male player'; } else { $select = 'Select female player'; } ?>
                              <option value="0"><?php _e( $select, 'racketmanager' ) ?></option>
                              <?php foreach ( $awayClubPlayer[$r][$p]['players'] as $clubPlayer ) {
                                if ( isset($clubPlayer->removed_date) && $clubPlayer->removed_date != '' ) { $disabled = 'disabled'; } else { $disabled = ''; } ?>
                                <option value="<?php echo $clubPlayer->roster_id ?>"<?php $player = 'away_player_'.$p; if (isset($rubber->$player)) { selected($clubPlayer->roster_id, $rubber->$player ); }  echo $disabled; ?>>
                                  <?php echo $clubPlayer->fullname ?>
                                </option>
                              <?php } ?>
                            </select>
                          </div>
                        </div>
                      <?php } ?>
                    </div>
                  </div>
                </div>
                <div class="row text-center mb-3">
                    <div class="col-12">
                      <input class="points" type="text" size="2" readonly id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" value="<?php echo isset($rubber->home_points) ? $rubber->home_points : '' ?>" />
                      <input class="points" type="text" size="2" readonly id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" value="<?php echo isset($rubber->away_points) ? $rubber->away_points : '' ?>" />
                    </div>
                </div>
              </div>
            </div>
            <?php
            $tabbase +=100;
            $r ++;
          }	?>
          <div id="captains" class="row mb-3">
            <div class="col-1 text-center align-self-center"></div>
            <div class="col-11">
              <div class="row">
                <div class="col-4 mb-3">
                  <div class="col-12 captain"><?php _e( 'Captain', 'racketmanager' ) ?></div>
                  <div class="col-12">
                    <?php echo $match->teams['home']->captain; ?>
                  </div>
                </div>
                <div class="col-4 mb-3">
                </div>
                <div class="col-4 mb-3">
                  <div class="col-12 captain"><?php _e( 'Captain', 'racketmanager' ) ?></div>
                  <div class="col-12">
                    <?php echo $match->teams['away']->captain; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php if ( isset($match->home_captain) || isset($match->away_captain) ) { ?>
            <div id="approvals" class="row mb-3">
              <div class="col-1 text-center align-self-center"></div>
              <div class="col-11">
                <div class="row">
                  <div class="col-4 mb-3">
                    <div class="col-12 captain"><?php _e( 'Approval', 'racketmanager' ) ?></div>
                    <div class="col-12">
                      <?php echo $racketmanager->getPlayerName($match->teams['home']->captain); ?>
                    </div>
                    <div class="col-12">
                      <?php if ( isset($match->home_captain) ) {
                        echo $racketmanager->getPlayerName($match->home_captain);
                      } else { ?>
                        <?php if ( !current_user_can( 'manage_racketmanager' ) && $match->confirmed == 'P' ) { ?>
                          <?php if ( $userType != 'admin' && $userTeam == 'home' ) { ?>
                            <div class="form-check">
                              <input type="hidden" name="resultHome" />
                              <input class="form-check-input" type="radio" name="resultConfirm" value="confirm" required />
                              <label class="form-check-label">Confirm</label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="resultConfirm" value="challenge" required />
                              <label class="form-check-label">Challenge</label>
                            </div>
                            <div class="form-floating">
                              <textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmCommentsHome" id="resultConfirmCommentsHome"></textarea>
                              <label for="resultConfirmCommentsHome"><?php _e( 'Comments', 'racketmanager' ) ?></label>
                            </div>
                          <?php } ?>
                        <?php } ?>
                      <?php } ?>
                    </div>
                  </div>
                  <div class="col-4 mb-3"></div>
                  <div class="col-4 mb-3">
                    <div class="col-12 captain"><?php _e( 'Approval', 'racketmanager' ) ?></div>
                    <div class="col-12">
                      <?php if ( isset($match->away_captain) ) {
                        echo $racketmanager->getPlayerName($match->away_captain);
                      } else { ?>
                        <?php if ( !current_user_can( 'manage_racketmanager' ) && $match->confirmed == 'P' ) { ?>
                          <?php if ( $userType != 'admin' && ($userTeam == 'away' || $userTeam == 'both') ) { ?>
                            <div class="form-check">
                              <input type="hidden" name="resultAway" />
                              <input class="form-check-input" type="radio" name="resultConfirm" value="confirm" required />
                              <label class="form-check-label"><?php _e( 'Confirm', 'racketmanager' ) ?></label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="radio" name="resultConfirm" value="challenge" required />
                              <label class="form-check-label"><?php _e( 'Challenge', 'racketmanager' ) ?></label>
                            </div>
                            <div class="form-floating">
                              <textarea class="form-control result-comments" placeholder="Leave a comment here" name="resultConfirmCommentsAway" id="resultConfirmCommentsAway"></textarea>
                              <label for="resultConfirmCommentsAway"><?php _e( 'Comments', 'racketmanager' ) ?></label>
                            </div>
                          <?php } ?>
                        <?php } ?>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
          <div class="row mt-3 mb-3">
            <div>
              <div class="form-floating">
                <textarea class="form-control result-comments" tabindex="490" placeholder="Leave a comment here" name="resultConfirmComments" id="resultConfirmComments"><?php echo $match->comments ?></textarea>
                <label for="resultConfirmComments"><?php _e( 'Comments', 'racketmanager' ) ?></label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <?php if ( isset($match->updated_user) ) { ?>
              <div class="row">
                <div class="col-auto">Updated By:</div>
                <div class="col-auto">
                  <?php echo $racketmanager->getPlayerName($match->updated_user); ?>
                </div>
              </div>
              <?php if ( isset($match->updated) ) { ?>
                <div class="row">
                  <div class="col-auto">On:</div>
                  <div class="col-auto"><?php echo $match->updated; ?></div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
          <?php if ( $userCanUpdate ) {
            if (current_user_can( 'update_results' ) || $match->confirmed == 'P' || $match->confirmed == null) { ?>
              <?php if ($userType == 'admin' || ($userTeam != 'away' && !isset($match->home_captain)) || ($userTeam != 'home' && !isset($match->away_captain))) {?>
                <div class="row mb-3">
                  <div class="col-12">
                    <input type="hidden" name="updateRubber" id="updateRubber" value="<?php if ( !$updatesAllowed ) { echo 'confirm';} else { echo 'results';} ?>" />
                    <button tabindex="500" class="button button-primary" type="button" id="updateRubberResults" onclick="Racketmanager.updateResults(this)">Update Results</button>
                  </div>
                </div>
                <div class="row mb-3">
                  <div id="updateResponse" class="updateResponse"></div>
                </div>
              <?php } else { ?>
                <div class="row mb-3">
                  <div class="col-12 updateResponse message-error">
                    <?php _e('Team result already entered', 'racketmanager') ?>
                  </div>
                </div>
              <?php } ?>
            <?php } else { ?>
              <div class="row mb-3">
                <div class="col-12 updateResponse message-error">
                  <?php _e('Updates not allowed', 'racketmanager') ?>
                </div>
              </div>
            <?php } ?>
          <?php } else { ?>
            <div class="row mb-3 justify-content-center">
              <div class="col-auto">
                <?php if ( $userMessage == 'notLoggedIn' ) { ?>
                  You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'] ); ?>">login</a> to update the result.
                <?php } else {
                  _e('User not allowed to update result', 'racketmanager');
                } ?>
              </div>
            </div>
          <?php } ?>
        </form>
      </div>
      <?php
    }

  }
