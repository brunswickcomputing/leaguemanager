<?php
/**
 * leaguemanager_upgrade() - update routine for older version
 * 
 * @return Success Message
 */
function leaguemanager_upgrade() {
	global $wpdb, $leaguemanager, $lmLoader;
	
	$options = get_option( 'leaguemanager' );
	$installed = $options['dbversion'];
	
	echo __('Upgrade database structure...', 'leaguemanager') . "<br />\n";
	$wpdb->show_errors();

	$lmLoader->install();

	if (version_compare($installed, '5.1.7', '<')) {

		$wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} ADD `system_record` VARCHAR(1) NULL DEFAULT NULL AFTER `removed_date` ");
	
    }
    if (version_compare($installed, '5.1.8', '<')) {
        
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_team_competition} (`id` int( 11 ) NOT NULL AUTO_INCREMENT ,`team_id` int( 11 ) NOT NULL default 0, `competition_id` int( 11 ) NOT NULL default 0, `captain` varchar( 255 ) NOT NULL default '',`contactno` varchar( 255 ) NOT NULL default '',`contactemail` varchar( 255 ) NOT NULL default '', `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, PRIMARY KEY ( `id` ), INDEX( `team_id` ), INDEX( `competition_id` ))") ;
        $wpdb->query( "INSERT INTO {$wpdb->leaguemanager_team_competition} (team_id, competition_id, captain, contactno, contactemail, match_day, match_time) (SELECT TE.id, L.`competition_id`, TE.captain, TE.contactno, TE.contactemail, TE.match_day, TE.match_time FROM `wp_leaguemanager_teams` TE, `wp_leaguemanager_table` TA, `wp_leaguemanager_leagues` L WHERE TE.id = TA.`team_id` AND TA.`league_id` = L.`id` GROUP BY team_id, competition_id, captain, contactno, contactemail, match_day, match_time)" );

    }
    if (version_compare($installed, '5.2.0', '<')) {
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} CHANGE `home_team` `home_team` VARCHAR(255) NOT NULL DEFAULT '0';" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} CHANGE `away_team` `away_team` VARCHAR(255) NOT NULL DEFAULT '0';" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} DROP `captain`, DROP `contactno`, DROP `contactemail`, DROP `match_day`, DROP `match_time`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_players} ADD `fullname` VARCHAR(255) NOT NULL AFTER `surname`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_players} SET `fullname`= concat(`firstname`,' ',`surname`);" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_competitions} ADD `competitiontype` VARCHAR(255) NOT NULL AFTER `seasons`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_competitions} SET `competitiontype` = 'league' WHERE `competitiontype` = '';" );
    }
    if (version_compare($installed, '5.3.0', '<')) {
        echo __('starting 5.3.0 upgrade', 'leaguemanager') . "<br />\n";
        $prev_player_id = 0;
        $rosters = $wpdb->get_results(" SELECT `id`, `player_id`, `affiliatedclub`, `removed_date` FROM {$wpdb->leaguemanager_roster} ORDER BY `player_id`;");
        foreach ($rosters AS $roster) {
            if ($roster->player_id != $prev_player_id) {
                $player = $wpdb->get_results( $wpdb->prepare(" SELECT `firstname`, `surname`, `gender`, `btm` FROM {$wpdb->leaguemanager_players} WHERE `id` = %d", $roster->player_id) );
                if ( !$player ) {
                    error_log($roster->player_id.' player not found');
                } else {
                    $player = $player[0];
                    $userdata = array();
                    $userdata['first_name'] = $player->firstname;
                    $userdata['last_name'] = $player->surname;
                    $userdata['display_name'] = $player->firstname.' '.$player->surname;
                    $userdata['user_login'] = strtolower($player->firstname).'.'.strtolower($player->surname);
                    $userdata['user_pass'] = $userdata['user_login'].'1';
                    $user = get_user_by( 'login', $userdata['user_login'] );
                    if ( !$user ) {
                        $user_id = wp_insert_user( $userdata );
                    } else {
                        $user_id = $user->ID;
                    }
                    update_user_meta($user_id, 'show_admin_bar_front', false );
                    update_user_meta($user_id, 'gender', $player->gender);
                    if ( isset($player->btm) && $player->btm != '' ) {
                        update_user_meta($user_id, 'btm', $player->btm);
                    }
                    if ( isset($player->removed_date) && $player->removed_date != '' ) {
                        update_user_meta($user_id, 'remove_date', $player->removed_date);
                    }
                }
            }
            $prev_player_id = $roster->player_id;
            $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_roster} SET `player_id` = %d WHERE `id` = %d", $user_id, $roster->id ) );
        }
    }
    if (version_compare($installed, '5.3.1', '<')) {
        echo __('starting 5.3.1 upgrade', 'leaguemanager') . "<br />\n";
        echo __('updating captains', 'leaguemanager') . "<br />\n";
        $prev_captain = '';
        $captains = $wpdb->get_results(" SELECT `id`, `captain`, `contactno`, `contactemail` FROM {$wpdb->leaguemanager_team_competition} WHERE `captain` != '' ORDER BY `captain`;");
        foreach ($captains AS $captain) {
            if ( !is_numeric($captain->captain) ) {
                if ( $prev_captain != $captain->captain ) {
                    $user = $wpdb->get_results( $wpdb->prepare( "SELECT `ID` FROM {$wpdb->users} WHERE `display_name` = '%s'", $captain->captain ) );
                    if ( !isset($user[0]) ) {
                        error_log($captain->captain.' not found');
                    } else {
                        $user = $user[0];
                        if ( isset($captain->contactno) && $captain->contactno != '' ) {
                            update_user_meta($user->ID, 'contactno', $captain->contactno);
                        }
                        if ( isset($captain->contactemail) && $captain->contactemail != '' ) {
                            $userid = wp_update_user( array( 'ID' => $user->ID, 'user_email' => $captain->contactemail ) );
                        }
                    }
                    $prev_captain = $captain->captain;
                }
                $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `captain` = %d WHERE `id` = %s", $user->ID, $captain->id ) );
            }
        }
    }
    if (version_compare($installed, '5.3.2', '<')) {
        echo __('starting 5.3.2 upgrade', 'leaguemanager') . "<br />\n";
        echo __('updating player captains', 'leaguemanager') . "<br />\n";
        $teams = $wpdb->get_results(" SELECT `id`, `title`, `roster` FROM {$wpdb->leaguemanager_teams} WHERE `status` = 'P' ORDER BY `title`; ");
        foreach ($teams AS $team) {
            $team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
            $team->roster = maybe_unserialize($team->roster);
            $captain = $leaguemanager->getRosterEntry($team->roster[0])->player_id;
            $contacts = $wpdb->get_results( $wpdb->prepare(" SELECT `id`, `captain`, `contactno`, `contactemail` FROM {$wpdb->leaguemanager_team_competition} WHERE `team_id` = %s;", $team->id) );
            foreach($contacts AS $contact) {
                if ( isset($contact->contactno) && $contact->contactno != '' ) {
                    update_user_meta($captain, 'contactno', $contact->contactno);
                }
                if ( isset($contact->contactemail) && $contact->contactemail != '' ) {
                    $userid = wp_update_user( array( 'ID' => $captain, 'user_email' => $contact->contactemail ) );
                }
                $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `captain` = %d WHERE `id` = %s", $captain, $contact->id ) );
            }
        }
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_team_competition} DROP `contactno`, DROP `contactemail`;" );
    }
    if (version_compare($installed, '5.3.3', '<')) {
        echo __('starting 5.3.3 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_roster} ADD `system_record` VARCHAR(1) NULL DEFAULT NULL AFTER `updated`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_roster} SET `system_record` = 'Y' WHERE `player_id` BETWEEN 1479 AND 1514;" );
   }
    if (version_compare($installed, '5.3.4', '<')) {
        echo __('starting 5.3.4 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->leaguemanager_players = $wpdb->prefix . 'leaguemanager_players';
        $wpdb->query( "DROP TABLE {$wpdb->leaguemanager_players}" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `updated_user` int(11) NULL  AFTER `custom`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `updated` datetime NULL AFTER `updated_user`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `confirmed` VARCHAR(1) NULL AFTER `updated`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `home_captain` int(11) NULL  AFTER `confirmed`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `away_captain` int(11) NULL  AFTER `home_captain`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_matches} SET `confirmed` = 'Y' WHERE `winner_id` != 0;" );
    }
 
    /*
	* Update version and dbversion
	*/
	$options['dbversion'] = LEAGUEMANAGER_DBVERSION;
	$options['version'] = LEAGUEMANAGER_VERSION;
	
	update_option('leaguemanager', $options);
	echo __('finished', 'leaguemanager') . "<br />\n";
	$wpdb->hide_errors();
	return;
}

/**
* leaguemanager_upgrade_page() - This page showsup , when the database version doesn't fit to the script LEAGUEMANAGER_DBVERSION constant.
* 
* @return Upgrade Message
*/
function leaguemanager_upgrade_page()  {	
	$filepath    = admin_url() . 'admin.php?page=' . htmlspecialchars($_GET['page']);

	if (isset($_GET['upgrade']) && $_GET['upgrade'] == 'now') {
		leaguemanager_do_upgrade($filepath);
		return;
	}
?>
	<div class="wrap">
		<h2><?php _e('Upgrade LeagueManager', 'leaguemanager') ;?></h2>
		<p><?php _e('Your database for LeagueManager is out-of-date, and must be upgraded before you can continue.', 'leaguemanager'); ?>
		<p><?php _e('The upgrade process may take a while, so please be patient.', 'leaguemanager'); ?></p>
		<h3><a class="button" href="<?php echo $filepath;?>&amp;upgrade=now"><?php _e('Start upgrade now', 'leaguemanager'); ?>...</a></h3>
	</div>
	<?php
}

/**
 * leaguemanager_do_upgrade() - Proceed the upgrade routine
 * 
 * @param mixed $filepath
 * @return void
 */
function leaguemanager_do_upgrade($filepath) {
	global $wpdb;
?>
<div class="wrap">
	<h2><?php _e('Upgrade LeagueManager', 'leaguemanager') ;?></h2>
	<p><?php leaguemanager_upgrade();?></p>
	<p><?php _e('Upgrade successful', 'leaguemanager') ;?></p>
	<h3><a class="button" href="<?php echo $filepath;?>"><?php _e('Continue', 'leaguemanager'); ?>...</a></h3>
</div>
<?php
}

?>
