<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or data quality rules simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('codebook-simplified-view') )
{
	exit;
}

function codebookEscape( $text )
{
	return trim( nl2br(
	            preg_replace( "/(\r|\n)+/", "\n",
	                          strip_tags( \label_decode( preg_replace( '/<\/(p|h[1-6]|tr)>/',
	                                                                   "$0\n", $text ) ) ) ) ) );
}

$lookupYN = [ '1' => $GLOBALS['lang']['design_100'], '0' => $GLOBALS['lang']['design_99'] ];


$queryFDL = $module->query( 'SELECT form_name, control_condition, event_id FROM ' .
                            'redcap_form_display_logic_conditions c JOIN (SELECT control_id, ' .
                            'form_name, group_concat(event_id SEPARATOR \',\') event_id FROM ' .
                            'redcap_form_display_logic_targets GROUP BY control_id, form_name) t ' .
                            'ON c.control_id = t.control_id WHERE project_id = ?',
                            [ $module->getProjectID() ] );
$listFDL = [];
while ( $infoFDL = $queryFDL->fetch_assoc() )
{
	if ( ! isset( $listFDL[ $infoFDL['form_name'] ] ) )
	{
		$listFDL[ $infoFDL['form_name'] ] = [];
	}
	$listFDL[ $infoFDL['form_name'] ][] = [ 'condition' => $infoFDL['control_condition'],
	                                        'event_id' => ( $infoFDL['event_id'] === null ? null :
	                                                        explode(',', $infoFDL['event_id']) ) ];
}
$listEventNames = \REDCap::getEventNames( true );


$queryCodebook = $module->query( 'SELECT *, (SELECT 1 FROM redcap_surveys WHERE project_id = ' .
                                 'm.project_id AND form_name = m.form_name) enabled_as_survey ' .
                                 'FROM redcap_metadata m WHERE project_id = ? ' .
                                 'ORDER BY field_order', [ $module->getProjectID() ] );
$listCodebook = [];
while ( $infoCodebook = $queryCodebook->fetch_assoc() )
{
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
	// Set element validation string.
	$infoCodebook['element_validation'] = $infoCodebook['element_validation_type'] ?? '';
	if ( $infoCodebook['element_validation_min'] != '' )
	{
		$infoCodebook['element_validation'] .=
				( $infoCodebook['element_validation'] == '' ? '' : ', ' ) .
				$GLOBALS['lang']['design_486'] . ' ' . $infoCodebook['element_validation_min'];
	}
	if ( $infoCodebook['element_validation_max'] != '' )
	{
		$infoCodebook['element_validation'] .=
				( $infoCodebook['element_validation'] == '' ? '' : ', ') .
				$GLOBALS['lang']['design_487'] . ' ' . $infoCodebook['element_validation_max'];
	}
	$infoCodebook['element_validation'] = $infoCodebook['element_validation'] == ''
	                                        ? '' : ' (' . $infoCodebook['element_validation'] . ')';
	// Set default field rowspan.
	$infoCodebook['rowspan'] = 1;
	// For dropdown/radio/checkbox fields, get the options and increment the rowspan.
	if ( in_array( $infoCodebook['element_type'], [ 'dropdown', 'radio', 'checkbox' ] ) )
	{
		if ( $infoCodebook['element_type'] == 'checkbox' &&
		     strpos( $infoCodebook['misc'], '@SQLCHECKBOX' ) !== false )
		{
			$infoCodebook['element_type'] = 'sqlcheckbox';
			$infoCodebook['element_enum'] = \Form::getValueInActionTag( $infoCodebook['misc'],
			                                                            '@SQLCHECKBOX' );
			$infoCodebook['rowspan'] = 2;
		}
		else
		{
			$infoCodebook['element_enum'] =
				array_map( function( $i ) { return explode( ', ', trim( $i ), 2 ); },
				           explode( '\n', $infoCodebook['element_enum'] ) );
			$infoCodebook['rowspan'] += count( $infoCodebook['element_enum'] );
		}
	}
	// For other non-blank element_enum (calc, sql), set the rowspan to 2.
	elseif ( $infoCodebook['element_enum'] != '' )
	{
		$infoCodebook['rowspan'] = 2;
	}
	// If @CALCTEXT action tag is used, move the calculation to the calculations column and set the
	// rowspan to 2.
	elseif ( $infoCodebook['element_type'] == 'text' &&
	         strpos( $infoCodebook['misc'], '@CALCTEXT' ) !== false )
	{
		$calcString = \Form::getValueInParenthesesActionTag( $infoCodebook['misc'], '@CALCTEXT' );
		if ( $calcString != '' )
		{
			$infoCodebook['element_type'] = 'calctext';
			$infoCodebook['element_enum'] = $calcString;
			$infoCodebook['misc'] =
				preg_replace( '/@CALCTEXT\s*\(\s*' . preg_quote( $calcString, '/' ) . '\s*\)/',
				              '@CALCTEXT', $infoCodebook['misc'] );
			$infoCodebook['rowspan'] = 2;
		}
	}
	// If @CALCDATE action tag is used, move the parameters to the calculations column and set the
	// rowspan to 2.
	elseif ( $infoCodebook['element_type'] == 'text' &&
	         strpos( $infoCodebook['misc'], '@CALCDATE' ) !== false )
	{
		$calcString = \Form::getValueInParenthesesActionTag( $infoCodebook['misc'], '@CALCDATE' );
		if ( $calcString != '' )
		{
			$infoCodebook['element_type'] = 'calcdate';
			$infoCodebook['element_enum'] = $calcString;
			$infoCodebook['misc'] =
				preg_replace( '/@CALCDATE\s*\(\s*' . preg_quote( $calcString, '/' ) . '\s*\)/',
				              '@CALCDATE', $infoCodebook['misc'] );
			$infoCodebook['rowspan'] = 2;
		}
	}
	// If an @IF action tag is used, remove any line breaks within it.
	for ( $i = 0; preg_match( '/@IF\s*\(/', $infoCodebook['misc'] ) && $i < 100; $i++ )
	{
		$ifString = \Form::getValueInParenthesesActionTag( $infoCodebook['misc'], '@IF' );
		$newIfString = str_replace( ["\r", "\n"], ' ', $ifString );
		$infoCodebook['misc'] =
			preg_replace( '/@IF\s*\(\s*' . preg_quote( $ifString, '/' ) . '\s*\)/',
			              '@IF(' . $newIfString . ')', $infoCodebook['misc'] );
	}
	// If a field note exists, ensure the rowspan is at least 2.
	if ( $infoCodebook['rowspan'] == 1 && $infoCodebook['element_note'] != '' )
	{
		$infoCodebook['rowspan'] = 2;
	}
	// Add codebook item to list.
	$listCodebook[] = $infoCodebook;
}
$prevFormName = '';


// If not a longitudinal project, show which forms are repeating.
// (On longitudinal projects, this is shown on the instrument/event mapping simplified view.)
$listRepeating = [];
if ( ! \REDCap::isLongitudinal() )
{
	$queryRepeating = $module->query( 'SELECT form_name FROM redcap_events_repeat WHERE event_id ' .
	                                  '= (SELECT em.event_id FROM redcap_events_metadata em JOIN ' .
	                                  'redcap_events_arms ea ON em.arm_id = ea.arm_id WHERE ' .
	                                  'ea.project_id = ?) AND form_name IS NOT NULL',
	                                  [ $module->getProjectID() ] );
	while ( $infoRepeating = $queryRepeating->fetch_assoc() )
	{
		$listRepeating[] = $infoRepeating['form_name'];
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

?>
<div class="projhdr"><i class="fas fa-book"></i> <?php echo $GLOBALS['lang']['global_116']; ?></div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpCodebookTable{width:95%;border:solid 1px #000} ' +
                   '.simpCodebookTable th, .simpCodebookTable td{border:solid 1px #000;' +
                     'border-bottom-style:dashed;padding:5px;vertical-align:top} ' +
                   '.simpCodebookTable th{background-color:#ddd;font-size:1.1em} ' +
                   '.simpCodebookForm{background-color:#bbb;font-weight:bold;font-size:1.1em} ' +
                   '.simpCodebookFormName{color:#444;font-weight:normal} ' +
                   '.simpCodebookFormSurvey{color:#070;font-size:0.9em} ' +
                   '.simpCodebookFormDispLogic{color:#454;font-size:0.8em} ' +
                   '.simpCodebookSection{background-color:#eed} .colID{text-align:center} ' +
                   'td.simpCodebookFieldNote{color:#669;font-size:0.9em;border-top-style:dashed} ' +
                   'td.simpCodebookFieldEnum{border-style:dashed}</style>')
    $('head').append(vStyle)
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
  })
</script>
<div>
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideID" style="margin-bottom:15px">Show/Hide #</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="showHideComp" style="margin-bottom:15px">Show/Hide Complete? fields</button>
 &nbsp;
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable" style="margin-bottom:15px">Select table</button>
</div>
<table class="simpCodebookTable">
 <tr>
  <th class="colID">#</th>
  <th><?php echo $GLOBALS['lang']['design_484']; ?></th>
  <th><?php echo $GLOBALS['lang']['global_40'], ' / ', $GLOBALS['lang']['database_mods_69']; ?></th>
  <th colspan="2"><?php echo $GLOBALS['lang']['design_494']; ?></th>
  <th><?php echo $GLOBALS['lang']['design_527']; ?></th>
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
?>
 <tr>
  <td class="colID simpCodebookForm"></td>
  <td colspan="5" class="simpCodebookForm"><?php
		echo $module->escapeHTML( $infoCodebook['form_menu_description'] ),
		     '&nbsp; <span class="simpCodebookFormName">',
		     $module->escapeHTML( $infoCodebook['form_name'] ), '</span>';
		if ( in_array( $infoCodebook['form_name'], $listRepeating ) )
		{
			echo ' &#10227;';
		}
		if ( $infoCodebook['enabled_as_survey'] == '1' )
		{
			echo ' &nbsp;&nbsp;&nbsp;<span class="simpCodebookFormSurvey">',
			     $GLOBALS['lang']['design_789'], '</span>';
		}
		if ( isset( $listFDL[ $infoCodebook['form_name'] ] ) )
		{
			$infoFDL =
				array_map( function ( $i ) use ( $listEventNames )
				           {
				               if ( $i['event_id'] === null )
				               {
				                   return $i['condition'];
				               }
				               $listEvents = array_map( function( $e ) use ( $listEventNames )
				                                        { return $listEventNames[$e]; },
				                                        $i['event_id'] );
				               return "([event-name] = '" .
				                      implode( "' or [event-name] = '", $listEvents ) .
				                      "') and (" . $i['condition'] . ')';
				           },
				           $listFDL[ $infoCodebook['form_name'] ] );
			if ( count( $infoFDL ) == 1 )
			{
				$formDisplayLogic = $infoFDL[0];
			}
			else
			{
				$formDisplayLogic = '(' . implode( ') or (', $infoFDL ) . ')';
			}
			echo '<br><span class="simpCodebookFormDispLogic">', $GLOBALS['lang']['design_985'],
			     ': ', $module->escapeHTML( $formDisplayLogic ), '</span>';
		}
?></td>
 </tr>
<?php
		$prevFormName = $infoCodebook['form_name'];
	}
	// If the field includes a section header, output a row for it.
	if ( $infoCodebook['element_preceding_header'] != '' )
	{
?>
 <tr<?php echo $rowClass; ?>>
  <td class="colID"></td>
  <td class="simpCodebookSection"></td>
  <td colspan="4" class="simpCodebookSection"><?php
		echo codebookEscape( $infoCodebook['element_preceding_header'] );
?></td>
 </tr>
<?php

	}

	// Output the row for the field.

?>
 <tr<?php echo $rowClass; ?>>
  <td class="colID" rowspan="<?php echo $infoCodebook['rowspan']; ?>"><?php

	echo intval( $infoCodebook['field_order'] );

?></td>
  <td rowspan="<?php echo $infoCodebook['rowspan']; ?>"><?php

	// Output the field name and branching logic.
	echo $module->escapeHTML( $infoCodebook['field_name'] );
	if ( $infoCodebook['branching_logic'] )
	{
		echo '<br><span style="color:#787">', $GLOBALS['lang']['design_485'], '<br>',
		     $module->escapeHTML( $infoCodebook['branching_logic'] ), '</span>';
	}

?></td>
  <td rowspan="<?php
	echo $infoCodebook['rowspan'] - ( $infoCodebook['element_note'] == '' ? 0 : 1 ); ?>"><?php
	// Output the field label.
	echo codebookEscape( $infoCodebook['element_label'] ); ?></td>
  <td colspan="2" rowspan="<?php

	// Output the field type, validation, alignment, survey attributes.
	echo ( $infoCodebook['element_enum'] == '' &&
	       $infoCodebook['element_note'] != '' ) ? 2 : 1; ?>"><?php
	echo $module->escapeHTML( $infoCodebook['element_type'] .
	                          $infoCodebook['element_validation'] ), ', ',
	     $GLOBALS['lang'][ $infoCodebook['field_req'] == '1' ? 'api_docs_063' : 'api_docs_064' ];
	if ( $infoCodebook['field_phi'] == '1' )
	{
		echo ' ', preg_replace( '/[^A-Za-z]/', '', $GLOBALS['lang']['design_103'] );
	}
	if ( $infoCodebook['custom_alignment'] != '' )
	{
		echo ', ', $module->escapeHTML( $infoCodebook['custom_alignment'] );
	}
	if ( $infoCodebook['question_num'] != '' )
	{
		echo ', ', $GLOBALS['lang']['survey_437'], ' ',
		     strtolower( $GLOBALS['lang']['design_491'] ),
		     $module->escapeHTML( $infoCodebook['question_num'] );
	}
	if ( $infoCodebook['stop_actions'] != '' )
	{
		echo ', ',  $GLOBALS['lang']['survey_437'], ' ',
		     strtolower( $GLOBALS['lang']['database_mods_108'] ), ': ',
		     $module->escapeHTML( $infoCodebook['stop_actions'] );
	}

?></td>
  <td rowspan="<?php echo $infoCodebook['rowspan']; ?>"><?php

	// Output the field annotation.
	echo codebookEscape( $infoCodebook['misc'] );

?></td>
 </tr>
<?php
	for ( $i = 0; $i < $infoCodebook['rowspan'] - 1; $i++ )
	{
?>
 <tr<?php echo $rowClass; ?>>
<?php

		// Output the field note.
		if ( $infoCodebook['element_note'] != '' && $i == $infoCodebook['rowspan'] - 2 )
		{
?>
  <td class="simpCodebookFieldNote">
   <?php echo $module->escapeHTML( $infoCodebook['element_note'] ), "\n"; ?>
  </td>
<?php
		}

		// Output the field values/labels for radio/dropdown/checkbox fields.
		if ( is_array( $infoCodebook['element_enum'] ) )
		{
?>
  <td class="simpCodebookFieldEnum">
   <?php echo $module->escapeHTML( $infoCodebook['element_enum'][$i][0] ), "\n"; ?>
  </td>
  <td class="simpCodebookFieldEnum">
   <?php echo $module->escapeHTML( $infoCodebook['element_enum'][$i][1] ), "\n"; ?>
  </td>
<?php
		}
		// Output calculations/SQL.
		elseif ( $infoCodebook['element_enum'] != '' )
		{
?>
  <td colspan="2" class="simpCodebookFieldEnum">
   <?php echo $module->escapeHTML( $infoCodebook['element_enum'] ), "\n"; ?>
  </td>
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
$module->ffFormattingFix( '.simpCodebookTable' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

