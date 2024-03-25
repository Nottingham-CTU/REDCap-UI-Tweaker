<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or data quality rules simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('codebook-simplified-view') )
{
	exit;
}

$svbr = REDCapUITweaker::SVBR;

function codebookEscape( $text )
{
	global $svbr;
	return trim( str_replace( [ "\r\n", "\r", "\n" ], $svbr,
	                          strip_tags( \label_decode( preg_replace( '/<\/(p|h[1-6]|tr)>/',
	                                                                   "$0\n", $text ) ) ) ) );
}

$lookupYN = [ '1' => $GLOBALS['lang']['design_100'], '0' => $GLOBALS['lang']['design_99'] ];

$listEventNames = \REDCap::getEventNames( true );


// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'codebook', 'fields' => [], 'forms' => [] ];

$queryCodebook = $module->query( 'SELECT * FROM redcap_metadata m WHERE project_id = ? ' .
                                 'ORDER BY field_order', [ $module->getProjectID() ] );
while ( $infoCodebook = $queryCodebook->fetch_assoc() )
{
	// Add instrument metadata.
	if ( $infoCodebook['form_menu_description'] !== null )
	{
		$listExport['forms'][ $infoCodebook['form_name'] ] =
				[ 'label' => $infoCodebook['form_menu_description'], 'survey' => false,
				  'survey_title' => null, 'fdl' => [], 'repeating' => false ];
	}
	// Remove newlines from field note.
	$infoCodebook['element_note'] = str_replace( '<br>', ' ', $infoCodebook['element_note'] ?? '' );
	// Update legacy field type validation names.
	if ( $infoCodebook['element_validation_type'] == 'int' )
	{
		$infoCodebook['element_validation_type'] = 'integer';
	}
	elseif ( $infoCodebook['element_validation_type'] == 'float' )
	{
		$infoCodebook['element_validation_type'] = 'number';
	}
	elseif ( in_array( $infoCodebook['element_validation_type'],
	                   [ 'date', 'datetime', 'datetime_seconds' ] ) )
	{
		$infoCodebook['element_validation_type'] .= '_ymd';
	}
	if ( $infoCodebook['element_type'] == 'select' )
	{
		$infoCodebook['element_type'] = 'dropdown';
	}
	// Remove unused data.
	unset( $infoCodebook['project_id'], $infoCodebook['form_menu_description'],
	       $infoCodebook['element_validation_checktype'] );
	// Add field data to exportable data structure.
	$listExport['fields'][] = $infoCodebook;
}

// Get survey titles for forms enabled as a survey.
$querySurvey = $module->query( 'SELECT form_name, title FROM redcap_surveys WHERE project_id = ? ' .
                               'AND survey_enabled = 1', [ $module->getProjectID() ] );
while ( $infoSurvey = $querySurvey->fetch_assoc() )
{
	$listExport['forms'][ $infoSurvey['form_name'] ]['survey'] = true;
	$listExport['forms'][ $infoSurvey['form_name'] ]['survey_title'] = $infoSurvey['title'];
}

// Get the form display logic.
$queryFDL = $module->query( 'SELECT form_name, control_condition, event_id FROM ' .
                            'redcap_form_display_logic_conditions c JOIN (SELECT control_id, ' .
                            'form_name, group_concat(event_id SEPARATOR \',\') event_id FROM ' .
                            'redcap_form_display_logic_targets GROUP BY control_id, form_name) t ' .
                            'ON c.control_id = t.control_id WHERE project_id = ?',
                            [ $module->getProjectID() ] );
while ( $infoFDL = $queryFDL->fetch_assoc() )
{
	$fdlEvents = null;
	if ( \REDCap::isLongitudinal() && $infoFDL['event_id'] !== null )
	{
		$fdlEvents = explode( ',', $infoFDL['event_id'] );
		foreach ( $fdlEvents as $i => $fdlEventID )
		{
			$fdlEvents[ $i ] = $listEventNames[ $fdlEventID ];
		}
	}
	$listExport['forms'][ $infoFDL['form_name'] ]['fdl'][] =
			[ 'condition' => $infoFDL['control_condition'], 'events' => $fdlEvents ];
}

// If not a longitudinal project, show which forms are repeating.
// (On longitudinal projects, this is shown on the instrument/event mapping simplified view.)
if ( ! \REDCap::isLongitudinal() )
{
	$queryRepeating = $module->query( 'SELECT form_name FROM redcap_events_repeat WHERE event_id ' .
	                                  '= (SELECT em.event_id FROM redcap_events_metadata em JOIN ' .
	                                  'redcap_events_arms ea ON em.arm_id = ea.arm_id WHERE ' .
	                                  'ea.project_id = ?) AND form_name IS NOT NULL',
	                                  [ $module->getProjectID() ] );
	while ( $infoRepeating = $queryRepeating->fetch_assoc() )
	{
		$listExport['forms'][ $infoRepeating['form_name'] ]['repeating'] = true;
	}
}


// Handle upload. Get old/new data structures.
$fileLoadError = false;
if ( isset( $_POST['simp_view_diff_mode'] ) && $_POST['simp_view_diff_mode'] == 'export' )
{
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' .
	        trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', \REDCap::getProjectTitle() ), '_-' ) .
	        '_' . date( 'Ymd' ) . '.svc.json"' );
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
		     $fileData['simplified_view'] != 'codebook' )
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
$listCodebook = [];
$listForms = [];

// Determine whether the fields match.
$mapCodebookN2O = [];
foreach ( $listNew['fields'] as $i => $itemNewField )
{
	foreach ( $listOld['fields'] as $j => $itemOldField )
	{
		if ( in_array( $j, $mapCodebookN2O ) )
		{
			continue;
		}
		if ( $itemNewField['field_name'] == $itemOldField['field_name'] )
		{
			$mapCodebookN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the fields to the combined data structure.
foreach ( $listNew['fields'] as $i => $itemNewField )
{
	$itemNewField['field_new'] = ( ! isset( $mapCodebookN2O[ $i ] ) );
	$itemNewField['field_deleted'] = false;
	if ( ! $itemNewField['field_new'] )
	{
		$itemOldField = $listOld['fields'][ $mapCodebookN2O[ $i ] ];
		$itemNewField['field_oldvals'] = $itemOldField;
		$itemNewField['field_moved'] = false;
		if ( $itemNewField['form_name'] != $itemOldField['form_name'] )
		{
			$itemNewField['field_moved'] = true;
		}
		else
		{
			$newFieldPrev = ( $i == 0 ? '' : $listNew['fields'][ $i - 1 ]['field_name'] );
			$oldFieldPrev = ( $mapCodebookN2O[ $i ] == 0 ? ''
			                    : $listOld['fields'][ $mapCodebookN2O[ $i ] - 1 ]['field_name'] );
			$newFieldNext = ( $i >= count( $listNew ) - 1 ? ''
			                    : $listNew['fields'][ $i + 1 ]['field_name'] );
			$oldFieldNext = ( $mapCodebookN2O[ $i ] >= count( $listOld ) - 1 ? ''
			                    : $listOld['fields'][ $mapCodebookN2O[ $i ] + 1 ]['field_name'] );
			if ( $newFieldPrev != $oldFieldPrev && $newFieldNext != $oldFieldNext )
			{
				$itemNewField['field_moved'] = true;
			}
		}
	}
	$listCodebook[] = $itemNewField;
	if ( $i == count( $listNew['fields'] ) - 1 ||
	     $itemNewField['form_name'] != $listNew['fields'][ $i + 1 ]['form_name'] )
	{
		foreach ( $listOld['fields'] as $j => $itemOldField )
		{
			if ( $itemOldField['form_name'] != $itemNewField['form_name'] )
			{
				continue;
			}
			if ( ! in_array( $j, $mapCodebookN2O ) )
			{
				$itemOldField['field_new'] = false;
				$itemOldField['field_deleted'] = true;
				$listCodebook[] = $itemOldField;
			}
		}
	}
}

// Add the forms to the combined data structure.
foreach ( $listNew['forms'] as $formName => $itemNewForm )
{
	if ( isset( $listOld['forms'][ $formName ] ) )
	{
		$itemNewForm['form_new'] = false;
		$itemNewForm['form_deleted'] = false;
		$itemNewForm['form_oldvals'] = $listOld['forms'][ $formName ];
	}
	else
	{
		$itemNewForm['form_new'] = true;
		$itemNewForm['form_deleted'] = false;
	}
	$listForms[ $formName ] = $itemNewForm;
}
foreach ( $listOld['forms'] as $formName => $itemOldForm )
{
	if ( isset( $listNew['forms'][ $formName ] ) )
	{
		continue;
	}
	$itemOldForm['form_new'] = false;
	$itemOldForm['form_deleted'] = true;
	foreach ( $listOld['fields'] as $i => $itemOldField )
	{
		if ( $itemOldField['form_name'] != $formName )
		{
			continue;
		}
		if ( ! in_array( $i, $mapCodebookN2O ) )
		{
			$itemOldField['field_new'] = false;
			$itemOldField['field_deleted'] = true;
			$listCodebook[] = $itemOldField;
		}
	}
	$listForms[ $formName ] = $itemOldForm;
}


$prevFormName = '';


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'codebook' );
$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR .
            ';background:' . REDCapUITweaker::BGC_HDR;

?>
<div class="projhdr"><i class="fas fa-book"></i> <?php echo $GLOBALS['lang']['global_116']; ?></div>
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
      var vElem = $('.simpCodebookTable')[0]
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
    var vFuncShowHideComp = function()
    {
      var vElems = $('.compRow')
      if ( vElems.first().css('display') == 'none' )
      {
        vElems.css('display', '')
      }
      else
      {
        vElems.css('display', 'none')
      }
    }
    $('#showHideComp').click(vFuncShowHideComp)
    var vFuncShowHideChg = function()
    {
      var vElems = $('.chgInd')
      if ( vElems.first().css('display') == 'none' )
      {
        vElems.css('display', '')
      }
      else
      {
        vElems.css('display', 'none')
      }
    }
    $('#showHideChg').click(vFuncShowHideChg)
  })
</script>
<div style="margin-bottom:15px">
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideID">Show/Hide #</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideChg">Show/Hide +/-</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideComp">Show/Hide Complete? fields</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable">Select table</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="simplifiedViewDiffBtn">Difference highlight</button>
</div>
<table class="simpCodebookTable" style="width:95%">
 <tr>
  <th style="<?php echo $tblStyle; ?>;text-align:center" class="colID">#</th>
  <th style="<?php echo $tblStyle; ?>"><?php echo $GLOBALS['lang']['design_484']; ?></th>
  <th style="<?php echo $tblStyle; ?>">
   <?php echo $GLOBALS['lang']['global_40'], ' / ', $GLOBALS['lang']['database_mods_69'], "\n"; ?>
  </th>
  <th style="<?php echo $tblStyle; ?>" colspan="2"><?php echo $GLOBALS['lang']['design_494']; ?></th>
  <th style="<?php echo $tblStyle; ?>"><?php echo $GLOBALS['lang']['design_527']; ?></th>
  <th style="<?php echo $tblStyle; ?>;text-align:center" class="chgInd">+/-</th>
 </tr>
<?php
foreach ( $listCodebook as $infoCodebook )
{
	// Check if this is one of the 'Complete?' fields and set the row class if so.
	$rowClass = ( $infoCodebook['field_name'] == $infoCodebook['form_name'] . '_complete' )
	            ? ' class="compRow"' : '';
	// If the form name is different from the previous field, this is a new form, so add a header.
	if ( $infoCodebook['form_name'] != $prevFormName )
	{
		$infoForm = $listForms[ $infoCodebook['form_name'] ];
		$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR . ';background:';
		$chgInd = '! ';
		if ( $infoForm['form_new'] )
		{
			$tblStyle .= REDCapUITweaker::BGC_HDR_NEW;
			$chgInd .= '+';
		}
		elseif ( $infoForm['form_deleted'] )
		{
			$tblStyle .= REDCapUITweaker::BGC_HDR_DEL . ';' . REDCapUITweaker::STL_DEL;
			$chgInd .= '-';
		}
		else
		{
			$tblStyle .= REDCapUITweaker::BGC_HDR;
		}
		$hasSurveyRow = $infoForm['survey'] ||
		              ( isset( $infoForm['form_oldvals'] ) && $infoForm['form_oldvals']['survey'] );
		$hasFDLRow = ! empty( $infoForm['fdl'] ) ||
		             ( isset( $infoForm['form_oldvals'] ) &&
		               ! empty( $infoForm['form_oldvals']['fdl'] ) );
		$formNameChanged = ( isset( $infoForm['form_oldvals'] ) &&
		                     ( $infoForm['label'] != $infoForm['form_oldvals']['label'] ||
		                       $infoForm['repeating'] != $infoForm['form_oldvals']['repeating'] ) );
		$surveyChanged = ( isset( $infoForm['form_oldvals'] ) &&
		                  $infoForm['survey_title'] !== $infoForm['form_oldvals']['survey_title'] );
		$fdlChanged = ( isset( $infoForm['form_oldvals'] ) &&
		                $infoForm['fdl'] != $infoForm['form_oldvals']['fdl'] );
		if ( $chgInd == '! ' && ( $formNameChanged || $surveyChanged || $fdlChanged ) )
		{
			$chgInd .= '#';
		}
		$rowspan = 1 + ( $hasSurveyRow ? 1 : 0 ) + ( $hasFDLRow ? 1 : 0 );
		$cellStyle = $tblStyle;
		if ( $formNameChanged )
		{
			$cellStyle .= ';background:' . REDCapUITweaker::BGC_HDR_CHG;
		}
		echo " <tr>\n";
		echo '  <td class="colID" rowspan="', $rowspan, '" style="', $tblStyle, '">', "</td>\n";
		echo '  <td colspan="5" style="', $cellStyle, ( $rowspan > 1 ? ';border-bottom:none' : '' ),
		     '"><b>', $module->escapeHTML( $infoForm['label'] ), '</b>&nbsp;&nbsp; ',
		     '<span style="font-size:0.9em"><i>', $module->escapeHTML( $infoCodebook['form_name'] ),
		     '</i></span>', ( $infoForm['repeating'] ? ' &#10227;' : '' );
		if ( $formNameChanged )
		{
			echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '"><b>',
			     $module->escapeHTML( $infoForm['form_oldvals']['label'] ), '</b>&nbsp;&nbsp; ',
			     '<span style="font-size:0.9em"><i>',
			     $module->escapeHTML( $infoCodebook['form_oldvals']['form_name'] ), '</i></span>',
			     ( $infoForm['form_oldvals']['repeating'] ? ' &#10227;' : '' ), '</span>';
		}
		echo "</td>\n";
		echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center',
		     ( $hasSurveyRow || $hasFDLRow ? ';border-bottom:none' : '' ),
		     '">', $chgInd, "</td>\n";
		echo " </tr>\n";
		if ( $hasSurveyRow )
		{
			$cellStyle = $tblStyle . ';border-top:none;padding-top:1px';
			if ( $rowspan > 2 )
			{
				$cellStyle .= ';border-bottom:none';
			}
			if ( $surveyChanged )
			{
				$cellStyle .= ';background:' . REDCapUITweaker::BGC_HDR_CHG;
			}
			echo " <tr>\n";
			echo '  <td colspan="5" style="', $cellStyle, '">&nbsp;&nbsp;';
			if ( $infoForm['survey'] )
			{
				echo '<span style="font-size:0.9em">', $GLOBALS['lang']['design_789'],
				     '&nbsp; (', $module->escapeHTML( $infoForm['survey_title'] ), ')</span>';
			}
			if ( $surveyChanged )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '"><span ',
				     'style="font-size:0.9em">', $GLOBALS['lang']['design_789'], '&nbsp; (',
				     $module->escapeHTML( $infoForm['form_oldvals']['survey_title'] ),
				     ')</span></span>';
			}
			echo "</td>\n";
			echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center;border-top:none',
			     ( $hasFDLRow ? ';border-bottom:none' : '' ), '">', $chgInd, "</td>\n";
			echo " </tr>\n";
		}
		if ( $hasFDLRow )
		{
			$cellStyle = $tblStyle . ';border-top:none;padding-top:1px';
			if ( $fdlChanged )
			{
				$cellStyle .= ';background:' . REDCapUITweaker::BGC_HDR_CHG;
			}
			echo " <tr>\n";
			echo '  <td colspan="5" style="', $cellStyle, '">&nbsp;&nbsp;';
			if ( !empty( $infoForm['fdl'] ) )
			{
				$infoFDL =
					array_map( function ( $i )
					           {
					               if ( $i['events'] === null )
					               {
					                   return $i['condition'];
					               }
					               return "([event-name] = '" .
					                      implode( "' or [event-name] = '", $i['events'] ) .
					                      "') and (" . $i['condition'] . ')';
					           },
					           $infoForm['fdl'] );
				if ( count( $infoFDL ) == 1 )
				{
					$formDisplayLogic = $infoFDL[0];
				}
				else
				{
					$formDisplayLogic = '(' . implode( ') or (', $infoFDL ) . ')';
				}
				echo '<span style="font-size:0.8em">', $GLOBALS['lang']['design_985'],
				     ': ', $module->escapeHTML( $formDisplayLogic ), '</span>';
			}
			if ( $fdlChanged )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
				if ( !empty( $infoForm['form_oldvals']['fdl'] ) )
				{
					$infoFDL =
						array_map( function ( $i )
						           {
						               if ( $i['events'] === null )
						               {
						                   return $i['condition'];
						               }
						               return "([event-name] = '" .
						                      implode( "' or [event-name] = '", $i['events'] ) .
						                      "') and (" . $i['condition'] . ')';
						           },
						           $infoForm['form_oldvals']['fdl'] );
					if ( count( $infoFDL ) == 1 )
					{
						$formDisplayLogic = $infoFDL[0];
					}
					else
					{
						$formDisplayLogic = '(' . implode( ') or (', $infoFDL ) . ')';
					}
					echo '<span style="font-size:0.8em">', $GLOBALS['lang']['design_985'],
					     ': ', $module->escapeHTML( $formDisplayLogic ), '</span>';
				}
				echo '</span>';
			}
			echo "</td>\n";
			echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center;border-top:none',
			     '">', $chgInd, "</td>\n";
			echo " </tr>\n";
		}
		$prevFormName = $infoCodebook['form_name'];
	}
	// If the field includes a section header, output a row for it.
	$headerNew = false;
	$headerDel = false;
	$headerChg = false;
	if ( $infoCodebook['element_preceding_header'] != '' ||
	     ( isset( $infoCodebook['field_oldvals'] ) &&
	       $infoCodebook['field_oldvals']['element_preceding_header'] != '' ) )
	{
		$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR;
		$headerNew = ( $infoCodebook['field_new'] ||
		               ( isset( $infoCodebook['field_oldvals'] ) &&
		                 $infoCodebook['field_oldvals']['element_preceding_header'] == '' ) );
		$headerDel = ( $infoCodebook['field_deleted'] ||
		               ( isset( $infoCodebook['field_oldvals'] ) &&
		                 $infoCodebook['element_preceding_header'] == '' ) );
		$headerChg = ( ! $headerNew && ! $headerDel && $infoCodebook['element_preceding_header'] !=
		                               $infoCodebook['field_oldvals']['element_preceding_header'] );
		$cellStyle = $tblStyle . ';background:';
		if ( $headerNew )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
			$cellStyle .= REDCapUITweaker::BGC_HDR_NEW;
		}
		elseif ( $headerDel )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
			$cellStyle .= REDCapUITweaker::BGC_HDR_DEL . ';' . REDCapUITweaker::STL_DEL;
		}
		elseif ( $headerChg )
		{
			$tblStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
			$cellStyle .= REDCapUITweaker::BGC_HDR_CHG;
		}
		else
		{
			$cellStyle .= REDCapUITweaker::BGC_HDR;
		}
		$chgInd = ( $headerNew || $headerDel || $headerChg ? '#' : '' );
		$chgInd = ( $infoCodebook['field_new'] ? '+' : $chgInd );
		$chgInd = ( $infoCodebook['field_deleted'] ? '-' : $chgInd );
		echo ' <tr', $rowClass, ">\n";
		echo '  <td class="colID" style="', $tblStyle, '">', "</td>\n";
		echo '  <td style="', $tblStyle, '">', "</td>\n";
		echo '  <td colspan="3" style="', $cellStyle, '">',
		     codebookEscape( $headerDel && ! $infoCodebook['field_deleted']
		                     ? $infoCodebook['field_oldvals']['element_preceding_header']
		                     : $infoCodebook['element_preceding_header'] );
		if ( $headerChg )
		{
			echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
			     codebookEscape( $infoCodebook['field_oldvals']['element_preceding_header'] ),
			     '</span>';
		}
		echo "</td>\n";
		echo '  <td style="', $tblStyle, '">', "</td>\n";
		echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center">', $chgInd, "</td>\n";
		echo " </tr>\n";

	}

	// Set element validation string.
	$fieldValidation = $infoCodebook['element_validation_type'] ?? '';
	if ( $infoCodebook['element_validation_min'] != '' )
	{
		$fieldValidation .=
				( $fieldValidation == '' ? '' : ', ' ) .
				$GLOBALS['lang']['design_486'] . ' ' . $infoCodebook['element_validation_min'];
	}
	if ( $infoCodebook['element_validation_max'] != '' )
	{
		$fieldValidation .=
				( $fieldValidation == '' ? '' : ', ') .
				$GLOBALS['lang']['design_487'] . ' ' . $infoCodebook['element_validation_max'];
	}
	$fieldValidation = $fieldValidation == '' ? '' : ' (' . $fieldValidation . ')';
	$oldFieldValidation = '';
	if ( isset( $infoCodebook['field_oldvals'] ) )
	{
		$oldFieldValidation = $infoCodebook['field_oldvals']['element_validation_type'] ?? '';
		if ( $infoCodebook['field_oldvals']['element_validation_min'] != '' )
		{
			$oldFieldValidation .=
					( $oldFieldValidation == '' ? '' : ', ' ) .
					$GLOBALS['lang']['design_486'] . ' ' .
					$infoCodebook['field_oldvals']['element_validation_min'];
		}
		if ( $infoCodebook['field_oldvals']['element_validation_max'] != '' )
		{
			$oldFieldValidation .=
					( $oldFieldValidation == '' ? '' : ', ') .
					$GLOBALS['lang']['design_487'] . ' ' .
					$infoCodebook['field_oldvals']['element_validation_max'];
		}
		$oldFieldValidation = $oldFieldValidation == '' ? '' : ' (' . $oldFieldValidation . ')';
	}

	// Set default field rowspan.
	$rowspan = 2;
	// For dropdown/radio/checkbox fields, get the options and increment the rowspan.
	for ( $r = 0; $r <= 1; $r++ )
	{
		if ( $r > 0 && ! isset( $infoCodebook['field_oldvals'] ) )
		{
			break;
		}
		if ( $r == 0 )
		{
			$infoTemp =& $infoCodebook;
		}
		else
		{
			$infoTemp =& $infoCodebook['field_oldvals'];
		}
		if ( in_array( $infoTemp['element_type'], [ 'dropdown', 'radio', 'checkbox' ] ) )
		{
			if ( $infoTemp['element_type'] == 'checkbox' &&
			     strpos( $infoTemp['misc'], '@SQLCHECKBOX' ) !== false )
			{
				$infoTemp['element_type'] = 'sqlcheckbox';
				$infoTemp['element_enum'] = \Form::getValueInActionTag( $infoTemp['misc'],
				                                                        '@SQLCHECKBOX' );
				$rowspan = 3;
			}
			else
			{
				$infoTemp['element_enum'] =
					array_map( function( $i )
					           { return array_merge( explode( ', ', trim( $i ), 2 ), [''] ); },
					           explode( '\n', $infoTemp['element_enum'] ) );
				if ( $r == 0 )
				{
					$rowspan += count( $infoTemp['element_enum'] );
				}
				elseif ( in_array( $infoCodebook['element_type'],
				                   [ 'dropdown', 'radio', 'checkbox' ] ) )
				{
					foreach ( $infoCodebook['element_enum'] as $i => $itemEnum )
					{
						$foundEnum = false;
						foreach ( $infoTemp['element_enum'] as $itemOldEnum )
						{
							if ( $itemEnum[0] == $itemOldEnum[0] )
							{
								$foundEnum = true;
								if ( $itemEnum[1] != $itemOldEnum[1] )
								{
									$infoCodebook['element_enum'][ $i ][2] = 'chg';
								}
								break;
							}
						}
						if ( ! $foundEnum )
						{
							$infoCodebook['element_enum'][ $i ][2] = 'new';
						}
					}
					foreach ( $infoTemp['element_enum'] as $i => $itemEnum )
					{
						$foundEnum = false;
						foreach ( $infoCodebook['element_enum'] as $itemNewEnum )
						{
							if ( $itemEnum[0] == $itemNewEnum[0] )
							{
								$foundEnum = true;
								break;
							}
						}
						if ( ! $foundEnum )
						{
							$infoTemp['element_enum'][ $i ][2] = 'del';
							$infoCodebook['element_enum'][] = $infoTemp['element_enum'][ $i ];
							$rowspan++;
						}
					}
				}
			}
		}
		// For other non-blank element_enum (calc, sql), set the rowspan to 3.
		elseif ( $infoTemp['element_enum'] != '' )
		{
			$rowspan = 3;
		}
		// If @CALCTEXT action tag is used, move the calculation to the calculations column and set
		// the rowspan to 3.
		elseif ( $infoTemp['element_type'] == 'text' &&
		         strpos( $infoTemp['misc'], '@CALCTEXT' ) !== false )
		{
			$calcString = \Form::getValueInParenthesesActionTag( $infoTemp['misc'], '@CALCTEXT' );
			if ( $calcString != '' )
			{
				$infoTemp['element_type'] = 'calctext';
				$infoTemp['element_enum'] = $calcString;
				$infoTemp['misc'] =
					preg_replace( '/@CALCTEXT\s*\(\s*' . preg_quote( $calcString, '/' ) . '\s*\)/',
					              '@CALCTEXT', $infoTemp['misc'] );
				$rowspan = 3;
			}
		}
		// If @CALCDATE action tag is used, move the parameters to the calculations column and set
		// the rowspan to 3.
		elseif ( $infoTemp['element_type'] == 'text' &&
		         strpos( $infoTemp['misc'], '@CALCDATE' ) !== false )
		{
			$calcString = \Form::getValueInParenthesesActionTag( $infoTemp['misc'], '@CALCDATE' );
			if ( $calcString != '' )
			{
				$infoTemp['element_type'] = 'calcdate';
				$infoTemp['element_enum'] = $calcString;
				$infoTemp['misc'] =
					preg_replace( '/@CALCDATE\s*\(\s*' . preg_quote( $calcString, '/' ) . '\s*\)/',
					              '@CALCDATE', $infoTemp['misc'] );
				$rowspan = 3;
			}
		}
		unset( $infoTemp );
	}

	// Identify field changes.
	$fcMove = false;
	$fcLabel = false;
	$fcType = false;
	$fcAnnotation = false;
	$fcLogic = false;
	$fcNote = false;
	$fcAttributes = false;
	$fcChoiceCalc = false;
	if ( isset( $infoCodebook['field_oldvals'] ) )
	{
		$fcMove = $infoCodebook['field_moved'];
		if ( $infoCodebook['element_label'] != $infoCodebook['field_oldvals']['element_label'] )
		{
			$fcLabel = true;
		}
		if ( $infoCodebook['element_type'] != $infoCodebook['field_oldvals']['element_type'] ||
		     $infoCodebook['element_validation_type'] !=
		       $infoCodebook['field_oldvals']['element_validation_type'] ||
		     $infoCodebook['element_validation_min'] !=
		       $infoCodebook['field_oldvals']['element_validation_min'] ||
		     $infoCodebook['element_validation_max'] !=
		       $infoCodebook['field_oldvals']['element_validation_max'] )
		{
			$fcType = true;
		}
		if ( $infoCodebook['misc'] != $infoCodebook['field_oldvals']['misc'] )
		{
			$fcAnnotation = true;
		}
		if ( $infoCodebook['branching_logic'] != $infoCodebook['field_oldvals']['branching_logic'] )
		{
			$fcLogic = true;
		}
		if ( $infoCodebook['element_note'] != $infoCodebook['field_oldvals']['element_note'] )
		{
			$fcNote = true;
		}
		if ( $infoCodebook['field_req'] != $infoCodebook['field_oldvals']['field_req'] ||
		     $infoCodebook['field_phi'] != $infoCodebook['field_oldvals']['field_phi'] ||
		     $infoCodebook['custom_alignment'] != $infoCodebook['field_oldvals']['custom_alignment'] ||
		     $infoCodebook['question_num'] != $infoCodebook['field_oldvals']['question_num'] ||
		     $infoCodebook['stop_actions'] != $infoCodebook['field_oldvals']['stop_actions'] )
		{
			$fcAttributes = true;
		}
		if ( $infoCodebook['element_enum'] != $infoCodebook['field_oldvals']['element_enum'] )
		{
			$fcChoiceCalc = true;
		}
	}

	// Output the row for the field.
	$tblStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR;
	$chgInd = '';
	if ( $infoCodebook['field_new'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
		$chgInd = '+';
	}
	elseif ( $infoCodebook['field_deleted'] )
	{
		$tblStyle .= ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL;
		$chgInd = '-';
	}
	elseif ( $fcMove || $fcLabel || $fcType || $fcAnnotation || $fcLogic || $fcNote ||
	         $fcAttributes || $fcChoiceCalc || $headerNew || $headerDel || $headerChg )
	{
		$chgInd = '#';
	}

	echo ' <tr', $rowClass, ">\n";
	// - Output the field number and field name.
	$cellStyle = $tblStyle . ';text-align:center';
	if ( $fcMove )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td class="colID" rowspan="', $rowspan, '" style="', $cellStyle, '">&#8203;',
	     intval( $infoCodebook['field_order'] );
	if ( $fcMove )
	{
		echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
		     intval( $infoCodebook['field_oldvals']['field_order'] ), '</span>';
	}
	echo "</td>\n";
	echo '  <td style="', $tblStyle, ';border-bottom-style:dashed">',
	     $module->escapeHTML( $infoCodebook['field_name'] ), "</td>\n";
	// - Output the field label.
	$cellStyle = $tblStyle . ';border-bottom-style:dashed';
	if ( $fcLabel )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td style="', $cellStyle, '" rowspan="', ( $rowspan > 3 ? $rowspan - 2 : $rowspan - 1 ),
	     '">', codebookEscape( $infoCodebook['element_label'] );
	if ( $fcLabel )
	{
		echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
		     codebookEscape( $infoCodebook['field_oldvals']['element_label'] ), '</span>';
	}
	echo "</td>\n";
	// - Output the field type and validation.
	$cellStyle = $tblStyle . ';border-bottom:none';
	if ( $fcType )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td style="', $cellStyle, '" colspan="2">',
	     $module->escapeHTML( $infoCodebook['element_type'] . $fieldValidation );
	if ( $fcType )
	{
		echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
		     $module->escapeHTML( $infoCodebook['field_oldvals']['element_type'] .
		                          $oldFieldValidation ), '</span>';
	}
	echo "</td>\n";
	// - Output the field annotation.
	$cellStyle = $tblStyle;
	if ( $fcAnnotation )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td rowspan="', $rowspan, '" style="', $cellStyle, '">',
	     codebookEscape( $infoCodebook['misc'] );
	if ( $fcAnnotation )
	{
		echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
		     codebookEscape( $infoCodebook['field_oldvals']['misc'] ), '</span>';
	}
	echo "</td>\n";
	// - Output the change marker.
	echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center;border-bottom:none">',
	     $chgInd, "</td>\n";
	echo " </tr>\n <tr", $rowClass, ">\n";
	// - Output the branching logic.
	$cellStyle = $tblStyle . ';font-size:0.9em;border-top-style:dashed';
	if ( $fcLogic )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td rowspan="', $rowspan - 1, '" style="', $cellStyle, '">';
	if ( $infoCodebook['branching_logic'] != '' )
	{
		echo $GLOBALS['lang']['design_485'], $svbr,
		     str_replace( [ "\r\n", "\n" ], $svbr,
		                  $module->escapeHTML( $infoCodebook['branching_logic'] ) );
	}
	if ( $fcLogic )
	{
		echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
		     str_replace( [ "\r\n", "\n" ], $svbr,
		                 $module->escapeHTML( $infoCodebook['field_oldvals']['branching_logic'] ) ),
		     '</span>';
	}
	echo "</td>\n";
	// - Prepare the field note.
	$cellStyle = $tblStyle . ';font-size:0.9em;border-top-style:dashed';
	if ( $fcNote )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	$fieldNoteCell = '  <td' . ( $rowspan > 3 ? ' rowspan="2"' : '' ) . ' style="' . $cellStyle .
	                 '">' . str_replace( "\n", $svbr,
	                                     $module->escapeHTML( str_replace( '<br>', "\n",
	                                                           $infoCodebook['element_note'] ) ) );
	if ( $fcNote )
	{
		$fieldNoteCell .= $svbr . '<span style="' . REDCapUITweaker::STL_OLD . '">' .
		                  str_replace( "\n", $svbr,
	                                   $module->escapeHTML( str_replace( '<br>', "\n",
	                                          $infoCodebook['field_oldvals']['element_note'] ) ) ) .
	                      '</span>';
	}
	$fieldNoteCell .= "</td>\n";
	// - If this is the final row for the field, output the field note here.
	if ( $rowspan == 2 )
	{
		echo $fieldNoteCell;
	}
	// - Output the field required/identifier status, alignment, survey attributes.
	$cellStyle = $tblStyle . ';border-top:none';
	if ( $rowspan > 2 )
	{
		$cellStyle .= ';border-bottom-style:dashed';
	}
	if ( $fcAttributes )
	{
		$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
	}
	echo '  <td colspan="2" style="', $cellStyle, '">';
	foreach ( [ false, true ] as $oldVals )
	{
		if ( $oldVals )
		{
			if ( ! $fcAttributes )
			{
				break;
			}
			echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">';
		}
		$infoTemp = $oldVals ? $infoCodebook['field_oldvals'] : $infoCodebook;
		echo ( $infoTemp['element_type'] == 'descriptive' && $infoTemp['field_req'] != '1'
		       ? ''
		       : ( $GLOBALS['lang'][ $infoTemp['field_req'] == '1' ? 'api_docs_063' : 'api_docs_064' ] ) );
		if ( $infoTemp['field_phi'] == '1' )
		{
			echo ', ', preg_replace( '/[^A-Za-z]/', '', $GLOBALS['lang']['design_103'] );
		}
		if ( $infoTemp['custom_alignment'] != '' )
		{
			echo $svbr, $module->escapeHTML( $GLOBALS['lang']['design_490'] . ' ' .
			                                 $infoTemp['custom_alignment'] );
		}
		if ( $infoTemp['question_num'] != '' )
		{
			echo $svbr, $GLOBALS['lang']['survey_437'], ' ',
			     strtolower( $GLOBALS['lang']['design_491'] ),
			     $module->escapeHTML( $infoTemp['question_num'] );
		}
		if ( $infoTemp['stop_actions'] != '' )
		{
			echo $svbr,  $GLOBALS['lang']['survey_437'], ' ',
			     strtolower( $GLOBALS['lang']['database_mods_108'] ), ': ',
			     $module->escapeHTML( $infoTemp['stop_actions'] );
		}
		if ( $oldVals )
		{
			echo '</span>';
		}
		unset( $infoTemp );
	}
	echo "</td>\n";
	// - Output the change marker.
		echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center;border-top:none',
		     ( $rowspan > 2 ? ';border-bottom:none' : '' ), '">', $chgInd, "</td>\n";
	echo " </tr>\n";
	// - Output the row(s) for choices/calculations.
	for ( $i = 0; $i < $rowspan - 2; $i++ )
	{
		echo ' <tr', $rowClass, ">\n";
		// Output the field note here if required.
		if ( ( $rowspan == 3 && $i == 0 ) || ( $rowspan > 3 && $i == $rowspan - 4 ) )
		{
			echo $fieldNoteCell;
		}
		// Output the field values/labels for radio/dropdown/checkbox fields.
		if ( is_array( $infoCodebook['element_enum'] ) )
		{
			$cellStyle = $tblStyle . ';border-top-style:dashed';
			if ( $i < $rowspan - 3 )
			{
				$cellStyle .= ';border-bottom-style:dashed';
			}
			if ( isset( $infoCodebook['field_oldvals'] ) )
			{
				if ( ! isset( $infoCodebook['element_enum'][$i][2] ) )
				{
					$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
				}
				elseif ( $infoCodebook['element_enum'][$i][2] == 'new' )
				{
					$cellStyle .= ';background:' . REDCapUITweaker::BGC_NEW;
				}
				elseif ( $infoCodebook['element_enum'][$i][2] == 'del' )
				{
					$cellStyle .= ';background:' . REDCapUITweaker::BGC_DEL .
					              ';' . REDCapUITweaker::STL_DEL;
				}
			}
			echo '  <td style="', $cellStyle, ';border-right-style:dashed">&#8203;',
			     $module->escapeHTML( str_replace( ',', '', $infoCodebook['element_enum'][$i][0] ) ),
			     "</td>\n";
			if ( isset( $infoCodebook['element_enum'][$i][2] ) &&
			     $infoCodebook['element_enum'][$i][2] == 'chg' )
			{
				$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
			}
			echo '  <td style="', $cellStyle, ';border-left-style:dashed">&#8203;',
			     $module->escapeHTML( $infoCodebook['element_enum'][$i][1] ), "</td>\n";
		}
		// Output calculations/SQL.
		else
		{
			$cellStyle = $tblStyle . ';border-top-style:dashed';
			if ( $fcChoiceCalc )
			{
				$cellStyle .= ';background:' . REDCapUITweaker::BGC_CHG;
			}
			if ( $infoCodebook['element_type'] == 'sql' )
			{
				$cellStyle .= ';font-size:0.8em';
			}
			echo '  <td colspan="2" style="', $cellStyle, '">',
			     str_replace( [ "\r\n", "\n" ],
			                  ( $infoCodebook['element_type'] == 'sql' ? ' ' : $svbr ),
			                  $module->escapeHTML( $infoCodebook['element_enum'] ) );
			if ( $fcChoiceCalc )
			{
				echo $svbr, '<span style="', REDCapUITweaker::STL_OLD, '">',
				     str_replace(
				         [ "\r\n", "\n" ],
				         ( $infoCodebook['field_oldvals']['element_type'] == 'sql' ? ' ' : $svbr ),
				         $module->escapeHTML( $infoCodebook['field_oldvals']['element_enum'] ) ),
				     '</span>';
			}
			echo "</td>\n";
		}
		// Output the change marker.
		echo '  <td class="chgInd" style="', $tblStyle, ';text-align:center;border-top:none',
		     ( $i < $rowspan - 3 ? ';border-bottom:none' : '' ), '">', $chgInd, "</td>\n";
		echo " </tr>\n";
	}
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff( '.svc' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

