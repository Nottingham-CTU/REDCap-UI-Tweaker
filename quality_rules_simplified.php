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


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<div class="projhdr"><i class="fas fa-clipboard-check"></i> Data Quality</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpRulesTable{width:95%} .simpRulesTable th, ' +
                   '.simpRulesTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpRulesTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
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
  })
</script>
<div>
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideID" style="margin-bottom:15px">Show/Hide ID</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable" style="margin-bottom:15px">Select table</button>
</div>
<table class="simpRulesTable">
 <tr>
  <th class="colID"><?php echo $GLOBALS['lang']['multilang_73'] ?? 'ID'; ?></th>
  <th><?php echo $GLOBALS['lang']['dataqueries_15']; ?></th>
  <th><?php echo $GLOBALS['lang']['dataqueries_16']; ?></th>
  <th><?php echo $GLOBALS['lang']['dataqueries_123']; ?></th>
 </tr>
<?php
foreach ( $listRules as $infoRule )
{
?>
 <tr>
  <td class="colID"><?php echo intval( $infoRule['rule_id'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoRule['rule_name'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoRule['rule_logic'] ); ?></td>
  <td><?php echo $lookupYN[ $infoRule['real_time_execute'] ]; ?></td>
 </tr>
<?php
}
?>
</table>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

