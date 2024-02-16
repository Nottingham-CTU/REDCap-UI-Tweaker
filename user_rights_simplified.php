<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or alerts simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('user-rights-simplified-view') )
{
	exit;
}

$lookupYN = [ '1' => $GLOBALS['lang']['design_100'], '0' => '' ];
$lookupDRW = [ '0' => '',
               '1' => $GLOBALS['lang']['dataqueries_143'],
               '2' => $GLOBALS['lang']['dataqueries_138'],
               '3' => $GLOBALS['lang']['dataqueries_139'],
               '4' => $GLOBALS['lang']['dataqueries_289'],
               '5' => $GLOBALS['lang']['dataqueries_290'] ];
$lookupLock = [ '0' => '',
                '1' => $GLOBALS['lang']['global_89'],
                '2' => $GLOBALS['lang']['global_89'] . ' (' . $GLOBALS['lang']['global_34'] . ')' ];
$lookupDataEntry = [ '1' => $GLOBALS['lang']['rights_138'],
                     '2' => $GLOBALS['lang']['rights_61'],
                     '3' => $GLOBALS['lang']['rights_138'] .
                            ' (+' . $GLOBALS['lang']['rights_137'] . ')'];
$lookupExport = [ '1' => $GLOBALS['lang']['rights_49'],
                  '2' => $GLOBALS['lang']['rights_48'],
                  '3' => $GLOBALS['lang']['data_export_tool_182']
                            ?? $GLOBALS['lang']['data_export_tool_290'] ];


$listForms = \REDCap::getInstrumentNames();

$queryRoles = $module->query( "SELECT * " .
                              "FROM redcap_user_roles WHERE project_id = ? ORDER BY role_name",
                              [ $module->getProjectID() ] );
$listRoles = [];
while ( $infoRole = $queryRoles->fetch_assoc() )
{
	if ( $infoRole['external_module_config'] == null )
	{
		$infoRole['external_module_config'] = [];
	}
	else
	{
		$infoRole['external_module_config'] =
			json_decode( $infoRole['external_module_config'], true );
	}
	unset( $infoRole['role_id'], $infoRole['project_id'],
	       $infoRole['unique_role_name'], $infoRole['dts'] );
	$listRoles[] = $infoRole;
}

$queryModules = $module->query( "SELECT directory_prefix FROM redcap_external_modules " .
                                "WHERE external_module_id IN ( SELECT external_module_id " .
                                "FROM redcap_external_module_settings WHERE " .
                                "`key` = 'config-require-user-permission' AND `value` = 'true' ) " .
                                "AND external_module_id IN ( SELECT external_module_id FROM " .
                                "redcap_external_module_settings WHERE `key` = 'enabled' AND " .
                                "`value` = 'true' AND (`project_id` IS NULL OR `project_id` = ?) )" .
                                " ORDER BY directory_prefix", [ $module->getProjectID() ] );

$listModules = [];
while ( $infoModule = $queryModules->fetch_assoc() )
{
	$moduleID = $infoModule['directory_prefix'];
	$moduleObj = \ExternalModules\ExternalModules::getModuleInstance( $moduleID );
	$moduleName = $moduleObj->getModuleName();
	$listModules[ $moduleID ] = $moduleName;
}


// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'user_rights', 'roles' => $listRoles,
                'modules' => $listModules, 'forms' => $listForms ];

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
		     $fileData['simplified_view'] != 'user_rights' )
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
$listRoles = [];
$listModules = [];
$listForms = [];

// Determine whether the roles match.
$mapRolesN2O = [];
foreach ( $listNew['roles'] as $i => $itemNewRole )
{
	foreach ( $listOld['roles'] as $j => $itemOldRole )
	{
		if ( in_array( $j, $mapRolesN2O ) )
		{
			continue;
		}
		if ( $itemNewRole['role_name'] == $itemOldRole['role_name'] )
		{
			$mapRolesN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the roles to the combined data structure.
foreach ( $listNew['roles'] as $i => $itemNewRole )
{
	$itemNewRole['role_new'] = ( ! isset( $mapRolesN2O[ $i ] ) );
	$itemNewRole['role_deleted'] = false;
	if ( ! $itemNewRole['role_new'] )
	{
		$itemNewRole['role_oldvals'] = $listOld['roles'][ $mapRolesN2O[ $i ] ];
	}
	$listRoles[] = $itemNewRole;
}
foreach ( $listOld['roles'] as $i => $itemOldRole )
{
	if ( ! in_array( $i, $mapRolesN2O ) )
	{
		$itemOldRole['role_new'] = false;
		$itemOldRole['role_deleted'] = true;
		$listRoles[] = $itemOldRole;
	}
}

// Add the modules to the combined data structure.
foreach( $listNew['modules'] as $i => $itemNewModule )
{
	$itemNewModule = [ 'name' => $itemNewModule ];
	$itemNewModule['new'] = ( ! isset( $listOld['modules'][ $i ] ) );
	$itemNewModule['deleted'] = false;
	$listModules[ $i ] = $itemNewModule;
}
foreach ( $listOld['modules'] as $i => $itemOldModule )
{
	if ( ! isset( $listNew['modules'][ $i ] ) )
	{
		$itemOldModule = [ 'name' => $itemOldModule ];
		$itemOldModule['new'] = false;
		$itemOldModule['deleted'] = true;
		$listModules[ $i ] = $itemOldModule;
	}
}

// Add the instruments to the combined data structure.
foreach( $listNew['forms'] as $i => $itemNewForm )
{
	$itemNewForm = [ 'name' => $itemNewForm ];
	$itemNewForm['new'] = ( ! isset( $listOld['forms'][ $i ] ) );
	$itemNewForm['deleted'] = false;
	if ( ! $itemNewForm['new'] )
	{
		$itemNewForm['oldname'] = $listOld['forms'][ $i ];
	}
	$listForms[ $i ] = $itemNewForm;
}
foreach ( $listOld['forms'] as $i => $itemOldForm )
{
	if ( ! isset( $listNew['forms'][ $i ] ) )
	{
		$itemOldForm = [ 'name' => $itemOldForm ];
		$itemOldForm['new'] = false;
		$itemOldForm['deleted'] = true;
		$listForms[ $i ] = $itemOldForm;
	}
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'user_rights' );

?>
<div class="projhdr"><i class="fas fa-user"></i> User Rights</div>
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
      var vElem = $('.simpRolesTable')[0]
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
<?php
if ( empty( $listRoles ) )
{
?>
<table class="simpRolesTable" style="width:95%">
 <tr>
  <td style="<?php echo REDCapUITweaker::STL_CEL; ?>">
   0 <?php echo $GLOBALS['lang']['data_export_tool_239'], "\n"; ?>
  </td>
 </tr>
</table>
<?php
}
else
{
?>
<table class="simpRolesTable" style="width:95%">
<?php

// Loop through the standard REDCap privileges and external module project-level privileges.
// 0: Privilege label
// 1: Privilege key(s), or boolean true/false for whether to display header.
// 2: Privilege value label lookup(s), or boolean true if external module.
$listModuleData = [];
foreach ( $listModules as $moduleID => $moduleData )
{
	$listModuleData[] = [ $moduleData['name'], $moduleID, true ];
}
foreach ( array_merge( [
            // Highest Level Privileges header
            [ ucwords( str_replace( ':', '', $GLOBALS['lang']['rights_299'] ) ), true ],
            // - Project design and setup
            [ $GLOBALS['lang']['rights_135'], 'design', $lookupYN ],
            // - User rights
            [ $GLOBALS['lang']['app_05'], 'user_rights', $lookupYN ],
            // - Data access groups
            [ $GLOBALS['lang']['global_22'], 'data_access_groups', $lookupYN ],
            // Basic Privileges header
            [ $GLOBALS['lang']['rights_198'] ?? $GLOBALS['lang']['rights_431'], true ],
            // - Manage MyCap participants
            [ $GLOBALS['lang']['rights_437'], 'mycap_participants', $lookupYN ],
            // - Survey distribution tools
            [ $GLOBALS['lang']['app_24'], 'participants', $lookupYN ],
            // - Calendar
            [ $GLOBALS['lang']['app_08'], 'calendar', $lookupYN ],
            // - Add/edit reports
            [ $GLOBALS['lang']['rights_356'], 'reports', $lookupYN ],
            // - Stats and charts
            [ $GLOBALS['lang']['report_builder_78'], 'graphical', $lookupYN ],
            // - Data import
            [ $GLOBALS['lang']['app_01'], 'data_import_tool', $lookupYN ],
            // - Data comparison
            [ $GLOBALS['lang']['app_02'], 'data_comparison_tool', $lookupYN ],
            // - Logging
            [ $GLOBALS['lang']['app_07'], 'data_logging', $lookupYN ],
            // - File repository
            [ $GLOBALS['lang']['app_04'], 'file_repository', $lookupYN ],
            // - Rando
            [ $GLOBALS['lang']['app_21'], [ 'random_setup', 'random_dashboard', 'random_perform' ],
              [ $GLOBALS['lang']['rights_142'], $GLOBALS['lang']['rights_143'],
                $GLOBALS['lang']['rights_144'] ] ],
            // - Data quality
            [ $GLOBALS['lang']['app_20'], [ 'data_quality_design', 'data_quality_execute' ],
              [ $GLOBALS['lang']['design_248'] . '/' . $GLOBALS['lang']['global_27'],
                $GLOBALS['lang']['dataqueries_80'] ] ],
            // - Data resolution workflow
            [ $GLOBALS['lang']['dataqueries_137'], 'data_quality_resolution', $lookupDRW ],
            // - API
            [ $GLOBALS['lang']['setup_77'], [ 'api_export', 'api_import' ],
              [ $GLOBALS['lang']['global_71'], $GLOBALS['lang']['global_72'] ] ],
            // - Mobile app
            [ $GLOBALS['lang']['global_118'], [ 'mobile_app', 'mobile_app_download_data' ],
              [ $GLOBALS['lang']['design_100'], $GLOBALS['lang']['mobile_app_27'] ] ],
            // - Record locking customization
            [ $GLOBALS['lang']['app_11'], 'lock_record_customize', $lookupYN ],
            // External Modules header
            [ $GLOBALS['lang']['global_142'], ! empty( $listModules ) ]
          ],
          $listModuleData,
          [
            // Data Entry header
            [ $GLOBALS['lang']['bottom_20'], true ],
            // - Create records
            [ $GLOBALS['lang']['rights_99'], 'record_create', $lookupYN ],
            // - Rename records
            [ $GLOBALS['lang']['rights_100'], 'record_rename', $lookupYN ],
            // - Delete records
            [ $GLOBALS['lang']['rights_101'], 'record_delete', $lookupYN ],
            // - Lock/unlock
            [ $GLOBALS['lang']['rights_115'], [ 'lock_record', 'lock_record_multiform' ],
              [ $lookupLock, $GLOBALS['lang']['global_49'] ] ]
          ] ) as $infoData )
{
	// Boolean in pos 1 indicates a header to be shown if the boolean is true.
	if ( is_bool( $infoData[1] ) )
	{
		if ( $infoData[1] )
		{
			$tblStyle = REDCapUITweaker::STL_HDR . ';' . REDCapUITweaker::STL_CEL . ';background:';
			echo " <tr>\n" , '  <th style="', $tblStyle, REDCapUITweaker::BGC_HDR, '">',
			     $infoData[0], "</th>\n";
			foreach ( $listRoles as $infoRole )
			{
				echo '  <th style="', $tblStyle,
				     ( $infoRole['role_new'] ? REDCapUITweaker::BGC_HDR_NEW :
				       ( $infoRole['role_deleted'] ? REDCapUITweaker::BGC_HDR_DEL . ';' .
				         REDCapUITweaker::STL_DEL : REDCapUITweaker::BGC_HDR ) ),
				     '">', $module->escapeHTML( $infoRole['role_name'] ), "</th>\n";
			}
			echo " </tr>\n";
		}
	}
	// Array in pos 1 indicates the row will indicate more than 1 privilege. Pos 2 will contain an
	// array of the strings/lookup-arrays for each privilege in pos 1.
	elseif ( is_array( $infoData[1] ) )
	{
		echo " <tr>\n", '  <td style="', REDCapUITweaker::STL_CEL, '">', $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			$itemChanged = false;
			if ( ! $infoRole['role_new'] && ! $infoRole['role_deleted'] )
			{
				foreach ( $infoData[1] as $key )
				{
					if ( $infoRole[ $key ] != $infoRole['role_oldvals'][ $key ] )
					{
						$itemChanged = true;
						break;
					}
				}
			}
			echo '  <td style="', REDCapUITweaker::STL_CEL;
			if ( $infoRole['role_deleted'] )
			{
				echo ';background:', REDCapUITweaker::BGC_DEL, ';', REDCapUITweaker::STL_DEL;
			}
			elseif ( $infoRole['role_new'] )
			{
				echo ';background:', REDCapUITweaker::BGC_NEW;
			}
			elseif ( $itemChanged )
			{
				echo ';background:', REDCapUITweaker::BGC_CHG;
			}
			echo '">';
			$list = [];
			foreach ( array_combine( $infoData[1], $infoData[2] ) as $key => $label )
			{
				if ( is_array( $label ) && $label[ $infoRole[ $key ] ] != '' )
				{
					$list[] = $label[ $infoRole[ $key ] ];
				}
				elseif ( ! is_array( $label ) && $infoRole[ $key ] == '1' )
				{
					$list[] = $label;
				}
			}
			echo empty( $list ) ? '' : implode( ', ', $list );
			echo "</td>\n";
		}
		echo " </tr>\n";
	}
	// If pos 2 is a boolean, this indicates the privilege in pos 1 is an external module, which
	// will be looked up in the external_module_config array.
	elseif ( is_bool( $infoData[2] ) )
	{
		echo " <tr>\n" , '  <td style="', REDCapUITweaker::STL_CEL,
		     ( $listModules[ $infoData[1] ]['new']
		       ? ';background:' . REDCapUITweaker::BGC_NEW : '' ),
		     ( $listModules[ $infoData[1] ]['deleted']
		       ? ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL : '' ),
		     '">', $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			$itemChanged = ( ! $infoRole['role_new'] && ! $infoRole['role_deleted'] &&
			                 in_array( $infoData[1], $infoRole['external_module_config'] ) !=
			                 in_array( $infoData[1],
			                           $infoRole['role_oldvals']['external_module_config'] ) );
			if ( $listModules[ $infoData[1] ]['deleted'] &&
			     ! $infoRole['role_new'] && ! $infoRole['role_deleted'] )
			{
				$roleModuleConfig = $infoRole['role_oldvals']['external_module_config'];
			}
			else
			{
				$roleModuleConfig = $infoRole['external_module_config'];
			}
			echo '  <td style="', REDCapUITweaker::STL_CEL;
			if ( $listModules[ $infoData[1] ]['deleted'] || $infoRole['role_deleted'] )
			{
				echo ';background:', REDCapUITweaker::BGC_DEL, ';', REDCapUITweaker::STL_DEL;
			}
			elseif ( $listModules[ $infoData[1] ]['new'] || $infoRole['role_new'] )
			{
				echo ';background:', REDCapUITweaker::BGC_NEW;
			}
			elseif ( $itemChanged )
			{
				echo ';background:', REDCapUITweaker::BGC_CHG;
			}
			echo '">', $lookupYN[ in_array( $infoData[1], $roleModuleConfig ) ? '1' : '0' ],
			     "</td>\n";
		}
		echo " </tr>\n";
	}
	// Otherwise, pos 1 contains a single privilege and pos 2 contains the lookup array for it.
	else
	{
		echo " <tr>\n", '  <td style="', REDCapUITweaker::STL_CEL, '">', $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			echo '  <td style="', REDCapUITweaker::STL_CEL;
			if ( $infoRole['role_new'] )
			{
				echo ';background:', REDCapUITweaker::BGC_NEW;
			}
			elseif ( $infoRole['role_deleted'] )
			{
				echo ';background:', REDCapUITweaker::BGC_DEL, ';', REDCapUITweaker::STL_DEL;
			}
			elseif ( $infoRole[ $infoData[1] ] != $infoRole['role_oldvals'][ $infoData[1] ] )
			{
				echo ';background:' . REDCapUITweaker::BGC_CHG;
			}
			echo '">', $infoData[2][ $infoRole[ $infoData[1] ] ], "</td>\n";
		}
		echo " </tr>\n";
	}
}

// Loop through the instruments for the project.
$tblStyle = REDCapUITweaker::STL_HDR . ';' . REDCapUITweaker::STL_CEL . ';background:';
echo " <tr>\n" , '  <th style="', $tblStyle, REDCapUITweaker::BGC_HDR, '">',
     $GLOBALS['lang']['global_89'], "</th>\n";
foreach ( $listRoles as $infoRole )
{
	echo '  <th style="', $tblStyle,
	     ( $infoRole['role_new'] ? REDCapUITweaker::BGC_HDR_NEW :
	       ( $infoRole['role_deleted'] ? REDCapUITweaker::BGC_HDR_DEL . ';' .
	         REDCapUITweaker::STL_DEL : REDCapUITweaker::BGC_HDR ) ),
	     '">', $module->escapeHTML( $infoRole['role_name'] ), "</th>\n";
}
echo " </tr>\n";
foreach ( $listForms as $formUniqueName => $infoForm )
{
	echo " <tr>\n" , '  <td style="', REDCapUITweaker::STL_CEL,
	     ( $infoForm['new'] ? ';background:' . REDCapUITweaker::BGC_NEW : '' ),
	     ( $infoForm['deleted']
	       ? ';background:' . REDCapUITweaker::BGC_DEL . ';' . REDCapUITweaker::STL_DEL : '' ),
	     ( ! $infoForm['new'] && ! $infoForm['deleted'] && $infoForm['name'] != $infoForm['oldname']
	       ? ';background:' . REDCapUITweaker::BGC_CHG : '' ),
	     '">', $infoForm['name'], "</td>\n";
	foreach ( $listRoles as $infoRole )
	{
		$dataEntry = '';
		$dataEntryOld = '';
		$dataExport = '';
		$dataExportOld = '';
		if ( strpos( $infoRole['data_entry'], "[$formUniqueName," ) !== false )
		{
			preg_match( '/' . preg_quote("[$formUniqueName,", '/') . '([0-3])\\]/',
			            $infoRole['data_entry'], $m );
			$dataEntry = $m[1];
		}
		if ( ! $infoRole['role_new'] && ! $infoRole['role_deleted'] &&
		     strpos( $infoRole['role_oldvals']['data_entry'], "[$formUniqueName," ) !== false )
		{
			preg_match( '/' . preg_quote("[$formUniqueName,", '/') . '([0-3])\\]/',
			            $infoRole['role_oldvals']['data_entry'], $m );
			$dataEntryOld = $m[1];
		}
		if ( isset( $infoRole['data_export_instruments'] ) &&
		     strpos( $infoRole['data_export_instruments'], "[$formUniqueName," ) !== false )
		{
			preg_match( '/' . preg_quote("[$formUniqueName,", '/') . '([0-3])\\]/',
			            $infoRole['data_entry'], $m );
			$dataExport = $m[1];
		}
		elseif ( $infoRole['data_export_tool'] != '' )
		{
			$dataExport = $infoRole['data_export_tool'];
		}
		if ( ! $infoRole['role_new'] && ! $infoRole['role_deleted'] )
		{
			if ( isset( $infoRole['role_oldvals']['data_export_instruments'] ) &&
			     strpos( $infoRole['role_oldvals']['data_export_instruments'],
			             "[$formUniqueName," ) !== false )
			{
				preg_match( '/' . preg_quote("[$formUniqueName,", '/') . '([0-3])\\]/',
				            $infoRole['role_oldvals']['data_export_instruments'], $m );
				$dataExportOld = $m[1];
			}
			elseif ( $infoRole['role_oldvals']['data_export_tool'] != '' )
			{
				$dataExportOld = $infoRole['role_oldvals']['data_export_tool'];
			}
			if ( $infoForm['deleted'] )
			{
				$dataEntry = $dataEntryOld;
				$dataExport = $dataExportOld;
			}
		}
		echo '  <td style="', REDCapUITweaker::STL_CEL;
		if ( $infoForm['deleted'] || $infoRole['role_deleted'] )
		{
			echo ';background:', REDCapUITweaker::BGC_DEL, ';', REDCapUITweaker::STL_DEL;
		}
		elseif ( $infoForm['new'] || $infoRole['role_new'] )
		{
			echo ';background:', REDCapUITweaker::BGC_NEW;
		}
		elseif ( $dataEntry !== $dataEntryOld || $dataExport !== $dataExportOld )
		{
			echo ';background:', REDCapUITweaker::BGC_CHG;
		}
		echo '">';
		if ( $dataEntry === '' || $dataExport === '' )
		{
			if ( ! $infoForm['deleted'] && ! $infoRole['role_deleted'] )
			{
				echo '<b><i>', $GLOBALS['lang']['missing_data_mdc_np'], '</i></b>';
			}
		}
		else
		{
			$list = [];
			if ( $dataEntry != '0' )
			{
				$list[] = $module->escapeHTML( $lookupDataEntry[ $dataEntry ] );
			}
			if ( $dataExport != '0' )
			{
				$list[] = $module->escapeHTML( $GLOBALS['lang']['global_71'] . ' ' .
				                               $lookupExport[ $dataExport ] );
			}
			echo empty( $list ) ? '' : implode( REDCapUITweaker::SVBR, $list );
		}
		echo "</td>\n";
	}
	echo " </tr>\n";
}
?>
</table>
<?php
}
$module->provideSimplifiedViewDiff();

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

