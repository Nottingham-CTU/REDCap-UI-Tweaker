<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or reports simplified view is disabled.
$enableSimplifiedView = $module->getSystemSetting('extmod-simplified-view');
if ( !isset( $_GET['pid'] ) ||
     !( $enableSimplifiedView == 'E' || ( $enableSimplifiedView == 'A' && SUPER_USER == 1 ) ) )
{
	exit;
}

$svbr = REDCapUITweaker::SVBR;

$listEvents = [];
if ( \REDCap::isLongitudinal() )
{
	$listEvents = \REDCap::getEventNames( true );
}

function getAllModSettings( $listSettings )
{
	$list = [];
	$fnWalk = function ( $val ) use ( &$list, &$fnWalk )
	{
		if ( $val['type'] == 'descriptive' )
		{
			return;
		}
		if ( isset( $val['sub_settings'] ) )
		{
			$subSettings = $val['sub_settings'];
			unset( $val['sub_settings'] );
		}
		$list[ $val['key'] ] = $val;
		if ( isset( $subSettings ) )
		{
			array_walk( $subSettings, $fnWalk );
		}
	};
	array_walk( $listSettings, $fnWalk );
	return $list;
}



// Get the external modules settings. This will get the settings using the following methods from
// highest to lowest priority. All project settings and any system settings beginning with
// `p[project-id]-` will be included when the database is queried directly.
// (1) If the module has supplied the UI Tweaker with a settings transformation function, get the
//     settings directly from the database and apply the transformation function to each setting.
// (2) If the module defines an exportProjectSettings() function, obtain the settings using this
//     function and disregard settings in the database.
// (3) Retrieve all settings for the module directly from the database as is.

$settingsFunctions = $module->getExtModSettings();

$queryModules = $module->query( "SELECT em.directory_prefix module, if( project_id IS NULL, " .
                                "substring( `key` FROM locate('-', `key`) + 1 ), `key` ) setting," .
                                " `value`FROM redcap_external_module_settings ems JOIN " .
                                "redcap_external_modules em ON ems.external_module_id = " .
                                "em.external_module_id WHERE ( em.external_module_id IN ( SELECT " .
                                "external_module_id FROM redcap_external_module_settings WHERE " .
                                "`key` = 'enabled' AND `value` = 'true' AND project_id = ? ) OR " .
                                "em.external_module_id IN ( SELECT external_module_id FROM " .
                                "redcap_external_module_settings WHERE `key` = 'enabled' AND " .
                                "`value` = 'true' AND project_id IS NULL ) ) AND " .
                                "em.external_module_id NOT IN ( SELECT external_module_id FROM " .
                                "redcap_external_module_settings WHERE `key` = 'enabled' AND " .
                                "`value` = 'false' AND project_id = ? ) AND ( (project_id IS NULL" .
                                " AND `key` COLLATE utf8mb4_unicode_ci LIKE concat('p',?,'-%')) " .
                                "COLLATE utf8mb4_unicode_ci OR project_id = ? ) " .
                                "AND `key` <> 'enabled' AND ( `key` <> " .
                                "'reserved-hide-from-non-admins-in-project-list' OR `value` <> " .
                                "'false' ) ORDER BY module, setting",
                               [ $module->getProjectID(), $module->getProjectID(),
                                 $module->getProjectID(), $module->getProjectID() ] );
$listModules = [];
$listModuleInstances = [];
$listModuleSettings = [];
$listIgnoreExport = [];
while ( $infoModule = $queryModules->fetch_assoc() )
{
	$fetchedModuleInstance = false;
	if ( ! isset( $listModuleInstances[ $infoModule['module'] ] ) )
	{
		$listModuleInstances[ $infoModule['module'] ] =
				\ExternalModules\ExternalModules::getModuleInstance( $infoModule['module'] );
		$fetchedModuleInstance = true;
		$listModuleSettings[ $infoModule['module'] ] =
				getAllModSettings( $listModuleInstances[ $infoModule['module'] ]
				                                            ->getConfig()['project-settings'] );
	}
	if ( array_key_exists( $infoModule['module'], $settingsFunctions ) )
	{
		$infoAmended = [ 'setting' => $infoModule['setting'], 'value' => $infoModule['value'] ];
		$infoAmended = $settingsFunctions[ $infoModule['module'] ]( $infoAmended );
		if ( is_array( $infoAmended ) )
		{
			$infoModule['setting'] = $infoAmended['setting'];
			$infoModule['value'] = $infoAmended['value'];
			$listModules[] = $infoModule;
		}
		elseif ( $infoAmended === true )
		{
			$listModules[] = $infoModule;
		}
	}
	else
	{
		if ( $fetchedModuleInstance )
		{
			if ( method_exists( $listModuleInstances[ $infoModule['module'] ],
			                    'exportProjectSettings' ) )
			{
				$listExportableSettings =
					$listModuleInstances[ $infoModule['module'] ]->exportProjectSettings();
				if ( ! is_array( $listExportableSettings ) ||
				     ( ! empty( $listExportableSettings ) &&
				       ( ! isset( $listExportableSettings[0]['key'] ) ||
				         ! isset( $listExportableSettings[0]['value'] ) ) ) )
				{
					$listIgnoreExport[ $infoModule['module'] ] = true;
				}
				else
				{
					foreach ( $listExportableSettings as $infoExportableSetting )
					{
						$infoSetting = [ 'module' => $infoModule['module'],
						                 'setting' => $infoExportableSetting['key'],
						                 'value' => $infoExportableSetting['value'] ];
						$listModules[] = $infoSetting;
					}
				}
			}
		}
		if ( isset( $listIgnoreExport[ $infoModule['module'] ] ) ||
		     ! method_exists( $listModuleInstances[ $infoModule['module'] ],
		                      'exportProjectSettings' ) )
		{
			$settingConfig = $listModuleSettings[ $infoModule['module'] ][ $infoModule['setting'] ]
			                 ?? [ 'type' => '' ];
			if ( $settingConfig['type'] == 'event-list' )
			{
				if ( ! \REDCap::isLongitudinal() )
				{
					continue;
				}
				if ( substr( $infoModule['value'], 0, 1 ) == '[' )
				{
					$infoModule['value'] = json_decode( $infoModule['value'], true );
					array_walk_recursive( $infoModule['value'],
					                      function(&$v) use ( $listEvents )
					                      { $v = $listEvents[ $v ]; } );
					$infoModule['value'] = json_encode( $infoModule['value'] );
				}
				else
				{
					$infoModule['value'] = $listEvents[ $infoModule['value'] ];
				}
			}
			$listModules[] = $infoModule;
		}
	}
}
unset( $listModuleInstances );



// Build the exportable data structure.
$listExport = [ 'simplified_view' => 'extmod', 'module_settings' => $listModules ];

// Handle upload. Get old/new data structures.
$fileLoadError = false;
if ( isset( $_POST['simp_view_diff_mode'] ) && $_POST['simp_view_diff_mode'] == 'export' )
{
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' .
	        trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', \REDCap::getProjectTitle() ), '_-' ) .
	        '_' . date( 'Ymd' ) . '.sve.json"' );
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
		     $fileData['simplified_view'] != 'extmod' )
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

// Create the combined data structure and determine whether the module settings match.
$listModules = [];
$mapModulesN2O = [];
foreach ( $listNew['module_settings'] as $i => $itemNewMS )
{
	foreach ( $listOld['module_settings'] as $j => $itemOldMS )
	{
		if ( $itemNewMS['module'] == $itemOldMS['module'] &&
		     $itemNewMS['setting'] == $itemOldMS['setting'] )
		{
			$mapModulesN2O[ $i ] = $j;
			continue 2;
		}
	}
}

// Add the module settings to the combined data structure.
foreach ( $listNew['module_settings'] as $i => $itemNewMS )
{
	$itemNewMS['new'] = ! isset( $mapModulesN2O[ $i ] );
	$itemNewMS['changed'] = false;
	$itemNewMS['deleted'] = false;
	if ( ! $itemNewMS['new'] )
	{
		if ( $itemNewMS['value'] != $listOld['module_settings'][ $mapModulesN2O[ $i ] ]['value'] )
		{
			$itemNewMS['changed'] = true;
			$itemNewMS['oldvalue'] = $listOld['module_settings'][ $mapModulesN2O[ $i ] ]['value'];
		}
	}
	$listModules[] = $itemNewMS;
}
foreach ( $listOld['module_settings'] as $i => $itemOldMS )
{
	if ( ! in_array( $i, $mapModulesN2O ) )
	{
		$itemOldMS['new'] = false;
		$itemOldMS['changed'] = false;
		$itemOldMS['deleted'] = true;
		$listModules[] = $itemOldMS;
	}
}

usort( $listModules, function( $a, $b ) use ( $listModuleSettings )
{
	$cmp1 = strcasecmp( $a['module'], $b['module'] );
	if ( $cmp1 == 0 )
	{
		$ordA = -1;
		$ordB = -1;
		if ( isset( $listModuleSettings[ $a['module'] ][ $a['setting'] ] ) )
		{
			$ordA = array_search( $a['setting'],
			                      array_keys( $listModuleSettings[ $a['module'] ] ) );
		}
		if ( isset( $listModuleSettings[ $b['module'] ][ $b['setting'] ] ) )
		{
			$ordB = array_search( $b['setting'],
			                      array_keys( $listModuleSettings[ $b['module'] ] ) );
		}
		if ( $ordA < $ordB )
		{
			return -1;
		}
		if ( $ordA > $ordB )
		{
			return 1;
		}
		return strcasecmp( $a['setting'], $b['setting'] );
	}
	return $cmp1;
} );


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'extmod' );
$tblHdrStyle = REDCapUITweaker::STL_CEL . ';' . REDCapUITweaker::STL_HDR .
               ';background:' . REDCapUITweaker::BGC_HDR;

?>
<div class="projhdr"><i class="fas fa-cube"></i> External Modules</div>
<script type="text/javascript">
  $(function()
  {
    var vFuncSelect = function()
    {
      var vElem = $('.simpExtModTable')[0]
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
<table class="simpExtModTable" style="width:95%">
 <tr>
  <th style="<?php echo $tblHdrStyle; ?>">Module</th>
  <th style="<?php echo $tblHdrStyle; ?>">Setting</th>
  <th style="<?php echo $tblHdrStyle; ?>">Value</th>
 </tr>
<?php
if ( empty( $listModules ) )
{
?>
 <tr>
  <td style="<?php echo REDCapUITweaker::STL_CEL; ?>" colspan="3">
   0 <?php echo $GLOBALS['lang']['multilang_72']; ?>.
  </td>
 </tr>
<?php
}
foreach ( $listModules as $infoModule )
{
	$tblStyle = REDCapUITweaker::STL_CEL .
	            ( $infoModule['new'] ? ';background:' . REDCapUITweaker::BGC_NEW : '' ) .
	            ( $infoModule['deleted'] ? ';background:' . REDCapUITweaker::BGC_DEL . ';' .
	                                       REDCapUITweaker::STL_DEL : '' );
	$tblValStyle = $tblStyle .
	               ( $infoModule['changed'] ? ';background:' . REDCapUITweaker::BGC_CHG : '' );
	$valueStr = str_replace( "\n", $svbr, $module->escapeHTML( $infoModule['value'] ) );
	if ( $infoModule['changed'] )
	{
		$valueStr .= $svbr . '<span style="' . REDCapUITweaker::STL_OLD . '">' .
		             str_replace( "\n", $svbr, $module->escapeHTML( $infoModule['oldvalue'] ) ) .
		             '</span>';
	}
?>
 <tr>
<?php
	// Output the module setting details.
?>
  <td style="<?php echo $tblStyle; ?>">
   <?php echo $module->escapeHTML( $infoModule['module'] ), "\n"; ?>
  </td>
  <td style="<?php echo $tblStyle; ?>">
   <?php echo $module->escapeHTML( $infoModule['setting'] ), "\n"; ?>
  </td>
  <td style="<?php echo $tblValStyle; ?>">
   <?php echo $valueStr, "\n"; ?>
  </td>
 </tr>
<?php
}
?>
</table>
<?php
$module->provideSimplifiedViewDiff( '.sve' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

