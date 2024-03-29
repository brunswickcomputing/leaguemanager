<?php
/**
 * League Contact administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $league->event->competition->id ); ?>"><?php echo esc_html( $league->event->competition->name ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $league->event->id ); ?>"><?php echo esc_html( $league->event->name ); ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo esc_html( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo; <?php esc_html_e( 'Contact', 'racketmanager' ); ?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Contact clubs', 'racketmanager' ); ?></h1>
	<!-- Nav tabs -->
	<ul class="nav nav-tabs" id="myTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="compose-tab" data-bs-toggle="tab" data-bs-target="#compose" type="button" role="tab" aria-controls="compose" aria-selected="true">
				<?php esc_html_e( 'Compose', 'racketmanager' ); ?>
			</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="false">
				<?php esc_html_e( 'Preview', 'racketmanager' ); ?>
			</button>
		</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div id="compose" class="tab-pane table-pane active show fade" role="tabpanel" aria-labelledby="compose">
			<form class="g-3 mt-3 form-control" action="admin.php?page=racketmanager&amp;subpage=contact&league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>" method="post" enctype="multipart/form-data" name="teams_contact">
				<?php wp_nonce_field( 'racketmanager_contact-teams', 'racketmanager_nonce' ); ?>
				<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
				<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
				<div class="col-12 form-floating mb-3">
					<input type="text" class="form-control" name="contactTitle" id="contactTitle" placeholder="Enter title" value="<?php echo esc_html( $email_title ); ?>" />
					<label for="contactTitle"><?php esc_html_e( 'Email title', 'racketmanager' ); ?></label>
				</div>
				<div class="col-12 form-floating mb-3">
					<input type="textarea" class="form-control contactText" name="contactIntro" id="contactIntro" placeholder="Enter intro" value="<?php echo esc_html( $email_intro ); ?>" />
					<label for="contactIntro"><?php esc_html_e( 'Email introduction', 'racketmanager' ); ?></label>
				</div>
				<?php for ( $i = 1; $i <= 5; $i++ ) { ?>
					<div class="col-12 form-floating mb-3">
						<input type="textarea" class="form-control contactBody" rows=20 name="contactBody[<?php echo esc_html( $i ); ?>]" id="contactBody-<?php echo esc_html( $i ); ?>" placeholder="Enter email text"
							<?php
							if ( isset( $email_body[ $i ] ) ) {
								echo ' value="' . esc_html( $email_body[ $i ] ) . '"';
							}
							?>
						/>
						<label for="contactBody-<?php echo esc_html( $i ); ?>"><?php esc_html_e( 'Paragraph', 'racketmanager' ); ?> <?php echo esc_html( $i ); ?></label>
					</div>
				<?php } ?>
				<div class="col-12 form-floating mb-3">
					<input type="textarea" class="form-control contactText" name="contactClose" id="contactClose" placeholder="Enter closing" value="<?php echo esc_html( $email_close ); ?>" />
					<label for="contactClose"><?php esc_html_e( 'Email closing', 'racketmanager' ); ?></label>
				</div>
				<div class="col-12">
					<button class="btn btn-primary" name="contactTeamPreview">
						<?php esc_html_e( 'Preview', 'racketmanager' ); ?>
					</button>
					<a href="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></a>
				</div>
			</form>
		</div>
		<div id="preview" class="tab-pane table-pane
			<?php
			if ( $email_message ) {
				echo ' show active ';
			}
			?>
			fade" role="tabpanel" aria-labelledby="preview">
			<?php
			if ( $email_message ) {
				?>
				<iframe id="iframeMsg" title="<?php esc_html_e( 'Email message', 'racketmanager' ); ?>" onload='setIframeHeight(this.id)' style="height:200px;width:100%;border:none;overflow:hidden;" srcdoc='<?php echo $email_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'></iframe>
			<?php } else { ?>
				<div class="mt-3 mb-3">
					<?php esc_html_e( 'No message to preview', 'racketmanager' ); ?>
				</div>
			<?php } ?>
			<form class="g-3 form-control" action="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $season ); ?>" method="post" enctype="multipart/form-data" name="teams_contact">
				<?php wp_nonce_field( 'racketmanager_contact-teams-preview', 'racketmanager_nonce' ); ?>
				<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
				<input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
				<input type="hidden" name="emailMessage" value='<?php echo htmlspecialchars( $email_message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>' />
				<div class="col-12">
					<button class="btn btn-primary" name="contactTeam"><?php esc_html_e( 'Send', 'racketmanager' ); ?></button>
					<button class="btn btn-secondary"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
