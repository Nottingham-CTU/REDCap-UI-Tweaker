<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or alerts simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('alerts-simplified-view') )
{
	exit;
}

function alertsEscape( $text )
{
	$text = str_replace( [ "\r\n", "\r" ], "\n", $text );
	$text = preg_replace( "/<\\/p>( |\n|\t)*<p>/", '<br><br>', $text );
	$text = str_replace( "\n", '<br>', $text );
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
	$text = str_replace( [ '&lt;', '&gt;', '&quot;', '&#039;', '&amp;' ], '', $text );
	$text = str_replace( [ '&amp;lt;', '&amp;gt;', '&amp;quot;', '&amp;#039;', '&amp;amp;' ],
	                     [ '&lt;', '&gt;', '&quot;', '&#039;', '&amp;' ], $text );
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
                               "IS NOT NULL LIMIT 1 ) form_menu_description " .
                               "FROM redcap_alerts WHERE project_id = ? " .
                               "ORDER BY email_deleted, alert_order",
                               [ $module->getProjectID() ] );


// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'alerts', 'alerts' => [], 'custom_alerts' => [] ];

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
	$listExport['alerts'][] = $infoAlert;
}

foreach ( $module->getCustomAlerts() as $infoAlert )
{
	$listExport['custom_alerts'][] = $infoAlert;
}

// Handle upload. Get old/new data structures.
$fileLoadError = false;
if ( isset( $_POST['simp_view_diff_mode'] ) && $_POST['simp_view_diff_mode'] == 'export' )
{
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="simplified_view.json"' );
	echo json_encode( $listExport );
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
$listCustomAlerts = [];

// Determine whether the alerts match.
$mapAlertsN2O = [];
$mapCustomAlertsN2O = [];
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
		if ( $itemNewAlert['alert_title'] == $itemOldAlert['alert_title'] )
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
		if ( $itemNewAlert['title'] == $itemOldAlert['title'] )
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
		$tblStyle .= ( $enabledAlerts ? '' : ';text-decoration:line-through');
		$alertAttachments = $infoAlert['email_attachment_variable'];
		if ( $infoAlert['email_attachments_num'] > 0 )
		{
			$alertAttachments .= ( $alertAttachments == '' ) ? '' : ' + ';
			$alertAttachments .= $infoAlert['email_attachments_num'] . ' ';
			$alertAttachments .= strtolower( $GLOBALS['lang'][
			                                              ( $infoAlert['email_attachments_num'] == 1
			                                                ? 'alerts_169' : 'alerts_128' )] );
		}
		echo " <tr>\n";

		// Output the alert title, type and form (if triggered on form save).
		foreach ( [ 'alert_title', 'alert_type', 'form_name' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     $module->escapeHTML( $infoAlert[ $key == 'form_name' ? 'form_menu_description'
			                                                          : $key ] ),
			     "</td>\n";
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
			echo "<br>",
			     $module->escapeHTML( $lookupFormStatus[ $infoAlert['email_incomplete'] ] );
		}
		if ( $infoAlert['email_repetitive'] != 1 && $infoAlert['email_repetitive_change'] != 1 &&
		     $infoAlert['email_repetitive_change_calcs'] != 1 )
		{
			echo "<br>",
			     $module->escapeHTML( ucfirst( $lookupLimit[ $infoAlert['alert_stop_type'] ] ) );
		}
		if ( $infoAlert['alert_trigger'] != 'submit' )
		{
			echo "<br><b>Conditional logic:</b><br>",
			     $module->escapeHTML( $infoAlert['alert_condition'] );
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
		if ( $infoAlert['cron_send_email_on'] == 'now' )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['alerts_110']
			                          ?? $GLOBALS['lang']['global_1540'] );
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'next_occurrence' )
		{
			echo $GLOBALS['lang']['survey_423'], ' ',
			     $module->escapeHTML( $lookupDaysOfWeek[ $infoAlert['cron_send_email_on_next_day_type'] ] ),
			     ' ', $GLOBALS['lang']['global_15'], ' ',
			     $module->escapeHTML( rtrim( rtrim( $infoAlert['cron_send_email_on_next_time'],
			                                        '0' ), ':' ) );
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'time_lag' )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['alerts_239'] . ' ' .
			                          $infoAlert['cron_send_email_on_time_lag_days'] . ' ' .
			                          $GLOBALS['lang']['survey_426'] . ' ' .
			                          $infoAlert['cron_send_email_on_time_lag_hours'] . ' ' .
			                          $GLOBALS['lang']['survey_427'] . ' ' .
			                          $infoAlert['cron_send_email_on_time_lag_minutes'] . ' ' .
			                          $GLOBALS['lang']['survey_428'] . ' ' .
			                          $lookupBeforeAfter[ $infoAlert['cron_send_email_on_field_after'] ] .
			                          ' ' . $infoAlert['cron_send_email_on_field'] );
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'date' )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['survey_429'] ), ' ',
			     $module->escapeHTML( rtrim( rtrim( $infoAlert['cron_send_email_on_date'],
			                                        '0' ), ':' ) );
		}
		echo '<br>';
		if ( $infoAlert['email_repetitive'] == 1 )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['alerts_116']
			                          ?? $GLOBALS['lang']['global_1546'] );
		}
		elseif ( $infoAlert['email_repetitive_change'] == 1 ||
		         $infoAlert['email_repetitive_change_calcs'] == 1 )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['alerts_226'] );
			echo ( $infoAlert['email_repetitive_change_calcs'] == 0
			       ? ( ' ' . $module->escapeHTML( $GLOBALS['lang']['alerts_231'] ) ) : '' );
		}
		elseif ( $infoAlert['cron_repeat_for'] != 0 )
		{
			echo $module->escapeHTML( $GLOBALS['lang']['survey_735'] ), ' ',
			     $module->escapeHTML( $infoAlert['cron_repeat_for'] ), ' ',
			     $module->escapeHTML( $lookupUnits[ $infoAlert['cron_repeat_for_units'] ] ), ' ',
			     $module->escapeHTML( $GLOBALS['lang']['alerts_152'] );
			echo ( $infoAlert['cron_repeat_for_max'] == '' ? '' :
			       ( "<br>" .
			         $module->escapeHTML( $GLOBALS['lang']['survey_737'] . ' ' .
			                              $infoAlert['cron_repeat_for_max'] . ' ' .
			                              $GLOBALS['lang']['alerts_233'] ) ) );
		}
		else
		{
			echo $module->escapeHTML( $GLOBALS['lang']['alerts_61'] );
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
		if ( $infoAlert['prevent_piping_identifiers'] )
		{
			echo '<i>', $module->escapeHTML( $GLOBALS['lang']['alerts_12'] ), '</i><br>';
		}
		if ( $infoAlert['email_from'] != '' )
		{
			echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_37'] ), '</b> ',
			     $module->escapeHTML( $infoAlert['email_from_display'] ), ' &lt;',
			     $module->escapeHTML( $infoAlert['email_from'] ), '&gt;<br>';
		}
		echo '<b>', $module->escapeHTML( $GLOBALS['lang']['global_38'] ), '</b> ',
		     $module->escapeHTML( $infoAlert['email_to'] . $infoAlert['phone_number_to'] );
		if ( $infoAlert['email_cc'] != '' )
		{
			echo '<br><b>', $module->escapeHTML( $GLOBALS['lang']['alerts_191'] ), '</b> ',
			     $module->escapeHTML( $infoAlert['email_cc'] );
		}
		if ( $infoAlert['email_bcc'] != '' )
		{
			echo '<br><b>', $module->escapeHTML( $GLOBALS['lang']['alerts_192'] ), '</b> ',
			     $module->escapeHTML( $infoAlert['email_bcc'] );
		}
		if ( $infoAlert['email_subject'] != '' )
		{
			echo '<br><b>', $module->escapeHTML( $GLOBALS['lang']['survey_103'] ), '</b> ',
			     $module->escapeHTML( $infoAlert['email_subject'] );
		}
		echo '<br><br>', alertsEscape( $infoAlert['alert_message'] );
		if ( $alertAttachments != '' )
		{
			echo '<br><br><b>', $module->escapeHTML( $GLOBALS['lang']['alerts_128'] ), ':</b> ',
			     $module->escapeHTML( $alertAttachments );
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
		$tblStyle .= ( $enabledAlerts ? '' : ';text-decoration:line-through');
		echo " <tr>\n", '  <td style="', $tblStyle, '">',
		     $module->escapeHTML( $infoAlert['title'] ), "</td>\n";
		foreach ( [ 'type', 'form' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     $module->escapeHTML( $infoAlert[ $key ] ), "</td>\n";
		}
		foreach ( [ 'trigger', 'schedule', 'message' ] as $key )
		{
			echo '  <td style="', $tblStyle,
			     ( $infoAlert['alert_new'] || $infoAlert['alert_deleted'] ||
			       $infoAlert[ $key ] == $infoAlert['alert_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     alertsEscape( $infoAlert[ $key ] ), "</td>\n";
		}
		echo " </tr>\n";
	}
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff();

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

