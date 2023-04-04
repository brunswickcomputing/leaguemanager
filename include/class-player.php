<?php
/**
* player API: player class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage Player
*/

/**
* Class to implement the Player object
*
*/
final class Player {

	/**
	* retrieve player instance
	*
	* @param int $player_id
	*/
	public static function get_instance($player_id) {
		global $wpdb;
		$player_id = (int) $player_id;
		if ( ! $player_id ) {
			return false;
		}

		$player = wp_cache_get( $player_id, 'players' );

		if ( ! $player ) {
			$player = get_userdata($player_id);

			if ( !$player ) return false;

			$player = new Player( $player );

			wp_cache_set( $player_id, $player, 'players' );
		}

		return $player;
	}

	/**
	* Constructor
	*
	* @param object $player Player object.
	*/
	public function __construct( $player = null ) {
		if ( !is_null($player) ) {
			foreach ( $player->data as $key => $value ) {
				$this->$key = $value;
			}
			$this->id = $this->ID;
			$this->email = $this->user_email;
			$this->firstname = get_user_meta($this->ID, 'first_name', true );
			$this->surname = get_user_meta($this->ID, 'last_name', true );
			$this->gender = get_user_meta($this->ID, 'gender', true );
			$this->type = get_user_meta($this->ID, 'racketmanager_type', true );
			$this->btm = get_user_meta($this->ID, 'btm', true );
			$this->locked = get_user_meta($this->ID, 'locked', true );
			$this->locked_date = get_user_meta($this->ID, 'locked_date', true );
			$this->locked_user = get_user_meta($this->ID, 'locked_user', true );
			if ( $this->locked_user ) {
				$this->lockedUserName = get_userdata($this->locked_user)->display_name;
			} else {
				$this->lockedUserName = '';
			}
		}
	}

  /**
	* update player
	*
	* @param string $firstname
	* @param string $surname
	* @param string $gender
	* @param string $btm
	* @param string $email
	* @param string $locked
	* @return null
	*/
	public function update( $firstname, $surname, $gender, $btm, $email, $locked ) {
		global $racketmanager;

		$update = false;
		$userData = array();
		if ( $this->firstname != $firstname ) {
			$update = true;
			$userData['first_name'] = $firstname;
			$userData['display_name'] = $firstname.' '.$surname;
			$userData['user_nicename'] = sanitize_title($userData['display_name']);
		}
		if ( $this->surname != $surname ) {
			$update = true;
			$userData['last_name'] = $surname;
			$userData['display_name'] = $firstname.' '.$surname;
			$userData['user_nicename'] = sanitize_title($userData['display_name']);
		}
		if ( $this->gender != $gender ) {
			$update = true;
			update_user_meta($this->ID, 'gender', $gender);
		}
		if ( $this->btm != $btm ) {
			$update = true;
			update_user_meta($this->ID, 'btm', $btm);
		}
		if ( $this->user_email != $email ) {
			$update = true;
			$userData['user_email'] = $email;
		}
		if ( $this->locked != $locked ) {
			$update = true;
			if ( $locked ) {
				update_user_meta($this->ID, 'locked', $locked);
				update_user_meta($this->ID, 'locked_date', date('Y-m-d'));
				update_user_meta($this->ID, 'locked_user', get_current_user_id());
			} else {
				delete_user_meta($this->ID, 'locked');
				delete_user_meta($this->ID, 'locked_date');
				delete_user_meta($this->ID, 'locked_user');
			}
		}

		if (!$update) {
			$racketmanager->setMessage( __('No updates','racketmanager') );
			return;
		}
		if ( $userData ) {
			$userData['ID'] = $this->ID;
			$userId = wp_update_user($userData);
			if ( is_wp_error($userId) ) {
				$racketmanager->setMessage($userId->get_error_message());
			} else {
				$racketmanager->setMessage( __('Player details updated','racketmanager') );
			}
		} else {
			$racketmanager->setMessage( __('Player details updated','racketmanager') );
		}
	}

}

/**
* get Player object
*
* @param int|Player|null Player ID or player object. Defaults to global $player
* @return Player|null
*/
function get_player( $player = null ) {
	if ( empty( $player ) && isset( $GLOBALS['player'] ) )
	$player = $GLOBALS['player'];

	if ( $player instanceof Player ) {
		$_player = $player;
	} elseif ( is_object( $player ) ) {
		$_player = new Player( $player );
	} else {
		$_player = Player::get_instance( $player );
	}

	if ( ! $_player )
	return null;

	return $_player;
}

/**
* get Player by name
*
* @param string $name
* @return Player|null
*/
function get_playerByName( $name ) {
    $player = get_user_by( 'slug', sanitize_title($name) );

    if ( !$player ) {
      return false;
    }

	return (object)(array)$player;

}

?>
