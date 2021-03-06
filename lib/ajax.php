<?php
/**
 * AJAX response methods
 *
 */

/**
 * Implement AJAX responses
 *
 * @author Kolja Schleich
 * @author Paul Moffat
 * @package    LeagueManager
 * @subpackage LeagueManagerAJAX
*/
class LeagueManagerAJAX extends LeagueManager {
	/**
	 * register ajax actions
	 */
	public function __construct() {
    add_action( 'wp_ajax_leaguemanager_getCaptainName', array(&$this, 'getCaptainName') );
    add_action( 'wp_ajax_leaguemanager_getPlayerDetails', array(&$this, 'getPlayerDetails') );
    add_action( 'wp_ajax_leaguemanager_add_teamplayer_from_db', array(&$this, 'addTeamPlayerFromDB') );
		add_action( 'wp_ajax_leaguemanager_save_team_standings', array(&$this, 'saveTeamStandings') );
		add_action( 'wp_ajax_leaguemanager_save_add_points', array(&$this, 'saveAddPoints') );
		add_action( 'wp_ajax_leaguemanager_insert_home_stadium', array(&$this, 'insertHomeStadium') );
		add_action( 'wp_ajax_leaguemanager_set_match_day_popup', array(&$this, 'setMatchDayPopUp') );
		add_action( 'wp_ajax_leaguemanager_set_match_date', array(&$this, 'setMatchDate') );
    add_action( 'wp_ajax_leaguemanager_checkTeamExists', array(&$this, 'checkTeamExists') );

    // admin/admin.php
    add_action( 'wp_ajax_leaguemanager_get_season_dropdown', array(&$this, 'setSeasonDropdown') );
    add_action( 'wp_ajax_leaguemanager_get_match_dropdown', array(&$this, 'setMatchesDropdown') );

		add_action( 'wp_ajax_leaguemanager_get_match_box', array(&$this, 'getMatchBox') );
		add_action( 'wp_ajax_nopriv_leaguemanager_get_match_box', array(&$this, 'getMatchBox') );

		add_action( 'wp_ajax_leaguemanager_show_rubbers', array(&$this, 'showRubbers') );
    add_action( 'wp_ajax_nopriv_leaguemanager_show_rubbers', array(&$this, 'showRubbers') );

		add_action( 'wp_ajax_leaguemanager_view_rubbers', array(&$this, 'viewRubbers') );
		add_action( 'wp_ajax_nopriv_leaguemanager_view_rubbers', array(&$this, 'viewRubbers') );

		add_action( 'wp_ajax_leaguemanager_update_rubbers', array(&$this, 'updateRubbers') );
    add_action( 'wp_ajax_leaguemanager_confirm_results', array(&$this, 'confirmResults') );

    add_action( 'wp_ajax_leaguemanager_roster_request', array(&$this, 'rosterRequest') );
    add_action( 'wp_ajax_leaguemanager_roster_remove', array(&$this, 'rosterRemove') );

    add_action( 'wp_ajax_leaguemanager_team_update', array(&$this, 'updateTeam') );
    add_action( 'wp_ajax_leaguemanager_update_club', array(&$this, 'clubUpdate') );

    add_action( 'wp_ajax_leaguemanager_tournament_entry', array(&$this, 'tournamentEntryRequest') );
	}

	/**
	 * Ajax Response to set match index in widget
	 *
	 */
	public function getMatchBox() {
		$widget = new LeagueManagerWidget(true);

		$current = $_POST['current'];
		$element = $_POST['element'];
		$operation = $_POST['operation'];
		$league_id = intval($_POST['league_id']);
		$match_limit = ( $_POST['match_limit'] == 'false' ) ? false : intval($_POST['match_limit']);
		$widget_number = intval($_POST['widget_number']);
		$season = htmlspecialchars($_POST['season']);
		$group = ( isset($_POST['group']) ? htmlspecialchars($_POST['group']) : '' );
		$home_only = htmlspecialchars($_POST['home_only']);
		$date_format = htmlspecialchars($_POST['date_format']);

		if ( $operation == 'next' )
			$index = $current + 1;
		elseif ( $operation == 'prev' )
			$index = $current - 1;

		$widget->setMatchIndex( $index, $element );

		if ( isset($group) ) {
			$instance = array( 'league' => $league_id, 'group' => $group, 'match_limit' => $match_limit, 'season' => $season, 'home_only' => $home_only, 'date_format' => $date_format );
		} else {
			$instance = array( 'league' => $league_id, 'match_limit' => $match_limit, 'season' => $season, 'home_only' => $home_only, 'date_format' => $date_format );
		}

		if ( $element == 'next' ) {
			$parent_id = 'next_matches_'.$widget_number;
			$match_box = $widget->showNextMatchBox($widget_number, $instance, false);
		} elseif ( $element == 'prev' ) {
			$parent_id = 'prev_matches_'.$widget_number;
			$match_box = $widget->showPrevMatchBox($widget_number, $instance, false, true);
		}

		die( "jQuery('div#".$parent_id."').fadeOut('fast', function() {
			jQuery('div#".$parent_id."').html('".addslashes_gpc($match_box)."').fadeIn('fast');
		});");
	}

    /**
     * Ajax Response to get player information
     *
     */
    public function getPlayerDetails() {
        global $wpdb, $leaguemanager;
        $name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';

        $sql = "SELECT  P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` as playerId, P.`user_email` FROM $wpdb->leaguemanager_roster R, $wpdb->users P, $wpdb->leaguemanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND C.`id` = R.`affiliatedclub` AND `display_name` like '%s' ORDER BY 1,2,3";
        $sql = $wpdb->prepare($sql, $name);
        $results = $wpdb->get_results($sql);
        $players = array();
        $player = array();
        foreach( $results AS $r) {
            $player['label'] = addslashes($r->fullname).' - '.$r->club;
            $player['value'] = addslashes($r->fullname);
            $player['id'] = $r->rosterId;
            $player['clubId'] = $r->clubId;
            $player['club'] = $r->club;
            $player['playerId'] = $r->playerId;
            $player['user_email'] = $r->user_email;
            $player['contactno'] = get_user_meta($r->playerId, 'contactno', true);
            array_push($players, $player);
        }
        die(json_encode($players));
    }

    /**
     * Ajax Response to get captain information
     *
     */
    public function getCaptainName() {
        global $wpdb;

        $name = $wpdb->esc_like(stripslashes($_POST['name']['term'])).'%';
        $affiliatedClub = isset($_POST['affiliatedClub']) ? $_POST['affiliatedClub'] : '';

        $sql = "SELECT P.`display_name` AS `fullname`, C.`name` as club, R.`id` as rosterId, C.`id` as clubId, P.`id` AS `playerId`, P.`user_email` FROM $wpdb->leaguemanager_roster R, $wpdb->users P, $wpdb->leaguemanager_clubs C WHERE R.`player_id` = P.`ID` AND R.`removed_date` IS NULL AND  C.`id` = R.`affiliatedclub` AND C.`id` = '%s' AND `display_name` like '%s' ORDER BY 1,2,3";
        $sql = $wpdb->prepare($sql, $affiliatedClub, $name);
        $results = $wpdb->get_results($sql);
        $captains = array();
        $captain = array();
        foreach( $results AS $r) {
            $captain['label'] = addslashes($r->fullname).' - '.$r->club;
            $captain['value'] = addslashes($r->fullname);
            $captain['id'] = $r->playerId;
            $captain['clubId'] = $r->clubId;
            $captain['club'] = $r->club;
            $captain['user_email'] = $r->user_email;
            $captain['contactno'] = get_user_meta($r->playerId, 'contactno', true);
            array_push($captains, $captain);
        }
        die(json_encode($captains));
    }

    /**
     * Ajax Response to save team standings
     *
     */
	public function saveTeamStandings() {
		global $wpdb, $lmLoader, $leaguemanager, $league;
		$ranking = $_POST['ranking'];
		$teams = $league->getRanking($ranking);
		foreach ( $teams AS $rank => $team_id ) {
			$old = get_team( $team_id );
			$oldRank = $old->rank;

			if ( $oldRank != 0 ) {
				if ( $rank == $oldRank )
					$status = '&#8226;';
				elseif ( $rank < $oldRank )
					$status = '&#8593';
				else
					$status = '&#8595';
			} else {
				$status = '&#8226;';
			}

			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `rank` = '%d', `status` = '%s' WHERE `team_id` = '%d'", $rank, $status, $team_id ) );
		}
	}

    /**
     * AJAX response to manually set additional points
     *
     * @see admin/standings.php
     */
	public function saveAddPoints() {
		global $wpdb;

		$team_id = intval($_POST['team_id']);
		$league = get_league(intval($_POST['league_id']));
        $season = $league->getSeason();
		$add_points = $_POST['points'];

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->leaguemanager_table} SET `add_points` = '%s' WHERE `team_id` = '%d' AND `league_id` = '%d' AND `season` = '%s'", $add_points, $team_id, $league->id, $season ) );
		$league->_rankTeams($league_id);

		die("jQuery('#loading_".$team_id."').fadeOut('fast'); window.location.reload(true);");
	}

    /**
     * AJAX response to get team data from database and insert into player team edit form
     *
     * @see admin/team.php
     */
	public function addTeamPlayerFromDB() {
		global $leaguemanager;

		$team_id = (int)$_POST['team_id'];
		$team = get_team( $team_id );
            $return = "document.getElementById('team_id').value = ".$team_id.";document.getElementById('team').value = '".$team->title."';document.getElementById('affiliatedclub').value = ".$team->affiliatedclub.";document.getElementById('teamPlayer1').value = '".$team->player[1]."';document.getElementById('teamPlayerId1').value = ".$team->playerId[1].";";
            if ( isset($team->player[2]) ) {
                $return .= "document.getElementById('teamPlayer2').value = '".$team->player[2]."';document.getElementById('teamPlayerId2').value = ".$team->playerId[2].";";
            }

		$home = '';

		die($return);
	}

    /**
     * insert home team stadium if available
     *
     * @see admin/match.php
     */
	public function insertHomeStadium() {

        $team_id = (int)$_POST['team_id'];

        $team = get_team( $team_id );

        if ($team) $stadium = trim($team->stadium);
        else $stadium = "";
        die($stadium);
	}

    /**
     * change all Match Day Pop-ups to match first one set
     *
     * @see admin/match.php
     */
	public function setMatchDayPopUp() {
		global $leaguemanager;
		$match_day = (int)$_POST['match_day'];
		$i = (int)$_POST['i'];
		$max_matches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

        if ( $i == 0 && $mode == 'add') {
            $myAjax = "";
            for ( $xx = 1; $xx < $max_matches; $xx++ ) {
    		    $myAjax .= "document.getElementById('match_day_".$xx."').value = '".$match_day."'; ";
            }
    		die("".$myAjax."");
        }
    }

	/**
	 * change all Match Date fields to match first one set
	 *
     * @see admin/match.php
	 */
	public function setMatchDate() {
		global $leaguemanager;
		$match_date = htmlspecialchars($_POST['match_date']);
		$i = (int)$_POST['i'];
		$max_matches = (int)$_POST['max_matches'];
		$mode = htmlspecialchars($_POST['mode']);

        if ( $i == 0 && $mode == 'add' ) {
            $myAjax = "";
            for ( $xx = 1; $xx < $max_matches; $xx++ ) {
    		    $myAjax .= "document.getElementById('mydatepicker[".$xx."]').value = '".$match_date."'; ";
            }
    		die("".$myAjax."");
        }
    }

    /**
     * set season dropdown for post metabox for match report
     *
     * @see admin/admin.php
     */
    public function setSeasonDropdown() {
        $league = get_league(intval($_POST['league_id']));
        $html = $league->getSeasonDropdown(true);
        die($html);
    }

    /**
     * set matches dropdown for post metabox for match report
     *
     * @see admin/admin.php
     */
    public function setMatchesDropdown() {
        $league = get_league(intval($_POST['league_id']));
        $league->setSeason(htmlspecialchars($_POST['season']));
        $html = $league->getMatchDropdown();

        die($html);
    }

    /**
     * Ajax Response to get check if Team Exists
     *
     */
    public function checkTeamExists() {
        global $leaguemanager;

        $name = stripslashes($_POST['name']);
        $team = $leaguemanager->getTeamId($name);
        if ($team) {
            $found = true;
        } else {
            $found = false;
        }
        die($found);
    }

	/**
	 * build screen to view match rubbers
	 *
	 */
	public function viewRubbers() {
		global $leaguemanager, $championship;
		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
		$league = get_league($match->league_id);
		$num_sets = $league->num_sets;
    $pointsspan = 2 + intval($num_sets);
		$num_rubbers = $league->num_rubbers;
		$match_type = $league->type;
		$sponsorhtml = sponsor_level_cat_func(array("columns" => 1, "title" => 'no', "bio" => 'no', "link" => 'no'), "");
	?>
<div id="matchrubbers" class="rubber-block">
	<div id="matchheader">
		<div class="leaguetitle"><?php echo $league->title ?></div>
		<div class="matchdate"><?php echo substr($match->date,0,10) ?></div>
		<div class="matchday">
<?php if ( $league->mode == 'championship' ) {
    echo $league->championship->getFinalName($match->final_round);
} else {
    echo 'Week'.$match->match_day;
}?>
        </div>
		<div class="matchtitle"><?php echo $match->match_title ?></div>
	</div>
    <form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
        <?php wp_nonce_field( 'rubbers-match' ) ?>

        <table class="widefat" summary="" style="margin-bottom: 2em;">
            <thead>
                <tr>
					<th style="text-align: center;"><?php _e( 'Pair', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="1"><?php _e( 'Home Team', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="1"><?php _e( 'Away Team', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

    <?php $class = '';
        $rubbers = $match->getRubbers();
        $r = 0 ;

        foreach ($rubbers as $rubber) {
    ?>
                <tr class="rtr">
					<td rowspan="3" class="rtd centered">
						<?php echo (isset($rubber->rubber_number) ? $rubber->rubber_number : '') ?>
					</td>
					<td class="rtd">
						<input class="player" name="homeplayer1[<?php echo $r ?>]" id="homeplayer1_<?php echo $r ?>" />
					</td>

                    <?php for ( $i = 1; $i <= $num_sets; $i++ ) { ?>
                        <td rowspan="2" class="rtd">
                            <input class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" />
                            :
                            <input class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" />
                        </td>
                    <?php } ?>

                    <td class="rtd">
						<input class="player" name="awayplayer1[<?php echo $r ?>]" id="awayplayer1_<?php echo $r ?>" />
                    </td>
                </tr>
				<tr class="rtr">
                    <td class="rtd">
						<input class="player" name="homeplayer2[<?php echo $r ?>]" id="homeplayer2_<?php echo $r ?>" />
                    </td>
                    <td class="rtd">
						<input class="player" name="awayplayer2[<?php echo $r ?>]" id="awayplayer2_<?php echo $r ?>">
                    </td>
				</tr>
                <tr>
                    <td colspan="<?php echo $pointsspan ?>" class="rtd" style="text-align: center;">
                        <input class="points" type="text" size="2" disabled id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
                        :
                        <input class="points" type="text" size="2" disabled id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
                    </td>
                </tr>
    <?php
        $r ++;
        }
	?>
		<tr>
			<td class="rtd centered">
			</td>
			<td class="rtd">
				<input class="player" name="homesig" id="homesig" placeholder="Home Captain Signature" />
			</td>
			<td colspan="<?php echo intval($num_sets) ?>" class="rtd" style="text-align: center;">
				<input class="points" type="text" size="2" disabled id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" />
				:
				<input class="points" type="text" size="2" disabled id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" />
			</td>
			<td class="rtd">
				<input class="player" name="awaysig" id="awaysig" placeholder="Away Captain Signature" />
			</td>
		</tr>
            </tbody>
        </table>
    </form>
<?php echo $sponsorhtml ?>
</div>
<?php
	die();
	}

	/**
	 * build screen to allow input of match rubber scores
	 *
	 */
    public function showRubbers() {
		global $leaguemanager, $league;

		$matchId = $_POST['matchId'];
		$match = get_match($matchId);
        if ( $match->final_round == '' ) {
            $matchRound = '';
            $matchType = 'league';
        } else {
            $matchRound = $match->final_round;
            $matchType = 'tournament';
        }
		$league = get_league($match->league_id);
		$num_sets = $league->num_sets;
		$num_rubbers = $league->num_rubbers;
		$match_type = $league->type;
		switch ($match_type) {
		case 'MD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterMen;
					$homeRoster[$r][2] = $homeRosterMen;
					$awayRoster[$r][1] = $awayRosterMen;
					$awayRoster[$r][2] = $awayRosterMen;
				}
				break;
		case 'WD':
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterWomen;
					$homeRoster[$r][2] = $homeRosterWomen;
					$awayRoster[$r][1] = $awayRosterWomen;
					$awayRoster[$r][2] = $awayRosterWomen;
				}
				break;
		case 'XD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				for ($r = 0; $r < $num_rubbers; $r++) {
					$homeRoster[$r][1] = $homeRosterMen;
					$homeRoster[$r][2] = $homeRosterWomen;
					$awayRoster[$r][1] = $awayRosterMen;
					$awayRoster[$r][2] = $awayRosterWomen;
				}
				break;
		case 'LD':
				$homeRosterMen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'M'));
				$awayRosterMen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'M'));
				$homeRosterWomen = $leaguemanager->getRoster(array('team' => $match->home_team, 'gender' => 'F'));
				$awayRosterWomen = $leaguemanager->getRoster(array('team' => $match->away_team, 'gender' => 'F'));
				$homeRoster[0][1] = $homeRosterWomen;
				$homeRoster[0][2] = $homeRosterWomen;
				$homeRoster[1][1] = $homeRosterMen;
				$homeRoster[1][2] = $homeRosterMen;
				$homeRoster[2][1] = $homeRosterMen;
				$homeRoster[2][2] = $homeRosterWomen;
				$awayRoster[0][1] = $awayRosterWomen;
				$awayRoster[0][2] = $awayRosterWomen;
				$awayRoster[1][1] = $awayRosterMen;
				$awayRoster[1][2] = $awayRosterMen;
				$awayRoster[2][1] = $awayRosterMen;
				$awayRoster[2][2] = $awayRosterWomen;
				break;
		}
	?>
<div id="matchrubbers" class="rubber-block">
	<div id="matchheader">
		<div class="leaguetitle"><?php echo $league->title ?></div>
		<div class="matchdate"><?php echo substr($match->date,0,10) ?></div>
<?php if ( isset($match->match_day) && $match->match_day > 0 ) { ?>
		<div class="matchday">Week <?php echo $match->match_day ?></div>
<?php } ?>
		<div class="matchtitle"><?php echo $match->match_title ?></div>
	</div>
    <form id="match-rubbers" action="#" method="post" onsubmit="return checkSelect(this)">
        <?php wp_nonce_field( 'rubbers-match' ) ?>

        <input type="hidden" name="current_league_id" id="current_league_id" value="<?php echo $match->league_id ?>" />
        <input type="hidden" name="current_match_id" id="current_match_id" value="<?php echo $matchId ?>" />
        <input type="hidden" name="current_season" id="current_season" value="<?php echo $match->season ?>" />
        <input type="hidden" name="num_rubbers" value="<?php echo $num_rubbers ?>" />
        <input type="hidden" name="home_team" value="<?php echo $match->home_team ?>" />
        <input type="hidden" name="away_team" value="<?php echo $match->away_team ?>" />
        <input type="hidden" name="match_type" value="<?php echo $matchType ?>" />
        <input type="hidden" name="match_round" value="<?php echo $matchRound ?>" />

        <table class="widefat" summary="" style="margin-bottom: 2em;">
            <thead>
                <tr>
					<th style="text-align: center;"><?php _e( 'Pair', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;"><?php _e( 'Home Team', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;" colspan="<?php echo $num_sets ?>"><?php _e('Sets', 'leaguemanager' ) ?></th>
                    <th style="text-align: center;"><?php _e( 'Away Team', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody class="rtbody rubber-table" id="the-list-rubbers-<?php echo $match->id ?>" >

    <?php $class = '';
        $rubbers = $match->getRubbers();
        $r = $tabbase = 0 ;

        foreach ($rubbers as $rubber) {
    ?>
                <tr class="rtr <?php echo $class ?>">
                    <input type="hidden" name="id[<?php echo $r ?>]" value="<?php echo $rubber->id ?>" </>
					<td rowspan="3" class="rtd centered">
						<?php echo (isset($rubber->rubber_number) ? $rubber->rubber_number : '') ?>
					</td>
					<td class="rtd playerselect">
<?php $tabindex = $tabbase + 1; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer1[<?php echo $r ?>]" id="homeplayer1_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $homeRoster[$r][1] AS $roster ) {
    if ( isset($roster->removed_date) && $roster->removed_date != '' )  $disabled = 'disabled'; else $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->home_player_1)) selected($roster->roster_id, $rubber->home_player_1 ); echo $disabled; ?>>
								<?php echo $roster->fullname ?>
							</option>
<?php } ?>
						</select>
					</td>

                    <?php for ( $i = 1; $i <= $num_sets; $i++ ) {
                        if (!isset($rubber->sets[$i])) {
                            $rubber->sets[$i] = array('player1' => '', 'player2' => '');
                        } ?>
<?php $tabindex = $tabbase + 10 + $i; ?>
                        <td class="rtd centered" rowspan="2">
                            <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player1" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player1]" value="<?php echo $rubber->sets[$i]['player1'] ?>" />
                            :
<?php $tabindex = $tabbase + 11 + $i; ?>
                            <input tabindex="<?php echo $tabindex ?>" class="points" type="text" size="2" id="set_<?php echo $r ?>_<?php echo $i ?>_player2" name="custom[<?php echo $r ?>][sets][<?php echo $i ?>][player2]" value="<?php echo $rubber->sets[$i]['player2'] ?>" />
                        </td>
                    <?php } ?>

                    <td class="rtd playerselect">
<?php $tabindex = $tabbase + 3; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer1[<?php echo $r ?>]" id="awayplayer1_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $awayRoster[$r][1] AS $roster ) {
    if ( isset($roster->removed_date) && $roster->removed_date != '' )  $disabled = 'disabled'; else $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->away_player_1)) selected($roster->roster_id, $rubber->away_player_1 ); echo $disabled; ?>>
								<?php echo $roster->fullname ?>
							</option>
<?php } ?>
						</select>
                    </td>
                </tr>
                <tr>
                    <td class="rtd playerselect">
<?php $tabindex = $tabbase + 2; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="homeplayer2[<?php echo $r ?>]" id="homeplayer2_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $homeRoster[$r][2] AS $roster ) {
    if ( isset($roster->removed_date) && $roster->removed_date != '' )  $disabled = 'disabled'; else $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->home_player_2)) selected($roster->roster_id, $rubber->home_player_2 ); echo $disabled; ?>>
							<?php echo $roster->fullname ?>
							</option>
<?php } ?>
						</select>
                    </td>
                    <td class="rtd playerselect">
<?php $tabindex = $tabbase + 4; ?>
						<select tabindex="<?php echo $tabindex ?>" required size="1" name="awayplayer2[<?php echo $r ?>]" id="awayplayer2_<?php echo $r ?>">
							<option><?php _e( 'Select Player', 'leaguemanager' ) ?></option>
<?php foreach ( $awayRoster[$r][2] AS $roster ) {
    if ( isset($roster->removed_date) && $roster->removed_date != '' )  $disabled = 'disabled'; else $disabled = ''; ?>
							<option value="<?php echo $roster->roster_id ?>"<?php if(isset($rubber->away_player_2)) selected($roster->roster_id, $rubber->away_player_2 ); echo $disabled; ?>>
							<?php echo $roster->fullname ?>
                            </option>
<?php } ?>
						</select>
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="rtd" style="text-align: center;">
                        <input class="points" type="text" size="2" readonly id="home_points[<?php echo $r ?>]" name="home_points[<?php echo $r ?>]" value="<?php echo (isset($rubber->home_points) ? $rubber->home_points : '') ?>" />
                        :
                        <input class="points" type="text" size="2" readonly id="away_points[<?php echo $r ?>]" name="away_points[<?php echo $r ?>]" value="<?php echo (isset($rubber->away_points) ? $rubber->away_points : '') ?>" />
                    </td>
                </tr>
    <?php
		$tabbase +=100;
        $r ++;
        }
    ?>
<?php if ( isset($match->home_captain) || isset($match->away_captain) ) { ?>
                <tr>
                    <td class="rtd centered"></td>
                    <td class="rtd captain"><?php _e( 'Home Captain', 'leaguemanager' ) ?></td>
                    <td colspan="<?php echo intval($num_sets) ?>" class="rtd">
                    <td class="rtd captain"><?php _e( 'Away Captain', 'leaguemanager' ) ?></td>
                </tr>
                <tr>
					<td class="rtd centered">
					</td>
                    <td class="rtd" id="homeCaptain">
<?php if ( isset($match->home_captain) ) {
    echo $leaguemanager->getPlayerName($match->home_captain);
} else { ?>
    <?php if ( !current_user_can( 'manage_leaguemanager' ) && $match->confirmed == 'P' ) { ?>
<div class="radio-list">
                        <label class="left"><input type="radio" name="resultConfirm" value="confirm" required />Confirm</label>
                        <label class="right"><input type="radio" name="resultConfirm" value="challenge" required />Challenge</label>
</div>
    <?php } ?>
<?php } ?>
                    </td>
                    <td colspan="<?php echo intval($num_sets) ?>" class="rtd">
                    </td>
                    <td class="rtd" id="awayCaptain">
<?php if ( isset($match->away_captain) ) {
    echo $leaguemanager->getPlayerName($match->away_captain);
} else { ?>
    <?php if ( !current_user_can( 'manage_leaguemanager' ) && $match->confirmed == 'P' ) { ?>
<div class="radio-list">
                        <label class="left"><input type="radio" name="resultConfirm" value="confirm" required />Confirm</label>
                        <label class="right"><input type="radio" name="resultConfirm" value="challenge" required />Challenge</label>
</div>
    <?php } ?>
<?php } ?>
                    </td>
                </tr>
<?php } ?>
            </tbody>
        </table>
<p>
<?php if ( isset($match->updated_user) ) echo 'Updated By:'.$leaguemanager->getPlayerName($match->updated_user) ?>
<?php if ( isset($match->updated) ) echo ' On:'.$match->updated ?>
</p>
<?php if ( current_user_can( 'update_results' ) || $match->confirmed == 'P' || $match->confirmed == NULL ) { ?>

        <input type="hidden" name="updateRubber" id="updateRubber" value="results" />
        <button tabindex="500" class="button button-primary" type="button" id="updateRubberResults" onclick="Leaguemanager.updateRubbers(this)">Update Results</button>
    <?php } ?>
        <p id="UpdateResponse"></p>
<?php if ( $match->confirmed == 'P' ) { ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                       Leaguemanager.disableRubberUpdate();
                       });
        </script>
<?php } ?>
    </form>
</div>
<?php
    die();
    }

    /**
     * update match rubber scores
     *
     */
    public function updateRubbers() {
        global $wpdb, $leaguemanager, $league, $match;

        if ( isset($_POST['updateRubber'])) {
            check_admin_referer('rubbers-match');
            $homepoints = array();
            $awaypoints = array();
            $return = array();
            $updates = false;
            $matchId = $_POST['current_match_id'];
            $match = get_match($matchId);
            $homepoints = isset($_POST['home_points']) ? $_POST['home_points'] : array();
            $awaypoints = isset( $_POST['away_points']) ? $_POST['away_points'] : array();
            $num_rubbers = $_POST['num_rubbers'];
            $home_team = $_POST['home_team'];
            $away_team = $_POST['away_team'];
            $options = $leaguemanager->getOptions();
            if ( $_POST['updateRubber'] == 'results' ) {

                for ($ix = 0; $ix < $num_rubbers; $ix++) {
                    $rubberId       = $_POST['id'][$ix];
                    $homeplayer1    = isset($_POST['homeplayer1'][$ix]) ? $_POST['homeplayer1'][$ix] : NULL;
                    $homeplayer2    = isset($_POST['homeplayer2'][$ix]) ? $_POST['homeplayer2'][$ix] : NULL;
                    $awayplayer1    = isset($_POST['awayplayer1'][$ix]) ? $_POST['awayplayer1'][$ix] : NULL;
                    $awayplayer2    = isset($_POST['awayplayer2'][$ix]) ? $_POST['awayplayer2'][$ix] : NULL;
                    $custom         = isset($_POST['custom'][$ix]) ? $_POST['custom'][$ix] : "";
                    $winner         = $loser = '';
                    $homescore      = $awayscore = 0;
                    $sets           = $custom['sets'];

                    foreach ( $sets as $set ) {
                        if ( $set['player1'] !== NULL && $set['player2'] !== NULL ) {
                            if ( $set['player1'] > $set['player2']) {
                                $homescore += 1;
                            } elseif ( $set['player1'] < $set['player2']) {
                                $awayscore += 1;
                            } elseif ( $set['player1'] == 'S' ){
                                $homescore += 0.5;
                                $awayscore += 0.5;
                            }
                        }
                    }

                    if ( $homescore > $awayscore) {
                        $winner = $home_team;
                        $loser = $away_team;
                    } elseif ( $homescore < $awayscore) {
                        $winner = $away_team;
                        $loser = $home_team;
					} elseif ( 'NULL' === $homescore && 'NULL' === $awayscore ) {
						$winner = 0;
						$loser = 0;
					} elseif ( '' == $homescore && '' == $awayscore ) {
						$winner = 0;
						$loser = 0;
					} else {
						$winner = -1;
						$loser = -1;
                    }

                    if (isset($homeplayer1) && isset($homeplayer2) && isset($awayplayer1) && isset($awayplayer2) && ( !empty($homescore) || !empty($awayscore) ) ) {
                        $homescore = !empty($homescore) ? $homescore : 0;
                        $awayscore = !empty($awayscore) ? $awayscore : 0;
                        $homepoints[$ix] = $homescore;
                        $awaypoints[$ix] = $awayscore;

                        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_rubbers} SET `home_points` = '%s',`away_points` = '%s',`home_player_1` = '%s',`home_player_2` = '%s',`away_player_1` = '%s',`away_player_2` = '%s',`winner_id` = '%d',`loser_id` = '%d',`custom` = '%s' WHERE `id` = '%d'", $homescore, $awayscore, $homeplayer1, $homeplayer2, $awayplayer1, $awayplayer2, $winner, $loser, maybe_serialize($custom), $rubberId));
                        $matchConfirmed = 'P';
                        $matchMessage = 'Result Saved';
                        $updates = true;
                        $this->checkPlayerResult($match, $rubberId, $homeplayer1, $home_team, $options);
                        $this->checkPlayerResult($match, $rubberId, $homeplayer2, $home_team, $options);
                        $this->checkPlayerResult($match, $rubberId, $awayplayer1, $away_team, $options);
                        $this->checkPlayerResult($match, $rubberId, $awayplayer2, $away_team, $options);
                    }
                }
            } elseif ( $_POST['updateRubber'] == 'confirm' ) {
                if ( isset($_POST['resultConfirm'])) {
                    switch ( $_POST['resultConfirm'] ) {
                        case "confirm":
                            $matchConfirmed = 'A';
                            $matchMessage = 'Result Approved';
                            $updates = true;
                            break;
                        case "challenge":
                            $matchConfirmed = 'C';
                            $matchMessage = 'Result Challenged';
                            $updates = true;
                            break;
                        default:
                            $matchConfirmed = '';
                    }
                } else {
                    $matchConfirmed = '';
                }
            }

            if ( $updates ) {
                $userid = get_current_user_id();
                $homeRoster = $leaguemanager->getRoster(array("count" => true, "team" => $home_team, "player" => $userid, "inactive" => true));
                if ( $homeRoster > 0 ) {
                    $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `home_captain` = %d WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $matchId));
                } else {
                    $awayRoster = $leaguemanager->getRoster(array("count" => true, "team" => $away_team, "player" => $userid, "inactive" => true));
                    if ( $awayRoster > 0 ) {
                        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s', `away_captain` = %d WHERE `id` = '%d'", $userid, $matchConfirmed, $userid, $matchId));
                    } else {
                        $matchConfirmed = 'A';
                        $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->leaguemanager_matches} SET `updated_user` = %d, `updated` = now(), `confirmed` = '%s' WHERE `id` = '%d'", get_current_user_id(), $matchConfirmed, $matchId));
                    }
                }
                $msg = sprintf(__('%s','leaguemanager'), $matchMessage);
                $options = $leaguemanager->getOptions();
                if ( $matchConfirmed == 'A' ) {
                    if ( $options['resultConfirmation'] == 'auto' || current_user_can( 'update_results' ) ) {
                        $leagueId = $_POST['current_league_id'];
                        $matchId = $_POST['current_match_id'];
                        $matches[$matchId] = $matchId;
                        $home_points[$matchId] = array_sum($homepoints);
                        $away_points[$matchId] = array_sum($awaypoints);
                        $home_team[$matchId] = $home_team;
                        $away_team[$matchId] = $away_team;
                        $custom[$matchId] = array();
                        $season = $_POST['current_season'];
                        $league = get_league($leagueId);
                        if ( $league->is_championship ) {
                            $round = $league->championship->getFinals($_POST['match_round'])['round'];
                            $league->championship->updateFinalResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $round, $season  );
                            $msg = __('Match saved','leaguemanager');
                        } else {
                            $matchCount = $league->_updateResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $season );
                            if ( $matchCount > 0 ) {
                                $msg = sprintf(__('Saved Results of %d matches','leaguemanager'), $matchCount);
                            } else {
                                $msg = __('No matches to save','leaguemanager');
                            }
                        }
                    } else {
                        if ( isset($options['resultConfirmationEmail']) && !is_null($options['resultConfirmationEmail']) ) {
                            $to = $options['resultConfirmationEmail'];
                            $subject = get_option('blogname')." Result Approval";
                            $message = "There is a new match result that needs approval.  Click <a href='".admin_url()."?page=leaguemanager&view=results'>here</a> to see the match result. ";
                            wp_mail($to, $subject, $message);
                        }
                    }
                } elseif ( $matchConfirmed == 'C' ) {
                    if ( isset($options['resultConfirmationEmail']) && !is_null($options['resultConfirmationEmail']) ) {
                        $to = $options['resultConfirmationEmail'];
                        $subject = get_option('blogname')." Result Challenge";
                        $message = "There is a new match result that has been challenged.  Click <a href='".admin_url()."?page=leaguemanager&view=results'>here</a> to see the match result. ";
                        wp_mail($to, $subject, $message);
                    }
                }
            } else {
                $msg = __('No results to save','leaguemanager');
            }
            array_push($return,$msg,$homepoints,$awaypoints);

            die(json_encode($return));
		} else {
			die(0);
		}
    }

    /**
     * confirm results
     *
     * @see admin/results.php
     */
    public function confirmResults() {
        global $league;

        $updateCount = 0;
        $return ='';
        $custom = array();
        check_admin_referer('results-update');

        foreach ( $_POST['league'] as $league_id ) {
            $league = get_league($league_id);
            $matchCount = $league->_updateResults( $_POST['matches'][$league_id], $_POST['home_points'][$league_id], $_POST['away_points'][$league_id], $_POST['home_team'][$league_id], $_POST['away_team'][$league_id], $custom, $_POST['season'][$league_id] );
            $updateCount += $matchCount;

        }
        if ( $updateCount == 0 ) {
            $return = __('No results to update','leaguemanager');
        } else {
            $return = sprintf(__('Updated Results of %d matches','leaguemanager'), $updateCount);
        }

        die(json_encode($return));

    }

    /**
     * update match results and automatically calculate score
     *
     * @param match $match
     * @return none
     */
    public function checkPlayerResult( $match, $rubber, $rosterId, $team, $options ) {
        global $wpdb, $leaguemanager;

        $player = $leaguemanager->getRosterEntry($rosterId, $team);
		if ( !empty($player->system_record) ) return;

        $teamName = get_team($team)->title;
        $currTeamNum = substr($teamName,-1);

        $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->leaguemanager_results_checker} WHERE `player_id` = %d AND `match_id` = %d", $player->player_id, $match->id) );

        if ( isset($options['rosterLeadTime']) ) {
            if ( isset($player->created_date) ) {
                $matchDate = new DateTime($match->date);
                $rosterDate = new DateTime($player->created_date);
                $interval = $rosterDate->diff($matchDate);
                if ( $interval->days < intval($options['rosterLeadTime']) ) {
                    $error = sprintf(__('player registered with club only %d days before match','leaguemanager'), $interval->days);
                    $leaguemanager->addResultCheck($match, $team, $player->player_id, $error );
                } elseif ( $interval->invert ) {
                    $error = sprintf(__('player registered with club %d days after match','leaguemanager'), $interval->days);
                    $leaguemanager->addResultCheck($match, $team, $player->player_id, $error );
                }
            }
        }

        if ( isset($match->match_day) ) {
            $sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->leaguemanager_matches} m, {$wpdb->leaguemanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` = %d AND  m.`league_id` != %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->leaguemanager} l, {$wpdb->leaguemanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->leaguemanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

            $count = $wpdb->get_var($sql);
            if ( $count > 0 ) {
                $error = sprintf(__('player has already played on match day %d','leaguemanager'), $match->match_day);
                $leaguemanager->addResultCheck($match, $team, $player->player_id, $error );
            }

            if ( isset($options['playedRounds']) ) {
				$league = get_league($match->league_id);
				$numMatchDays = $league->seasons[$match->season]['num_match_days'];
				if ( $match->match_day >= ($numMatchDays - $options['playedRounds']) ) {
					$sql = $wpdb->prepare("SELECT count(*) FROM {$wpdb->leaguemanager_matches} m, {$wpdb->leaguemanager_rubbers} r WHERE m.`id` = r.`match_id` AND m.`season` = '%s' AND m.`match_day` < %d AND m.`league_id` in (SELECT l.`id` from {$wpdb->leaguemanager} l, {$wpdb->leaguemanager_competitions} c WHERE l.`competition_id` = (SELECT `competition_id` FROM {$wpdb->leaguemanager} WHERE `id` = %d)) AND (`home_player_1` = %d or `home_player_2` = %d or `away_player_1` = %d or `away_player_2` = %d)", $match->season, $match->match_day, $match->league_id, $rosterId, $rosterId, $rosterId, $rosterId);

	                $count = $wpdb->get_var($sql);
	                if ( $count == 0 ) {
	                    $error = sprintf(__('player has not played before the final %d match days','leaguemanager'), $options['playedRounds']);
	                    $leaguemanager->addResultCheck($match, $team, $player->player_id, $error );
	                }
				}

            }
            if ( isset($options['playerLocked']) ) {
                $competition = get_competition($match->league->competition_id);
                $playerStats = $competition->getPlayerStats(array('season' => $match->season, 'roster' => $rosterId));
                $prevTeamNum = $playdowncount = $prevMatchDay = 0;
                $teamplay = array();
                foreach ( $playerStats AS $playerStat ) {
                    foreach ( $playerStat->matchdays AS $m => $matchDay) {
                        if ( $prevMatchDay != $matchDay->match_day ) {
                            $i = 0;
                        }
                        $teamNum = substr($matchDay->team_title,-1) ;
                        if (isset($teamplay[$teamNum])) $teamplay[$teamNum] ++;
                        else $teamplay[$teamNum] = 1;
                    }
                    foreach ( $teamplay AS $teamNum => $played) {
                        if ($teamNum < $currTeamNum) {
                            if ($played > 2) {
                                $error = sprintf(__('player is locked to team %d','leaguemanager'), $teamNum);
                                $leaguemanager->addResultCheck($match, $team, $player->player_id, $error );
                            }
                        }
                    }
                }
            }
        }

        return;
    }

    /**
     * save roster requests
     *
     * @see templates/club.php
     */
    public function rosterRequest() {
        global $wpdb, $leaguemanager;

        $return = array();
        $msg = '';
        $error = false;
        $errorField = array();
        $errorId = 0;
        $rosterFound = false;
        $custom = array();
        check_admin_referer('roster-request');
        $affiliatedClub = $_POST['affiliatedClub'];
        if ( $_POST['firstName'] == '' ) {
            $error = true;
            $errorField[$errorId] = "First name required";
            $errorId ++;
        } else {
            $firstName = $_POST['firstName'];
        }
        if ( $_POST['surname'] == '' ) {
            $error = true;
            $errorField[$errorId] = "Surname required";
            $errorId ++;
        } else {
            $surname = $_POST['surname'];
        }
        if ( !isset($_POST['gender']) || $_POST['gender'] == '' ) {
            $error = true;
            $errorField[$errorId] = "Gender required";
            $errorId ++;
        } else {
            $gender = $_POST['gender'];
        }
        if ( !isset($_POST['btm']) || $_POST['btm'] == '' ) {
            $btmSupplied = false;
            $btm = '';
        } else {
            $btmSupplied = true;
            $btm = $_POST['btm'];
        }

        if ( !$error ) {
            $fullName = $firstName . ' ' . $surname;
            $player = $leaguemanager->getPlayer(array('fullname' => $fullName));
            if ( !$player ) {
                $playerId = $leaguemanager->addPlayer( $firstName, $surname, $gender, $btm);
                $rosterFound = false;
            } else {
                $playerId = $player->ID;
                $rosterCount = $leaguemanager->getRoster(array('club' => $affiliatedClub, 'player' => $playerId, 'inactive' => true, 'count' => true));
                if ( $rosterCount == 0 ) {
                    $rosterFound = false;
                } else {
                    $rosterFound = true;
                }
            }
            if ( $rosterFound == false ) {
                $userid = get_current_user_id();
                if ( $btmSupplied  ) {
                    $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->leaguemanager_roster_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `btm`, `player_id`, `requested_date`, `requested_user`) values (%d, '%s', '%s', '%s', %d, %d, now(), %d) ", $affiliatedClub, $firstName, $surname, $gender, $btm, $playerId, $userid ) );
                } else {
                    $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->leaguemanager_roster_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `player_id`, `requested_date`, `requested_user`) values (%d, '%s', '%s', '%s', %d, now(), %d)", $affiliatedClub, $firstName, $surname, $gender, $playerId, $userid ) );
                }
                $rosterRequestId = $wpdb->insert_id;
                $options = $leaguemanager->getOptions();
                if ( $options['rosterConfirmation'] == 'auto' ) {
                    $leaguemanager->approveRosterRequest( $rosterRequestId );
                    $msg = __('Player added to club','leaguemanager');
                } else {
                    $msg = __('Player request submitted','leaguemanager');
                    if ( isset($options['rosterConfirmationEmail']) && !is_null($options['rosterConfirmationEmail']) ) {
                        $to = $options['rosterConfirmationEmail'];
                        $subject = get_option('blogname')." Player Request";
                        $message = "There is a new player request from ".get_club($affiliatedClub)->name." that needs approval.  Click <a href='".admin_url()."?page=leaguemanager&view=rosterRequest'>here</a> to see the request. ";
                        wp_mail($to, $subject, $message);
                    }
                }

            } else {
                $msg = __('Player already registered with club','leaguemanager');
            }
        } else {
            $msg = __('No player to add','leaguemanager');
        }

        array_push($return, $msg, $error, $errorField);
        die(json_encode($return));

    }

    /**
     * remove roster entry
     *
     * @see admin/settings.php
     */
    public function rosterRemove() {
        global $leaguemanager;

        $return = array();
        check_admin_referer('roster-remove');

        $userid = get_current_user_id();
        foreach ( $_POST['roster'] AS $roster_id ) {
            $leaguemanager->delRoster( intval($roster_id) );
        }
        die(json_encode($return));
    }

    /**
     * update Team
     *
     * @see templates/club.php
     */
    public function updateTeam() {
        global $wpdb, $leaguemanager, $competition;

        $updates = false;
        $return = array();
        $msg = '';
        check_admin_referer('team-update');
        $competitionId = $_POST['competition_id'];
        $teamId = $_POST['team_id'];

        $captain = $_POST['captain-'.$competitionId.'-'.$teamId];
        $captainId = $_POST['captainId-'.$competitionId.'-'.$teamId];
        $contactno = $_POST['contactno-'.$competitionId.'-'.$teamId];
        $contactemail = $_POST['contactemail-'.$competitionId.'-'.$teamId];
        $matchday = $_POST['matchday-'.$competitionId.'-'.$teamId];
        $matchtime = $_POST['matchtime-'.$competitionId.'-'.$teamId];

        $competition = get_competition($competitionId);
        $team = $competition->getTeamInfo($teamId);

        if ( $team->captainId != $captainId || $team->match_day != $matchday || $team->match_time != $matchtime ) {
            $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->leaguemanager_team_competition} SET `captain` = '%s', `match_day` = '%s', `match_time` = '%s' WHERE `team_id` = %d AND `competition_id` = %d", $captainId, $matchday, $matchtime, $teamId, $competitionId ) );
            $updates = true;
        }
        if ( $team->contactno != $contactno || $team->contactemail != $contactemail ) {
            $update = $leaguemanager->updatePlayerDetails($captainId, $contactno, $contactemail);
            if ($update) {
                $updates = true;
            } else {
                $updates = false;
                $msg = "error updating team";
            }
        }

        if ( $updates ) {
            $msg = "Team updated";
        } elseif ( empty($msg) ) {
            $msg = "nothing to update";
        }

        array_push($return, $msg);
        die(json_encode($return));

    }

    /**
     * update Club
     *
     * @see templates/club.php
     */
    public function clubUpdate() {
        global $wpdb, $leaguemanager;

        $updates = false;
        $return = array();
        $msg = '';
        check_admin_referer('club-update');
        $clubId = $_POST['clubId'];

        $contactno = $_POST['clubContactNo'];
        $facilities = $_POST['facilities'];
        $founded = $_POST['founded'];
        $matchSecretaryName = $_POST['matchSecretaryName'];
        $matchSecretaryId = $_POST['matchSecretaryId'];
        $matchSecretaryContactNo = $_POST['matchSecretaryContactNo'];
        $matchSecretaryEmail = $_POST['matchSecretaryEmail'];
        $website = $_POST['website'];

        $club = get_club($clubId);

        if ( $club->contactno != $contactno || $club->facilities != $facilities || $club->founded != $founded || $club->matchsecretary != $matchSecretaryId || $club->website != $website ) {
            $update = $leaguemanager->updateClub( $clubId, $club->name, $club->type, $club->shortcode, $matchSecretaryId, $matchSecretaryContactNo, $matchSecretaryEmail, $contactno, $website, $founded, $facilities, $club->address, $club->latitude, $club->longitude );
            $updates = true;
        }
        if ( $club->matchSecretaryContactNo != $matchSecretaryContactNo || $club->matchSecretaryEmail != $matchSecretaryEmail ) {
            $update = $leaguemanager->updatePlayerDetails($matchSecretaryId, $matchSecretaryContactNo, $matchSecretaryEmail);
            if ($update) {
                $updates = true;
            } else {
                $updates = false;
                $msg = "error updating match secretary";
            }
        }

        if ( $updates ) {
            $msg = "Club updated";
        } elseif ( empty($msg) ) {
            $msg = "nothing to update";
        }

        array_push($return, $msg);
        die(json_encode($return));

    }

    /**
     * tournament entry request
     *
     * @see templates/tournamententry.php
     */
    public function tournamentEntryRequest() {
        global $wpdb, $leaguemanager;

        $return = array();
        $msg = '';
        $error = false;
        $errorField = array();
        $errorMsg = array();
        $errorId = 0;

        check_admin_referer('tournament-entry');

        $season = $_POST['season'];
        $tournamentSeason = $_POST['tournamentSeason'];
        $tournamentSecretaryEmail = $_POST['tournamentSecretaryEmail'];
        $playerId = $_POST['playerId'];
        $contactNo = isset($_POST['contactno']) ? $_POST['contactno'] : '';
        $contactEmail = isset($_POST['contactemail']) ? $_POST['contactemail'] : '';
        if ( $contactEmail == '' ) {
            $error = true;
            $errorField[$errorId] = 'contactEmail';
            $errorMsg[$errorId] = __('Email address required', 'leaguemanager');
            $errorId ++;
        }
        $affiliatedclub = isset($_POST['affiliatedclub']) ? $_POST['affiliatedclub'] : 0;
        if ($affiliatedclub == 0) {
            $error = true;
            $errorField[$errorId] = 'affiliatedclub';
            $errorMsg[$errorId] = __('Select the club you are a member of', 'leaguemanager');
            $errorId ++;
        } else {
            $playerName = $leaguemanager->getPlayerName($playerId);
            $playerRoster = $leaguemanager->getRoster(array('club' => $affiliatedclub, 'player' => $playerId));
            $playerRosterId = $playerRoster[0]->roster_id;
        }
        $competitions = isset($_POST['competition']) ? $_POST['competition'] : array();
        if ( empty($competitions) ) {
            $error = true;
            $errorField[$errorId] = 'competition';
            $errorMsg[$errorId] = __('You must select a competition to enter', 'leaguemanager');
            $errorId ++;
        } else {
            $partners = isset($_POST['partner']) ? $_POST['partner'] : array();
            foreach ($competitions AS $competition) {
                $competition = get_competition($competition);
                if ( substr($competition->type,1,1) == 'D' ) {
                    $partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;

                    if ( empty($partnerId) ) {
                        $error = true;
                        $errorField[$errorId] = 'partner['.$competition->id.']';
                        $errorMsg[$errorId] = sprintf(__('Partner not selected for %s', '$leaguemanager'), $competition->name);
                        $errorId ++;
                    }
                }
            }
        }
        $acceptance = isset($_POST['acceptance']) ? $_POST['acceptance'] : '';
        if ( empty($acceptance) ) {
            $error = true;
            $errorField[$errorId] = 'acceptance';
            $errorMsg[$errorId] = __('You must agree to the rules', 'leaguemanager');
            $errorId ++;
        }

        if ( !$error ) {
            $emailTo = $tournamentSecretaryEmail;
            $emailSubject = get_option('blogname')." ".ucfirst($tournamentSeason)." ".$season." Tournament Entry";
            $emailMessage = "<p>There is a new tournament entry.</p><ul><li>".$playerName."</li><li>".$contactNo."</li><li>".$contactEmail."</li></ul><p>The following events have been entered:</p><ul>";
            foreach ($competitions AS $competition) {
                $partner = '';
                $partnerName = '';
                $newTeam = false;
                $competition = get_competition($competition);
                $emailMessage .= "<li>".$competition->name;
                if (isset($competition->primary_league)) {
                    $league = $competition->primary_league;
                } else {
                    $leagues = $competition->getLeagues(array( 'competition' => $competition->id ));
                    $league = get_league(array_key_first($competition->league_index))->id;
                }
                $team = $playerName;
                if ( substr($competition->type,1,1) == 'D' ) {
                    $partnerId = isset($partners[$competition->id]) ? $partners[$competition->id] : 0;
                    $partner = $leaguemanager->getRosterEntry($partnerId);
                    $partnerName = $partner->fullname;
                    $team .= ' / '.$partnerName;
                    $emailMessage .= " with partner ".$partnerName;
                }
                $teamId = $leaguemanager->getTeamId($team);
                if (!$teamId) {
                    if ( $partnerName != '' ) {
                        $team2 = $partnerName.' / '.$playerName;
                        $teamId = $leaguemanager->getTeamId($team2);
                        if (!$teamId) {
                            $newTeam = true;
                        }
                    } else {
                        $newTeam = true;
                    }
                }
                if ($newTeam) {
                    $teamId = $leaguemanager->addPlayerTeam( $playerName, $playerRosterId, $partnerName, $partnerId, $contactNo, $contactEmail, $affiliatedclub, $league );
                }
                $leaguemanager->addTeamtoTable($league, $teamId, $season);
                $emailMessage .= "</li>";
            }
            $emailMessage .= "</ul><p>The teams have been added to the relevant competitions.";
            wp_mail($emailTo, $emailSubject, $emailMessage);
            $msg = __('Tournament entry complete', 'leaguemanager');
        } else {
            $msg = __('Errors in tournament entry form', 'leaguemanager');
        }

        array_push($return, $msg, $error, $errorMsg, $errorField);
        die(json_encode($return));

    }

}
?>
