<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or data quality rules simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('quality-rules-simplified-view') )
{
	exit;
}

$lookupYN = [ '1' => $GLOBALS['lang']['design_100'], '0' => $GLOBALS['lang']['design_99'] ];


$queryRules = $module->query( 'SELECT rule_id, rule_name, rule_logic, real_time_execute ' .
                              'FROM redcap_data_quality_rules WHERE project_id = ? ' .
                              'ORDER BY rule_order', [ $module->getProjectID() ] );
$listRules = [];
while ( $infoRule = $queryRules->fetch_assoc() )
{
	$listRules[] = $infoRule;
}

// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'quality_rules', 'rules' => $listRules ];

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
		     $fileData['simplified_view'] != 'quality_rules' )
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


// Create the combined data structure and determine whether the rules match.
$listRules = [];
$mapRulesN2O = [];
foreach ( $listNew['rules'] as $i => $itemNewRule )
{
	foreach ( $listOld['rules'] as $j => $itemOldRule )
	{
		if ( $itemNewRule['rule_name'] == $itemOldRule['rule_name'] &&
		     $itemNewRule['rule_logic'] == $itemOldRule['rule_logic'] )
		{
			$mapRulesN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['rules'] as $i => $itemNewRule )
{
	if ( isset( $mapRulesN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['rules'] as $j => $itemOldRule )
	{
		if ( ! in_array( $j, $mapRulesN2O ) &&
		     $itemNewRule['rule_logic'] == $itemOldRule['rule_logic'] )
		{
			$mapRulesN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['rules'] as $i => $itemNewRule )
{
	if ( isset( $mapRulesN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['rules'] as $j => $itemOldRule )
	{
		if ( ! in_array( $j, $mapRulesN2O ) &&
		     $itemNewRule['rule_name'] == $itemOldRule['rule_name'] )
		{
			$mapRulesN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the rules to the combined data structure.
foreach ( $listNew['rules'] as $i => $itemNewRule )
{
	$itemNewRule['new'] = ! isset( $mapRulesN2O[ $i ] );
	$itemNewRule['changed_name'] = false;
	$itemNewRule['changed_logic'] = false;
	$itemNewRule['changed_rte'] = false;
	$itemNewRule['deleted'] = false;
	$itemNewRule['old_rule_id'] = '';
	if ( ! $itemNewRule['new'] )
	{
		$itemOldRule = $listOld['rules'][ $mapRulesN2O[ $i ] ];
		if ( $itemNewRule['rule_name'] != $itemOldRule['rule_name'] )
		{
			$itemNewRule['changed_name'] = true;
		}
		if ( $itemNewRule['rule_logic'] != $itemOldRule['rule_logic'] )
		{
			$itemNewRule['changed_logic'] = true;
		}
		if ( $itemNewRule['real_time_execute'] != $itemOldRule['real_time_execute'] )
		{
			$itemNewRule['changed_rte'] = true;
		}
		$itemNewRule['old_rule_id'] = $itemOldRule['rule_id'];
	}
	$listRules[] = $itemNewRule;
}
foreach ( $listOld['rules'] as $i => $itemOldRule )
{
	if ( ! in_array( $i, $mapRulesN2O ) )
	{
		$itemOldRule['new'] = false;
		$itemOldRule['changed_name'] = false;
		$itemOldRule['changed_logic'] = false;
		$itemOldRule['changed_rte'] = false;
		$itemOldRule['deleted'] = true;
		$itemOldRule['old_rule_id'] = $itemOldRule['rule_id'];
		$itemOldRule['rule_id'] = '';
		$listRules[] = $itemOldRule;
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'quality_rules' );
$tblHdrStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR .
               ';background:' . REDCapUITweaker::BGC_HDR;

?>
<div class="projhdr"><i class="fas fa-clipboard-check"></i> Data Quality</div>
<script type="text/javascript">
  $(function()
  {
    var vFuncSelect = function()
    {
      var vElem = $('.simpRulesTable')[0]
      var vSel = window.getSelection()
      var vRange = document.createRange()
      vRange.selectNodeContents(vElem)
      vSel.removeAllRanges()
      vSel.addRange(vRange)
    }
    $('#selectTable').click(vFuncSelect)
    var vFuncShowHideID = function()
    {
      var vElems = $('.colID')
      if ( vElems.first().css('display') == 'none' )
      {
        vElems.css('display', '')
      }
      else
      {
        vElems.css('display', 'none')
      }
    }
    $('#showHideID').click(vFuncShowHideID)
    vFuncShowHideID()
  })
</script>
<div style="margin-bottom:15px">
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideID">Show/Hide ID</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable">Select table</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="simplifiedViewDiffBtn">Difference highlight</button>
</div>
<table class="simpRulesTable" style="width:95%">
 <tr>
  <th style="<?php echo $tblHdrStyle; ?>"
      class="colID"><?php echo $GLOBALS['lang']['multilang_73'] ?? 'ID'; ?></th>
  <th style="<?php echo $tblHdrStyle; ?>"><?php echo $GLOBALS['lang']['dataqueries_15']; ?></th>
  <th style="<?php echo $tblHdrStyle; ?>"><?php echo $GLOBALS['lang']['dataqueries_16']; ?></th>
  <th style="<?php echo $tblHdrStyle; ?>"><?php echo $GLOBALS['lang']['dataqueries_123']; ?></th>
 </tr>
<?php
if ( empty( $listRules ) )
{
?>
 <tr>
  <td style="<?php echo REDCapUITweaker::STL_CEL; ?>" class="colID"></td>
  <td style="<?php echo REDCapUITweaker::STL_CEL; ?>" colspan="3">
   0 <?php echo $GLOBALS['lang']['dataqueries_81']; ?>.
  </td>
 </tr>
<?php
}
foreach ( $listRules as $infoRule )
{
	$tblIDStyle = REDCapUITweaker::STL_CEL .
	              ( $infoRule['new'] ? ';background:' . REDCapUITweaker::BGC_NEW : '' ) .
	              ( $infoRule['deleted'] ? ';background:' . REDCapUITweaker::BGC_DEL . ';' .
	                                       REDCapUITweaker::STL_DEL : '' );
	if ( $infoRule['new'] || $infoRule['deleted'] )
	{
		$tblNameStyle = $tblIDStyle;
		$tblLogicStyle = $tblIDStyle;
		$tblRTEStyle = $tblIDStyle;
	}
	else
	{
		$tblNameStyle = REDCapUITweaker::STL_CEL .
		             ( $infoRule['changed_name'] ? ';background:' . REDCapUITweaker::BGC_CHG : '' );
		$tblLogicStyle = REDCapUITweaker::STL_CEL .
		            ( $infoRule['changed_logic'] ? ';background:' . REDCapUITweaker::BGC_CHG : '' );
		$tblRTEStyle = REDCapUITweaker::STL_CEL .
		              ( $infoRule['changed_rte'] ? ';background:' . REDCapUITweaker::BGC_CHG : '' );
	}
?>
 <tr>
  <td style="<?php echo $tblIDStyle; ?>" class="colID">
   <?php echo ( $infoRule['rule_id'] == '' ? '' : intval( $infoRule['rule_id'] ) ), ' ',
              ( $infoRule['old_rule_id'] == '' ||
                $infoRule['rule_id'] == $infoRule['old_rule_id'] ? '' :
                 ( '(' . intval( $infoRule['old_rule_id'] ) . ')' ) ), "\n"; ?>
  </td>
  <td style="<?php echo $tblNameStyle; ?>">
   <?php echo $module->escapeHTML( $infoRule['rule_name'] ), "\n"; ?>
  </td>
  <td style="<?php echo $tblLogicStyle; ?>">
   <?php echo $module->escapeHTML( $infoRule['rule_logic'] ), "\n"; ?>
  </td>
  <td style="<?php echo $tblRTEStyle; ?>">
   <?php echo $lookupYN[ $infoRule['real_time_execute'] ], "\n"; ?>
  </td>
 </tr>
<?php
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff();

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

