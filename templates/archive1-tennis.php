php } ?>
				</tr>
			<?php } ?>
		</tbody>
	</table>
								
	<?php if ( 'popup' == $mode ) { ?>
			</div>
		</div>
		<p><a class='thickbox' href='#TB_inline&width=800&height=500&inlineId=leaguemanager_crosstable' title='<?php _e( 'Crosstable', 'leaguemanager' )." ".$league->title ?>'><?php _e( 'Crosstable', 'leaguemanager' )." ".$league->title ?> (<?php _e('Popup','leaguemanager') ?>)</a></p>
	<?php } ?>

<?php } ?>
  :P�X����I��<VE�o0/*
* @section: Backend
*/
#lm_admin {
	width:92%;
	margin-top:10px;
}

#lm_head {
	font-family:Verdana;
	font-size:18px;
    position: relative;
	top: -2px;
}
#lm_admin .lm_head_strong {
	font-family:Verdana;
	font-size:18px;
	font-weight:bold;
}

#lm_version {
    font-family:Verdana;
    font-size:14px;
    position: relative;
	top: -20px;
    float: right;
}

#lm_admin .lm_version_strong {
    font-family:Verdana;
    font-size:14px;
    font-weight:bold;
}

.check-column {
	vertical-align: middle;
}

/* 
 * Global Settings Page
 */
.colorbox {
	display: inline-block;
	margin-left: 5px;
	width: 15px;
	height: 15px;
	border: 1px solid #000;
}

/*
 * League page
 */
.subsubsub:after {
	content: " ";
	clear: both;
	display: block;
}
.subsubsub {
	margin-bottom: 1.5em;
}
.league-blocks {
	clear: both;
}
.league-blocks h2.header {
	margin-bottom: 0;
}
.championship-blocks {
	clear: both;
}

/*
 * sortable tables 
 */
tbody.sortable tr:hover th,
tbody.sortable tr:hover td {
	border-top: 1px solid #585858;
	border-bottom: 1px solid #585858;
}
tbody.sortable th,
tbody.sortable td {
	background-color: #fff;
}
tbody.sortable tr.alternate th,
tbody.sortable tr.alternate td {
	background-color: #f9f9f9;
}

/*
 * Admin Crosstable
 */
.widefat.crosstable {
	margin-top: 1em;
}
.widefat.crosstable th {
	font-weight: bold;
}

/*--- Documentation ---*/
dl.leaguemanager {
	}
dl.leaguemanager dt {
	clear: both;
	font-weight: bold;
	}
dl.leaguemanager dd {
	text-indent: 1.5em;
	}
ul.doc-