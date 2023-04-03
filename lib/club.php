<?php
/**
* Club API: Club class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage Club
*/

/**
* Class to implement the Club object
*
*/
final class Club {

  /**
  * retrieve club instance
  *
  * @param int $club_id
  */
  public static function get_instance($club_id, $queryTerm = "id") {
    global $wpdb;

    switch ($queryTerm) {
      case "id":
      $club_id = (int) $club_id;
      $search = "`id` = '%d'";
      break;
      case "name":
      $search = "`name` = '%s'";
      break;
      case "shortcode":
      $search = "`shortcode` = '%s'";
      break;
    }

    if ( ! $club_id ) {
      return false;
    }

    $club = wp_cache_get( $club_id, 'clubs' );

    if ( ! $club ) {
      $club = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} WHERE ".$search." LIMIT 1", $club_id ) );

      if ( !$club ) return false;

      $club = new Club( $club );

      wp_cache_set( $club->id, $club, 'clubs' );
    }

    return $club;
  }

  /**
  * Constructor
  *
  * @param object $club Club object.
  */
  public function __construct( $club = null ) {
    if ( !is_null($club) ) {

      foreach ( get_object_vars( $club ) as $key => $value ) {
        $this->$key = $value;
      }

      if ( !isset($this->id) ) {
        $this->add();
      }
      $this->matchSecretaryName = '';
      $this->matchSecretaryEmail = '';
      $this->matchSecretaryContactNo = '';
      if ( isset($this->matchsecretary) && $this->matchsecretary != '0' ) {
        $matchSecretaryDtls = get_userdata($this->matchsecretary);
        if ($matchSecretaryDtls) {
          $this->matchSecretaryName = $matchSecretaryDtls->display_name;
          $this->matchSecretaryEmail = $matchSecretaryDtls->user_email;
          $this->matchSecretaryContactNo = get_user_meta($this->matchsecretary, 'contactno', true);
        }
      }
      $this->desc = '';
    }
  }

  /**
  * create club in database
  *
  */
  private function add() {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_clubs} (`name`, `type`, `shortcode`, `contactno`, `website`, `founded`, `facilities`, `address`, `latitude`, `longitude`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s' )", $this->name, $this->type, $this->shortcode, $this->contactno, $this->website, $this->founded, $this->facilities, $this->address, $this->latitude, $this->longitude ) );
    $this->id = $wpdb->insert_id;
  }

  /**
	* update club
	*
	* @param object $club
  * @param string $oldShortcode
	* @return null
	*/
	public function update( $club, $oldShortcode ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_clubs} SET `name` = '%s', `type` = '%s', `shortcode` = '%s',`matchsecretary` = '%d', `contactno` = '%s', `website` = '%s', `founded`= '%s', `facilities` = '%s', `address` = '%s', `latitude` = '%s', `longitude` = '%s' WHERE `id` = %d", $club->name, $club->type, $club->shortcode, $club->matchsecretary, $club->contactno, $club->website, $club->founded, $club->facilities, $club->address, $club->latitude, $club->longitude, $this->id ) );

    if ( $oldShortcode != $this->shortcode ) {
      $teams = $this->getTeams();
      foreach ( $teams as $team ) {
        $team = get_team($team->id);
        $teamRef = substr($team->title,strlen($oldShortcode)+1,strlen($team->title));
        $newTitle = $club->shortcode.' '.$teamRef;
        $team->updateTitle($newTitle);
      }
    }
		if ( $club->matchsecretary != '') {
			$currentContactNo = get_user_meta( $club->matchsecretary, 'contactno', true);
			$currentContactEmail = get_userdata($club->matchsecretary)->user_email;
			if ($currentContactNo != $club->matchSecretaryContactNo ) {
				update_user_meta( $club->matchsecretary, 'contactno', $club->matchSecretaryContactNo );
			}
			if ($currentContactEmail != $club->matchSecretaryEmail ) {
				$userdata = array();
				$userdata['ID'] = $club->matchsecretary;
				$userdata['user_email'] = $club->matchSecretaryEmail;
				$userId = wp_update_user( $userdata );
				if ( is_wp_error($userId) ) {
					$errorMsg = $userId->get_error_message();
					error_log('Unable to update user email '.$club->matchsecretary.' - '.$club->matchSecretaryEmail.' - '.$errorMsg);
				}
			}
		}
	}

  /**
	* delete Club
	*
	*/
	public function delete() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_roster_requests} WHERE `affiliatedclub` = '%d'", $this->id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d'", $this->id) );
		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_clubs} WHERE `id` = '%d'", $this->id) );
	}

  /**
  * get teams from database
  *
  * @return count number of teams
  */
  public function hasTeams() {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
    $args[] = intval($this->id);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var( $sql );

  }

  /**
  * get teams from database
  *
  * @param array $args
  * @param string $output OBJECT | ARRAY
  * @return array database results
  */
  public function getTeams( $players = false, $type = false ) {
    global $wpdb;

    $args = array();
    $sql = "SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
    $args[] = intval($this->id);
    if ( !$players ) {
      $sql .= " AND `status` != 'P'";
    } else {
      $sql .= " AND `status` = 'P'";
    }
    if ( $type ) {
      $sql .= " AND `type` = '%s'";
      $args[] = $type;
    }

    $sql .= " ORDER BY `title`";
    $sql = $wpdb->prepare($sql, $args);

    $teams = wp_cache_get( md5($sql), 'teams' );
    if ( !$teams ) {
      $teams = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $teams, 'teams' );
    }

    $class = '';
    foreach ( $teams AS $i => $team ) {
      $class = ( 'alternate' == $class ) ? '' : 'alternate';
      $team = get_team($team->id);
      $teams[$i] = $team;
    }

    return $teams;
  }

  /**
  * gets roster requests from database
  *
  * @param array $query_args
  * @return array
  */
  public function getRosterRequests( $query_args ) {
    global $wpdb, $racketmanager;

    $defaults = array( 'count' => false, 'firstName' => false, 'surname' => false, 'gender' => false, 'completed' => false, 'orderby' => array('completed_date' => 'ASC', 'requested_date' => 'ASC', 'surname' => 'DESC', 'first_name' => 'DESC' ));
    $query_args = array_merge($defaults, (array)$query_args);
    extract($query_args, EXTR_SKIP);

    $search_terms = array();
    $sql = "SELECT `id`, `first_name`, `surname`, `affiliatedclub`, `requested_date`, `requested_user`, `completed_date`, `completed_user`, `gender`, `btm`, `email` FROM {$wpdb->racketmanager_roster_requests} WHERE `affiliatedclub` = ".$this->id ;

    if ( !$completed ) {
      $search_terms[] = "`completed_date` IS NULL";
    }
    if ( $firstName ) {
      $search_terms[] = $wpdb->prepare("`first_name` = '%s'", htmlspecialchars($firstName));
    }
    if ( $surname ) {
      $search_terms[] = $wpdb->prepare("`surname` = '%s'", htmlspecialchars($surname));
    }
    $search = "";
    if (count($search_terms) > 0) {
      $search = implode(" AND ", $search_terms);
    }

    if ( $count ) {
      $sql = $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_roster_requests} WHERE `affiliatedclub` = ".$this->id;
      if ( $search != "") {
        $sql .= " AND $search";
      }
      $numRosterRequests = $wpdb->get_var($sql);
      return $numRosterRequests;
    }

    $orderby_string = "";
    $i = 0;
    foreach ($orderby AS $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
        $direction = "ASC";
      }
      $orderby_string .= "`".$order."` ".$direction;
      if ($i < (count($orderby)-1)) {
        $orderby_string .= ",";
      }
      $i++;
    }
    $order = $orderby_string;
    if ( $search != "") {
      $sql .= " AND $search";
    }
    if ( $order != "") {
      $sql .= " ORDER BY $order";
    }

    $rosterRequests = wp_cache_get( md5($sql), 'rosterRequests' );
    if ( !$rosterRequests ) {
      $rosterRequests = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $rosterRequests, 'rosterRequests' );
    }

    $class = '';
    foreach ( $rosterRequests AS $i => $rosterRequest ) {
      $class = ( 'alternate' == $class ) ? '' : 'alternate';
      $rosterRequest->class = $class;

      $rosterRequest->requestedUserId = $rosterRequest->requested_user;
      $rosterRequest->requestedUser = get_userdata($rosterRequest->requested_user)->display_name;
      $rosterRequest->completedUserId = $rosterRequest->completed_user;
      if ( $rosterRequest->completed_user != '' ) {
        $rosterRequest->completedUser = get_userdata($rosterRequest->completed_user)->display_name;
      } else {
        $rosterRequest->completedUser = '';
      }

      $rosterRequests[$i] = $rosterRequest;
    }

    return $rosterRequests;
  }

  /**
  * get single roster request
  *
  * @param int $rosterRequestId
  * @return array
  */
  private function getRosterRequest( $rosterRequestId ) {
    global $wpdb;

    $rosterRequest = $wpdb->get_row("SELECT `first_name`, `surname`, `gender`, `btm`, `email`, `player_id`, `requested_date`, `requested_user`, `completed_date`, `completed_user` FROM {$wpdb->racketmanager_roster_requests} WHERE `id` = '".intval($rosterRequestId)."'");

    if ( !$rosterRequest ) return false;

    $this->rosterRequest[$rosterRequestId] = $rosterRequest;
    return $this->rosterRequest[$rosterRequestId];
  }

  /**
  * approve Roster Request
  *
  * @param int $rosterRequst_id
  * @return boolean
  */
  public function approveRosterRequest( $rosterRequestId ) {
    global $wpdb, $racketmanager;

    $rosterRequest = $this->getRosterRequest($rosterRequestId);
    if ( empty($rosterRequest->completed_date) ) {
      if ( empty($rosterRequest->player_id) ) {
        $rosterRequest->player_id = $racketmanager->addPlayer( $rosterRequest->first_name, $rosterRequest->surname, $rosterRequest->gender, $rosterRequest->btm, $rosterRequest->email);
      }
      $rosterId = $this->addRoster( $rosterRequest->player_id, false);
      $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_roster_requests} SET `completed_date` = now(), `completed_user` = %d WHERE `id` = %d ", get_current_user_id(), $rosterRequestId ) );
      $racketmanager->setMessage( __('Roster added', 'racketmanager') );
    }

    return true;
  }

  /**
  * add new roster
  *
  * @param int $affiliatedclub
  * @param int $playerid
  * @param boolean $message (optional)
  * @return int | false
  */
  public function addRoster( $player_id, $message = true ) {
    global $wpdb, $racketmanager;

    $userid = get_current_user_id();
    $sql = "INSERT INTO {$wpdb->racketmanager_roster} (`affiliatedclub`, `player_id`, `created_date`, `created_user` ) VALUES ('%d', '%d', now(), %d)";
    $wpdb->query( $wpdb->prepare ( $sql, $this->id, $player_id, $userid ) );
    $roster_id = $wpdb->insert_id;

    $racketmanager->setMessage( __('Roster added', 'racketmanager') );

    return $roster_id;
  }

  /**
  * gets roster from database
  *
  * @param array $query_args
  * @return array
  */
  public function getRoster( $args, $output = 'OBJECT' ) {
    global $wpdb;

    $defaults = array( 'count' => false, 'team' => false, 'player' => false, 'gender' => false, 'inactive' => false, 'cache' => true, 'type' => false, 'orderby' => array("display_name" => "ASC" ));
    $args = array_merge($defaults, (array)$args);
    extract($args, EXTR_SKIP);

    //$cachekey = md5(implode(array_map(function($entry) { if(is_array($entry)) { return implode($entry); } else { return $entry; } }, $args)) . $output);

    $search_terms = array();
    if ($team) {
      $search_terms[] = $wpdb->prepare("`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = '%d')", intval($team));
    }

    if ($player) {
      $search_terms[] = $wpdb->prepare("`player_id` = '%d'", intval($player));
    }

    if ($gender) {
      //            $search_terms[] = $wpdb->prepare("`gender` = '%s'", htmlspecialchars(strip_tags($gender)));
    }

    if ($type) {
      $search_terms[] = "`system_record` IS NULL";
    }

    if ($inactive) {
      $search_terms[] = "`removed_date` IS NULL";
    }

    $search = "";
    if (count($search_terms) > 0) {
      $search = implode(" AND ", $search_terms);
    }

    $orderby_string = ""; $i = 0;
    foreach ($orderby AS $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) $direction = "ASC";
      $orderby_string .= "`".$order."` ".$direction;
      if ($i < (count($orderby)-1)) $orderby_string .= ",";
      $i++;
    }
    $order = $orderby_string;

    $offset = 0;

    if ( $count ) {
      $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = ".$this->id;
      if ( $search != "") $sql .= " AND $search";
      $cachekey = md5($sql);
      if ( isset($this->num_players[$cachekey]) && $cache && $count )
      return intval($this->num_players[$cachekey]);

      $this->num_players[$cachekey] = $wpdb->get_var($sql);
      return $this->num_players[$cachekey];
    }

    $sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = ".$this->id ;
    if ( $search != "") $sql .= " AND $search";
    if ( $order != "") $sql .= " ORDER BY $order";

    $rosters = wp_cache_get( md5($sql), 'rosters' );
    if ( !$rosters ) {
      $rosters = $wpdb->get_results( $sql );
      wp_cache_set( md5($sql), $rosters, 'rosters' );
    }

    $i = 0;
    $class = '';
    foreach ( $rosters AS $roster ) {
      $class = ( 'alternate' == $class ) ? '' : 'alternate';
      $rosters[$i]->class = $class;

      $rosters[$i] = (object)(array)$roster;

      $rosters[$i]->roster_id = $roster->roster_id;
      $rosters[$i]->player_id = $roster->player_id;
      $rosters[$i]->fullname = $roster->fullname;
      $rosters[$i]->gender = get_user_meta($roster->player_id, 'gender', true );
      $rosters[$i]->type = get_user_meta($roster->player_id, 'racketmanager_type', true );
      $rosters[$i]->removed_date = $roster->removed_date;
      $rosters[$i]->removed_user = $roster->removed_user;
      if ( $roster->removed_user ) {
        $rosters[$i]->removedUserName = get_userdata($roster->removed_user)->display_name;
      } else {
        $rosters[$i]->removedUserName = '';
      }
      $rosters[$i]->btm = get_user_meta($roster->player_id, 'btm', true );;
      $rosters[$i]->created_date = $roster->created_date;
      $rosters[$i]->created_user = $roster->created_user;
      if ( $roster->created_user ) {
        $rosters[$i]->createdUserName = get_userdata($roster->created_user)->display_name;
      } else {
        $rosters[$i]->createdUserName = '';
      }
      if ( $gender && $gender != $rosters[$i]->gender ) {
        unset($rosters[$i]);
      }
      $rosters[$i]->locked = get_user_meta($roster->player_id, 'locked', true );
			$rosters[$i]->locked_date = get_user_meta($roster->player_id, 'locked_date', true );
			$rosters[$i]->locked_user = get_user_meta($roster->player_id, 'locked_user', true );
			if ( $rosters[$i]->locked_user ) {
				$rosters[$i]->lockedUserName = get_userdata($rosters[$i]->locked_user)->display_name;
			} else {
				$rosters[$i]->lockedUserName = '';
			}

      $i++;
    }

    return $rosters;
  }

  /**
  * gets player for club from database
  *
  * @param array $playerId
  * @return array
  */
  public function getPlayer( $playerId ) {
    global $wpdb;

    $sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_roster} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = ".$this->id." AND `player_id` = ".intval($playerId);

    $player = wp_cache_get( md5($sql), 'players' );
    if ( !$player ) {
      $player = $wpdb->get_row( $sql );
      wp_cache_set( md5($sql), $player, 'players' );
    }

    if ( $player ) {
      $player->gender = get_user_meta($player->player_id, 'gender', true );
      $player->type = get_user_meta($player->player_id, 'racketmanager_type', true );
      if ( $player->removed_user ) {
        $player->removedUserName = get_userdata($player->removed_user)->display_name;
      } else {
        $player->removedUserName = '';
      }
      $player->btm = get_user_meta($player->player_id, 'btm', true );;
      if ( $player->created_user ) {
        $player->createdUserName = get_userdata($player->created_user)->display_name;
      } else {
        $player->createdUserName = '';
      }
      $player->locked = get_user_meta($player->player_id, 'locked', true );
			$player->locked_date = get_user_meta($player->player_id, 'locked_date', true );
			$player->locked_user = get_user_meta($player->player_id, 'locked_user', true );
			if ( $player->locked_user ) {
				$player->lockedUserName = get_userdata($player->locked_user)->display_name;
			} else {
				$player->lockedUserName = '';
			}
    }

    return $player;
  }

  /**
  * check if player is captain
  *
  * @param int $rosterRequst_id
  * @return boolean
  */
  public function isPlayerCaptain( $player ) {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager_clubs} c WHERE c.`id` = '%d' AND c.`id` = t.`affiliatedclub` AND t.`status` != 'P' AND t.`id` = tc.`team_id` AND tc.`captain` = %d";
    $args[] = intval($this->id);
    $args[] = intval($player);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var( $sql );
  }

}

/**
* get Club object
*
* @param int|Club|null Club ID or club object. Defaults to global $club
* @return Club|null
*/
function get_club( $club = null, $queryTerm = "id" ) {
  if ( empty( $club ) && isset( $GLOBALS['club'] ) )
  $club = $GLOBALS['club'];

  if ( $club instanceof Club ) {
    $_club = $club;
  } elseif ( is_object( $club ) ) {
    $_club = new Club( $club );
  } else {
    $_club = Club::get_instance( $club, $queryTerm );
  }

  if ( ! $_club )
  return null;

  return $_club;
}
?>
