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


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'user_rights' );

?>
<div class="projhdr"><i class="fas fa-user"></i> User Rights</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpRolesTable{width:95%} .simpRolesTable th, ' +
                   '.simpRolesTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpRolesTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
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
<div>
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable" style="margin-bottom:15px">Select table</button>
</div>
<?php
if ( empty( $listRoles ) )
{
?>
<table class="simpRolesTable">
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
$listModuleData = [];
foreach ( $listModules as $moduleID => $moduleName )
{
	$listModuleData[] = [ $moduleName, $moduleID, true ];
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
	if ( is_bool( $infoData[1] ) )
	{
		if ( $infoData[1] )
		{
			echo " <tr>\n  <th>", $infoData[0], "</th>\n";
			foreach ( $listRoles as $infoRole )
			{
				echo '  <th>', $module->escapeHTML( $infoRole['role_name'] ), "</th>\n";
			}
			echo " </tr>\n";
		}
	}
	elseif ( is_array( $infoData[1] ) )
	{
		echo " <tr>\n  <td>", $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			echo '  <td>';
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
			echo empty( $list ) ? $lookupYN['0'] : implode( ', ', $list );
			echo "</td>\n";
		}
		echo " </tr>\n";
	}
	elseif ( is_bool( $infoData[2] ) )
	{
		echo " <tr>\n  <td>", $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			echo '  <td>',
			     $lookupYN[ in_array( $infoData[1],
			                          $infoRole['external_module_config'] ) ? '1' : '0' ],
			     "</td>\n";
		}
		echo " </tr>\n";
	}
	else
	{
		echo " <tr>\n  <td>", $infoData[0], "</td>\n";
		foreach ( $listRoles as $infoRole )
		{
			echo '  <td>', $infoData[2][ $infoRole[ $infoData[1] ] ], "</td>\n";
		}
		echo " </tr>\n";
	}
}
?>
 <tr>
  <th><?php echo $GLOBALS['lang']['global_89']; ?></th>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <th><?php echo $module->escapeHTML( $infoRole['role_name'] ); ?></th>
<?php
}
?>
 </tr>
<?php
foreach ( $listForms as $formUniqueName => $formName )
{
?>
 <tr>
  <td><?php echo htmlspecialchars( $formName ); ?></td>
<?php
	foreach ( $listRoles as $infoRole )
	{
		$list = [];
		if ( strpos( $infoRole['data_entry'], "[$formUniqueName," ) === false ||
		     strpos( $infoRole['data_entry'], "[$formUniqueName,1]" ) !== false )
		{
			$list[] = $GLOBALS['lang']['rights_138'];
		}
		elseif ( strpos( $infoRole['data_entry'], "[$formUniqueName,2]" ) !== false )
		{
			$list[] = $GLOBALS['lang']['rights_61'];
		}
		elseif ( strpos( $infoRole['data_entry'], "[$formUniqueName,3]" ) !== false )
		{
			$list[] = $GLOBALS['lang']['rights_138'] . ' (+' . $GLOBALS['lang']['rights_137'] . ')';
		}
		if ( ! isset( $infoRole['data_export_instruments'] ) ||
		     $infoRole['data_export_instruments'] == '' )
		{
			if ( $infoRole['data_export_tool'] != '' && $infoRole['data_export_tool'] != '0' )
			{
				$list[] = $GLOBALS['lang']['global_71'] . ' ' .
				          $lookupExport[ $infoRole['data_export_tool'] ];
			}
		}
		elseif ( strpos( $infoRole['data_export_instruments'], "[$formUniqueName," ) !== false )
		{
			preg_match( '/' . preg_quote("[$formUniqueName,", '/') . '([0-3])\\]/',
			            $infoRole['data_export_instruments'], $m );
			if ( $m[1] != '0' )
			{
				$list[] = $GLOBALS['lang']['global_71'] . ' ' . $lookupExport[ $m[1] ];
			}
		}
		else
		{
			$list[] = $GLOBALS['lang']['global_71'] . ' ' . $lookupExport['1'];
		}
		echo '<td>' . htmlspecialchars( empty( $list ) ? '' : implode( ', ', $list ) ) . '</td>';
	}
?>
 </tr>
<?php
}
?>
</table>
<?php
}
$module->ffFormattingFix( '.simpRolesTable' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

