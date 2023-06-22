<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or instrument/event mapping simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('instrument-simplified-view') )
{
	exit;
}

$queryEvents =
	$module->query( 'SELECT a.arm_num, m.descrip AS event_name, if(r.event_id IS NULL, 0, 1) ' .
	                'AS `repeat` FROM redcap_events_metadata m JOIN redcap_events_arms a ' .
	                'ON m.arm_id = a.arm_id LEFT JOIN redcap_events_repeat r ' .
	                'ON m.event_id = r.event_id AND form_name IS NULL WHERE a.project_id = ? ' .
	                'ORDER BY a.arm_num, m.day_offset', [ $module->getProjectID() ] );
$listEvents = [];
$maxEvents = 0;
while ( $infoEvent = $queryEvents->fetch_assoc() )
{
	if ( ! isset( $listEvents[ $infoEvent['arm_num'] ] ) )
	{
		$listEvents[ $infoEvent['arm_num'] ] = [];
	}
	$listEvents[ $infoEvent['arm_num'] ][] = [ 'name' => $infoEvent['event_name'],
	                                           'repeat' => $infoEvent['repeat'] ];
	if ( $maxEvents < count( $listEvents[ $infoEvent['arm_num'] ] ) )
	{
		$maxEvents = count( $listEvents[ $infoEvent['arm_num'] ] );
	}
}

$queryInstruments =
	$module->query( 'SELECT a.arm_num, a.arm_name, rm.form_menu_description AS form_name, ' .
	                'm.descrip AS event_names, if(r.event_id IS NULL, 0, 1) AS `repeat` ' .
	                'FROM redcap_events_forms f JOIN redcap_events_metadata m ' .
	                'ON f.event_id = m.event_id JOIN redcap_events_arms a ON m.arm_id = a.arm_id ' .
	                'JOIN redcap_metadata rm ON a.project_id = rm.project_id AND ' .
	                'f.form_name = rm.form_name AND rm.form_menu_description IS NOT NULL ' .
	                'LEFT JOIN redcap_events_repeat r ON m.event_id = r.event_id AND ' .
	                'r.form_name = f.form_name WHERE a.project_id = ? ' .
	                'ORDER BY a.arm_num, rm.field_order, m.day_offset',
	                [ $module->getProjectID() ] );
$listInstruments = [];
$lastInstrument = '';
while ( $infoInstrument = $queryInstruments->fetch_assoc() )
{
	if ( $infoInstrument['arm_num'] . '/' . $infoInstrument['form_name'] == $lastInstrument )
	{
		$listInstruments[ array_key_last( $listInstruments ) ]['event_names'][] =
				$infoInstrument['event_names'];
		if ( $infoInstrument['repeat'] == 1 )
		{
			$listInstruments[ array_key_last( $listInstruments ) ]['repeat'][] =
					$infoInstrument['event_names'];
		}
	}
	else
	{
		$infoInstrument['event_names'] = [ $infoInstrument['event_names'] ];
		$infoInstrument['repeat'] =
				( $infoInstrument['repeat'] == 1 ) ? $infoInstrument['event_names'] : [];
		$listInstruments[] = $infoInstrument;
	}
	$lastInstrument = $infoInstrument['arm_num'] . '/' . $infoInstrument['form_name'];
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<div class="projhdr"><i class="fas fa-business-time"></i> Instruments and Events</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpInstTable{width:95%} .simpInstTable th, ' +
                   '.simpInstTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpInstTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
    var vFuncSelect = function()
    {
      var vElem = $('.simpInstTable')[0]
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
<table class="simpInstTable">
<?php

$armNum = '';
foreach ( $listInstruments as $infoInstrument )
{
	if ( $infoInstrument['arm_num'] !== $armNum )
	{
		$armNum = $infoInstrument['arm_num'];

?>
 <tr>
  <th colspan="<?php echo $maxEvents + 1;?>"><?php
		echo $module->escapeHTML( $GLOBALS['lang']['global_08'] . ' ' . $armNum . ': ' .
		                          $infoInstrument['arm_name'] );
?></th>
 </tr>
 <tr>
  <th><?php echo $module->escapeHTML( $GLOBALS['lang']['global_35'] ); ?></th>
<?php

		foreach ( $listEvents[ $armNum ] as $infoEvent )
		{
?>
  <th><?php echo $module->escapeHTML( $infoEvent['name'] ),
                 ( $infoEvent['repeat'] == 1 ? ' &#10227;' : '' ); ?></th>
<?php
		}
		for ( $i = count( $listEvents[ $armNum ] ); $i < $maxEvents; $i++ )
		{
?>
  <th></th>
<?php
		}

?>
 </tr>
<?php

	}

?>
 <tr>
  <td><?php echo $module->escapeHTML( $infoInstrument['form_name'] ); ?></td>
<?php

		foreach ( $listEvents[ $armNum ] as $infoEvent )
		{
?>
  <td><?php
			echo in_array( $infoEvent['name'], $infoInstrument['event_names'] )
			     ? $module->escapeHTML( $GLOBALS['lang']['design_100'] ) : '',
			     in_array( $infoEvent['name'], $infoInstrument['repeat'] ) ? ' &#10227;' : '';
?></td>
<?php
		}
		for ( $i = count( $listEvents[ $armNum ] ); $i < $maxEvents; $i++ )
		{
?>
  <td></td>
<?php
		}

?>
 </tr>
<?php

}

?>
</table>
<?php
$module->ffFormattingFix( '.simpInstTable' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

