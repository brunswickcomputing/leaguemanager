jQuery(document).ready(function ($) {
	jQuery('[data-bs-toggle="tooltip"]').tooltip();
	jQuery("#acceptance").prop("checked", false)
	jQuery("#entrySubmit").hide();
	jQuery('#acceptance').change(function (e) {
		if (this.checked) {
			jQuery("#entrySubmit").show();
		} else {
			jQuery("#entrySubmit").hide();
		}
	});

	jQuery("tr.match-rubber-row").slideToggle('fast', 'linear');
	jQuery("i", "td.angle-dir", "tr.match-row").toggleClass("angle-right angle-down");

	jQuery("tr.match-row").click(function (e) {
		jQuery(this).next("tr.match-rubber-row").slideToggle('0', 'linear');
		jQuery(this).find("i.angledir").toggleClass("angle-right angle-down");
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_archive').on('change', function () {
		let league = jQuery('#league_id').val(); //
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/league/' + league.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_competititon_archive #season').on('change', function () {
		let pagename = jQuery('#pagename').val();
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + pagename.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').on('change', function () {
		let league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		league = league.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery('#season').val();
		let matchday = jQuery('#match_day').val();
		if (matchday == -1) matchday = 0;

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/league/' + league.toLowerCase() + '/' + season + '/day' + matchday + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_winners #selection').on('change', function () {
		let selection = jQuery(`#selection`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		selection = selection.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		selection = selection.replace("-", "_"); // Replace '-' with a '-' symbol */
		selection = selection.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let competitionSeason = jQuery(`#competitionSeason`).val();
		let competitionType = jQuery(`#competitionType`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + competitionType + 's/' + competitionSeason + '/winners/' + selection.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_orderofplay #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace '-' with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery(`#season`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournaments/' + season + '/order-of-play/' + tournament.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_tournament #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '_' symbol */
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournament/' + tournament.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_daily_matches #match_date').on('change', function () {
		let matchDate = jQuery(`#match_date`).val();
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/leagues/daily-matches/' + encodeURIComponent(matchDate) + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#match_date').on('change', function () {
		let match_date = jQuery(`#match_date`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		let tournament = jQuery(`#tournament_id`).val();
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '_' symbol */
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournament/' + tournament.toLowerCase() + '/matches/' + match_date + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#tournament-match-date-form').submit(function () {
	});
	jQuery('.teamcaptain').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#affiliatedClub").val();
			let fieldref = this.element[0].id;
			let ref = fieldref.substr(7);
			let notifyField = '#updateTeamResponse'.concat(ref);
			response(get_player_details(request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let player = "#".concat(captaininput);
			let playerId = "#captainId".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let player = "#".concat(captaininput);
			let playerId = "#captainid".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(contactno).val(ui.item.contactno);
				jQuery(contactemail).val(ui.item.user_email);
			}
		}
	});

	jQuery('#matchSecretaryName').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#club_id").val();
			let notifyField = '#match-secretary-feedback';
			response(get_player_details(request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let player = "#matchSecretaryName";
			let playerId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let player = "#matchSecretaryName";
			let playerId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(contactno).val(ui.item.contactno);
				jQuery(contactemail).val(ui.item.user_email);
			}
		}
	});

	jQuery('.passwordShow').hover(function () {
		let input = jQuery(this).parent().find('.password');
		input.attr('type', 'text');
	}, function () {
		jQuery('.password').attr('type', 'password');
		let input = jQuery(this).parent().find('.password');
		input.attr('type', 'password');
	});

	jQuery(":checkbox").click(function (event) {
		let $target = event.target;

		// If a checkbox with aria-controls, handle click
		let isCheckbox = $target.getAttribute('type') === 'checkbox';
		let hasAriaControls = $target.getAttribute('aria-controls');
		if (isCheckbox && hasAriaControls) {
			let $target2 = this.parentNode.parentNode.querySelector('#' + $target.getAttribute('aria-controls'));

			if ($target2?.classList.contains('form-checkboxes__conditional')) {
				let inputIsChecked = $target.checked;

				$target2.setAttribute('aria-expanded', inputIsChecked);
				$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			}
		}
	});

	jQuery('select.cupteam').on('change', function (e) {
		let team = this.value;
		let event = this.name;
		event = event.substring(5, event.length - 1);
		let notifyField = "#notify-" + event;
		let responseField = "#team-dtls-" + event;
		let splash = '#splash-' + event;
		jQuery(splash).removeClass("d-none");
		jQuery(splash).css('opacity', 1);
		jQuery(splash).show();
		jQuery(responseField).hide();
		jQuery(notifyField).hide();

		jQuery.ajax({
			type: 'POST',
			datatype: 'json',
			url: ajax_var.url,
			data: {
				"team": team,
				"event": event,
				"action": "racketmanager_get_team_info",
				"security": ajax_var.ajax_nonce,
			},
			success: function (response) {
				let team_info = response.data;
				let captaininput = "captain-".concat(event);
				let ref = captaininput.substring(7);
				let captain = "#".concat(captaininput);
				let captainId = "#captainId".concat(ref);
				let contactno = "#contactno".concat(ref);
				let contactemail = "#contactemail".concat(ref);
				let matchday = "#matchday".concat(ref);
				let matchtime = "#matchtime".concat(ref);
				jQuery(captain).val(team_info.captain);
				jQuery(captainId).val(team_info.captainid);
				jQuery(contactno).val(team_info.contactno);
				jQuery(contactemail).val(team_info.user_email);
				jQuery(matchday).val(team_info.match_day);
				jQuery(matchtime).val(team_info.match_time);
			},
			error: function (response) {
				if (response.responseJSON) {
					jQuery(notifyField).text(response.responseJSON.data);
				} else {
					jQuery(notifyField).text(response.statusText);
				}
				jQuery(notifyField).show();
			},
			complete: function () {
				jQuery(splash).css('opacity', 0);
				jQuery(splash).hide();
				jQuery(responseField).show();
			}
		});
	});
	jQuery('[data-js=add-favourite]').click(function (e) {
		e.preventDefault();
		let favouriteid = $(this).data('favourite');
		let favouritetype = $(this).data('type');
		let favourite_field = "#".concat(e.currentTarget.id);
		let notifyField = "#fav-msg-".concat(favouriteid);

		jQuery.ajax({
			url: ajax_var.url,
			type: "POST",
			data: {
				"type": favouritetype,
				"id": favouriteid,
				"action": "racketmanager_add_favourite",
				"security": ajax_var.ajax_nonce,
			},
			success: function (response) {
				let $action = response.data.action;
				if ($action == 'del') {
					jQuery(favourite_field).attr("data-bs-original-title", "Add favourite");
					jQuery(favourite_field).removeClass('is-favourite');
					jQuery(favourite_field).find('i').removeClass('fav-icon-svg-selected');
				} else if ($action == 'add') {
					jQuery(favourite_field).attr("data-bs-original-title", "Remove favourite");
					jQuery(favourite_field).addClass('is-favourite');
					jQuery(favourite_field).find('i').addClass('fav-icon-svg-selected');
				}
			},
			error: function (response) {
				if (response.responseJSON) {
					jQuery(notifyField).text(response.responseJSON.data);
				} else {
					jQuery(notifyField).text(response.statusText);
				}
				jQuery(notifyField).show();
				jQuery(notifyField).addClass('message-error');
			}
		});
	});
});

let Racketmanager = new Object();

Racketmanager.printScoreCard = function (e, link) {
	e.preventDefault();
	let $matchCardWindow;
	let matchId = jQuery(link).attr('id');
	let notifyField = '#feedback-' + matchId;
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass('message-success message-error');
	let styleSheetList = document.styleSheets;
	let head = '<html><head><title>Match Card</title>';
	for (let item of styleSheetList) {
		if (item.url != 'null') head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
	};
	head += '</head>';
	let foot = '</body></html>';

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_matchcard",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			if (!$matchCardWindow || $matchCardWindow.closed) {
				let $matchCardWindow = window.open("about:blank", "_blank", "width=800,height=775");
				if (!$matchCardWindow) {
					jQuery(notifyField).text("Match Card not available - turn off pop blocker and retry");
					jQuery(notifyField).show();
					jQuery(notifyField).addClass('message-error');
				} else {
					$matchCardWindow.document.write(head + response.data + foot);
				}
			} else {
				// window still exists from last time and has not been closed.
				$matchCardWindow.document.body.innerHTML = response.data;
				$matchCardWindow.focus()
			}
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.updateMatchResults = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_update_match";
	let notifyField = '#updateResponse';
	let splash = '#splash';
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".winner").val("");
	jQuery(".winner").removeClass("winner");
	jQuery(notifyField).removeClass("message-success");
	jQuery(notifyField).removeClass("message-error");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery(splash).removeClass("d-none");
	jQuery(splash).css('opacity', 1);
	jQuery(splash).show();
	jQuery(".match__body").hide();
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = response.data;
			let $message = $response[0];
			jQuery(notifyField).show();
			jQuery(notifyField).html($message);
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
			let homepoints = $response[1];
			let formfield = "#home_points";
			let fieldval = homepoints;
			jQuery(formfield).val(fieldval);
			let awaypoints = $response[2];
			formfield = "#away_points";
			fieldval = awaypoints;
			jQuery(formfield).val(fieldval);
			let winner = $response[3];
			formfield = '#match-status-' + winner;
			jQuery(formfield).addClass('winner');
			jQuery(formfield).val('W');
			let sets = Object.entries($response[4]);
			for (let set of sets) {
				let setno = set[0];
				let teams = Object.entries(set[1]);
				for (let team of teams) {
					formfield = '#set_' + setno + '_' + team[0];
					fieldval = team[1];
					jQuery(formfield).val(fieldval);
				}
			}
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				for (let errorMsg of data[1]) {
					$message += '<br />' + errorMsg;
				}
				let $errorFields = data[2];
				for (let $errorField of $errorFields) {
					let $id = '#'.concat($errorField);
					jQuery($id).addClass("is-invalid");
				}
				jQuery(notifyField).show();
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery(splash).css('opacity', 0);
			jQuery(splash).hide();
			jQuery(".match__body").show();
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
	});
};
Racketmanager.updateResults = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_update_rubbers";
	let notifyField = '#updateResponse';
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).removeClass("message-success");
	jQuery(notifyField).removeClass("message-error");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = response.data;
			let $message = $response[0];
			jQuery("#updateResponse").show();
			jQuery("#updateResponse").addClass('message-success');
			jQuery("#updateResponse").html($message);
			jQuery("#updateResponse").delay(10000).fadeOut('slow');
			let $homepoints = $response[1];
			let $matchhome = 0;
			let $matchaway = 0;
			for (let i in $homepoints) {
				let $formfield = "#home_points-" + i;
				let $fieldval = $homepoints[i];
				jQuery($formfield).val($fieldval);
				$matchhome = +$matchhome + +$homepoints[i];
			}
			let $awaypoints = $response[2];
			for (let j in $awaypoints) {
				let $awayformfield = "#away_points-" + j;
				let $awayfieldval = $awaypoints[j];
				jQuery($awayformfield).val($awayfieldval);
				$matchaway = +$matchaway + +$awaypoints[j];
			}
			let $updatedRubbers = $response[3];
			let rubberNo = 1;
			for (let r in $updatedRubbers) {
				let $rubber = $updatedRubbers[r];
				let winner = $rubber['winner'];
				let formfield = '#match-status-' + rubberNo + '-' + winner;
				jQuery(formfield).addClass('winner');
				jQuery(formfield).val('W');
				for (let t in $rubber['players']) { // home or away
					let $team = $rubber['players'][t];
					for (let p = 0; p < $team.length; p++) {
						let $player = $team[p];
						let id = p + 1;
						let formfield = '#' + t + 'player' + id + '_' + rubberNo;
						let fieldval = $player;
						jQuery(formfield).val(fieldval);
						formfield = '#' + 'players_' + rubberNo + '_' + t + '_' + id;
						fieldval = $player;
						jQuery(formfield).val(fieldval);
					}
				}
				for (let s in $rubber['sets']) {
					let team = $rubber['sets'][s];
					for (let p in team) {
						let score = team[p];
						let formfield = '#' + 'set_' + rubberNo + '_' + s + '_' + p;
						let fieldval = score;
						jQuery(formfield).val(fieldval);
					}
				}
				rubberNo++;
			}
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				for (let errorMsg of data[1]) {
					$message += '<br />' + errorMsg;
				}
				let $errorFields = data[2];
				for (let $errorField of $errorFields) {
					let $id = '#'.concat($errorField);
					jQuery($id).addClass("is-invalid");
				}
				jQuery(notifyField).show();
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
	});
};
Racketmanager.club_player_request = function (link) {

	let notifyField = '#updateResponse';
	let $form = jQuery('#playerRequestFrm').serialize();
	$form += "&action=racketmanager_club_player_request";
	jQuery(notifyField).val("");
	jQuery("#clubPlayerUpdateSubmit").hide();
	jQuery("#clubPlayerUpdateSubmit").addClass("disabled");
	jQuery(notifyField).removeClass("message-success");
	jQuery(notifyField).removeClass("message-error");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalidFeedback").val("");

	jQuery.ajax({
		url: ajax_var.url,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery("#firstname").val("");
			jQuery("#surname").val("");
			jQuery("#genderMale").prop('checked', false);
			jQuery("#genderFemale").prop('checked', false);
			jQuery("#btm").val("");
			jQuery("#year_of_birth").val("");
			jQuery("#email").val("");
			jQuery(notifyField).addClass("message-success");
			jQuery(notifyField).show();
			jQuery(notifyField).html(response.data);
			jQuery(notifyField).delay(10000).fadeOut('slow');
			jQuery("#clubPlayerUpdateSubmit").removeClass("disabled");
			jQuery("#clubPlayerUpdateSubmit").show();
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				let $errorField = data[2];
				let $errorMsg = data[3];
				for (let $i = 0; $i < $errorField.length; $i++) {
					let $id = '#'.concat($errorField[$i]);
					jQuery($id).addClass("is-invalid");
					let $id2 = '#'.concat($errorField[$i], 'Feedback');
					jQuery($id2).html($errorMsg[$i]);
				}
				jQuery(notifyField).show();
				jQuery(notifyField).html($message);
				jQuery("#clubPlayerUpdateSubmit").removeClass("disabled");
				jQuery("#clubPlayerUpdateSubmit").show();
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.clubPlayerRemove = function (link) {

	let notifyField = '#playerRemove';
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).removeClass('message-success');
	let $form = jQuery(link).serialize();
	$form += "&action=racketmanager_club_players_remove";

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery(link).find('tr').each(function () {
				let row = jQuery(this);
				if (row.find('input[type="checkbox"]').is(':checked')) {
					let rowId = "#" + row.attr('id');
					jQuery(rowId).remove();
				}
			});
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				for (let errorMsg of response.responseJSON.data[1]) {
					$message += '<br />' + errorMsg;
				}
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).show();
		}
	});
};
Racketmanager.updateTeam = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let event = link.form[3].value;
	let team = link.form[2].value;
	let notifyField = "#updateTeamResponse-".concat(event, "-", team);
	let submitButton = "#teamUpdateSubmit-".concat(event, "-", team);
	$form += "&action=racketmanager_update_team";
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery(submitButton).hide();
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).removeClass('message-success');
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		async: false,
		data: $form,
		success: function (response) {
			jQuery(notifyField).show();
			jQuery(notifyField).addClass("message-success");
			jQuery(notifyField).html(response.data);
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					jQuery(notifyField).addClass('message-error');
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + '-feedback';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.updateClub = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let notifyField = "#updateClub";
	let submitButton = "#updateClubSubmit";
	$form += "&action=racketmanager_update_club";
	jQuery(notifyField).html("");
	jQuery(submitButton).hide();
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).removeClass('message-success');

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			jQuery(notifyField).show();
			jQuery(notifyField).addClass("message-success");
			jQuery(notifyField).html(response.data);
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					jQuery(notifyField).addClass('message-error');
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + '-feedback';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.updatePlayer = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let notifyField = "#updatePlayer";
	let submitButton = "#updatePlayerSubmit";
	$form += "&action=racketmanager_update_player";
	jQuery(notifyField).html("");
	jQuery(submitButton).hide();
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).removeClass('message-success');

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			jQuery(notifyField).show();
			jQuery(notifyField).addClass("message-success");
			jQuery(notifyField).html(response.data);
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					jQuery(notifyField).addClass('message-error');
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + 'Feedback';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};

Racketmanager.entryRequest = function (event, type) {
	event.preventDefault();
	let $form = jQuery('#form-entry').serialize();
	let action = "&action=racketmanager_" + type + "_entry";
	$form += action;
	let notifyField = '#entryResponse';
	jQuery(notifyField).val("");
	jQuery("#entrySubmit").hide();
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).removeClass('message-success');

	jQuery.ajax({
		url: ajax_var.url,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).html(response.data);
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				for (let errorMsg of response.responseJSON.data[1]) {
					$message += '<br />' + errorMsg;
				}
				for (let errorField of response.responseJSON.data[2]) {
					let $formfield = '#'.concat(errorField);
					jQuery($formfield).addClass('is-invalid');
				}
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).show();
		},
		complete: function () {
			jQuery("#acceptance").prop("checked", false);
		}
	});
};
Racketmanager.resetMatchScores = function (e, formId) {
	e.preventDefault();
	formId = '#'.concat(formId);
	jQuery(':input', formId)
		.not(':button, :submit, :reset, :hidden, :radio')
		.val('')
	jQuery(':input', formId)
		.not(':button, :submit, :reset, :hidden')
		.prop('checked', false)
		.prop('selected', false);
};
Racketmanager.matchMode = function (match_id, mode) {
	let notifyField = "#showMatchRubbers";
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery(".match-print").hide();
	jQuery(".match-mode").hide();
	jQuery(".match-mode").removeClass("d-none");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"mode": mode,
			"action": "racketmanager_match_mode",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
			Racketmanager.matchHeader(match_id);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField).show();
				jQuery(notifyField).html(message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			if ('view' === mode) {
				jQuery(".match-print").show();
			}
			jQuery(".match-mode").show();
			let hidefield = '#' + mode + 'MatchMode';
			jQuery(hidefield).hide();
			jQuery(notifyField).show();
		}
	});

};
Racketmanager.matchHeader = function (match_id) {
	let notifyField = "#match-header";
	jQuery(notifyField).val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"action": "racketmanager_update_match_header",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField).show();
				jQuery(notifyField).html(message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});

};
Racketmanager.viewMatch = function (e) {
	let link = jQuery(e.currentTarget).find("a.score-row__anchor").attr('href');
	if (link) {
		e.preventDefault();
		window.location = link;
	}
};
Racketmanager.SetCalculator = function (inputdata) {
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	let fieldRef = inputdata.id;
	let team = "#" + fieldRef;
	let fieldSplit = fieldRef.split('_');
	let setLength = 0;
	if (fieldSplit.length == 4) {
		setLength = 7;
	} else {
		setLength = 5;
	}
	let setRef = fieldRef.substring(0, setLength);
	let teamRefAlt = '';
	let teamRef = fieldRef.substr(fieldRef.length - 1, 1);
	if (teamRef == 1) {
		teamRefAlt = 2;
	} else {
		teamRefAlt = 1;
	}
	let teamScore = '';
	if (inputdata.value != '') {
		teamScore = parseInt(inputdata.value);
	}
	let teamAlt = "#" + setRef + "_player" + teamRefAlt;
	let teamDataAlt = jQuery(teamAlt)[0];
	let teamScoreAlt = '';
	if (teamDataAlt.value != '') {
		teamScoreAlt = parseInt(teamDataAlt.value);
	}
	let tieBreak = "#" + setRef + "_tiebreak";
	let tieBreakWrapper = tieBreak + '_wrapper';
	let tieBreakData = jQuery(tieBreak)[0];
	let tieBreakScore = '';
	if (tieBreakData.value != '') {
		tieBreakScore = parseInt(tieBreakData.value);
	}
	let setGroup = '#' + setRef;
	let maxWin = jQuery(setGroup).data('maxwin');
	let minWin = jQuery(setGroup).data('minwin');
	let maxLoss = jQuery(setGroup).data('maxloss');
	let minLoss = jQuery(setGroup).data('minloss');
	if (teamRef == 1) {
		if (teamScore == minWin) {
			if ('' === teamScoreAlt) {
				if ((teamScore + 2) < maxWin) {
					teamScoreAlt = teamScore + 2;
				} else {
					teamScoreAlt = maxWin;
				}
			}
		} else if (teamScore == maxWin) {
			if ('' == teamScoreAlt) {
				teamScoreAlt = minWin;
			}
		} else if ('' !== teamScore) {
			if ('' === teamScoreAlt) {
				if (teamScore === maxLoss) {
					teamScoreAlt = maxWin;
				} else if (teamScore < minWin) {
					teamScoreAlt = minWin;
				}
			}
		}
	} else if (teamRef == 2) {
		if ((teamScore == maxWin && teamScoreAlt == minWin) || (teamScoreAlt == maxWin && teamScore == minWin)) {
			jQuery(tieBreakWrapper).show();
			jQuery(tieBreak).focus();
		} else {
			tieBreakScore = '';
			jQuery(tieBreakWrapper).hide();
		}
	}
	jQuery(team).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(teamAlt).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(tieBreak).removeClass(classes.inputError);
	if (teamScore > teamScoreAlt) {
		Racketmanager.SetValidator(team, teamAlt, teamScore, teamScoreAlt, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin);
	} else if (teamScore < teamScoreAlt) {
		Racketmanager.SetValidator(teamAlt, team, teamScoreAlt, teamScore, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin);
	} else if (teamScore === teamScoreAlt) {
		if (!isNaN(teamScore) && '' != teamScore) {
			jQuery(team).addClass(classes.inputError);
			jQuery(teamAlt).addClass(classes.inputError)
		}
	}
	if (!isNaN(teamScore)) {
		jQuery(team).val(teamScore);
	}
	if (!isNaN(teamScoreAlt)) {
		jQuery(teamAlt).val(teamScoreAlt);
	}
	if (!isNaN(tieBreakScore)) {
		jQuery(tieBreak).val(tieBreakScore);
	}
};
Racketmanager.SetValidator = function (team1, team2, team1Score, team2Score, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin) {
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	if (team1Score > maxWin) {
		jQuery(team1).addClass(classes.inputError);
	} else if (team1Score == minWin && team2Score > minLoss) {
		jQuery(team1).addClass(classes.inputError);
		jQuery(team2).addClass(classes.inputError);
	} else if (team1Score === maxWin) {
		if (team2Score < maxLoss) {
			jQuery(team1).addClass(classes.inputError);
			jQuery(team2).addClass(classes.inputError);
		} else if (team2Score > maxLoss) {
			jQuery(team1).addClass(classes.won);
			if ('' === tieBreakScore) {
				jQuery(tieBreak).addClass(classes.inputError);
			}
		}
	} else if (team1Score > minWin && team2Score < minLoss) {
		jQuery(team1).addClass(classes.inputError);
	} else if (team1Score > minWin && team2Score > minLoss && team2Score != (team1Score - 2)) {
		jQuery(team1).addClass(classes.inputError);
	} else {
		jQuery(team1).addClass(classes.won);
	}
};
Racketmanager.SetCalculatorTieBreak = function (inputdata) {
	let classes = {
		inputError: 'input-validation-error'
	}
	let fieldRef = inputdata.id;
	let tieBreak = "#" + fieldRef;
	let tieBreakScore = parseInt(inputdata.value);
	if (isNaN(tieBreakScore)) {
		jQuery(tieBreak).addClass(classes.inputError);
	} else {
		jQuery(tieBreak).removeClass(classes.inputError);
		jQuery(tieBreak).removeClass('is-invalid');
	}
};
function activaTab(tab) {
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function get_player_details(name, club = null, notifyField = null) {
	let response = '';
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: {
			"name": name,
			"affiliatedClub": club,
			"action": "racketmanager_get_player_details",
			"security": ajax_var.ajax_nonce,
		},
		success: function (data) {
			response = JSON.parse(data.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
	return response;
}