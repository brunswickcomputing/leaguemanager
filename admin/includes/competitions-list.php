<?php
?>
<div class="wrap league-block">
    <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <?php echo $season->name ?> &raquo; <?php echo 'Add Competitions to Season' ?></p>
    <h1><?php printf( "%s &mdash; %s",  $season->name, 'Add Competitions to Season' ); ?></h1>
    <legend>Select Competitions to Add</legend>
    <script type='text/javascript'>
    jQuery(function() {
           jQuery("#tabs-competitions").tabs({
                                     });
           });
    </script>
    <div id="tabs-competitions" class="leaguemanager-blocks">
        <form action="admin.php?page=leaguemanager" method="post" enctype="multipart/form-data" name="competitions_add">
        <?php wp_nonce_field( 'leaguemanager_add-seasons-competitions-bulk' ) ?>
            <input type="hidden" name="season_id" value="<?php echo $season->id ?>" />
            <input type="hidden" name="season" value="<?php echo $season->name ?>" />
            <div id="matchDays">
                <label for="num_match_days"><?php _e( 'Number of Match Days', 'leaguemanager' ) ?></label>
                <input type="number" min="1" step="1" required="required" class="small-text" name="num_match_days" id="num_match_days" size="2" />
            </div>
            <ul id="tablist">
                <li><a href="#competitions-cup"><?php _e( 'Cups', 'leaguemanager' ) ?></a></li>
                <li><a href="#competitions-league"><?php _e( 'Leagues', 'leaguemanager' ) ?></a></li>
                <li><a href="#competitions-tournament"><?php _e( 'Tournaments', 'leaguemanager' ) ?></a></li>
            </ul>

            <div class="tablenav">
                <!-- Bulk Actions -->
                <select name="action" size="1">
                    <option value="addCompetitionsToSeason"><?php _e('Add')?></option>
                </select>
                <input type="submit" value="<?php _e('Apply'); ?>" name="doaddCompetitionsToSeason" id="doaddCompetitionsToSeason" class="button action" />
            </div>

            <table class="widefat" summary="" title="LeagueManager Competitions">
                <thead>
                <tr>
                    <th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('competitions-filter'));" /></th>
                    <th scope="col" class="num">ID</th>
                    <th scope="col"><?php _e( 'Name', 'leaguemanager' ) ?></th>
                </tr>

<?php $prevCompType = '';
    if ( $competitions = $leaguemanager->getCompetitions(array('orderby' => array("competitiontype" => "ASC", "name" => "ASC"))) ) {
        $class = '';
        foreach ( $competitions AS $competition ) {
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
            if ( $competition->competitiontype != $prevCompType ) {
                if ( $prevCompType != '' ) { ?>
                </tbody>
                <?php }
                    $prevCompType = $competition->competitiontype; ?>
                <tbody id="competitions-<?php echo $prevCompType ?>" style="display: contents">
            <?php } ?>
                    <tr class="<?php echo $class ?>">
                        <th scope="row" class="check-column">
<?php if ( !array_search($season->name,array_column($competition->seasons, 'name') ,true) ) { ?>
                            <input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" />
<?php } ?>
                        </th>
                        <td class="num"><?php echo $competition->id ?></td>
                        <td><?php echo $competition->name ?></td>
                    </tr>
                <?php } ?>
            <?php } ?>
                </tbody>
            </table>
        </form>
    </div>

