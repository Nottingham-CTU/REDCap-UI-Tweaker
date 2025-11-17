<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or enhanced rule H is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('dq-enhanced-calc') )
{
	exit;
}


// Get the user rights
$userRights = $module->getUser()->getRights();
$isAdmin = ( defined('SUPER_USER') && SUPER_USER == 1 );
if ( ! $isAdmin && $userRights['data_quality_execute'] != '1' )
{
	exit;
}
$userDAG = $userRights['group_id'];


// Get the Arms
$queryArms = $module->query( 'SELECT ea.arm_num, ea.arm_name FROM redcap_events_arms ea ' .
                             'WHERE ea.project_id = ?', [ $module->getProjectId() ] );
$listArms = [];
while ( $infoArm = $queryArms->fetch_assoc() )
{
	$listArms[ $infoArm['arm_num'] ] = $infoArm['arm_name'];
}


// Get the Data Access Groups
$listDAGs = \REDCap::getGroupNames();



if ( isset( $_SERVER['HTTP_X_RC_UITWEAK_DQR'] ) )
{
	header( 'Content-Type: application/json' );
	// Get the list of records to check/update.
	if ( $_SERVER['HTTP_X_RC_UITWEAK_DQR'] == 'records' )
	{
		$queryRecords = 'SELECT record FROM redcap_record_list WHERE project_id = ?';
		$paramsRecords = [ $module->getProjectId() ];
		if ( preg_match( '/^[0-9]+$/', $_POST['arm'] ?? '' ) )
		{
			$queryRecords .= ' AND arm = ?';
			$paramsRecords[] = $_POST['arm'];
		}
		if ( preg_match( '/^[0-9]+$/', $_POST['dag'] ?? '' ) )
		{
			$queryRecords .= ' AND dag_id = ?';
			$paramsRecords[] = $_POST['dag'];
		}
		$queryRecords = $module->query( $queryRecords, $paramsRecords );
		echo '[';
		$first = true;
		while ( $infoRecord = $queryRecords->fetch_assoc() )
		{
			if ( $first ) $first = false;
			else echo ',';
			$module->echoText( json_encode( strval( $infoRecord['record'] ) ) );
		}
		echo ']';
	}
	// Check the calculated fields for a record.
	elseif ( $_SERVER['HTTP_X_RC_UITWEAK_DQR'] == 'calcs' )
	{
		if ( $_POST['record'] ?? '' != '' )
		{
			$dq = new \DataQuality();
			$dq->executeRule( 'pd-10', $_POST['record'] );
			$dqResults = [ 'record' => $_POST['record'],
			               'results' => $dq->logicCheckResults['pd-10'],
			               'valuesFixed' => $dq->valuesFixed,
			               'errorMsg' => $dq->errorMsg ];
			if ( $_POST['action'] ?? '' == 'fixCalcs' )
			{
				$_POST['action'] = '';
				$dq->executeRule( 'pd-10', $_POST['record'] );
				$dqResults['results'] = $dq->logicCheckResults['pd-10'];
			}
			foreach ( $dqResults['results'] as $key => $val )
			{
				$dqResults['results'][ $key ] = $val['data_display'];
			}
			$module->echoText( json_encode( $dqResults ) );
		}
	}
	exit;
}



// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<div class="projhdr"><i class="fas fa-clipboard-check"></i> Data Quality</div>
<div id="sub-nav" style="margin:5px 0 20px">
 <ul>
  <li>
   <a href="../DataQuality/index.php?pid=<?php echo intval( $_GET['pid'] ); ?>"
      style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;"><i class="fas fa-search"></i>
     <?php echo $GLOBALS['lang']['dataqueries_193']; ?>
   </a>
  </li>
  <li class="active">
   <a href="<?php echo $module->escapeHTML( $_SERVER['REQUEST_URI'] ); ?>"
      style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;"><i class="fas fa-wand-sparkles"></i>
     <?php echo $GLOBALS['lang']['dataqueries_149']; ?>
   </a>
  </li>
 </ul>
</div>
<p>&nbsp;</p>
<table>
 <tr>
  <td><?php echo $GLOBALS['lang']['global_08']; ?></td>
  <td style="padding-bottom:5px">
   <select id="armselect">
    <option value="*"><?php echo $GLOBALS['lang']['dashboard_12']; ?></option>
<?php
foreach ( $listArms as $armID => $armName )
{
?>
    <option value="<?php echo $module->escapeHTML( $armID ); ?>">
     <?php echo $module->escapeHTML( $armName ), "\n"; ?>
    </option>
<?php
}
?>
   </select>
   <br>
  </td>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['global_78']; ?></td>
  <td style="padding-bottom:5px">
   <select id="dagselect">
<?php
if ( $isAdmin || $userDAG == '' )
{
?>
    <option value="*"><?php echo $GLOBALS['lang']['dataqueries_135']; ?></option>
<?php
}
foreach ( $listDAGs as $groupID => $groupName )
{
	if ( $isAdmin || $userDAG == '' || $userDAG == $groupID )
	{
?>
     <option value="<?php echo $module->escapeHTML( $groupID ); ?>">
      <?php echo $module->escapeHTML( $groupName ), "\n"; ?>
     </option>
<?php
	}
}
?>
   </select>
  </td>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['dataqueries_292']; ?></td>
  <td style="padding-left:5px;padding-bottom:5px">
   <input id="fixcalcs" type="checkbox">
  </td>
 </tr>
 <tr>
  <td></td>
  <td><input id="exec" type="button" value="<?php echo $GLOBALS['lang']['dataqueries_80']; ?>"></td>
 </tr>
</table>
<div id="progress" style="display:none">
 <?php echo $GLOBALS['lang']['data_import_tool_338']; ?> <span id="progress1"></span> /
 <span id="progress2"></span>
 <br>
 <progress id="progressbar" style="min-width:60%"></progress>
</div>
<table id="resultstable" style="display:none;width:97%;margin-top:10px">
 <thead>
  <th style="width:150px"><?php echo $GLOBALS['lang']['global_49']; ?></th>
  <th><?php echo $GLOBALS['lang']['dataqueries_23']; ?></th>
 </thead>
 <tbody>
 </tbody>
</table>
<script type="text/javascript">
$('#resultstable th').css('font-weight', 'bold').css('padding', '5px')
$('#resultstable th').css('border', '1px solid #ccc').css('background', '#ececec')
$('#exec').on('click',function()
{
  $('#exec').prop( 'disabled', true )
  var vFixCalcs = $('#fixcalcs').prop('checked')
  $.ajax( { url : '<?php echo $module->getUrl( 'quality_rules_ecalc.php' ); ?>',
            method : 'POST',
            data : { arm : $('#armselect').val(), dag : $('#dagselect').val() },
            headers : { 'X-RC-UITweak-DQR' : 'records' },
            dataType : 'json'
          } )
   .done( function( vRecordList )
   {
     $('#progress1').text('0')
     $('#progress2').text( vRecordList.length )
     $('#progressbar').attr( 'max', vRecordList.length+1 ).attr( 'value', '0' )
     $('#progress').css('display', '')
     $('#resultstable').css('display', '')
     $('#resultstable tbody').html('')
     var vEven = false
     var vFnExec = function()
     {
       if ( vRecordList.length == 0 )
       {
         $('#progressbar').attr( 'value', $('#progressbar').attr('max') )
         setTimeout( function(){ $('#progress').css('display','none') }, 3000 )
         $('#exec').prop('disabled', false)
         return
       }
       $('#progressbar').attr( 'value', $('#progressbar').attr('max')-vRecordList.length )
       $('#progress1').text( $('#progressbar').attr('max')-vRecordList.length )
       var vRecordID = vRecordList.shift()
       $.ajax( { url : '<?php echo $module->getUrl( 'quality_rules_ecalc.php' ); ?>',
                 method : 'POST',
                 data : { action : vFixCalcs ? 'fixCalcs' : '',
                          record : vRecordID },
                 headers : { 'X-RC-UITweak-DQR' : 'calcs' },
                 dataType : 'json'
               } )
        .done( function( vResponse )
        {
          var vTdRecord = $('<td></td>').text( vResponse.record )
          var vTdStatus = ''
          if ( vFixCalcs )
          {
            vTdStatus += '<span style="color:'
            vTdStatus += vResponse.results.length == 0 ? 'green' : 'red'
            vTdStatus += ';font-weight:bold">' + vResponse.valuesFixed +
                         <?php echo json_encode( ' ' .$GLOBALS['lang']['dataqueries_295'] ); ?> +
                         '</span><br>'
          }
          for ( var i = 0; i < vResponse.results.length; i++ )
          {
            if ( i > 0 )
            {
              vTdStatus += '<hr style="width:49%;margin:6px 1%">'
            }
            vTdStatus += vResponse.results[i]
          }
          vTdStatus = $('<td></td>').html( vTdStatus )
          vTdRecord.css('padding', '5px').css('border', '1px solid #ccc')
          vTdRecord.css('vertical-align', 'top')
          vTdStatus.css('padding', '5px').css('border', '1px solid #ccc')
          if ( vEven )
          {
            vTdRecord.css('background', '#f3f3f3')
            vTdStatus.css('background', '#f3f3f3')
          }
          vEven = ! vEven
          var vTr = $('<tr></tr>').append( vTdRecord ).append( vTdStatus )
          $('#resultstable tbody').append( vTr )
          vFnExec()
        } )
     }
     vFnExec()
   } )
})
</script>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

