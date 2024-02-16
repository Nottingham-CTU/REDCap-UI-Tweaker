<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or reports simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('reports-simplified-view') )
{
	exit;
}

function reportsEscape( $text )
{
	$text = str_replace( [ "\r\n", "\r" ], "\n", $text );
	$text = htmlspecialchars( $text, ENT_QUOTES );
	$text = str_replace( [ '&lt;b&gt;', '&lt;/b&gt;', '&lt;i&gt;', '&lt;/i&gt;' ],
	                     [ '<b>', '</b>', '<i>', '</i>' ], $text );
	$text = preg_replace( '/&(amp;)*amp;lt;(b|i)&(amp;)*amp;gt;/', '&$1lt;$2&$3gt;', $text );
	$text = preg_replace( '/&(amp;)*amp;lt;\\/(b|i)&(amp;)*amp;gt;/', '&$1lt;/$2&$3gt;', $text );
	$text = str_replace( "\n", '<br>', $text );
	return $text;
}


$queryReports = $module->query( "SELECT r.*, ( SELECT group_concat( field_name ORDER BY " .
                                "field_order SEPARATOR ', ' ) FROM redcap_reports_fields WHERE " .
                                "report_id = r.report_id AND limiter_operator IS NULL ) fields, " .
                                "( SELECT trim(substring(group_concat( concat( " .
                                "limiter_group_operator, ' ', field_name, ' ', CASE " .
                                "limiter_operator WHEN 'E' THEN '=' WHEN 'NE' THEN '<>' ELSE " .
                                "limiter_operator END, ' ', limiter_value ) ORDER BY field_order " .
                                "SEPARATOR ' ' ),4)) FROM redcap_reports_fields WHERE " .
                                "report_id = 3 AND limiter_operator IS NOT NULL ) simple_logic, " .
                                "( SELECT group_concat( role_name SEPARATOR ', ' ) FROM " .
                                "redcap_reports_access_roles rr JOIN redcap_user_roles ur ON " .
                                "rr.role_id = ur.role_id WHERE rr.report_id = r.report_id ) " .
                                "access_roles, " .
                                "( SELECT group_concat( role_name SEPARATOR ', ' ) FROM " .
                                "redcap_reports_edit_access_roles rr JOIN redcap_user_roles ur ON " .
                                "rr.role_id = ur.role_id WHERE rr.report_id = r.report_id ) " .
                                "edit_access_roles, " .
                                "( SELECT 1 FROM redcap_reports_access_users " .
                                "WHERE report_id = r.report_id ) access_users, " .
                                "( SELECT 1 FROM redcap_reports_edit_access_users " .
                                "WHERE report_id = r.report_id ) edit_access_users, " .
                                "( SELECT 1 FROM redcap_reports_access_dags " .
                                "WHERE report_id = r.report_id ) access_dags, " .
                                "( SELECT 1 FROM redcap_reports_edit_access_dags " .
                                "WHERE report_id = r.report_id ) edit_access_dags " .
                                "FROM redcap_reports r " .
                                "WHERE project_id = ? ORDER BY report_order",
                               [ $module->getProjectID() ] );


// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'reports', 'reports' => [], 'custom_reports' => [] ];

while ( $infoReport = $queryReports->fetch_assoc() )
{
	if ( $infoReport['user_access'] == 'SELECTED' )
	{
		$infoReport['user_access'] = $infoReport['access_roles'];
		if ( $infoReport['access_users'] )
		{
			$infoReport['user_access'] .=
					( $infoReport['user_access'] == '' ? '' : ', ' ) . '+Users';
		}
		if ( $infoReport['access_dags'] )
		{
			$infoReport['user_access'] .=
					( $infoReport['user_access'] == '' ? '' : ', ' ) . '+DAGs';
		}
	}
	if ( $infoReport['user_edit_access'] == 'SELECTED' )
	{
		$infoReport['user_edit_access'] = $infoReport['edit_access_roles'];
		if ( $infoReport['edit_access_users'] )
		{
			$infoReport['user_edit_access'] .=
					( $infoReport['user_edit_access'] == '' ? '' : ', ' ) . '+Users';
		}
		if ( $infoReport['edit_access_dags'] )
		{
			$infoReport['user_edit_access'] .=
					( $infoReport['user_edit_access'] == '' ? '' : ', ' ) . '+DAGs';
		}
	}
	unset( $infoReport['report_id'], $infoReport['project_id'], $infoReport['unique_report_name'],
	       $infoReport['hash'], $infoReport['short_url'], $infoReport['report_order'] );
	$listExport['reports'][] = $infoReport;
}

foreach ( $module->getCustomReports() as $infoReport )
{
	$listExport['custom_reports'][] = $infoReport;
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
		     $fileData['simplified_view'] != 'reports' )
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
$listReports = [];
$listCustomReports = [];

// Determine whether the reports match.
$mapReportsN2O = [];
$mapCustomReportsN2O = [];
foreach ( $listNew['reports'] as $i => $itemNewReport )
{
	if ( $itemNewReport['title'] == '' )
	{
		continue;
	}
	foreach ( $listOld['reports'] as $j => $itemOldReport )
	{
		if ( $itemNewReport['title'] == '' || in_array( $j, $mapReportsN2O ) )
		{
			continue;
		}
		if ( $itemNewReport['title'] == $itemOldReport['title'] )
		{
			$mapReportsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['reports'] as $i => $itemNewReport )
{
	if ( isset( $mapReportsN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['reports'] as $j => $itemOldReport )
	{
		if ( in_array( $j, $mapReportsN2O ) )
		{
			continue;
		}
		$matchingKeys = 0;
		$totalKeys = 0;
		foreach ( $itemNewReport as $key => $value )
		{
			if ( ! isset( $itemOldReport[ $key ] ) )
			{
				continue;
			}
			$totalKeys++;
			if ( $value == $itemOldReport[ $key ] )
			{
				$matchingKeys++;
			}
		}
		if ( $totalKeys - $matchingKeys < 5 )
		{
			$mapReportsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['custom_reports'] as $i => $itemNewReport )
{
	if ( $itemNewReport['title'] == '' )
	{
		continue;
	}
	foreach ( $listOld['custom_reports'] as $j => $itemOldReport )
	{
		if ( $itemNewReport['title'] == '' || in_array( $j, $mapCustomReportsN2O ) )
		{
			continue;
		}
		if ( $itemNewReport['title'] == $itemOldReport['title'] &&
		     $itemNewReport['type'] == $itemOldReport['type'] )
		{
			$mapCustomReportsN2O[ $i ] = $j;
			continue 2;
		}
	}
}
foreach ( $listNew['custom_reports'] as $i => $itemNewReport )
{
	if ( isset( $mapCustomReportsN2O[ $i ] ) )
	{
		continue;
	}
	foreach ( $listOld['custom_reports'] as $j => $itemOldReport )
	{
		if ( in_array( $j, $mapCustomReportsN2O ) )
		{
			continue;
		}
		$matchingKeys = 0;
		foreach ( [ 'title', 'type', 'description',
		            'permissions', 'definition', 'options' ] as $key )
		{
			if ( isset( $itemNewReport[ $key ] ) && isset( $itemOldReport[ $key ] ) &&
			     $itemNewReport[ $key ] == $itemOldReport[ $key ] )
			{
				$matchingKeys++;
			}
		}
		if ( $matchingKeys >= 3 )
		{
			$mapCustomReportsN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the reports to the combined data structure.
foreach ( $listNew['reports'] as $i => $itemNewReport )
{
	$itemNewReport['report_new'] = ( ! isset( $mapReportsN2O[ $i ] ) );
	$itemNewReport['report_deleted'] = false;
	if ( ! $itemNewReport['report_new'] )
	{
		$itemNewReport['report_oldvals'] = $listOld['reports'][ $mapReportsN2O[ $i ] ];
	}
	$listReports[] = $itemNewReport;
}
foreach ( $listOld['reports'] as $i => $itemOldReport )
{
	if ( ! in_array( $i, $mapReportsN2O ) )
	{
		$itemOldReport['report_new'] = false;
		$itemOldReport['report_deleted'] = true;
		$listReports[] = $itemOldReport;
	}
}
foreach ( $listNew['custom_reports'] as $i => $itemNewReport )
{
	$itemNewReport['report_new'] = ( ! isset( $mapCustomReportsN2O[ $i ] ) );
	$itemNewReport['report_deleted'] = false;
	if ( ! $itemNewReport['report_new'] )
	{
		$itemNewReport['report_oldvals'] = $listOld['custom_reports'][ $mapCustomReportsN2O[ $i ] ];
	}
	$listCustomReports[] = $itemNewReport;
}
foreach ( $listOld['custom_reports'] as $i => $itemOldReport )
{
	if ( ! in_array( $i, $mapCustomReportsN2O ) )
	{
		$itemOldReport['report_new'] = false;
		$itemOldReport['report_deleted'] = true;
		$listCustomReports[] = $itemOldReport;
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'reports' );
$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR .
            ';background:' . REDCapUITweaker::BGC_HDR;

?>
<div class="projhdr">Data Exports, Reports and Stats</div>
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
<div style="margin-bottom:15px">
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable">Select table</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="simplifiedViewDiffBtn">Difference highlight</button>
</div>
<table class="simpReportsTable" style="width:95%">
 <tr>
  <th style="<?php echo $tblStyle; ?>">Report Title</th>
  <th style="<?php echo $tblStyle; ?>">Report Type</th>
  <th style="<?php echo $tblStyle; ?>">Description</th>
  <th style="<?php echo $tblStyle; ?>">Permissions</th>
  <th style="<?php echo $tblStyle; ?>">Definition</th>
  <th style="<?php echo $tblStyle; ?>">Options</th>
 </tr>
<?php
foreach ( $listReports as $infoReport )
{
	$tblStyle = REDCapUITweaker::STL_CEL;
	if ( $infoReport['report_new'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
	}
	elseif ( $infoReport['report_deleted'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
	}

	// Get the options for the report.
	$listOptions = [];
	$optionsIdentical = true;
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
		if ( ! $infoReport['report_new'] && ! $infoReport['report_deleted'] &&
		     $infoReport[ $field ] != $infoReport['report_oldvals'][ $field ] )
		{
			$optionsIdentical = false;
		}
	}

	echo " <tr>\n";

	// Output the report title, type and description.
	foreach ( [ 'title', 'type', 'description' ] as $key )
	{
		echo '  <td style="', $tblStyle,
		     ( $infoReport['report_new'] || $infoReport['report_deleted'] ||
		       ( $key == 'type' || $infoReport[ $key ] == $infoReport['report_oldvals'][ $key ] )
		       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">';
		if ( $key == 'type' )
		{
			echo 'REDCap';
		}
		else
		{
			echo $module->escapeHTML( $key == 'description' ? strip_tags( $infoReport[ $key ] )
			                                                : $infoReport[ $key ] );
		}
		echo "</td>\n";
	}

	// Output the report permissions.
	$tblIdentical = true;
	if ( ! $infoReport['report_new'] && ! $infoReport['report_deleted'] )
	{
		foreach ( [ 'user_access', 'user_edit_access' ] as $key )
		{
			if ( $infoReport[ $key ] != $infoReport['report_oldvals'][ $key ] )
			{
				$tblIdentical = false;
				break;
			}
		}
	}
	echo '  <td style="', $tblStyle,
	     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
	     '<b>', $module->escapeHTML( $GLOBALS['lang']['report_builder_153'] ), '</b><br>',
	     $module->escapeHTML( $infoReport['user_access'] ), '<br><b>',
	     $module->escapeHTML( $GLOBALS['lang']['report_builder_152'] ), '</b><br>',
	     $module->escapeHTML( $infoReport['user_edit_access'] ), "</td>\n";

	// Output the report definition.
	$tblIdentical = true;
	if ( ! $infoReport['report_new'] && ! $infoReport['report_deleted'] )
	{
		foreach ( [ 'fields', 'simple_logic', 'advanced_logic', 'orderby_field1', 'orderby_sort1',
		            'orderby_field2', 'orderby_sort2', 'orderby_field3', 'orderby_sort3' ] as $key )
		{
			if ( $infoReport[ $key ] != $infoReport['report_oldvals'][ $key ] )
			{
				$tblIdentical = false;
				break;
			}
		}
	}
	echo '  <td style="', $tblStyle,
	     ( $tblIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
	     '<b>', $module->escapeHTML( $GLOBALS['lang']['home_32'] ), ':</b><br>',
	     $module->escapeHTML( $infoReport['fields'] );
	if ( $infoReport['simple_logic'] != '' || $infoReport['advanced_logic'] != '' )
	{
		echo '<br><b>', $module->escapeHTML( $GLOBALS['lang']['asi_012'] ), ':</b><br>';
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
		echo '<br><b>', $module->escapeHTML( $GLOBALS['lang']['report_builder_20'] ), ':</b><br>',
		     $module->escapeHTML( $infoReport['orderby_field1'] ), ' ',
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
	echo "</td>\n";

	// Output the report options.
	echo '  <td style="', $tblStyle,
	     ( $optionsIdentical ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
	     implode( '<br>', $listOptions ), "</td>\n </tr>\n";
}
foreach ( $listCustomReports as $infoReport )
{
	$tblStyle = REDCapUITweaker::STL_CEL;
	if ( $infoReport['report_new'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
	}
	elseif ( $infoReport['report_deleted'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
	}
	echo " <tr>\n";
	foreach ( ['title', 'type', 'description' ] as $key )
	{
		echo '  <td style="', $tblStyle,
			     ( $infoReport['report_new'] || $infoReport['report_deleted'] ||
			       $infoReport[ $key ] == $infoReport['report_oldvals'][ $key ]
			       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
			     $module->escapeHTML( $infoReport[ $key ] ), "</td>\n";
	}
	foreach ( [ 'permissions', 'definition', 'options' ] as $key )
	{
		echo '  <td style="', $tblStyle,
		     ( $infoReport['report_new'] || $infoReport['report_deleted'] ||
		       $infoReport[ $key ] == $infoReport['report_oldvals'][ $key ]
		       ? '' : ';background:' . REDCapUITweaker::BGC_CHG ), '">',
		     reportsEscape( $infoReport[ $key ] ), "</td>\n";
	}
	echo " </tr>\n";
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff();

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

