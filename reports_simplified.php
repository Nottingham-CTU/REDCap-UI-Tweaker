<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or reports simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('reports-simplified-view') )
{
	exit;
}


$queryReports = $module->query( "SELECT r.*, ( SELECT group_concat( field_name ORDER BY " .
                                "field_order SEPARATOR ', ' ) FROM redcap_reports_fields WHERE " .
                                "report_id = r.report_id AND limiter_operator IS NULL ) fields, " .
                                "( SELECT trim(reverse(substring(reverse(group_concat( concat( " .
                                "field_name, ' ', CASE limiter_operator WHEN 'E' THEN '=' WHEN " .
                                "'NE' THEN '<>' ELSE limiter_operator END, ' ', limiter_value, " .
                                "' ', limiter_group_operator ) ORDER BY field_order SEPARATOR " .
                                "' ' )),4))) FROM redcap_reports_fields WHERE report_id = " .
                                "r.report_id AND limiter_operator IS NOT NULL ) simple_logic " .
                                "FROM redcap_reports r " .
                                "WHERE project_id = ? ORDER BY report_order",
                               [ $module->getProjectID() ] );
$listReports = [];
while ( $infoReport = $queryReports->fetch_assoc() )
{
	$listReports[] = $infoReport;
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$listCustomReports = [];
foreach ( $module->getCustomReports() as $infoReport )
{
	$listCustomReports[] = $infoReport;
}

?>
<div class="projhdr">Data Exports, Reports and Stats</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpReportsTable{width:95%} .simpReportsTable th, ' +
                   '.simpReportsTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpReportsTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
    var vFuncSelect = function()
    {
      var vElem = $('.simpReportsTable')[0]
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
<table class="simpReportsTable">
 <tr>
  <th>Report Title</th>
  <th>Report Type</th>
  <th>Description</th>
  <th>Permissions</th>
  <th>Definition</th>
  <th>Options</th>
 </tr>
<?php
foreach ( $listReports as $infoReport )
{
	// Get the options for the report.
	$listOptions = [];
	foreach ( [ 'combine_checkbox_values' => 'Combine checkbox values',
	            'output_dags' => 'Output DAGs',
	            'output_survey_fields' => 'Include survey identifier/timestamp fields',
	            'report_display_include_repeating_fields' => 'Include repeating instance fields',
	            'output_missing_data_codes' => 'Output missing data codes' ] as $field => $desc )
	{
		if ( $infoReport[ $field ] == '1' )
		{
			$listOptions[] = $desc;
		}
	}
?>
 <tr>
<?php
	// Output the report title, type and description.
?>
  <td><?php echo $module->escapeHTML( $infoReport['title'] ); ?></td>
  <td>REDCap</td>
  <td><?php echo $module->escapeHTML( strip_tags( $infoReport['description'] ) ); ?></td>
<?php
	// Output the report permissions.
?>
  <td>
   View Access:
   <br>
   Edit Access:
  </td>
<?php
	// Output the report definition.
?>
  <td>
   <?php
	echo $module->escapeHTML( $infoReport['fields'] );
	if ( $infoReport['simple_logic'] != '' || $infoReport['advanced_logic'] != '' )
	{
		echo '<br>WHERE ';
		if ( $infoReport['advanced_logic'] != '' )
		{
			echo $module->escapeHTML( $infoReport['advanced_logic'] );
		}
		else
		{
			echo $module->escapeHTML( $infoReport['simple_logic'] );
		}
	}
	if ( $infoReport['orderby_field1'] != '' )
	{
		echo '<br>ORDER BY ', $module->escapeHTML( $infoReport['orderby_field1'] ), ' ',
		     $module->escapeHTML( $infoReport['orderby_sort1'] );
		if ( $infoReport['orderby_field2'] != '' )
		{
			echo ', ', $module->escapeHTML( $infoReport['orderby_field2'] ), ' ',
			     $module->escapeHTML( $infoReport['orderby_sort2'] );
		}
		if ( $infoReport['orderby_field3'] != '' )
		{
			echo ', ', $module->escapeHTML( $infoReport['orderby_field3'] ), ' ',
			     $module->escapeHTML( $infoReport['orderby_sort3'] );
		}
	}
	echo "\n";
?>
  </td>
<?php
	// Output the report options.
?>
  <td> <?php echo implode( '<br>', $listOptions ); ?></td>
 </tr>
<?php
}
foreach ( $listCustomReports as $infoReport )
{
?>
 <tr>
  <td><?php echo $module->escapeHTML( $infoReport['title'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoReport['type'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoReport['description'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoReport['permissions'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoReport['definition'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoReport['options'] ); ?></td>
 </tr>
<?php
}
?>
</table>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

