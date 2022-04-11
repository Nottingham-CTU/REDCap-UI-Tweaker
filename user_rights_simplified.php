<?php

// Exit if not in project context or alerts simplified view is disabled.
if ( !isset( $_GET['pid'] ) || ! $module->getSystemSetting('user-rights-simplified-view') )
{
	exit;
}

$lookupYN = [ '1' => $GLOBALS['lang']['design_100'], '0' => $GLOBALS['lang']['design_99'] ];
$lookupDRW = [ '0' => $GLOBALS['lang']['rights_47'],
               '1' => $GLOBALS['lang']['dataqueries_143'],
               '2' => $GLOBALS['lang']['dataqueries_138'],
               '3' => $GLOBALS['lang']['dataqueries_139'],
               '4' => $GLOBALS['lang']['dataqueries_289'],
               '5' => $GLOBALS['lang']['dataqueries_290'] ];
$lookupLock = [ '0' => $GLOBALS['lang']['global_23'],
                '1' => $GLOBALS['lang']['rights_115'],
                '2' => $GLOBALS['lang']['rights_116'] ];
$lookupExport = [ '1' => $GLOBALS['lang']['rights_49'],
                  '2' => $GLOBALS['lang']['rights_48'],
                  '3' => $GLOBALS['lang']['data_export_tool_182']
                            ?? $GLOBALS['lang']['data_export_tool_290'] ];


$listForms = REDCap::getInstrumentNames();

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
	$moduleObj = ExternalModules\ExternalModules::getModuleInstance( $moduleID );
	$moduleName = $moduleObj->getModuleName();
	$listModules[ $moduleID ] = $moduleName;
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

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
<table class="simpRolesTable">
 <tr>
  <th><?php echo $GLOBALS['lang']['rights_198']; ?></th>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <th><?php echo htmlspecialchars( $infoRole['role_name'] ); ?></th>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_135']; /* project design and setup */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['design'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_05']; /* user rights */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['user_rights'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['global_22']; /* data access groups */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['data_access_groups'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_08']; /* calendar */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['calendar'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_356']; /* add/edit reports */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['reports'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo htmlspecialchars( $GLOBALS['lang']['report_builder_78'] ); /* stats */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['graphical'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_01']; /* data import */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['data_import_tool'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_02']; /* data comparison */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['data_comparison_tool'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_07']; /* logging */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['data_logging'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_04']; /* file repository */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['file_repository'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_21']; /* rando */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
	echo '  <td>';
	$list = [];
	foreach ( [ 'random_setup' => $GLOBALS['lang']['rights_142'],
	            'random_dashboard' => $GLOBALS['lang']['rights_143'],
	            'random_perform' => $GLOBALS['lang']['rights_144'] ] as $key => $label )
	{
		if ( $infoRole[ $key ] == '1' )
		{
			$list[] = $label;
		}
	}
	echo empty( $list ) ? $lookupYN['0'] : implode( ', ', $list );
	echo "</td>\n";
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_20']; /* data quality */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
	echo '  <td>';
	$list = [];
	foreach ( [ 'data_quality_design' => $GLOBALS['lang']['design_248'] . '/' .
	                                        $GLOBALS['lang']['global_27'],
	            'data_quality_execute' => $GLOBALS['lang']['dataqueries_80'] ] as $key => $label )
	{
		if ( $infoRole[ $key ] == '1' )
		{
			$list[] = $label;
		}
	}
	echo empty( $list ) ? $lookupYN['0'] : implode( ', ', $list );
	echo "</td>\n";
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['dataqueries_137']; /* data resolution workflow */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupDRW[ $infoRole['data_quality_resolution'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['setup_77']; /* api */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
	echo '  <td>';
	$list = [];
	foreach ( [ 'api_export' => $GLOBALS['lang']['rights_139'],
	            'api_import' => $GLOBALS['lang']['rights_314'] ] as $key => $label )
	{
		if ( $infoRole[ $key ] == '1' )
		{
			$list[] = $label;
		}
	}
	echo empty( $list ) ? $lookupYN['0'] : implode( ', ', $list );
	echo "</td>\n";
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['global_118']; /* mobile app */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['mobile_app'] ],
                 $infoRole['mobile_app_download_data'] == '1'
                   ? ( ' (+' . $GLOBALS['lang']['design_121'] . ')' ) : ''; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_99']; /* create records */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['record_create'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_100']; /* rename records */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['record_rename'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_101']; /* delete records */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['record_delete'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['app_11']; /* record locking customization */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['lock_record_customize'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_97'] . ' ' .
                 $GLOBALS['lang']['rights_371']; /* lock/unlock (instrument) */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupLock[ $infoRole['lock_record'] ]; ?></td>
<?php
}
?>
 </tr>
 <tr>
  <td><?php echo $GLOBALS['lang']['rights_370']; /* lock/unlock (record) */ ?></td>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <td><?php echo $lookupYN[ $infoRole['lock_record_multiform'] ]; ?></td>
<?php
}
?>
 </tr>
<?php
foreach ( $listModules as $moduleID => $moduleName )
{
?>
 <tr>
  <td><?php echo htmlspecialchars( $moduleName ); ?></td>
<?php
	foreach ( $listRoles as $infoRole )
	{
?>
  <td><?php echo $lookupYN[ in_array( $moduleID,
                                      $infoRole['external_module_config'] ) ? '1' : '0' ]; ?></td>
<?php
	}
?>
 </tr>
<?php
}
?>
 <tr>
  <th><?php echo $GLOBALS['lang']['global_89']; ?></th>
<?php
foreach ( $listRoles as $infoRole )
{
?>
  <th><?php echo htmlspecialchars( $infoRole['role_name'] ); ?></th>
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
		echo '<td>' .
		     htmlspecialchars( empty( $list )
		                       ? $GLOBALS['lang']['rights_47'] : implode( ', ', $list ) ) . '</td>';
	}
?>
 </tr>
<?php
}
?>
</table>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

