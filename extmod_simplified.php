<?php

namespace Nottingham\REDCapUITweaker;


// Exit if not in project context or reports simplified view is disabled.
$enableSimplifiedView = $module->getSystemSetting('extmod-simplified-view');
if ( !isset( $_GET['pid'] ) ||
     !( $enableSimplifiedView == 'E' || ( $enableSimplifiedView == 'A' && SUPER_USER == 1 ) ) )
{
	exit;
}


$settingsFunctions = $module->getExtModSettings();

$queryModules = $module->query( "SELECT em.directory_prefix module, if( project_id IS NULL, " .
                                "substring( `key` FROM locate('-', `key`) + 1 ), `key` ) setting," .
                                " `value` FROM redcap_external_module_settings ems JOIN " .
                                "redcap_external_modules em ON ems.external_module_id = " .
                                "em.external_module_id WHERE em.external_module_id IN ( SELECT " .
                                "external_module_id FROM redcap_external_module_settings WHERE " .
                                "`key` = 'enabled' AND `value` = 'true' AND project_id = ? ) AND " .
                                "( (project_id IS NULL AND `key` LIKE concat('p',?,'-%')) OR " .
                                "project_id = ? ) AND `key` <> 'enabled' ORDER BY module, setting",
                               [ $module->getProjectID(), $module->getProjectID(),
                                 $module->getProjectID() ] );
$usedFunction = false;
$listModules = [];
while ( $infoModule = $queryModules->fetch_assoc() )
{
	if ( array_key_exists( $infoModule['module'], $settingsFunctions ) )
	{
		$infoAmended = [ 'setting' => $infoModule['setting'], 'value' => $infoModule['value'] ];
		$infoAmended = $settingsFunctions[ $infoModule['module'] ]( $infoAmended );
		if ( is_array( $infoAmended ) )
		{
			$infoModule['setting'] = $infoAmended['setting'];
			$infoModule['value'] = $infoAmended['value'];
			$listModules[] = $infoModule;
			$usedFunction = true;
		}
		elseif ( $infoAmended === true )
		{
			$listModules[] = $infoModule;
		}
	}
	else
	{
		$listModules[] = $infoModule;
	}
}

if ( $usedFunction )
{
	uasort( $listModules, function( $a, $b )
	{
		$cmp1 = strcasecmp( $a['module'], $b['module'] );
		if ( $cmp1 == 0 )
		{
			return strcasecmp( $a['setting'], $b['setting'] );
		}
		return $cmp1;
	} );
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$module->provideSimplifiedViewTabs( 'extmod' );

?>
<div class="projhdr"><i class="fas fa-cube"></i> External Modules</div>
<script type="text/javascript">
  $(function()
  {
    var vStyle = $('<style type="text/css">.simpExtModTable{width:95%} .simpExtModTable th, ' +
                   '.simpExtModTable td{border:solid 1px #000;padding:5px;vertical-align:top} ' +
                   '.simpExtModTable th{background-color:#ddd;font-size:1.1em}</style>')
    $('head').append(vStyle)
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
<div>
 <button class="jqbuttonmed invisible_in_print ui-button ui-corner-all ui-widget"
         id="selectTable" style="margin-bottom:15px">Select table</button>
</div>
<table class="simpExtModTable">
 <tr>
  <th>Module</th>
  <th>Setting</th>
  <th>Value</th>
 </tr>
<?php
foreach ( $listModules as $infoModule )
{
?>
 <tr>
<?php
	// Output the module setting details.
?>
  <td><?php echo $module->escapeHTML( $infoModule['module'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoModule['setting'] ); ?></td>
  <td><?php echo $module->escapeHTML( $infoModule['value'] ); ?></td>
 </tr>
<?php
}
?>
</table>
<?php
$module->ffFormattingFix( '.simpExtModTable' );

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

