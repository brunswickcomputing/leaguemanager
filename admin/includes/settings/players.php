<div class="form-control">
      <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playerLeadTime' id='playerLeadTime' value='<?php echo isset($options['checks']['playerLeadTime']) ? $options['checks']['playerLeadTime'] : '' ?>' />
        <label for='playerLeadTime'><?php _e( 'Player Registration Lead Time (days)', 'racketmanager' ) ?></label>
      </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playedRounds' id='playedRounds' value='<?php echo isset($options['checks']['playedRounds']) ? $options['checks']['playedRounds'] : '' ?>' />
        <label for='playedRounds'><?php _e( 'End of season eligibility (Match Days)', 'racketmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playerLocked' id='playerLocked' value='<?php echo isset($options['checks']['playerLocked']) ? $options['checks']['playerLocked'] : '' ?>' />
        <label for='playerLocked'><?php _e( 'How many matches lock a player', 'racketmanager' ) ?></label>
    </div>
</div>
