<?php

// Exit if not in project context or alerts simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('alerts-simplified-view') )
{
	exit;
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
                               "ORDER BY email_deleted, alert_title",
                               [ $module->getProjectID() ] );
$listEnabledAlerts = [];
$listDisabledAlerts = [];
while ( $infoAlert = $queryAlerts->fetch_assoc() )
{
	if ( $infoAlert['email_deleted'] == 1 )
	{
		$listDisabledAlerts[] = $infoAlert;
	}
	else
	{
		$listEnabledAlerts[] = $infoAlert;
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$listEnabledCustomAlerts = [];
$listDisabledCustomAlerts = [];
foreach ( $module->getCustomAlerts() as $infoAlert )
{
	if ( $infoAlert['enabled'] )
	{
		$listEnabledCustomAlerts[] = $infoAlert;
	}
	else
	{
		$listDisabledCustomAlerts[] = $infoAlert;
	}
}

?>
<div class="projhdr"><i class="fas fa-bell"></i> Alerts &amp; Notifications</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpAlertsTable{width:95%} .simpAlertsTable th, ' +
                   '.simpAlertsTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpAlertsTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
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
<div>
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable" style="margin-bottom:15px">Select table</button>
</div>
<table class="simpAlertsTable">
 <tr>
  <th>Alert Title</th>
  <th>Alert Type</th>
  <th>Form Name</th>
  <th>Triggered On</th>
  <th>Alert Schedule</th>
  <th>Message</th>
 </tr>
<?php
foreach ( [ true, false ] as $enabledAlerts )
{
	$listAlerts = $enabledAlerts ? $listEnabledAlerts : $listDisabledAlerts;
	foreach ( $listAlerts as $infoAlert )
	{
		$alertAttachments = $infoAlert['email_attachment_variable'];
		$numAttachments = ( $infoAlert['email_attachment1'] == '' ? 0 : 1 ) +
		                  ( $infoAlert['email_attachment2'] == '' ? 0 : 1 ) +
		                  ( $infoAlert['email_attachment3'] == '' ? 0 : 1 ) +
		                  ( $infoAlert['email_attachment4'] == '' ? 0 : 1 ) +
		                  ( $infoAlert['email_attachment5'] == '' ? 0 : 1 );
		if ( $numAttachments > 0 )
		{
			$alertAttachments .= ( $alertAttachments == '' ) ? '' : ' + ';
			$alertAttachments .= $numAttachments . ' ';
			$alertAttachments .= strtolower( $GLOBALS['lang'][
			                              ( $numAttachments == 1 ? 'alerts_169' : 'alerts_128' )] );
		}
?>
 <tr<?php echo $infoAlert['email_deleted'] == 1 ? ' style="text-decoration:line-through"' : ''; ?>>
<?php
		// Output the alert title, type and form (if triggered on form save).
?>
  <td><?php echo htmlspecialchars( $infoAlert['alert_title'] ); ?></td>
  <td><?php echo htmlspecialchars( $lookupAlertTypes[ $infoAlert['alert_type'] ] ); ?></td>
  <td><?php echo htmlspecialchars( $infoAlert['form_menu_description'] ); ?></td>
<?php
		// Output the alert trigger.
?>
  <td>
   <?php echo $lookupTriggered[ $infoAlert['alert_trigger'] ], "\n"; ?>
<?php
		if ( $infoAlert['alert_trigger'] != 'logic' )
		{
			echo "   <br>\n   ",
			     htmlspecialchars( $lookupFormStatus[ $infoAlert['email_incomplete'] ] ), "\n";
		}
		if ( $infoAlert['email_repetitive'] != 1 && $infoAlert['email_repetitive_change'] != 1 &&
		     $infoAlert['email_repetitive_change_calcs'] != 1 )
		{
			echo "   <br>\n   ",
			     htmlspecialchars( ucfirst( $lookupLimit[ $infoAlert['alert_stop_type'] ] ) ), "\n";
		}
		if ( $infoAlert['alert_trigger'] != 'submit' )
		{
			echo "   <br>\n   <b>Conditional logic:</b>\n   <br>\n   ",
			     htmlspecialchars( $infoAlert['alert_condition'] ), "\n";
		}
?>
  </td>
<?php
		// Output the alert schedule and recurrence.
?>
  <td>
<?php
		if ( $infoAlert['cron_send_email_on'] == 'now' )
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['alerts_110']
			                                ?? $GLOBALS['lang']['global_1540'] ), "\n";
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'next_occurrence' )
		{
			echo '   ', $GLOBALS['lang']['survey_423'], ' ',
			     htmlspecialchars( $lookupDaysOfWeek[ $infoAlert['cron_send_email_on_next_day_type'] ] ),
			     ' ', $GLOBALS['lang']['global_15'], ' ',
			     rtrim( rtrim( $infoAlert['cron_send_email_on_next_time'], '0' ), ':' ), "\n";
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'time_lag' )
		{
			echo '   ', $GLOBALS['lang']['alerts_239'], ' ',
			     $infoAlert['cron_send_email_on_time_lag_days'], ' ', $GLOBALS['lang']['survey_426'],
			     ' ', $infoAlert['cron_send_email_on_time_lag_hours'], ' ',
			     $GLOBALS['lang']['survey_427'], ' ', $infoAlert['cron_send_email_on_time_lag_minutes'],
			     ' ', $GLOBALS['lang']['survey_428'], ' ',
			     $lookupBeforeAfter[ $infoAlert['cron_send_email_on_field_after'] ], ' ',
			     $infoAlert['cron_send_email_on_field'], "\n";
		}
		elseif ( $infoAlert['cron_send_email_on'] == 'date' )
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['survey_429'] ), ' ',
			     rtrim( rtrim( $infoAlert['cron_send_email_on_date'], '0' ), ':' ), "\n";
		}
?>
   <br>
<?php
		if ( $infoAlert['email_repetitive'] == 1 )
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['alerts_116']
			                              ?? $GLOBALS['lang']['global_1546'] ), "\n";
		}
		elseif ( $infoAlert['email_repetitive_change'] == 1 ||
		         $infoAlert['email_repetitive_change_calcs'] == 1 )
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['alerts_226'] ), "\n";
			echo ( $infoAlert['email_repetitive_change_calcs'] == 0
			       ? ( '   ' . htmlspecialchars( $GLOBALS['lang']['alerts_231'] ) . "\n" ) : '' );
		}
		elseif ( $infoAlert['cron_repeat_for'] != 0 )
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['survey_735'] ), ' ',
			     $infoAlert['cron_repeat_for'], ' ',
			     htmlspecialchars( $lookupUnits[ $infoAlert['cron_repeat_for_units'] ] ), ' ',
			     htmlspecialchars( $GLOBALS['lang']['alerts_152'] ), "\n";
			echo ( $infoAlert['cron_repeat_for_max'] == '' ? '' :
			       ( "   <br>\n   " . htmlspecialchars( $GLOBALS['lang']['survey_737'] ) .
			         ' ' . $infoAlert['cron_repeat_for_max'] . ' ' .
			         htmlspecialchars( $GLOBALS['lang']['alerts_233'] ) . "\n" ) );
		}
		else
		{
			echo '   ', htmlspecialchars( $GLOBALS['lang']['alerts_61'] ), "\n";
		}
?>
  </td>
<?php
		// Output the alert sender, recipients and message.
?>
  <td>
   <p style="margin-top:0px">
<?php
		if ( $infoAlert['email_from'] != '' )
		{
?>
    <b><?php echo htmlspecialchars( $GLOBALS['lang']['global_37'] ); ?></b>
    <?php echo htmlspecialchars( $infoAlert['email_from_display'] ); ?>
    &lt;<?php echo htmlspecialchars( $infoAlert['email_from'] ); ?>&gt;
    <br>
<?php
		}
?>
    <b><?php echo htmlspecialchars( $GLOBALS['lang']['global_38'] ); ?></b>
    <?php echo htmlspecialchars( $infoAlert['email_to'] . $infoAlert['phone_number_to'] ), "\n"; ?>
<?php
		if ( $infoAlert['email_cc'] != '' )
		{
?>
    <br>
    <b><?php echo htmlspecialchars( $GLOBALS['lang']['alerts_191'] ); ?></b>
    <?php echo htmlspecialchars( $infoAlert['email_cc'] ), "\n"; ?>
<?php
		}
		if ( $infoAlert['email_bcc'] != '' )
		{
?>
    <br>
    <b><?php echo htmlspecialchars( $GLOBALS['lang']['alerts_192'] ); ?></b>
    <?php echo htmlspecialchars( $infoAlert['email_bcc'] ), "\n"; ?>
<?php
		}
?>
   </p>
   <p>
    <?php echo $infoAlert['alert_message'], "\n"; ?>
   </p>
<?php
		if ( $alertAttachments != '' )
		{
?>
   <p>
    <b><?php echo htmlspecialchars( $GLOBALS['lang']['alerts_128'] ); ?>:</b>
    <?php echo htmlspecialchars( $alertAttachments ), "\n"; ?>
   </p>
<?php
		}
?>
  </td>
 </tr>
<?php
	}
	$listAlerts = $enabledAlerts ? $listEnabledCustomAlerts : $listDisabledCustomAlerts;
	foreach ( $listAlerts as $infoAlert )
	{
?>
 <tr<?php echo $enabledAlerts ? '' : ' style="text-decoration:line-through"'; ?>>
  <td><?php echo htmlspecialchars( $infoAlert['title'] ); ?></td>
  <td><?php echo htmlspecialchars( $infoAlert['type'] ); ?></td>
  <td><?php echo htmlspecialchars( $infoAlert['form'] ); ?></td>
  <td><?php echo $infoAlert['trigger']; ?></td>
  <td><?php echo $infoAlert['schedule']; ?></td>
  <td><?php echo $infoAlert['message']; ?></td>
 </tr>
<?php
	}
}
?>
</table>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

