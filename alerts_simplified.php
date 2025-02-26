<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or alerts simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('alerts-simplified-view') )
{
	exit;
}

$svbr = REDCapUITweaker::SVBR;

function alertsEscape( $text )
{
	global $svbr;
	$text = str_replace( [ "\r\n", "\r" ], "\n", $text );
	$text = preg_replace( "/<\\/p>( |\n|\t)*<p[^>]*>/", '<br><br>', $text );
	$text = preg_replace( "/<\\/p>( |\n|\t)*<[ou]l>/", '<br>', $text );
	$text = preg_replace( '/<li[^>]*>/', '<br>', $text );
	$text = str_replace( [ '<p>', '</p>' ], '', $text );
	$text = htmlspecialchars( $text, ENT_QUOTES );
	$text = preg_replace( '/&lt;(b|i|u|strong|em|span|br)&gt;/', '<$1>', $text );
	$text = preg_replace( '/&lt;(b|i|u|strong|em|span) style=&quot;((?(?=&quot;)|.)*)' .
	                      '&quot;&gt;/', '<$1 style="$2">', $text );
	$text = preg_replace( '/&lt;\\/(b|i|u|strong|em|span)&gt;/', '</$1>', $text );
	$text = preg_replace( '/&lt;a href=&quot;((?(?=&quot;)|.)*)&quot;&gt;((?(?=&lt;\\/a&gt;)|.)*)' .
	                      '&lt;\\/a&gt;/',
	                      '<span style="text-decoration:underline;color:#009">$2</span>' .
	                      '<sub style="color:#999">$1</sub>', $text );
	$text = preg_replace( '/&lt;((?(?=&gt;)|.)*)&gt;/', '', $text );
	$text = str_replace( [ '&lt;', '&gt;', '&quot;', '&#039;' ], '', $text );
	$text = str_replace( [ '&amp;lt;', '&amp;gt;', '&amp;quot;', '&amp;#039;', '&amp;amp;' ],
	                     [ '&lt;', '&gt;', '&quot;', '&#039;', '&amp;' ], $text );
	$lineCount = 0;
	$textLines = explode( '<br>', $text );
	$text = '';
	while ( ! empty( $textLines ) )
	{
		if ( $text != '' )
		{
			if ( $lineCount > 25 )
			{
				$text .= '<br>';
				$lineCount = 0;
			}
			else
			{
				$text .= $svbr;
			}
		}
		$lineCount += 1 + floor( strlen( $textLines[0] ) / 50 );
		$text .= array_shift( $textLines );
	}
	return $text;
}

$lookupTriggered = [ 'submit' => $GLOBALS['lang']['alerts_134'],
                     'submit-logic' => $GLOBALS['lang']['alerts_135']
                                       ?? $GLOBALS['lang']['alerts_316'],
                     'logic' => $GLOBALS['lang']['alerts_136'] ?? $GLOBALS['lang']['alerts_317'] ];
$lookupFormStatus = [ '1' => $GLOBALS['lang']['alerts_126'] . ' ' . $GLOBALS['lang']['alerts_120'] .
                             ' ' . $GLOBALS['lang']['alerts_138'],
                      '0' => $GLOBALS['lang']['alerts_126'] . ' ' . $GLOBALS['lang']['alerts_120'] .
                             ' ' . $GLOBALS['lang']['alerts_139'] ];
$lookupAlertTypes = [ 'EMAIL' => $GLOBALS['lang']['global_33'],
                      'SMS' => $GLOBALS['lang']['alerts_201'],
                      'VOICE_CALL' => $GLOBALS['lang']['alerts_202'] ];
$lookupDaysOfWeek = [ 'DAY' => $GLOBALS['lang']['global_96'],
                      'WEEKDAY' => $GLOBALS['lang']['global_97'],
                      'WEEKENDDAY' => $GLOBALS['lang']['global_98'],
                      'SUNDAY' => $GLOBALS['lang']['global_99'],
                      'MONDAY' => $GLOBALS['lang']['global_100'],
                      'TUESDAY' => $GLOBALS['lang']['global_101'],
                      'WEDNESDAY' => $GLOBALS['lang']['global_102'],
                      'THURSDAY' => $GLOBALS['lang']['global_103'],
                      'FRIDAY' => $GLOBALS['lang']['global_104'],
                      'SATURDAY' => $GLOBALS['lang']['global_105'] ];
$lookupBeforeAfter = [ 'before' => $GLOBALS['lang']['alerts_245'],
                       'after' => $GLOBALS['lang']['alerts_238'] ];
$lookupUnits = [ 'MINUTES' => $GLOBALS['lang']['survey_428'],
                 'HOURS' => $GLOBALS['lang']['survey_427'],
                 'DAYS' => $GLOBALS['lang']['survey_426'] ];
$lookupLimit = [ 'RECORD' => $GLOBALS['lang']['alerts_216'],
                 'RECORD_INSTRUMENT' => $GLOBALS['lang']['alerts_222'],
                 'RECORD_EVENT' => $GLOBALS['lang']['alerts_218'],
                 'RECORD_EVENT_INSTRUMENT_INSTANCE' =>
                     \REDCap::isLongitudinal()
                     ? ( $GLOBALS['lang']['alerts_218'] . ' ' . $GLOBALS['lang']['alerts_223'] )
                     : ( $GLOBALS['lang']['alerts_216'] . ' ' . $GLOBALS['lang']['alerts_217'] ) ];


$queryAlerts = $module->query( "SELECT *, if( form_name IS NULL, 'logic', if( alert_condition IS " .
                               "NULL, 'submit', 'submit-logic' ) ) alert_trigger, " .
                               "( SELECT form_menu_description FROM redcap_metadata WHERE " .
                               "form_name = redcap_alerts.form_name AND form_menu_description " .
                               "IS NOT NULL AND project_id = redcap_alerts.project_id LIMIT 1 ) " .
                               "form_menu_description FROM redcap_alerts WHERE project_id = ? " .
                               "ORDER BY email_deleted, alert_order",
                               [ $module->getProjectID() ] );


$queryASI = $module->query( "SELECT rss.*, rs.form_name, rem.descrip event_label, rem2.descrip " .
                            "condition_surveycomplete_event_label, ( SELECT form_menu_description" .
                            " FROM redcap_metadata WHERE form_name = rs.form_name AND" .
                            " form_menu_description IS NOT NULL AND project_id = rs.project_id" .
                            " LIMIT 1 ) form_menu_description, ( SELECT form_menu_description" .
                            " FROM redcap_metadata WHERE form_name = rs2.form_name AND" .
                            " form_menu_description IS NOT NULL AND project_id = rs.project_id" .
                            " LIMIT 1 ) condition_surveycomplete_form " .
                            "FROM redcap_surveys_scheduler rss JOIN redcap_surveys rs " .
                            "ON rss.survey_id = rs.survey_id LEFT JOIN redcap_surveys rs2 " .
                            "ON rss.condition_surveycomplete_survey_id = rs2.survey_id " .
                            "LEFT JOIN redcap_events_metadata rem ON rss.event_id = rem.event_id " .
                            "LEFT JOIN redcap_events_metadata rem2 " .
                            "ON rss.condition_surveycomplete_event_id = rem2.event_id " .
                            "WHERE rss.active = 1 AND rs.survey_enabled = 1 AND rs.project_id = ? " .
                            "ORDER BY ( SELECT field_order FROM redcap_metadata WHERE form_name =" .
                            " rs.form_name AND form_menu_description IS NOT NULL AND project_id =" .
                            " rs.project_id LIMIT 1 ), rem.day_offset",
                            [ $module->getProjectID() ] );

$querySurvey = $module->query( "SELECT form_name, ( SELECT form_menu_description FROM " .
                               "redcap_metadata WHERE form_name = rs.form_name AND " .
                               "form_menu_description IS NOT NULL AND project_id = rs.project_id " .
                               " LIMIT 1 ) form_menu_description, confirmation_email_from, " .
                               "confirmation_email_from_display, confirmation_email_subject, " .
                               "confirmation_email_content, confirmation_email_attach_pdf, " .
                               "if( confirmation_email_attachment IS NULL, 0, 1) " .
                               "confirmation_email_attachment FROM redcap_surveys rs " .
                               "WHERE survey_enabled = 1 AND confirmation_email_subject IS NOT " .
                               "NULL AND confirmation_email_content IS NOT NULL AND " .
                               "project_id = ? AND form_name IN ( SELECT DISTINCT form_name " .
                               "FROM redcap_metadata WHERE project_id = rs.project_id ) " .
                               "ORDER BY ( SELECT field_order FROM redcap_metadata WHERE " .
                               "form_name = rs.form_name AND form_menu_description IS NOT NULL " .
                               "AND project_id = rs.project_id LIMIT 1 )",
                               [ $module->getProjectID() ] );


// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'alerts', 'alerts' => [], 'custom_alerts' => [],
                'asi' => [], 'survey' => [] ];

while ( $infoAlert = $queryAlerts->fetch_assoc() )
{
	$infoAlert['email_attachments_num'] = ( $infoAlert['email_attachment1'] == '' ? 0 : 1 ) +
	                                      ( $infoAlert['email_attachment2'] == '' ? 0 : 1 ) +
	                                      ( $infoAlert['email_attachment3'] == '' ? 0 : 1 ) +
	                                      ( $infoAlert['email_attachment4'] == '' ? 0 : 1 ) +
	                                      ( $infoAlert['email_attachment5'] == '' ? 0 : 1 );
	foreach ( $infoAlert as $key => $value )
	{
		if ( $key == 'project_id' || $key == 'email_sent' || $key == 'email_timestamp_sent' ||
		     $key == 'alert_id' || $key == 'email_failed' || substr( $key, 0, 9 ) == 'sendgrid_' ||
		     ( substr( $key, 0, 16 ) == 'email_attachment' && strlen( $key ) < 19 ) )
		{
			unset( $infoAlert[ $key ] );
		}
	}
	$infoAlert['alert_condition'] = str_replace( "\r\n", "\n", $infoAlert['alert_condition'] );
	$infoAlert['alert_message'] = str_replace( "\r\n", "\n", $infoAlert['alert_message'] );
	$infoAlert['alert_message'] = preg_replace( '/ +/', ' ', $infoAlert['alert_message'] );
	$infoAlert['alert_message'] = preg_replace( '!<br ?/>!', '<br>', $infoAlert['alert_message'] );
	$infoAlert['alert_message'] = str_replace( ' <br>', '<br>', $infoAlert['alert_message'] );
	$infoAlert['alert_message'] = str_replace( ' </p>', '</p>', $infoAlert['alert_message'] );
	$listExport['alerts'][] = $infoAlert;
}

foreach ( $module->getCustomAlerts() as $infoAlert )
{
	$listExport['custom_alerts'][] = $infoAlert;
}

while ( $infoASI = $queryASI->fetch_assoc() )
{
	unset( $infoASI['ss_id'], $infoASI['survey_id'], $infoASI['event_id'], $infoASI['active'] );
	$listExport['asi'][] = $infoASI;
}

while ( $infoSurvey = $querySurvey->fetch_assoc() )
{
	$infoSurvey['confirmation_email_content'] =
			str_replace( "\r\n", "\n", $infoSurvey['confirmation_email_content'] );
	$infoSurvey['confirmation_email_content'] =
			preg_replace( '/ +/', ' ', $infoSurvey['confirmation_email_content'] );
	$infoSurvey['confirmation_email_content'] =
			preg_replace( '!<br ?/>!', '<br>', $infoSurvey['confirmation_email_content'] );
	$infoSurvey['confirmation_email_content'] =
			str_replace( ' <br>', '<br>', $infoSurvey['confirmation_email_content'] );
	$infoSurvey['confirmation_email_content'] =
			str_replace( ' </p>', '</p>', $infoSurvey['confirmation_email_content'] );
	$listExport['survey'][] = $infoSurvey;
}

// Handle upload. Get old/new data structures.
$fileLoadError = false;
if ( isset( $_POST['simp_view_diff_mode'] ) && $_POST['simp_view_diff_mode'] == 'export' )
{
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' .
	        trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', \REDCap::getProjectTitle() ), '_-' ) .
	        '_' . date( 'Ymd' ) . '.sva.json"' );
	$module->echoText( json_encode( $listExport ) );
	exit;
}
$listOld = $listExport;
$listNew = $listExport;
if ( isset( $_POST['simp_view_diff_mode'] ) )
{
	try
	{
		if ( ! is_uploaded_file( $_FILES['simp_view_diff_file']['tmp_name'] ) )
		{
			throw new \Exception();
		}
		$fileData = json_decode( file_get_contents( $_FILES['simp_view_diff_file']['tmp_name'] ),
		                         true );
		if ( $fileData == null || ! is_array( $fileData ) ||
		     $fileData['simplified_view'] != 'alerts' )
		{
			throw new \Exception();
		}
		if ( $_POST['simp_view_diff_mode'] == 'old' )
		{
			$listOld = $fileData;
		}
		elseif ( $_POST['simp_view_diff_mode'] == 'new' )
		{
			$listNew = $fileData;
		}
		else
		{
			throw new \Exception();
		}
	}
	catch ( \Exception $e )
	{
		$fileLoadError = true;
	}
}

// Create the combined data structure.
$listAlerts = [];
$listASIs = [];
$listCustomAlerts = [];
$listSurveys = [];

// Determine whether the alerts match.
$mapAlertsN2O = [];
$mapCustomAlertsN2O = [];
$mapASIsN2O = [];
$mapSurveysN2O = [];
foreach ( $listNew['alerts'] as $i => $itemNewAlert )
{
	if ( $itemNewAlert['alert_title'] == '' )
	{
		continue;
	}
	foreach ( $listOld['alerts'] as $j => $itemOldAlert )
	{
		if ( $itemNewAlert['alert_title'] == '' || in_array( $j, $mapAlertsN2O ) )
		{
			continue;
		}
		if ( $itemNewAlert['alert_title'] == $itemOldAlert['alert_title'] &&
		     $itemNewAlert['alert_type'] == $itemOldAlert['alert_type'] )
		{
			$mapAlertsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['alerts'] as $i => $itemNewAlert )
{
	if ( isset( $mapAlertsN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['alerts'] as $j => $itemOldAlert )
	{
		if ( in_array( $j, $mapAlertsN2O ) )
		{
			continue;
		}
		$matchingKeys = 0;
		$totalKeys = 0;
		foreach ( $itemNewAlert as $key => $value )
		{
			if ( ! isset( $itemOldAlert[ $key ] ) )
			{
				continue;
			}
			$totalKeys++;
			if ( $value == $itemOldAlert[ $key ] )
			{
				$matchingKeys++;
			}
		}
		if ( $totalKeys - $matchingKeys < 5 )
		{
			$mapAlertsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['custom_alerts'] as $i => $itemNewAlert )
{
	if ( $itemNewAlert['title'] == '' )
	{
		continue;
	}
	foreach ( $listOld['custom_alerts'] as $j => $itemOldAlert )
	{
		if ( $itemNewAlert['title'] == '' || in_array( $j, $mapCustomAlertsN2O ) )
		{
			continue;
		}
		if ( $itemNewAlert['title'] == $itemOldAlert['title'] &&
		     $itemNewAlert['type'] == $itemOldAlert['type'] )
		{
			$mapCustomAlertsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['custom_alerts'] as $i => $itemNewAlert )
{
	if ( isset( $mapCustomAlertsN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['custom_alerts'] as $j => $itemOldAlert )
	{
		if ( in_array( $j, $mapCustomAlertsN2O ) )
		{
			continue;
		}
		$matchingKeys = 0;
		foreach ( [ 'type', 'form', 'trigger', 'schedule', 'message' ] as $key )
		{
			if ( isset( $itemNewAlert[ $key ] ) && isset( $itemOldAlert[ $key ] ) &&
			     $itemNewAlert[ $key ] == $itemOldAlert[ $key ] )
			{
				$matchingKeys++;
			}
		}
		if ( $matchingKeys >= 4 )
		{
			$mapCustomAlertsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['asi'] as $i => $itemNewASI )
{
	foreach ( $listOld['asi'] as $j => $itemOldASI )
	{
		if ( in_array( $j, $mapASIsN2O ) )
		{
			continue;
		}
		if ( $itemNewASI['form_name'] == $itemOldASI['form_name'] &&
		     $itemNewASI['event_label'] == $itemOldASI['event_label'] )
		{
			$mapASIsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['survey'] as $i => $itemNewSurvey )
{
	foreach ( $listOld['survey'] as $j => $itemOldSurvey )
	{
		if ( in_array( $j, $mapSurveysN2O ) )
		{
			continue;
		}
		if ( $itemNewSurvey['form_name'] == $itemOldSurvey['form_name'] )
		{
			$mapSurveysN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the alerts to the combined data structure.
foreach ( $listNew['alerts'] as $i => $itemNewAlert )
{
	$itemNewAlert['alert_new'] = ( ! isset( $mapAlertsN2O[ $i ] ) );
	$itemNewAlert['alert_deleted'] = false;
	if ( ! $itemNewAlert['alert_new'] )
	{
		$itemNewAlert['alert_oldvals'] = $listOld['alerts'][ $mapAlertsN2O[ $i ] ];
	}
	$listAlerts[] = $itemNewAlert;
}
foreach ( $listOld['alerts'] as $i => $itemOldAlert )
{
	if ( ! in_array( $i, $mapAlertsN2O ) )
	{
		$itemOldAlert['alert_new'] = false;
		$itemOldAlert['alert_deleted'] = true;
		$listAlerts[] = $itemOldAlert;
	}
}
foreach ( $listNew['custom_alerts'] as $i => $itemNewAlert )
{
	$itemNewAlert['alert_new'] = ( ! isset( $mapCustomAlertsN2O[ $i ] ) );
	$itemNewAlert['alert_deleted'] = false;
	if ( ! $itemNewAlert['alert_new'] )
	{
		$itemNewAlert['alert_oldvals'] = $listOld['custom_alerts'][ $mapCustomAlertsN2O[ $i ] ];
	}
	$listCustomAlerts[] = $itemNewAlert;
}
foreach ( $listOld['custom_alerts'] as $i => $itemOldAlert )
{
	if ( ! in_array( $i, $mapCustomAlertsN2O ) )
	{
		$itemOldAlert['alert_new'] = false;
		$itemOldAlert['alert_deleted'] = true;
		$listCustomAlerts[] = $itemOldAlert;
	}
}
foreach ( $listNew['asi'] as $i => $itemNewASI )
{
	$itemNewASI['asi_new'] = ( ! isset( $mapASIsN2O[ $i ] ) );
	$itemNewASI['asi_deleted'] = false;
	if ( ! $itemNewASI['asi_new'] )
	{
		$itemNewASI['asi_oldvals'] = $listOld['asi'][ $mapASIsN2O[ $i ] ];
	}
	$listASIs[] = $itemNewASI;
}
foreach ( $listOld['asi'] as $i => $itemOldASI )
{
	if ( ! in_array( $i, $mapASIsN2O ) )
	{
		$itemOldASI['asi_new'] = false;
		$itemOldASI['asi_deleted'] = true;
		$listASIs[] = $itemOldASI;
	}
}
foreach ( $listNew['survey'] as $i => $itemNewSurvey )
{
	$itemNewSurvey['survey_new'] = ( ! isset( $mapSurveysN2O[ $i ] ) );
	$itemNewSurvey['survey_deleted'] = false;
	if ( ! $itemNewSurvey['survey_new'] )
	{
		$itemNewSurvey['survey_oldvals'] = $listOld['survey'][ $mapSurveysN2O[ $i ] ];
	}
	$listSurveys[] = $itemNewSurvey;
}
foreach ( $listOld['survey'] as $i => $itemOldSurvey )
{
	if ( ! in_array( $i, $mapSurveysN2O ) )
	{
		$itemOldSurvey['survey_new'] = false;
		$itemOldSurvey['survey_deleted'] = true;
		$listSurveys[] = $itemOldSurvey;
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'alerts' );
$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR .
            ';background:' . REDCapUITweaker::BGC_HDR;

?>
<div class="projhdr"><i class="fas fa-bell"></i> Alerts &amp; Notifications</div>
<?php
if ( $fileLoadError )
{
?>
<p style="margin:15px 0px">
 <span class="yellow">
  <img src="<?php echo APP_PATH_WEBROOT; ?>Resources/images/exclamation_orange.png" alt="">
  An error occurred while loading the file for difference highlighting.
 </span>
</p>
<?php
}
?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSelect = function()
    {
      var vElem = $('.simpAlertsTable')[0]
      var vSel = window.getSelection()
      var vRange = document.createRange()
      vRange.selectNodeContents(vElem)
      vSel.removeAllRanges()
      vSel.addRange(vRange)
    }
    $('#selectTable').click(vFuncSelect)
  })
</script>
<div style="margin-bottom:15px">
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable">Select table</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="simplifiedViewDiffBtn">Difference highlight</button>
</div>
<table class="simpAlertsTable" style="width:95%">
 <tr>
  <th style="<?php echo $tblStyle; ?>">Alert Title</th>
  <th style="<?php echo $tblStyle; ?>">Alert Type</th>
  <th style="<?php echo $tblStyle; ?>">Form Name</th>
  <th style="<?php echo $tblStyle; ?>">Triggered On</th>
  <th style="<?php echo $tblStyle; ?>">Alert Schedule</th>
  <th style="<?php echo $tblStyle; ?>">Message</th>
 </tr>
<?php
foreach ( [ true, false ] as $enabledAlerts )
{
	foreach ( $listAlerts as $infoAlert )
	{
		if ( $infoAlert['email_deleted'] != ( $enabledAlerts ? 0 : 1 ) )
		{
			continue;
		}
		$tblStyle = REDCapUITweaker::STL_CEL;
		if ( $infoAlert['alert_new'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
		}
		elseif ( $infoAlert['alert_deleted'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
		}
		elseif( $infoAlert['email_deleted'] != $infoAlert['alert_oldvals']['email_deleted'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
		}
		$tblStyle .= ( $enabledAlerts ? '' : ';text-decoration:line-through');
		$alertAttachments = $infoAlert['email_attachment_variable'];
		$oldAlertAttachments = '';
		if ( $infoAlert['email_attachments_num'] > 0 )
		{
			$alertAttachments .= ( $alertAttachments == '' ) ? '' : ' + ';
			$alertAttachments .= $infoAlert['email_attachments_num'] . ' ';
			$alertAttachments .= strtolower( $GLOBALS['lang'][
			                                              ( $infoAlert['email_attachments_num'] == 1
			                                                ? 'alerts_169' : 'alerts_128' )] );
		}
		if ( isset( $infoAlert['alert_oldvals'] ) )
		{
			$oldAlertAttachments = $infoAlert['alert_oldvals']['email_attachment_variable'];
			if ( $infoAlert['alert_oldvals']['email_attachments_num'] > 0 )
			{
				$oldAlertAttachments .= ( $oldAlertAttachments == '' ) ? '' : ' + ';
				$oldAlertAttachments .= $infoAlert['alert_oldvals']['email_attachments_num'] . ' ';
				$oldAlertAttachments .= strtolower( $GLOBALS['lang'][
				                         ( $infoAlert['alert_oldvals']['email_attachments_num'] == 1
				                                                 ? 'alerts_169' : 'alerts_128' )] );
			}
		}
		echo " <tr>\n";

		// Output the alert title, type and form (if triggered on form save).
		foreach ( [ 'alert_title', 'alert_type', 'form_name' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
			if ( $key == 'alert_type' )
			{
				echo $module->escapeHTML( $lookupAlertTypes[ $infoAlert[ $key ] ] );
			}
			else
			{
				echo $module->escapeHTML( $infoAlert[ $key == 'form_name' ? 'form_menu_description'
				                                                          : $key ] );
			}
			if ( isset( $infoAlert['alert_oldvals'] ) &&
			     $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				if ( $key == 'alert_type' )
				{
					echo $module->escapeHTML( $lookupAlertTypes[
					                                    $infoAlert['alert_oldvals'][ $key ] ] );
				}
				else
				{
					echo $module->escapeHTML( $infoAlert['alert_oldvals'][ $key == 'form_name'
					                                                       ? 'form_menu_description'
					                                                       : $key ] );
				}
				echo '</span>';
			}
			echo "</td>\n";
		}

		// Output the alert trigger.
		$tblIdentical = true;
		if ( ! $infoAlert['alert_new'] && ! $infoAlert['alert_deleted'] )
		{
			foreach ( [ 'alert_trigger', 'email_incomplete', 'email_repetitive',
			            'email_repetitive_change', 'email_repetitive_change_calcs',
			            'alert_stop_type', 'alert_trigger', 'alert_condition' ] as $key )
			{
				if ( $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
				{
					$tblIdentical = false;
					break;
				}
			}
		}
		echo '  <td style="', $tblStyle,
		     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
		     $lookupTriggered[ $infoAlert['alert_trigger'] ];
		if ( $infoAlert['alert_trigger'] != 'logic' )
		{
			echo $svbr, $module->escapeHTML( $lookupFormStatus[ $infoAlert['email_incomplete'] ] );
		}
		if ( $infoAlert['email_repetitive'] != 1 && $infoAlert['email_repetitive_change'] != 1 &&
		     $infoAlert['email_repetitive_change_calcs'] != 1 )
		{
			echo $svbr,
			     $module->escapeHTML( ucfirst( $lookupLimit[ $infoAlert['alert_stop_type'] ] ) );
		}
		if ( $infoAlert['alert_trigger'] != 'submit' )
		{
			echo $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['asi_012'] ), ':</b>', $svbr,
			     str_replace( "\n", $svbr, $module->escapeHTML( $infoAlert['alert_condition'] ) );
		}
		if ( ! $tblIdentical )
		{
			echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
			echo $lookupTriggered[ $infoAlert['alert_oldvals']['alert_trigger'] ];
			if ( $infoAlert['alert_oldvals']['alert_trigger'] != 'logic' )
			{
				echo $svbr, $module->escapeHTML( $lookupFormStatus[
				                                $infoAlert['alert_oldvals']['email_incomplete'] ] );
			}
			if ( $infoAlert['alert_oldvals']['email_repetitive'] != 1 &&
			     $infoAlert['alert_oldvals']['email_repetitive_change'] != 1 &&
			     $infoAlert['alert_oldvals']['email_repetitive_change_calcs'] != 1 )
			{
				echo $svbr,
				     $module->escapeHTML( ucfirst( $lookupLimit[
				                               $infoAlert['alert_oldvals']['alert_stop_type'] ] ) );
			}
			if ( $infoAlert['alert_oldvals']['alert_trigger'] != 'submit' )
			{
				echo $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['asi_012'] ), ':</b>',
				     $svbr, str_replace( "\n", $svbr,
				            $module->escapeHTML( $infoAlert['alert_oldvals']['alert_condition'] ) );
			}
			echo '</span>';
		}
		echo "</td>\n";

		// Output the alert schedule and recurrence.
		$tblIdentical = true;
		if ( ! $infoAlert['alert_new'] && ! $infoAlert['alert_deleted'] )
		{
			foreach ( [ 'cron_send_email_on', 'cron_send_email_on_next_day_type',
			            'cron_send_email_on_next_time', 'cron_send_email_on_time_lag_days',
			            'cron_send_email_on_time_lag_hours', 'cron_send_email_on_time_lag_minutes',
			            'cron_send_email_on_field_after', 'cron_send_email_on_field',
			            'cron_send_email_on_date', 'email_repetitive', 'email_repetitive_change',
			            'email_repetitive_change_calcs', 'cron_repeat_for', 'cron_repeat_for_units',
			            'cron_repeat_for_max' ] as $key )
			{
				if ( $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
				{
					$tblIdentical = false;
					break;
				}
			}
		}
		echo '  <td style="', $tblStyle,
		     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
		foreach ( [ false, true ] as $oldVals )
		{
			if ( $oldVals )
			{
				if ( $tblIdentical )
				{
					break;
				}
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
			}
			$infoTemp = $oldVals ? $infoAlert['alert_oldvals'] : $infoAlert;
			if ( $infoTemp['cron_send_email_on'] == 'now' )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['global_1540'] );
			}
			elseif ( $infoTemp['cron_send_email_on'] == 'next_occurrence' )
			{
				echo $GLOBALS['lang']['survey_423'], ' ',
				     $module->escapeHTML( $lookupDaysOfWeek[
				                                  $infoTemp['cron_send_email_on_next_day_type'] ] ),
				     ' ', $GLOBALS['lang']['global_15'], ' ',
				     $module->escapeHTML( rtrim( rtrim( $infoTemp['cron_send_email_on_next_time'],
				                                        '0' ), ':' ) );
			}
			elseif ( $infoTemp['cron_send_email_on'] == 'time_lag' )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['alerts_239'] . ' ' .
				                          $infoTemp['cron_send_email_on_time_lag_days'] . ' ' .
				                          $GLOBALS['lang']['survey_426'] . ' ' .
				                          $infoTemp['cron_send_email_on_time_lag_hours'] . ' ' .
				                          $GLOBALS['lang']['survey_427'] . ' ' .
				                          $infoTemp['cron_send_email_on_time_lag_minutes'] . ' ' .
				                          $GLOBALS['lang']['survey_428'] . ' ' .
				                          $lookupBeforeAfter[
				                                     $infoTemp['cron_send_email_on_field_after'] ] .
				                          ' ' . $infoTemp['cron_send_email_on_field'] );
			}
			elseif ( $infoTemp['cron_send_email_on'] == 'date' )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['survey_429'] ), ' ',
				     $module->escapeHTML( rtrim( rtrim( $infoTemp['cron_send_email_on_date'],
				                                        '0' ), ':' ) );
			}
			echo $svbr;
			if ( $infoTemp['email_repetitive'] == 1 )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['alerts_116']
				                          ?? $GLOBALS['lang']['global_1546'] );
			}
			elseif ( $infoTemp['email_repetitive_change'] == 1 ||
			         $infoTemp['email_repetitive_change_calcs'] == 1 )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['alerts_226'] );
				echo ( $infoTemp['email_repetitive_change_calcs'] == 0
				       ? ( ' ' . $module->escapeHTML( $GLOBALS['lang']['alerts_231'] ) ) : '' );
			}
			elseif ( $infoTemp['cron_repeat_for'] != 0 )
			{
				echo $module->escapeHTML( $GLOBALS['lang']['survey_735'] ), ' ',
				     $module->escapeHTML( $infoTemp['cron_repeat_for'] ), ' ',
				     $module->escapeHTML( $lookupUnits[ $infoTemp['cron_repeat_for_units'] ] ), ' ',
				     $module->escapeHTML( $GLOBALS['lang']['alerts_152'] );
				echo ( $infoAlert['cron_repeat_for_max'] == '' ? '' :
				       ( $svbr . $module->escapeHTML( $GLOBALS['lang']['survey_737'] . ' ' .
				                                      $infoTemp['cron_repeat_for_max'] . ' ' .
				                                      $GLOBALS['lang']['alerts_233'] ) ) );
			}
			else
			{
				echo $module->escapeHTML( $GLOBALS['lang']['alerts_61'] );
			}
			if ( $oldVals )
			{
				echo '</span>';
			}
			unset( $infoTemp );
		}
		echo "</td>\n";

		// Output the alert sender, recipients and message.
		$tblIdentical = true;
		if ( ! $infoAlert['alert_new'] && ! $infoAlert['alert_deleted'] )
		{
			foreach ( [ 'email_from_display', 'email_from', 'email_to', 'phone_number_to',
			            'email_cc', 'email_bcc', 'email_subject', 'alert_message',
			            'email_attachment_variable', 'email_attachments_num',
			            'prevent_piping_identifiers' ] as $key )
			{
				if ( $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
				{
					$tblIdentical = false;
					break;
				}
			}
		}
		echo '  <td style="', $tblStyle,
		     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
		foreach ( [ false, true ] as $oldVals )
		{
			if ( $oldVals )
			{
				if ( $tblIdentical )
				{
					break;
				}
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
			}
			$infoTemp = $oldVals ? $infoAlert['alert_oldvals'] : $infoAlert;
			if ( $infoTemp['prevent_piping_identifiers'] )
			{
				echo '<i>', $module->escapeHTML( $GLOBALS['lang']['alerts_12'] ), '</i>', $svbr;
			}
			if ( $infoTemp['email_from'] != '' )
			{
				echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_37'] ), '</b> ',
				     $module->escapeHTML( $infoTemp['email_from_display'] ), ' &lt;',
				     $module->escapeHTML( $infoTemp['email_from'] ), '&gt;', $svbr;
			}
			echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_38'] ), '</b> ',
			     $module->escapeHTML( $infoTemp['email_to'] . $infoTemp['phone_number_to'] );
			if ( $infoTemp['email_cc'] != '' )
			{
				echo $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['alerts_191'] ), '</b> ',
				     $module->escapeHTML( $infoTemp['email_cc'] );
			}
			if ( $infoTemp['email_bcc'] != '' )
			{
				echo $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['alerts_192'] ), '</b> ',
				     $module->escapeHTML( $infoTemp['email_bcc'] );
			}
			if ( $infoTemp['email_subject'] != '' )
			{
				echo $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_103'] ), '</b> ',
				     $module->escapeHTML( $infoTemp['email_subject'] );
			}
			echo $svbr, $svbr, alertsEscape( $infoTemp['alert_message'] );
			if ( $alertAttachments != '' )
			{
				echo $svbr, $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['alerts_128'] ),
				     ':</b> ',
				     $module->escapeHTML( $oldVals ? $oldAlertAttachments : $alertAttachments );
			}
			if ( $oldVals )
			{
				echo '</span>';
			}
			unset( $infoTemp );
		}
		echo "  </td>\n </tr>\n";
	}
	foreach ( $listCustomAlerts as $infoAlert )
	{
		if ( $infoAlert['enabled'] != $enabledAlerts )
		{
			continue;
		}
		$tblStyle = REDCapUITweaker::STL_CEL;
		if ( $infoAlert['alert_new'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
		}
		elseif ( $infoAlert['alert_deleted'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
		}
		elseif( $infoAlert['enabled'] != $infoAlert['alert_oldvals']['enabled'] )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
		}
		$tblStyle .= ( $enabledAlerts ? '' : ';text-decoration:line-through');
		echo " <tr>\n";
		foreach ( [ 'title', 'type', 'form' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     $module->escapeHTML( $infoAlert[ $key ] );
			if ( isset( $infoAlert['alert_oldvals'] ) &&
			     $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
				     $module->escapeHTML( $infoAlert['alert_oldvals'][ $key ] ), '</span>';
			}
			echo "</td>\n";
		}
		foreach ( [ 'trigger', 'schedule', 'message' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     alertsEscape( $infoAlert[ $key ] );
			if ( isset( $infoAlert['alert_oldvals'] ) &&
			     $infoAlert[ $key ] != $infoAlert['alert_oldvals'][ $key ] )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
				     alertsEscape( $infoAlert['alert_oldvals'][ $key ] ), '</span>';
			}
			echo "</td>\n";
		}
		echo " </tr>\n";
	}

	if ( $enabledAlerts )
	{
		foreach ( $listASIs as $infoASI )
		{
			$tblStyle = REDCapUITweaker::STL_CEL;
			if ( $infoASI['asi_new'] )
			{
				$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
			}
			elseif ( $infoASI['asi_deleted'] )
			{
				$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL .
				             ';' . REDCapUITweaker::STL_DEL;
			}
			echo " <tr>\n";

			// Output the ASI title, type and form.
			echo '  <td style="', $tblStyle, '">', "</td>\n";
			echo '  <td style="', $tblStyle, '">',
			     $module->escapeHTML( $GLOBALS['lang']['survey_1239'] ), ' ', $svbr,
			     $module->escapeHTML( $lookupAlertTypes[ $infoASI['delivery_type'] ] ), "</td>\n";
			echo '  <td style="', $tblStyle, '">',
			     $module->escapeHTML( $infoASI['form_menu_description'] ),
			     ( \REDCap::isLongitudinal()
			       ? $svbr . '(' . $module->escapeHTML( $infoASI['event_label'] ) . ')' : '' ),
			     "</td>\n";

			// Output the ASI trigger.
			$tblIdentical = true;
			if ( ! $infoASI['asi_new'] && ! $infoASI['asi_deleted'] )
			{
				foreach ( [ 'condition_surveycomplete_survey_id',
				            'condition_surveycomplete_event_id', 'condition_andor',
				            'condition_logic' ] as $key )
				{
					if ( $infoASI[ $key ] != $infoASI['asi_oldvals'][ $key ] )
					{
						$tblIdentical = false;
						break;
					}
				}
			}
			echo '  <td style="', $tblStyle,
			     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
			foreach ( [ false, true ] as $oldVals )
			{
				if ( $oldVals )
				{
					if ( $tblIdentical )
					{
						break;
					}
					echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				}
				$infoTemp = $oldVals ? $infoASI['asi_oldvals'] : $infoASI;
				if ( $infoTemp['condition_surveycomplete_survey_id'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_419'] ), '</b>',
					     $svbr, $module->escapeHTML( $infoTemp['condition_surveycomplete_form'] );
					if ( \REDCap::isLongitudinal() &&
					     $infoTemp['condition_surveycomplete_event_id'] != '' )
					{
						echo ' (',
						   $module->escapeHTML( $infoTemp['condition_surveycomplete_event_label'] ),
						     ')';
					}
				}
				if ( $infoTemp['condition_surveycomplete_survey_id'] != '' &&
				     $infoTemp['condition_logic'] != '' )
				{
					echo $svbr, '<b>', $module->escapeHTML( $infoTemp['condition_andor'] ), '</b> ';
				}
				if ( $infoASI['condition_logic'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_420'] ), '</b>',
					     $svbr, str_replace( "\n", $svbr,
					                         $module->escapeHTML( $infoTemp['condition_logic'] ) );
				}
				if ( $oldVals )
				{
					echo '</span>';
				}
				unset( $infoTemp );
			}
			echo "</td>\n";

			// Output the ASI schedule and recurrence.
			$tblIdentical = true;
			if ( ! $infoASI['asi_new'] && ! $infoASI['asi_deleted'] )
			{
				foreach ( [ 'condition_send_time_option', 'condition_send_next_day_type',
				            'condition_send_next_time', 'condition_send_time_lag_days',
				            'condition_send_time_lag_hours', 'condition_send_time_lag_minutes',
				            'condition_send_time_lag_field_after', 'condition_send_time_lag_field',
				            'condition_send_time_exact', 'num_recurrence', 'units_recurrence',
				            'max_recurrence', 'reminder_type', 'reminder_nextday_type',
				            'reminder_nexttime' ,'reminder_timelag_days', 'reminder_timelag_hours',
				            'reminder_timelag_minutes', 'reminder_exact_time',
				            'reminder_num' ] as $key )
				{
					if ( $infoASI[ $key ] != $infoASI['asi_oldvals'][ $key ] )
					{
						$tblIdentical = false;
						break;
					}
				}
			}
			echo '  <td style="', $tblStyle,
			     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
			foreach ( [ false, true ] as $oldVals )
			{
				if ( $oldVals )
				{
					if ( $tblIdentical )
					{
						break;
					}
					echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				}
				$infoTemp = $oldVals ? $infoASI['asi_oldvals'] : $infoASI;
				if ( $infoTemp['condition_send_time_option'] == 'IMMEDIATELY' )
				{
					echo $module->escapeHTML( $GLOBALS['lang']['global_1540'] );
				}
				elseif ( $infoTemp['condition_send_time_option'] == 'NEXT_OCCURRENCE' )
				{
					echo $GLOBALS['lang']['survey_423'], ' ',
					     $module->escapeHTML( $lookupDaysOfWeek[
					                                  $infoTemp['condition_send_next_day_type'] ] ),
					     ' ', $GLOBALS['lang']['global_15'], ' ',
					     $module->escapeHTML( rtrim( rtrim( $infoTemp['condition_send_next_time'],
					                                        '0' ), ':' ) );
				}
				elseif ( $infoTemp['condition_send_time_option'] == 'TIME_LAG' )
				{
					echo $module->escapeHTML( $GLOBALS['lang']['alerts_239'] . ' ' .
					                          $infoTemp['condition_send_time_lag_days'] . ' ' .
					                          $GLOBALS['lang']['survey_426'] . ' ' .
					                          $infoTemp['condition_send_time_lag_hours'] . ' ' .
					                          $GLOBALS['lang']['survey_427'] . ' ' .
					                          $infoTemp['condition_send_time_lag_minutes'] . ' ' .
					                          $GLOBALS['lang']['survey_428'] . ' ' .
					                          $lookupBeforeAfter[
					                            $infoTemp['condition_send_time_lag_field_after'] ] .
					                          ' ' . $infoTemp['condition_send_time_lag_field'] );
				}
				elseif ( $infoTemp['condition_send_time_option'] == 'EXACT_TIME' )
				{
					echo $module->escapeHTML( $GLOBALS['lang']['survey_429'] ), ' ',
					     $module->escapeHTML( rtrim( rtrim( $infoTemp['condition_send_time_exact'],
					                                        '0' ), ':' ) );
				}
				echo $svbr;
				if ( $infoTemp['num_recurrence'] != 0 )
				{
					echo $module->escapeHTML( $GLOBALS['lang']['survey_735'] ), ' ',
					     $module->escapeHTML( $infoTemp['num_recurrence'] ), ' ',
					     $module->escapeHTML( $lookupUnits[ $infoASI['units_recurrence'] ] ), ' ',
					     $module->escapeHTML( $GLOBALS['lang']['alerts_152'] );
					echo ( $infoASI['max_recurrence'] == '' ? '' :
					       ( $svbr . $module->escapeHTML( $GLOBALS['lang']['survey_737'] . ' ' .
					                                      $infoTemp['max_recurrence'] . ' ' .
					                                      $GLOBALS['lang']['alerts_233'] ) ) );
				}
				else
				{
					echo $module->escapeHTML( $GLOBALS['lang']['alerts_61'] );
				}
				if ( $infoTemp['reminder_type'] == 'NEXT_OCCURRENCE' )
				{
					echo $svbr, $GLOBALS['lang']['survey_754'], ' (x',
					     $module->escapeHTML( $infoTemp['reminder_num'] ), '): ',
					     $GLOBALS['lang']['survey_423'], ' ',
					     $module->escapeHTML( $lookupDaysOfWeek[
					                                         $infoTemp['reminder_nextday_type'] ] ),
					     ' ', $GLOBALS['lang']['global_15'], ' ',
					     $module->escapeHTML( rtrim( rtrim( $infoTemp['reminder_nexttime'],
					                                        '0' ), ':' ) );
				}
				elseif ( $infoTemp['reminder_type'] == 'TIME_LAG' )
				{
					echo $svbr, $GLOBALS['lang']['survey_754'], ' (x',
					     $module->escapeHTML( $infoTemp['reminder_num'] ), '): ',
					     $module->escapeHTML( $GLOBALS['lang']['alerts_239'] . ' ' .
					                          $infoTemp['reminder_timelag_days'] . ' ' .
					                          $GLOBALS['lang']['survey_426'] . ' ' .
					                          $infoTemp['reminder_timelag_hours'] . ' ' .
					                          $GLOBALS['lang']['survey_427'] . ' ' .
					                          $infoTemp['reminder_timelag_minutes'] . ' ' .
					                          $GLOBALS['lang']['survey_428'] );
				}
				elseif ( $infoTemp['reminder_type'] == 'EXACT_TIME' )
				{
					echo $svbr, $GLOBALS['lang']['survey_754'], ' (x',
					     $module->escapeHTML( $infoTemp['reminder_num'] ), '): ',
					     $module->escapeHTML( $GLOBALS['lang']['survey_429'] ), ' ',
					     $module->escapeHTML( rtrim( rtrim( $infoTemp['reminder_exact_time'],
					                                        '0' ), ':' ) );
				}
				if ( $oldVals )
				{
					echo '</span>';
				}
				unset( $infoTemp );
			}
			echo "</td>\n";

			// Output the ASI sender and message.
			$tblIdentical = true;
			if ( ! $infoASI['asi_new'] && ! $infoASI['asi_deleted'] )
			{
				foreach ( [ 'email_sender_display', 'email_sender', 'email_subject',
				            'email_content' ] as $key )
				{
					if ( $infoASI[ $key ] != $infoASI['asi_oldvals'][ $key ] )
					{
						$tblIdentical = false;
						break;
					}
				}
			}
			echo '  <td style="', $tblStyle,
			     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
			foreach ( [ false, true ] as $oldVals )
			{
				if ( $oldVals )
				{
					if ( $tblIdentical )
					{
						break;
					}
					echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				}
				$infoTemp = $oldVals ? $infoASI['asi_oldvals'] : $infoASI;
				if ( $infoTemp['email_sender'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_37'] ), '</b> ',
					     $module->escapeHTML( $infoTemp['email_sender_display'] ), ' &lt;',
					     $module->escapeHTML( $infoTemp['email_sender'] ), '&gt;', $svbr;
				}
				if ( $infoTemp['email_subject'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_103'] ), '</b> ',
					     $module->escapeHTML( $infoTemp['email_subject'] ), $svbr;
				}
				echo $svbr, alertsEscape( $infoTemp['email_content'] );
				if ( $oldVals )
				{
					echo '</span>';
				}
				unset( $infoTemp );
			}
			echo "  </td>\n </tr>\n";
		}

		foreach ( $listSurveys as $infoSurvey )
		{
			$tblStyle = REDCapUITweaker::STL_CEL;
			if ( $infoSurvey['survey_new'] )
			{
				$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
			}
			elseif ( $infoSurvey['survey_deleted'] )
			{
				$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL .
				             ';' . REDCapUITweaker::STL_DEL;
			}
			echo " <tr>\n";

			// Output the Survey Confirmation title, type and form.
			echo '  <td style="', $tblStyle, '">', "</td>\n";
			echo '  <td style="', $tblStyle, '">',
			     $module->escapeHTML( str_replace( [ '[', ']', '(', ')', '{', '}' ], '',
			                                       $GLOBALS['lang']['design_211'] ) ), ' ', $svbr,
			     $module->escapeHTML( $GLOBALS['lang']['global_33'] ), "</td>\n";
			echo '  <td style="', $tblStyle, '">',
			     $module->escapeHTML( $infoSurvey['form_menu_description'] ), "</td>\n";

			// Output the Survey Confirmation trigger.
			echo '  <td style="', $tblStyle, '">',
			     '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_419'] ), '</b>', $svbr,
			     $module->escapeHTML( $infoSurvey['form_menu_description'] ),
			     "</td>\n";

			// Output the Survey Confirmation schedule.
			echo '  <td style="', $tblStyle, '">',
			     $module->escapeHTML( $GLOBALS['lang']['global_1540'] ), $svbr,
			     $module->escapeHTML( $GLOBALS['lang']['alerts_61'] ),
			     "</td>\n";

			// Output the Survey Confirmation sender and message.
			$tblIdentical = true;
			if ( ! $infoSurvey['survey_new'] && ! $infoSurvey['survey_deleted'] )
			{
				foreach ( [ 'confirmation_email_from_display', 'confirmation_email_from',
				            'confirmation_email_subject', 'confirmation_email_content',
				            'confirmation_email_attach_pdf',
				            'confirmation_email_attachment' ] as $key )
				{
					if ( $infoSurvey[ $key ] != $infoSurvey['survey_oldvals'][ $key ] )
					{
						$tblIdentical = false;
						break;
					}
				}
			}
			echo '  <td style="', $tblStyle,
			     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
			foreach ( [ false, true ] as $oldVals )
			{
				if ( $oldVals )
				{
					if ( $tblIdentical )
					{
						break;
					}
					echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				}
				$infoTemp = $oldVals ? $infoSurvey['survey_oldvals'] : $infoSurvey;
				if ( $infoTemp['confirmation_email_from'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_37'] ), '</b> ',
					   $module->escapeHTML( $infoTemp['confirmation_email_from_display'] ), ' &lt;',
					     $module->escapeHTML( $infoTemp['confirmation_email_from'] ), '&gt;', $svbr;
				}
				if ( $infoTemp['confirmation_email_subject'] != '' )
				{
					echo '<b>', $module->escapeHTML( $GLOBALS['lang']['survey_103'] ), '</b> ',
					     $module->escapeHTML( $infoTemp['confirmation_email_subject'] ), $svbr;
				}
				echo $svbr, alertsEscape( $infoTemp['confirmation_email_content'] );
				if ( $infoTemp['confirmation_email_attach_pdf'] == '1' ||
				     $infoTemp['confirmation_email_attachment'] == '1' )
				{
					echo $svbr, $svbr, '<b>', $module->escapeHTML( $GLOBALS['lang']['alerts_128'] ),
					     ':</b> ';
					if ( $infoTemp['confirmation_email_attachment'] == '1' )
					{
						echo $module->escapeHTML( $GLOBALS['lang']['docs_22'] );
					}
					if ( $infoTemp['confirmation_email_attach_pdf'] == '1' &&
					     $infoTemp['confirmation_email_attachment'] == '1' )
					{
						echo ' + ';
					}
					if ( $infoTemp['confirmation_email_attach_pdf'] == '1' )
					{
						echo $module->escapeHTML( $GLOBALS['lang']['global_59'] );
					}
				}
				if ( $oldVals )
				{
					echo '</span>';
				}
				unset( $infoTemp );
			}
			echo "  </td>\n </tr>\n";
		}
	}
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff( '.sva' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

