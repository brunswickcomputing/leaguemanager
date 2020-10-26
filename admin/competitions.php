<form id="competitions-filter" method="post" action="">
	<?php wp_nonce_field( 'competitions-bulk' ) ?>

	<div class="tablenav" style="margin-bottom: 0.1em;">
		<!-- Bulk Actions -->
		<select name="action" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="docompdel" id="docompdel" class="button-secondary action" />
	</div>

	<table class="widefat" summary="" title="LeagueManager Competitions">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('competitions-filter'));" /></th>
			<th scope="col" class="num">ID</th>
			<th scope="col"><?php _e( 'Competition', 'leaguemanager' ) ?></th>
			<th scope="col" class="num"><?php _e( 'Number of Seasons', 'leaguemanager' ) ?></th>
			<th scope="col" class="num"><?php _e( 'Number of Sets', 'leaguemanager' ) ?></th>
			<th scope="col" class="num"><?php _e( 'Number of Rubbers', 'leaguemanager' ) ?></th>
			<th scope="col" class="centered"><?php _e( 'Type', 'leaguemanager' ) ?></th>
			<th scope="col" class="num"><?php _e( 'Leagues', 'leaguemanager' ) ?></th>
		</tr>
		<tbody id="the-list">
<?php if ( $competitions = $leaguemanager->getCompetitions() ) { $class = ''; ?>
	<?php foreach ( $competitions AS $competition ) { ?>
			<?php $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column"><input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" /></th>
				<td class="num"><?php echo $competition->id ?></td>
				<td><a href="index.php?page=leaguemanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a></td>
				<td class="num"><?php echo $leaguemanager->getNumSeasons($competition->seasons) ?></td>
				<td class="num"><?php echo $competition->num_rubbers ?></td>
				<td class="num"><?php echo $competition->num_sets ?></td>
				<td>
<?php switch ($competition->type) {
case 'WD': _e( 'Ladies Doubles', 'leaguemanager' ); break;
case 'MD': _e( 'Mens Doubles', 'leaguemanager' ); break;
case 'XD': _e( 'Mixed Doubles', 'leaguemanager' ); break;
case 'LD': _e( 'The League', 'leaguemanager' ); break;
	} ?>
				</td>
				<td class="num"><?php echo $leaguemanager->getNumLeagues( $competition->id ) ?></td>
			</tr>
	<?php } ?>
<?php } ?>
		</tbody>
	</table>
</form>

<h2><?php _e( 'Add Competition', 'leaguemanager' ) ?></h2>
<!-- Add New Competition -->
<form action="" method="post">
	<?php wp_nonce_field( 'leaguemanager_add-competition' ) ?>
	<table class="lm-form-table">
		<tr valign="top">
			<th scope="row"><label for="competition_name"><?php _e( 'Competition', 'leaguemanager' ) ?></label></th>
			<td><input required="required" placeholder="<?php _e( 'Enter name for new competition', 'leaguemanager') ?>" type="text" name="competition_name" id="competition_name" value="" size="30" style="margin-bottom: 1em;" /></td>
		</tr>
		<tr valign='top'>
			<th scope='row'><label for='num_sets'><?php _e('Number of Sets', 'leaguemanager') ?></label></th>
			<td><input required="required" placeholder="<?php _e( 'How many sets', 'leaguemanager') ?>" type='number' name='num_sets' id='num_sets' value='' size='3' /></td>
		</tr>
		<tr valign='top'>
			<th scope='row'><label for='num_rubbers'><?php _e('Number of Rubbers', 'leaguemanager') ?></label></th>
			<td><input required="required" placeholder="<?php _e( 'How many rubbers', 'leaguemanager') ?>" type='number' name='num_rubbers' id='num_rubbers' value='' size='3' /></td>
		</tr>
		<tr valign='top'>
			<th scope='row'><label for='competition_type'><?php _e('Competition Type', 'leaguemanager') ?></label></th>
			<td>
				<select size='1' required="required" name='competition_type' id='competition_type'>
					<option><?php _e( 'Select', 'leaguemanager') ?></option>
					<option value='WD' <?php if ( isset($competition->type)) ($competition->type == 'WD' ? 'selected' : '') ?>><?php _e( 'Ladies Doubles', 'leaguemanager') ?></option>
					<option value='MD' <?php if ( isset($competition->type)) ($competition->type == 'MD' ? 'selected' : '') ?>><?php _e( 'Mens Doubles', 'leaguemanager') ?></option>
					<option value='XD' <?php if ( isset($competition->type)) ($competition->type == 'XD' ? 'selected' : '') ?>><?php _e( 'Mixed Doubles', 'leaguemanager') ?></option>
					<option value='LD' <?php if ( isset($competition->type)) ($competition->type == 'LD' ? 'selected' : '') ?>><?php _e( 'The League', 'leaguemanager') ?></option>
				</select>
			</td>
		</tr>

	</table>
	<input type="hidden" name="addCompetiton" value="competition" />
	<p class="submit"><input type="submit" name="addCompetition" value="<?php _e( 'Add Competition','leaguemanager' ) ?>" class="button button-primary" /></p>

</form>

