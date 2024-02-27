<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or instrument/event mapping simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('instrument-simplified-view') )
{
	exit;
}

// Get the arms, events and instruments.
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

// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'instrument', 'max_events' => $maxEvents, 'arms' => [] ];
$exportArm = [];
$armNum = '';
foreach ( $listInstruments as $infoInstrument )
{
	if ( $infoInstrument['arm_num'] !== $armNum )
	{
		if ( !empty( $exportArm ) )
		{
			$listExport['arms'][] = $exportArm;
		}
		$armNum = $infoInstrument['arm_num'];
		$exportArm = [ 'arm_num' => $armNum, 'arm_name' => $infoInstrument['arm_name'],
		               'events' => [], 'instruments' => [] ];
		foreach ( $listEvents[ $armNum ] as $infoEvent )
		{
			$exportArm['events'][] = [ 'name' => $infoEvent['name'],
			                           'repeat' => ( $infoEvent['repeat'] == 1 ) ];
		}
	}

	$exportArm['instruments'][] = [ 'name' => $infoInstrument['form_name'],
	                                'events' => $infoInstrument['event_names'],
	                                'repeat' => $infoInstrument['repeat'] ];
}
$listExport['arms'][] = $exportArm;

// Handle upload. Get old/new data structures.
$fileLoadError = false;
if ( isset( $_POST['simp_view_diff_mode'] ) && $_POST['simp_view_diff_mode'] == 'export' )
{
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="simplified_view.json"' );
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
		     $fileData['simplified_view'] != 'instrument' )
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
$listData = [];
$listData['max_events'] = ( ( $listNew['max_events'] ?? 1 ) > ( $listOld['max_events'] ?? 1 ) )
                          ? ( $listNew['max_events'] ?? 1 ) : ( $listOld['max_events'] ?? 1 );

// Determine whether the arms match.
$mapArmsN2O = [];
foreach ( $listNew['arms'] as $i => $itemNewArm )
{
	foreach ( $listOld['arms'] as $j => $itemOldArm )
	{
		if ( in_array( $j, $mapArmsN2O ) )
		{
			continue;
		}
		if ( $itemNewArm['arm_name'] == $itemOldArm['arm_name'] )
		{
			$mapArmsN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the arms to the combined data structure.
foreach ( $listNew['arms'] as $i => $itemNewArm )
{
	$itemNewArm['arm_new'] = ( ! isset( $mapArmsN2O[$i] ) );
	$itemNewArm['arm_deleted'] = false;
	if ( ! $itemNewArm['arm_new'] )
	{
		// Determine which events within the arm are new/changed/deleted.
		$itemOldArm = $listOld['arms'][ $mapArmsN2O[$i] ];
		$listOldEvents = $itemOldArm['events'];
		foreach ( $itemNewArm['events'] as $j => $itemNewEvent )
		{
			$itemNewArm['events'][$j]['new'] = true;
			$itemNewArm['events'][$j]['changed'] = false;
			$itemNewArm['events'][$j]['deleted'] = false;
		}
		foreach ( $itemNewArm['events'] as $j => $itemNewEvent )
		{
			foreach ( $listOldEvents as $k => $itemOldEvent )
			{
				if ( $itemNewEvent['name'] == $itemOldEvent['name'] )
				{
					$itemNewArm['events'][$j]['new'] = false;
					if ( $itemNewEvent['repeat'] != $itemOldEvent['repeat'] )
					{
						$itemNewArm['events'][$j]['changed'] = true;
					}
					unset( $listOldEvents[$k] );
					continue 2;
				}
			}
		}
		foreach ( $listOldEvents as $j => $itemOldEvent )
		{
			$itemOldEvent['new'] = false;
			$itemOldEvent['changed'] = false;
			$itemOldEvent['deleted'] = true;
			$itemNewArm['events'][] = $itemOldEvent;
		}
		if ( $listData['max_events'] < count( $itemNewArm['events'] ) )
		{
			$listData['max_events'] = count( $itemNewArm['events'] );
		}
		// Determine which instruments/mappings within the arm are new/changed/deleted.
		$listOldInstruments = $itemOldArm['instruments'];
		foreach ( $itemNewArm['instruments'] as $j => $itemNewInstrument )
		{
			$itemNewArm['instruments'][$j]['new'] = true;
			$itemNewArm['instruments'][$j]['changed'] = [];
			$itemNewArm['instruments'][$j]['deleted'] = false;
		}
		foreach ( $itemNewArm['instruments'] as $j => $itemNewInstrument )
		{
			foreach ( $listOldInstruments as $k => $itemOldInstrument )
			{
				if ( $itemNewInstrument['name'] == $itemOldInstrument['name'] )
				{
					$itemNewArm['instruments'][$j]['new'] = false;
					$itemNewArm['instruments'][$j]['changed'] =
						array_values( array_unique(
							array_merge( array_diff( $itemNewInstrument['events'],
							                         $itemOldInstrument['events'] ),
							             array_diff( $itemOldInstrument['events'],
							                         $itemNewInstrument['events'] ),
							             array_diff( $itemNewInstrument['repeat'],
							                         $itemOldInstrument['repeat'] ),
							             array_diff( $itemOldInstrument['repeat'],
							                         $itemNewInstrument['repeat'] ) )
						) );
					unset( $listOldInstruments[$k] );
					continue 2;
				}
			}
		}
		foreach ( $listOldInstruments as $j => $itemOldInstrument )
		{
			$itemOldInstrument['new'] = false;
			$itemOldInstrument['changed'] = [];
			$itemOldInstrument['deleted'] = true;
			$itemNewArm['instruments'][] = $itemOldInstrument;
		}
	}
	$listData['arms'][] = $itemNewArm;
}
foreach ( $listOld['arms'] as $i => $itemOldArm )
{
	if ( ! in_array( $i, $mapArmsN2O ) )
	{
		$itemOldArm['arm_new'] = false;
		$itemOldArm['arm_deleted'] = true;
		$listData['arms'][] = $itemOldArm;
	}
}



// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'instrument' );

?>
<div class="projhdr"><i class="fas fa-business-time"></i> Instruments and Events</div>
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
<div style="margin-bottom:15px">
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable">Select table</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="simplifiedViewDiffBtn">Difference highlight</button>
</div>
<table class="simpInstTable" style="width:95%">
<?php
foreach ( $listData['arms'] as $infoArm )
{
	$armHdrStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR . ';background:' .
	               ( $infoArm['arm_new'] ? REDCapUITweaker::BGC_HDR_NEW :
	                 ( $infoArm['arm_deleted'] ? REDCapUITweaker::BGC_HDR_DEL . ';' .
	                   REDCapUITweaker::STL_DEL : REDCapUITweaker::BGC_HDR ) );
?>
 <tr>
  <th style="<?php echo $armHdrStyle; ?>" colspan="<?php echo $listData['max_events'] + 1; ?>"><?php
	echo $module->escapeHTML( $GLOBALS['lang']['global_08'] . ' ' . $infoArm['arm_num'] . ': ' .
	                          $infoArm['arm_name'] );
?></th>
 </tr>
 <tr>
  <th style="<?php echo $armHdrStyle; ?>"><?php
	echo $module->escapeHTML( $GLOBALS['lang']['global_35'] ); ?></th>
<?php
	foreach ( $infoArm['events'] as $infoEvent )
	{
		$eventNew = $infoArm['arm_new'] || $infoEvent['new'];
		$eventDel = $infoArm['arm_deleted'] || $infoEvent['deleted'];
		$eventStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR . ';background:' .
		              ( $eventNew ? REDCapUITweaker::BGC_HDR_NEW :
		                ( $eventDel ? REDCapUITweaker::BGC_HDR_DEL . ';' .
		                              REDCapUITweaker::STL_DEL :
		                  ( $infoEvent['changed'] ? REDCapUITweaker::BGC_HDR_CHG
		                                          : REDCapUITweaker::BGC_HDR ) ) );
?>
  <th style="<?php echo $eventStyle; ?>"><?php echo $module->escapeHTML( $infoEvent['name'] ),
                 ( $infoEvent['repeat'] == 1 ? ' &#10227;' : '' ); ?></th>
<?php
	}
	for ( $i = count( $infoArm['events'] ); $i < $listData['max_events']; $i++ )
	{
?>
  <th style="<?php echo $armHdrStyle; ?>"></th>
<?php
	}
?>
 </tr>
<?php
	foreach ( $infoArm['instruments'] as $infoInstrument )
	{
		$instNew = $infoArm['arm_new'] || $infoInstrument['new'];
		$instDel = $infoArm['arm_deleted'] || $infoInstrument['deleted'];
		$instStyle = REDCapUITweaker::STL_CEL . ( $instNew || $instDel ? ';background:' : '' ) .
		             ( $instNew ? REDCapUITweaker::BGC_NEW : '' ) .
		             ( $instDel ? REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL : '' );
?>
 <tr>
  <td style="<?php echo $instStyle; ?>"><?php
                   echo $module->escapeHTML( $infoInstrument['name'] ); ?></td>
<?php
		foreach ( $infoArm['events'] as $infoEvent )
		{
			$instEvNew = $instNew || $infoEvent['new'];
			$instEvDel = $instDel || $infoEvent['deleted'];
			$instEvChg = ! $instEvNew && ! $instEvDel &&
			             in_array( $infoEvent['name'], $infoInstrument['changed'] );
			$instEvStyle = REDCapUITweaker::STL_CEL .
			               ( $instEvNew ? ';background:' . REDCapUITweaker::BGC_NEW : '' ) .
			               ( $instEvDel ? ';background:' . REDCapUITweaker::BGC_DEL . ';' .
			                              REDCapUITweaker::STL_DEL : '' ) .
			               ( $instEvChg ? ';background:' . REDCapUITweaker::BGC_CHG : '' );
?>
  <td style="<?php echo $instEvStyle; ?>"><?php
		echo ( in_array( $infoEvent['name'], $infoInstrument['events'] ) ||
		      ( $infoEvent['deleted'] &&
		        in_array( $infoEvent['name'], $infoInstrument['changed'] ) ) )
		     ? $module->escapeHTML( $GLOBALS['lang']['design_100'] ) : '',
		     in_array( $infoEvent['name'], $infoInstrument['repeat'] ) ? ' &#10227;' : '';
?></td>
<?php
		}
		for ( $i = count( $infoArm['events'] ); $i < $listData['max_events']; $i++ )
		{
?>
  <td style="<?php echo $instStyle; ?>"></td>
<?php
		}
?>
 </tr>
<?php
	}
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff();

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

