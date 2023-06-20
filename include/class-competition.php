<?php
/**
* Competition API: Competition class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage Competition
*/

/**
* Class to implement the Competition object
*
*/
class Competition {
	/**
	* Competition ID
	*
	* @var int
	*/
	public $id;

	/**
	* Competition name
	*
	* @var string
	*/
	public $name;

	/**
	* seasons data
	*
	* @var array
	*/
	public $seasons = array();

	/**
	* number of seasons
	*
	* @var int
	*/
	public $num_seasons = 0;

	/**
	* sport type
	*
	* @var string
	*/
	public $sport = "tennis";

	/**
	* point rule
	*
	* @var string
	*/
	public $point_rule = "tennis";

	/**
	* primary points format
	*
	* @var string
	*/
	public $point_format = "%d-%d";

	/**
	* secondary points format
	*
	* @var string
	*/
	public $point_format2 = "%d-%d";

	/**
	* team ranking mode
	*
	* @var string
	*/
	public $team_ranking = "auto";

	/**
	* league mode
	*
	* @var string
	*/
	public $mode = "default";

	/**
	* default match starting time
	*
	* @var array
	*/
	public $default_match_start_time = array("hour" => 19, "minutes" => 30);

	/**
	* standings table layout settings
	*
	* @var array
	*/
	public $standings = array( 'status' => 1, 'pld' => 1, 'won' => 1, 'tie' => 1, 'lost' => 1, 'winPercent' => 1, 'last5' => 1 );

	/**
	* number of teams ascending
	*
	* @var int
	*/
	public $num_ascend = 0;

	/**
	* number of teams descending
	*
	* @var int
	*/
	public $num_descend = 0;

	/**
	* number of teams for relegation
	*
	* @var int
	*/
	public $num_relegation = 0;

	/**
	* number of teams per page in list
	*
	* @var int
	*/
	public $num_matches_per_page = 10;

	/**
	* league offsets indexed by ID
	*
	* @var array
	*/
	public $league_index = array();

	/**
	* league loop
	*
	* @var boolean
	*/
	public $in_the_league_loop = false;

	/**
	* current league
	*
	* @var int
	*/
	public $league_team = -1;

	/**
	* custom team input field keys and translated labels
	*
	* @var array
	*/
	public $fields_team = array();

	/**
	* championship flag
	*
	* @var boolean
	*/
	public $is_championship = false;

	/**
	* num_rubbers
	*
	* @var int
	*/
	public $num_rubbers = '';

	/**
	* num_sets
	*
	* @var int
	*/
	public $num_sets = '';

	/**
	* type
	*
	* @var string
	*/
	public $type = '';

	/**
	* current season
	*
	* @var array
	*/
	public $current_season = '';

	/**
	* number of match days
	*
	* @var int
	*/
	public $num_match_days = '';

	/**
	* number of leagues
	*
	* @var int
	*/
	public $num_leagues = '';

	/**
	* leagues
	*
	* @var array
	*/
	public $leagues = '';

	/**
	* settings keys
	*
	* @var array
	*/
	public $settings_keys = '';

	/**
	* constitutions
	*
	* @var array
	*/
	public $constitutions = '';

	/**
	* competition Teams
	*
	* @var array
	*/
	public $competitionTeams = '';
	public $settings;
	public $groups;
	public $teams_per_group;
	public $num_advance;
	public $match_place3;
	public $non_group;
	public $entry_open;
	/**
	* retrieve competition instance
	*
	* @param int $competition_id
	*/
	public static function get_instance($competition_id) {
		global $wpdb;

		$competition_id = (int) $competition_id;
		if ( ! $competition_id ) {
			return false;
		}

		$competition = wp_cache_get( $competition_id, 'competitions' );

		if ( ! $competition ) {
			$competition = $wpdb->get_row( $wpdb->prepare( "SELECT `name`, `id`, `num_sets`, `num_rubbers`, `type`, `settings`, `seasons`, `competitiontype` FROM {$wpdb->racketmanager_competitions} WHERE `id` = '%d'", $competition_id ) );
			$competition->settings = (array)maybe_unserialize($competition->settings);
			$competition = (object)array_merge((array)$competition, $competition->settings);

			if ( !$competition ) {
				return false;
			}

			// check if specific sports class exists
			if ( !isset($competition->sport) ) {
				$competition->sport = '';
			}
			$instance = "Competition_". ucfirst($competition->sport);
			if (class_exists($instance)) {
				$competition = new $instance( $competition );
			} else {
				$competition = new Competition( $competition );
			}

			wp_cache_set( $competition->id, $competition, 'competitions' );
		}

		return $competition;
	}

	/**
	* Constructor
	*
	* @param object $competition Competition object.
	*/
	public function __construct( $competition ) {

		if ( !isset($competition->id) ) {
			$this->add($competition);
		}
		if (isset($competition->settings)) {
			$competition->settings = (array)maybe_unserialize($competition->settings);
			$competition->settings_keys = array_keys((array)maybe_unserialize($competition->settings));
			$competition = (object)array_merge((array)$competition, $competition->settings);
		}

		foreach ( get_object_vars( $competition ) as $key => $value ) {
			if ( $key == "standings") {
				$this->$key = array_merge($this->$key, $value);
			} else {
				$this->$key = $value;
			}
		}

		$this->name = stripslashes($this->name);
		$this->num_rubbers = stripslashes($this->num_rubbers);
		$this->num_sets = stripslashes($this->num_sets);
		$this->type = stripslashes($this->type);

		// set seasons
		if ( $this->seasons == '' ) {
			$this->seasons = array();
		} else {
			$this->seasons = (array)maybe_unserialize($this->seasons);
		}
		if ( !is_admin() ) {
			$i = 0;
			$seasons = array();
			foreach ($this->seasons as $season) {
				$seasons[$season['name']] = $season;
				if ( isset($season['status']) && $season['status'] == 'draft' ) {
					unset($seasons[$season['name']]);
				}
				$i++;
			}
			$this->seasons = $seasons;
		}
		$this->num_seasons = count($this->seasons);
		$this->setNumLeagues(true);

		// set default standings display options for additional team fields
		if ( count($this->fields_team) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				if ( !isset($this->standings[$key]) ) {
					$this->standings[$key] = 1;
				}
			}
		}

		// set season to latest
		if ( $this->num_seasons > 0 ) {
			$this->setSeason();
		}

		// Championship
		if ( $this->mode == "championship" ) {
			$this->is_championship = true;
		}

		// add actions & filter
		add_filter( 'competition_standings_options', array(&$this, 'standingsTableDisplayOptions') );
		return $competition;
	}

	/**
	* add new competition
	*
	* @param object $competition
	* @return none
	*/
	private function add($competition) {
		global $wpdb;

		if ( $competition->competitionType == 'league' ) {
			$mode = 'default';
			$entryType = 'team';
		} elseif ( $competition->competitionType == 'cup' ) {
			$mode = 'championship';
			$entryType = 'team';
		} elseif ( $competition->competitionType == 'tournament' ) {
			$mode = 'championship';
			$entryType = 'player';
		}
		if ( $mode == 'championship' ) {
			$ranking = "manual";
			$standings = array( 'pld' => 1, 'won' => 1, 'tie' => 1, 'lost' => 1 );
		} else {
			$ranking = "auto";
			$standings = array( 'pld' => 0, 'won' => 0, 'tie' => 0, 'lost' => 0 );
		}
		$settings = array(
			"sport" => "tennis",
			"point_rule" => "tennis",
			"point_format" => "%s",
			"point_format2" => "%s",
			"team_ranking" => $ranking,
			"mode" => $mode,
			"entryType" => $entryType,
			"default_match_start_time" => array("hour" => 19, "minutes" => 30),
			"standings" => $standings,
			"num_ascend" => "",
			"num_descend" => "",
			"num_relegation" => "",
			"num_matches_per_page" => 10,
		);

		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_competitions} (`name`, `num_rubbers`, `num_sets`, `type`, `settings`, `competitiontype`) VALUES ('%s', '%d', '%d', '%s', '%s', '%s')", $competition->name, $competition->num_rubbers, $competition->num_sets, $competition->type, maybe_serialize($settings), $competition->competitionType ) );
		$competition->id = $wpdb->insert_id;

	}

	/**
	* delete Competition
	*
	* @param int $competition_id
	* @return boolean
	*/
	public function delete() {
		global $wpdb;

		foreach ( $this->getLeagues() as $league ) {
			$league = get_league($league->id);
			$league->delete();
		}

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_team_competition} WHERE `competition_id` = '%d'", $this->id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_competitions_seasons} WHERE `competition_id` = '%d'", $this->id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_competitions} WHERE `id` = '%d'", $this->id) );
	}

	/**
	* set name
	*
	* @param string $name
	* @return null
	*/
	public function setName($name) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_competitions} SET `name` = '%s' WHERE `id` = '%d'", $name, $this->id ) );
		$this->name = $name;
	}

	/**
	* update settings
	*
	* @param array $settings
	* @return null
	*/
	public function setSettings($settings) {
		global $wpdb, $racketmanager;

		foreach ( $racketmanager->getStandingsDisplayOptions() as $key ) {
			$settings['standings'][$key] = isset($settings['standings'][$key]) ? 1 : 0;
		}

		$numRubbers = $settings['num_rubbers'];
		$numSets = $settings['num_sets'];
		$type = $settings['competition_type'];

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_competitions} SET `settings` = '%s', `num_rubbers` = '%d', `num_sets` = '%d', `type` = '%s' WHERE `id` = '%d'", maybe_serialize($settings), $numRubbers, $numSets, $type, $this->id ) );
	}

	/**
	* set current season
	*
	* @param mixed $season
	* @param boolean $force_overwrite
	*/
	public function setSeason( $season = false, $force_overwrite = false ) {
		if ( !empty($season) && $force_overwrite === true ) {
			$data = $this->seasons[$season];
		} elseif ( isset($_GET['season']) && !empty($_GET['season']) ) {
			$key = htmlspecialchars(strip_tags($_GET['season']));
			if (!isset($this->seasons[$key])) {
				$data = false;
			} else {
				$data = $this->seasons[$key];
			}
		} elseif ( isset($_GET['season_'.$this->id]) && !empty($_GET['season_'.$this->id]) ) {
			$key = htmlspecialchars(strip_tags($_GET['season_'.$this->id]));
			if (!isset($this->seasons[$key])) {
				$data = false;
			} else {
				$data = $this->seasons[$key];
			}
		} elseif ( !empty($season) ) {
			$data = $this->seasons[$season];
		} else {
			$data = end($this->seasons);
		}

		if (empty($data)) {
			$data = end($this->seasons);
		}

		$this->current_season = $data;
		$this->num_match_days = $data['num_match_days'];
	}

	/**
	* get current season name
	*
	* @return string
	*/
	public function getSeason() {
		return stripslashes($this->current_season['name']);
	}

	/**
	* get current season
	*
	* @param object $league
	* @param mixed $season
	* @return array
	*/
	public function getSeasonCompetition( $season = false, $index = false ) {

		if ( isset($_GET['season']) && !empty($_GET['season']) ) {
			$key = htmlspecialchars(strip_tags($_GET['season']));
			if (!isset($this->seasons[$key])) {
				$data = false;
			} else {
				$data = $this->seasons[$key];
			}
		} elseif ( isset($_GET['season_'.$this->id]) ) {
			$key = htmlspecialchars(strip_tags($_GET['season_'.$this->id]));
			if (!isset($this->seasons[$key])) {
				$data = false;
			} else {
				$data = $this->seasons[$key];
			}
		} elseif ( $season ) {
			$data = $this->seasons[$season];
		} elseif ( !empty($this->seasons) ) {
			$data = end($this->seasons);
		} else {
			$data = false;
		}
		if (empty($data)) {
			$data = end($this->seasons);
		}
		if ( $index ) {
			return $data[$index];
		}	else {
			return $data;
		}
	}

	/**
	* gets number of leagues
	*
	* @param boolean $total
	*/
	public function setNumLeagues($total=false) {
		global $wpdb;

		if ($total === true) {
			$this->num_leagues = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->racketmanager} WHERE `competition_id` = '%d'", $this->id) );
		}
	}

	/**
	* get leagues from database
	*
	* @param int $league_id (default: false)
	* @param string $search
	* @return array
	*/
	public function getLeagues( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'competition' => false, 'orderby' => array("title" => "ASC") );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		$search_terms[] = $wpdb->prepare("`competition_id` = '%d'", intval($this->id));

		$search = "";
		if (!empty($search_terms)) {
			$search = " WHERE ";
			$search .= implode(" AND ", $search_terms);
		}

		$orderby_string = ""; $i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderby_string .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) {
				$orderby_string .= ",";
			}
			$i++;
		}
		$orderby = $orderby_string;
		$sql = $wpdb->prepare( "SELECT `title`, `id`, `settings`, `competition_id` FROM {$wpdb->racketmanager} $search ORDER BY $orderby LIMIT %d, %d", intval($offset), intval($limit) );
		$leagues = wp_cache_get( md5($sql), 'leagues' );
		if ( !$leagues ) {
			$leagues =  $wpdb->get_results($sql);
			wp_cache_set( md5($sql), $leagues, 'leagues' );
		}

		$league_index = array();
		foreach ( $leagues as $i => $league ) {

			$league_index[$league->id] = $i;
			$leagues[$i] = $league;
		}

		$this->leagues = $leagues;
		$this->league_index = $league_index;

		return $leagues;
	}

	/**
	* get league from database
	*
	* @param string $title (default: false)
	* @return int $league_id
	*/
	public function getLeagueId( $title ) {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT `id` FROM {$wpdb->racketmanager} WHERE `title` = '%s'", $title );
		$league = wp_cache_get( md5($sql), 'league' );
		if ( !$league ) {
			$league =  $wpdb->get_row($sql);
			wp_cache_set( md5($sql), $league, 'league' );
		}

		if ( !$league ) {
			return 0;
		}

		return $league->id;
	}

	/**
	* get player stats
	*
	* @param array $query_args
	* @return array
	*/
	public function getPlayerStats( $args ) {
		global $wpdb;

		$defaults = array( 'season' => false, 'cache' => true, 'club' => false, 'leagueId' => false, 'system' => false, 'player' => false );
		$args = array_merge($defaults, (array)$args);
		extract($args, EXTR_SKIP);

		$sql1 = "SELECT p.ID AS `player_id`, p.`display_name` AS `fullname`, ro.`id` AS `roster_id`,  ro.`affiliatedclub` FROM {$wpdb->racketmanager_club_players} AS ro, {$wpdb->users} AS p WHERE ro.`player_id` = p.`ID`";
		$sql2 = "FROM {$wpdb->racketmanager_teams} AS t, {$wpdb->racketmanager_rubbers} AS r, {$wpdb->racketmanager_matches} AS m, {$wpdb->racketmanager_club_players} AS ro WHERE r.`winner_id` != 0 AND (((r.`home_player_1` = ro.`id` OR r.`home_player_2` = ro.`id`) AND  m.`home_team` = t.`id`) OR ((r.`away_player_1` = ro.`id` OR r.`away_player_2` = ro.`id`) AND m.`away_team` = t.`id`)) AND ro.`affiliatedclub` = t.`affiliatedclub` AND r.`match_id` = m.`id` AND m.`league_id` IN (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` = '%d') ";

		$search_terms2 = array($this->id);

		if ($season) {
			$sql2 .= " AND m.`season` = '%s'";
			$search_terms2[] = htmlspecialchars(strip_tags($season));
		}
		if ($leagueId) {
			$sql2 .= " AND m.`league_id` = '%d'";
			$search_terms2[] = intval($leagueId);
		}
		if ($club) {
			$sql2 .= " AND ro.`affiliatedclub` = '%d'";
			$search_terms2[] = intval($club);
		}
		if ($player) {
			$sql2 .= " AND ro.`id` = '%d'";
			$search_terms2[] = intval($player);
		}
		if (!$system) {
			$sql2 .= " AND ro.`system_record` IS NULL";
		}

		$order = "`affiliatedclub`, `fullname` ";

		$sql = $sql1." AND ro.`id` in (SELECT ro.id ".$sql2.")";

		if ( $order != "") {
			$sql .= " ORDER BY $order";
		}

		$sql = $wpdb->prepare($sql, $search_terms2);
		$playerstats = wp_cache_get( md5($sql), 'playerstats' );
		if ( !$playerstats ) {
			$playerstats = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $playerstats, 'playerstats' );
		}

		foreach ( $playerstats as $i => $playerstat ) {

			$sql3 = "SELECT t.`id` AS team_id,  t.`title` AS team_title, m.`season`, m.`match_day`, m.`home_team`, m.`away_team`, m.`winner_id` AS match_winner, m.`home_points`, m.`away_points`, m.`loser_id` AS match_loser, r.`rubber_number`, r.`home_player_1`, r.`home_player_2`, r.`away_player_1`, r.`away_player_2`, r.`winner_id` AS rubber_winner, r.`loser_id` AS rubber_loser, r.`custom`, m.`final`";
			$sql3 .= $sql2." AND ro.`ID` = ".$playerstat->roster_id;
			$sql3 .= " ORDER BY m.`season`, m.`match_day`";

			$sql = $wpdb->prepare($sql3, $search_terms2);
			$stats = wp_cache_get( md5($sql), 'playerstats' );
			if ( !$stats ) {
				$stats = $wpdb->get_results( $sql );
				wp_cache_set( md5($sql), $stats, 'playerstats' );
			}

			foreach ( $stats as $s => $stat ) {

				$stat->custom = stripslashes_deep(maybe_unserialize($stat->custom));
				$stats[$s] = $stat;

			}

			$playerstat->matchdays = $stats;
			$playerstats[$i] = (object)(array)$playerstat;
		}

		return $playerstats;
	}

	/**
	* get teams from database
	*
	* @param array $args
	* @param string $output OBJECT | ARRAY
	* @return array database results
	*/
	public function getTeamsInfo( $args = array() ) {
		global $wpdb;

		$defaults = array( 'leagueId' => false, 'group' => false, 'rank' => false, 'orderby' => array("rank" => "ASC", "title" => "ASC"), "home" => false, "cache" => true, 'affiliatedclub' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		if ( $leagueId ) {
			if ($leagueId == "any") {
				$search_terms[] = "A.`league_id` != ''";
			} else {
				$search_terms[] = $wpdb->prepare("A.`league_id` = '%d'", intval($leagueId));
			}
		}
		if ( $affiliatedclub ) {
			$search_terms[] = $wpdb->prepare("`affiliatedclub` = '%d'", intval($affiliatedclub));
		}
		if ( $rank ) {
			$search_terms[] = $wpdb->prepare("A.`rank` = '%s'", $rank);
		}

		if ( $home ) {
			$search_terms[] = "B.`home` = 1";
		}

		$search = "";
		if (!empty($search_terms)) {
			$search = " AND ";
			$search .= implode(" AND ", $search_terms);
		}

		$orderby_string = ""; $i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderby_string .= "`".$order."` ".$direction;
			if ($i < (count($orderby)-1)) {
				$orderby_string .= ",";
			}
			$i++;
		}
		$orderby = $orderby_string;

		$sql = "SELECT DISTINCT B.`id`, B.`title`, C.`captain`, B.`affiliatedclub`, B.`stadium`, B.`home`, B.`roster`, B.`profile`, A.`group`, C.`match_day`, C.`match_time` FROM {$wpdb->racketmanager_teams} B, {$wpdb->racketmanager_table} A, {$wpdb->racketmanager_team_competition} C WHERE B.id = A.team_id AND A.team_id = C.team_id and C.competition_id in (select `competition_id` from {$wpdb->racketmanager} WHERE `id` = A.league_id) AND C.`competition_id` = ".$this->id." AND A.season = ".$this->current_season['name']." $search ORDER BY $orderby";

		$teams = wp_cache_get( md5($sql), 'teams' );
		if ( !$teams ) {
			$teams = $wpdb->get_results( $sql );
			wp_cache_set( md5($sql), $teams, 'teams' );
		}

		$class = '';
		foreach ( $teams as $i => $team ) {
			$class = ( 'alternate' == $class ) ? '' : 'alternate';
			$captain = get_userdata($team->captain);
			$team->roster = maybe_unserialize($team->roster);
			$team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
			if ( $captain != '' ) {
				$team->captain = $captain->display_name;
				$team->captainId = $captain->ID;
				$team->contactno = get_user_meta($captain->ID, 'contactno', true);
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain = 'Unknown';
				$team->captainId = '';
				$team->contactno = '';
				$team->contactemail = '';
			}
			$team->affiliatedclub = stripslashes($team->affiliatedclub);
			$team->affiliatedclubname = get_club( $team->affiliatedclub )->name;
			$team->stadium = stripslashes($team->stadium);
			$team->class = $class;
			$teams[$i] = $team;
		}

		return $teams;
	}

	/**
	* get specific team details from database
	*
	* @param int #team_id
	* @return array database results
	*/
	public function getTeamInfo( $team_id ) {
		global $wpdb;

		$sql = "SELECT `captain`, `match_day`, `match_time` FROM {$wpdb->racketmanager_team_competition} WHERE `competition_id` = ".$this->id." AND `team_id` = ".$team_id;

		$team = wp_cache_get( md5($sql), 'team' );
		if ( !$team ) {
			$team = $wpdb->get_row( $sql );
			wp_cache_set( md5($sql), $team, 'team' );
		}

		if ( $team ) {
			$captain = get_userdata($team->captain);
			if ( $captain != '' ) {
				$team->captain = $captain->display_name;
				$team->captainId = $captain->ID;
				$team->contactno = get_user_meta($captain->ID, 'contactno', true);
				$team->contactemail = $captain->user_email;
			} else {
				$team->captain = 'Unknown';
				$team->captainId = '';
				$team->contactno = '';
				$team->contactemail = '';
			}
		}

		return $team;
	}

	/**
	* get settings
	*
	* @param string $key settings key
	* @return array
	*/
	public function getSettings($key=false) {
		$settings = array();
		foreach ($this->settings_keys as $k) {
			$settings[$k] = $this->$k;
		}

		if ( $key ) {
			return (isset($settings[$key])) ? $settings[$key] : false;
		}

		return $settings;
	}

	/**
	* reload settings from database
	*/
	public function reloadSettings() {
		global $wpdb;

		wp_cache_delete( $this->id, 'competitions' );
		$result = $wpdb->get_row( $wpdb->prepare("SELECT `settings` FROM {$wpdb->racketmanager_competitions} WHERE `id` = '%d'", intval($this->id)) );
		foreach ( maybe_unserialize($result->settings) as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	* add custom standings table display options
	*
	* @param array $options
	* @return array
	*/
	public function standingsTableDisplayOptions( $options ) {
		if ( count($this->fields_team) > 0 ) {
			foreach ( $this->fields_team as $key => $data ) {
				$options[$key] = isset($data['desc']) ? $data['desc'] : $data['label'];
			}
		}

		return $options;
	}

	/**
	* get constitution from database
	*
	* @param int $league_id (default: false)
	* @param string $search
	* @return array
	*/
	public function getConstitution( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'season' => false, 'oldseason' => false, 'orderby' => false, 'club' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		$search_terms[] = $wpdb->prepare("`competition_id` = %d", $this->id);

		if ( $season ) {
			$search_terms[] = $wpdb->prepare("t1.`season` = '%s'", $season);
		}

		if ( !$oldseason ) {
			$oldseason = $season;
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare("t2.`affiliatedclub` = %d", intval($club));
		}

		$search = "";
		if (!empty($search_terms)) {
			$search = " AND ";
			$search .= implode(" AND ", $search_terms);
		}

		$sql = $wpdb->prepare( "SELECT `l`.`title` AS `leagueTitle`, l.`id` AS `leagueId`, ot.`league_id` AS oldLeagueId, t2.`id` AS `teamId`, t1.`id` AS `tableId`, `t2`.`title`,`t1`.`rank`,`ot`.`rank` AS oldRank, l.`id`, ot.`points_plus`, ot.`add_points`, t1.`status`, t1.`profile` FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1 LEFT OUTER JOIN {$wpdb->racketmanager_table} ot ON `ot`.`season` = '%s' and `ot`.`team_id` = `t1`.`team_id` and ot.league_id in (select id from wp_racketmanager_leagues ol where ol.`competition_id` = %d) WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d", $oldseason, $this->id, intval($offset), intval($limit) );

		$constitutions = wp_cache_get( md5($sql), 'constitution' );
		if ( !$constitutions ) {
			$constitutions =  $wpdb->get_results($sql);
			wp_cache_set( md5($sql), $constitutions, 'constitution' );
		}

		foreach ( $constitutions as $i => $constitution ) {
			if ( isset($constitution->oldLeagueId) ) {
				$constitution->oldLeagueTitle = get_league($constitution->oldLeagueId)->title;
			} else {
				$constitution->oldLeagueTitle = '';
			}
			$constitutions[$i] = $constitution;
		}

		$this->constitutions = $constitutions;

		return $constitutions;
	}

	/**
	* get constitution from database
	*
	* @param int $league_id (default: false)
	* @param string $search
	* @return array
	*/
	public function buildConstitution( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'season' => false, 'orderby' => false, 'club' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		$search_terms[] = $wpdb->prepare("`competition_id` = %d", intval($this->id));

		if ( $season ) {
			$search_terms[] = $wpdb->prepare("`season` = '%s'", $season);
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare("t2.`affiliatedclub` = %d", intval($club));
		}

		$search = "";
		if (!empty($search_terms)) {
			$search = " AND ";
			$search .= implode(" AND ", $search_terms);
		}

		$sql = $wpdb->prepare( "SELECT `l`.`title` AS `oldLeagueTitle`, l.`id` AS `oldLeagueId`, t2.`id` AS `teamId`, t1.`id` AS `tableId`, `t2`.`title`,`t1`.`rank` AS oldRank, l.`id`, t1.`points_plus`, t1.`add_points`, t1.`status`, t1.`profile` FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_table} t1, {$wpdb->racketmanager_teams} t2 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` $search ORDER BY l.`title` ASC, t1.`rank` ASC LIMIT %d, %d", intval($offset), intval($limit) );
		$constitutions = wp_cache_get( md5($sql), 'constitution' );
		if ( !$constitutions ) {
			$constitutions =  $wpdb->get_results($sql);
			wp_cache_set( md5($sql), $constitutions, 'constitution' );
		}

		foreach ( $constitutions as $i => $constitution ) {
			$constitution->rank = $constitution->oldRank;
			$constitution->status = '';
			$constitution->leagueId = $constitution->oldLeagueId;

			$constitutions[$i] = $constitution;
		}

		$this->constitutions = $constitutions;

		return $constitutions;
	}

	/**
	* get teams from database
	*
	* @param int $league_id (default: false)
	* @param string $search
	* @return array
	*/
	public function getTeams( $args = array() ) {
		global $wpdb;

		$defaults = array( 'offset' => 0, 'limit' => 99999999, 'season' => false, 'orderby' => false, 'club' => false, 'status' => false, 'count' => false );
		$args = array_merge($defaults, $args);
		extract($args, EXTR_SKIP);

		$search_terms = array();
		$search_terms[] = $wpdb->prepare("`competition_id` = %d", $this->id);

		if ( $season ) {
			$search_terms[] = $wpdb->prepare("t1.`season` = '%s'", $season);
		}

		if ( $club ) {
			$search_terms[] = $wpdb->prepare("t2.`affiliatedclub` = %d", intval($club));
		}

		if ( $status ) {
			$search_terms[] = $wpdb->prepare("t1.`profile` = %d", intval($status));
		}

		$search = "";
		if (!empty($search_terms)) {
			$search = " AND ";
			$search .= implode(" AND ", $search_terms);
		}

		if ( $count ) {
			$sql = "SELECT COUNT(*)";
		} else {
			$sql = "SELECT `l`.`title` AS `leagueTitle`, l.`id` AS `leagueId`, t2.`id` AS `teamId`, t1.`id` AS `tableId`, `t2`.`title`,`t1`.`rank`, l.`id`, t1.`status`, t1.`profile`, t1.`group`";
		}
		$sql .= " FROM {$wpdb->racketmanager} l, {$wpdb->racketmanager_teams} t2, {$wpdb->racketmanager_table} t1 WHERE t1.`team_id` = t2.`id` AND l.`id` = t1.`league_id` ".$search;

		if ( $count ) {
			return $wpdb->get_var($sql);
		}
		$sql = $wpdb->prepare( $sql." ORDER BY l.`title` ASC, t2.`title` ASC LIMIT %d, %d", intval($offset), intval($limit) );

		$competitionTeams = wp_cache_get( md5($sql), 'competitionTeams' );
		if ( !$competitionTeams ) {
			$competitionTeams =  $wpdb->get_results($sql);
			wp_cache_set( md5($sql), $competitionTeams, 'competitionTeams' );
		}

		foreach ( $competitionTeams as $i => $competitionTeam ) {
			$competitionTeams[$i] = $competitionTeam;
		}

		$this->competitionTeams = $competitionTeams;

		return $competitionTeams;
	}

	/**
	* mark teams withdrawn from competition
	*
	* @param string $season season
	* @param int $club Club Id
	* @return none
	*/
	public function markTeamsWithdrawn( $season, $club, $team = false ) {
		global $wpdb;

		$search_terms = array();
		if ( $team ) {
			$search_terms[] = $wpdb->prepare("`team_id` = %d", intval($team));
		}
		$search = "";
		if (!empty($search_terms)) {
			$search = " AND ";
			$search .= implode(" AND ", $search_terms);
		}

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_table} SET `profile` = 3, `status` = 'W' WHERE `league_id` IN (select `id` FROM {$wpdb->racketmanager} WHERE `competition_id` = '%d') AND `season` = '%s' AND `team_id` IN (SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d') $search ", $this->id, $season, $club ) );
	}

	/**
	* mark teams entered into competition
	*
	* @param int $team Team Id
	* @param string $season season
	* @return none
	*/
	public function markTeamsEntered( $team, $season ) {
		global $wpdb;

			$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_table} SET `profile` = 1 WHERE `league_id` IN (select `id` FROM {$wpdb->racketmanager} WHERE `competition_id` = '%d') AND `season` = '%s' AND `team_id` = '%d'", $this->id, $season, $team ) );
	}

	/**
	* add team entered into competition
	*
	* @param int $team Team Id
	* @param string $season season
	* @return none
	*/
	public function addTeamToCompetition( $team, $season ) {

		$leagues = $this->getLeagues( array('orderby' => array("title" => "DESC")));
		$leagueId = $leagues[0]->id;
		$rank = 99;
		$status = 'NT';
		$profile = 2;
		$league = get_league($leagueId);
		$league->addTeam($team, $season, $rank, $status, $profile);
	}

}

/**
* get Competition object
*
* @param int|Competition|null Competition ID or competition object. Defaults to global $competition
* @return object competition|null
*/
function get_competition( $competition = null ) {
	if ( empty( $competition ) && isset( $GLOBALS['competition'] ) ) {
		$competition = $GLOBALS['competition'];
	}

	if ( $competition instanceof Competition ) {
		$_competition = $competition;
	} elseif ( is_object( $competition ) ) {
		// check if specific sports class exists
		if ( !isset($competition->sport) ) {
			$competition->sport = '';
		}
		$instance = "Competition_". ucfirst($competition->sport);
		if (class_exists($instance)) {
			$_competition = new $instance( $competition );
		} else {
			$_competition = new Competition( $competition );
		}
	} else {
		$_competition = Competition::get_instance( $competition );
	}

	if ( ! $_competition ) {
		return null;
	}

	return $_competition;
}