<?php
/**
 * Results main page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo $tab; ?>');
});
</script>
<div class="container">
	<h1><?php esc_html_e( 'Results', 'racketmanager' ); ?></h1>
	<div class="container">
		<!-- Nav tabs -->
		<ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="resultschecker-tab" data-bs-toggle="tab" data-bs-target="#resultschecker" type="button" role="tab" aria-controls="resultschecker" aria-selected="false">Results Checker</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="pendingresults-tab" data-bs-toggle="tab" data-bs-target="#pendingresults" type="button" role="tab" aria-controls="pendingresults" aria-selected="true">Pending Results</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="awaitingconfirmation-tab" data-bs-toggle="tab" data-bs-target="#awaitingconfirmation" type="button" role="tab" aria-controls="awaitingconfirmation" aria-selected="true">Awaiting confirmation</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="challangeresults-tab" data-bs-toggle="tab" data-bs-target="#challangeresults" type="button" role="tab" aria-controls="challangeresults" aria-selected="true">Challenged Results</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab" aria-controls="results" aria-selected="true">Results</button>
			</li>
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="resultschecker" role="tabpanel" aria-labelledby="resultschecker-tab">
				<h2 class="header"><?php esc_html_e( 'Results Checker', 'racketmanager' ); ?></h2>
				<?php require 'results/results-checker.php'; ?>
			</div>
			<div class="tab-pane fade" id="pendingresults" role="tabpanel" aria-labelledby="pendingresults-tab">
				<h2 class="header"><?php esc_html_e( 'Results not yet in', 'racketmanager' ); ?></h2>
				<?php require 'results/pending-results.php'; ?>
			</div>
			<div class="tab-pane fade" id="awaitingconfirmation" role="tabpanel" aria-labelledby="awaitingconfirmation-tab">
				<h2 class="header"><?php esc_html_e( 'Results awaiting confirmation', 'racketmanager' ); ?></h2>
				<?php require 'results/awaiting-confirmation.php'; ?>
			</div>
			<div class="tab-pane fade" id="challangeresults" role="tabpanel" aria-labelledby="challangeresults-tab">
				<h2 class="header"><?php esc_html_e( 'Challenged results', 'racketmanager' ); ?></h2>
				<?php require 'results/challenged-results.php'; ?>
			</div>
			<div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
				<h2 class="header"><?php esc_html_e( 'Results requiring action', 'racketmanager' ); ?></h2>
				<?php require 'results/results.php'; ?>
			</div>
		</div>
	</div>
</div>
