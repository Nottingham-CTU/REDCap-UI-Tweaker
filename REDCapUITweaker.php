<?php

namespace Nottingham\REDCapUITweaker;


class REDCapUITweaker extends \ExternalModules\AbstractExternalModule
{


	// Initialise module when enabled.
	// Set default system settings and enable in all projects.

	function redcap_module_system_enable()
	{
		$this->setSystemSetting( 'field-types-order', '1,2,7,5,6,11,3|4,8,10,9' );
		$this->setSystemSetting( 'field-default-required', '1' );
		$this->setSystemSetting( 'submit-option-tweak', '0' );
		$this->setSystemSetting( 'enabled', true );
	}




	// Perform actions on every page (before page starts loading).

	function redcap_every_page_before_render( $project_id = null )
	{

		// If the option to redirect users with one project to that project is enabled, perform the
		// redirect from the my projects page the first time that page is loaded in that session.

		if ( $project_id === null && !isset( $_SESSION['module_uitweaker_single_proj_redirect'] ) &&
		     $this->getSystemSetting( 'single-project-redirect' ) &&
		     in_array( $_GET['action'], [ '', 'myprojects' ] ) &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT_PARENT ), 9 ) == 'index.php' )
		{
			$projIDs = $this->query( 'SELECT group_concat(p.project_id SEPARATOR \',\') ' .
			                         'FROM redcap_user_rights u ' .
			                         'JOIN redcap_projects p ON u.project_id = p.project_id ' .
			                         'WHERE u.username = ? AND p.date_deleted IS NULL',
			                         USERID )->fetch_row()[0];
			if ( strlen( $projIDs ) > 0 && strpos( $projIDs, ',' ) === false )
			{
				header( 'Location: ' . APP_PATH_WEBROOT_FULL . 'redcap_v' . REDCAP_VERSION .
				        '/index.php?pid=' . $projIDs );
				$_SESSION['module_uitweaker_single_proj_redirect'] = true;
				$this->exitAfterHook();
			}
		}


		// Exit the function here if a system-level page.

		if ( $project_id === null )
		{
			return;
		}


		// If the project home page is to redirect to another URL, and this is the project home
		// page, then perform the redirect.

		$projectHomeRedirect = $this->getProjectSetting( 'project-home-redirect' );

		if ( $projectHomeRedirect != '' &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 9 ) == 'index.php' &&
		     ( ! isset( $_GET['route'] ) || ! isset( $_GET['__redirect'] ) ) )
		{
			if ( ! preg_match( '!^https?://!', $projectHomeRedirect ) )
			{
				$projectHomeRedirect = str_replace( 'pid=*', 'pid=' . $project_id,
				                                    $projectHomeRedirect );
				$projectHomeRedirect = APP_PATH_WEBROOT_FULL . 'redcap_v' . REDCAP_VERSION .
				                       ( substr( $projectHomeRedirect, 0, 1 ) == '/' ? '' : '/' ) .
				                       $projectHomeRedirect;
			}
			header( 'Location: ' . $projectHomeRedirect );
			$this->exitAfterHook();
		}


	}




	// Perform actions on every page, or on pages without specific hooks
	// (after page starts loading).

	function redcap_every_page_top( $project_id = null )
	{
		if ( $project_id === null )
		{
			return;
		}


		// If the external modules page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 35 ) ==
		                                                     'ExternalModules/manager/project.php' )
		{

?>
<script type="text/javascript">
  $(function()
  {
    $('tr[data-module="<?php echo preg_replace( '/_v[^_]*$/', '', $this->getModuleDirectoryName() );
?>"] button.external-modules-disable-button').css('display','none')
  })
</script>
<?php

		}


		// If an instrument designer page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 26 ) == 'Design/online_designer.php' &&
			 isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] != '' )
		{

?>
<script type="text/javascript">
  $(function() {
<?php


			// Set field 'required' option on by default.

			if ( $this->getSystemSetting( 'field-default-required' ) != '0' )
			{

?>
    setInterval(function() {
      var vEditID = $('#sq_id')
      if ( vEditID.length > 0 && vEditID[0].value == '' )
      {
        if ( $('#div_field_req')[0].style.display != 'none' )
        {
          $('#field_req0')[0].checked = false
          $('#field_req1')[0].checked = true
          $('#field_req')[0].value = '1'
          $('#div_field_req')[0].style.display = 'none'
        }
        if ( $('#field_type')[0].value == 'descriptive' ||
             $('#field_type')[0].value == 'calc' )
        {
          $('#field_req0')[0].checked = true
          $('#field_req1')[0].checked = false
          $('#field_req')[0].value = '0'
        }
      }
      var vMEditID = $('#old_grid_name')
      if ( vMEditID.length > 0 )
      {
        var vMInputReq = $('.field_req_matrix')
        var vMInputLab = $('.field_labelmatrix')
        var vMInputNam = $('.field_name_matrix')
        if ( vMInputReq.length == vMInputLab.length &&
             vMInputReq.length == vMInputNam.length )
        {
          for ( var i = 0; i < vMInputReq.length; i++ )
          {
            if ( ( vMInputLab[ i ].value == '' && vMInputNam[ i ].value == '' ) )
            {
              vMInputReq[ i ].checked = true
            }
          }
        }
      }
    }, 500 )
<?php

			}


			// Rearrange the list of field types.

			$fieldTypesAll = $this->getSystemSetting( 'field-types-order' );
			if ( preg_match( '/^[1-9][0-9]?([,][1-9][0-9]?)*([|][1-9][0-9]?([,][1-9][0-9]?)*)?$/',
			     $fieldTypesAll ) )
			{
				$fieldTypesAll = explode( '|', $fieldTypesAll );
				$fieldTypesCommon = explode( ',', $fieldTypesAll[0] );
				$fieldTypesOther = isset( $fieldTypesAll[1] )
				                   ? explode( ',', $fieldTypesAll[1] ) : '';
				$fieldTypesUsed = [ '12' => true, '13' => true ];

?>
    setTimeout(function() {
      var vTypeOptions = $('#field_type option')
      var vTypeList = $('#field_type')
      vTypeList.html('')
      var vCommonTypeList =
            $( '<optgroup label="Common Field Types"></optgroup>' )
      var vHeaderTypeList =
            $( '<optgroup label="Headers and Descriptions"></optgroup>' )
      var vOtherTypeList =
            $( '<optgroup label="Other Field Types"></optgroup>' )
<?php

				foreach ( $fieldTypesCommon as $fieldTypeCode )
				{
					$fieldTypesUsed[$fieldTypeCode] = true;

?>
      if ( typeof( vTypeOptions[<?php echo $fieldTypeCode; ?>] ) != 'undefined' )
      {
        vCommonTypeList.append( vTypeOptions[<?php echo $fieldTypeCode; ?>] )
      }
<?php

				}
				foreach ( $fieldTypesOther as $fieldTypeCode )
				{
					$fieldTypesUsed[$fieldTypeCode] = true;

?>
      if ( typeof( vTypeOptions[<?php echo $fieldTypeCode; ?>] ) != 'undefined' )
      {
        vOtherTypeList.append( vTypeOptions[<?php echo $fieldTypeCode; ?>] )
      }
<?php

				}
				for ( $fieldTypeCode = 1; $fieldTypeCode < 25; $fieldTypeCode++ )
				{
					if ( ! isset( $fieldTypesUsed[$fieldTypeCode] ) )
					{

?>
      if ( typeof( vTypeOptions[<?php echo $fieldTypeCode; ?>] ) != 'undefined' )
      {
        vOtherTypeList.append( vTypeOptions[<?php echo $fieldTypeCode; ?>] )
      }
<?php

					}
				}

?>
      vHeaderTypeList.append( vTypeOptions[13] ) // new section
      vHeaderTypeList.append( vTypeOptions[12] ) // desc. text
      vTypeList.append( vCommonTypeList )
      vTypeList.append( vHeaderTypeList )
      vTypeList.append( vOtherTypeList )
      vTypeList.prepend( vTypeOptions[0] )
    }, 800 )
<?php

			}

?>
  })
</script>
<?php

		} // end if instrument designer


		// If any data entry page, and alternate status icons enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 10 ) == 'DataEntry/' &&
			 $this->getSystemSetting( 'alternate-status-icons' ) )
		{

?>
<script type="text/javascript">
(function()
{
  var vFuncNewIcons = function()
  {
    $('img[src$="circle_gray.png"]').attr('src','<?php echo $this->getIconUrl( 'gray' ); ?>')
    $('img[src$="circle_red.png"]').attr('src','<?php echo $this->getIconUrl( 'red '); ?>')
    $('img[src$="circle_red_stack.png"]').attr('src','<?php echo $this->getIconUrl( 'reds' ); ?>')
    $('img[src$="circle_blue_stack.png"]').attr('src','<?php echo $this->getIconUrl( 'blues' ); ?>')
  }
  $(function(){
    $('img[src$="circle_red_stack.png"], img[src$="circle_blue_stack.png"]').on('click',
      function(){setTimeout(vFuncNewIcons,500);setTimeout(vFuncNewIcons,1000)})
  })
  $(vFuncNewIcons)
})()
</script>
<?php

		} // end if data entry page


	}




	// Perform actions on data entry forms.

	function redcap_data_entry_form( $project_id, $record, $instrument )
	{

?>
<script type="text/javascript">
  $(function() {
<?php


		// If the 'Unverified' option in the form status dropdown is to be hidden,
		// find it and remove it if it is not the currently selected option.

		if ( $this->getProjectSetting( 'hide-unverified-option' ) === true )
		{

?>
    var vOptUnver = $('select[name="<?php echo $instrument; ?>_complete"] option[value="1"]')
    if ( vOptUnver.length == 1 && vOptUnver[0].parentElement.value != '1' )
    {
      vOptUnver[0].remove()
    }
<?php

		}


		// If the form has already been marked as 'complete', then require a reason for any changes.
		// A reason is not required if the form is not marked as complete (including if the form has
		// been previously changed from 'complete' to 'incomplete').

		if ( $this->getProjectSetting( 'require-change-reason-complete' ) === true )
		{

?>
    if ( $('select[name="<?php echo $instrument; ?>_complete"]')[0].value == '2' )
    {
      require_change_reason = 1
    }
    else
    {
      require_change_reason = 0
    }
<?php

		}


		// Perform the selected submit option tweak. Either remove the 'Save & Go To Next Record'
		// option, or switch to only New Instance, Next Form and Stay options.
		// Note: '[id=' selectors are used instead of '#' selectors for element IDs, as REDCap is
		// using duplicate element IDs (in violation of HTML spec).

		if ( $this->getSystemSetting( 'submit-option-tweak' ) == '1' )
		{
			// Identify the 'Save & Go To Next Record' option and remove it. If the option is on the
			// button rather than in the dropdown, move the first item in the dropdown to the button.

?>
    var vBtnSvNxtRec = $('button[name="submit-btn-savenextrecord"]')
    if ( vBtnSvNxtRec.length > 0 )
    {
      $.each( vBtnSvNxtRec, function( vCount, vBtnInstance )
      {
        var vNewBtn = $(vBtnInstance).siblings('.dropdown-menu').find('a')[0]
        vBtnInstance.id = vNewBtn.id
        vBtnInstance.name = vNewBtn.name
        vBtnInstance.onclick = vNewBtn.onclick
        vBtnInstance.innerHTML = vNewBtn.innerHTML
        vNewBtn.remove()
      } )
    }
    else
    {
      $('[id="submit-btn-savenextrecord"]').remove()
    }
<?php

		}
		elseif ( $this->getSystemSetting( 'submit-option-tweak' ) == '2' )
		{
			// Identify the 'Save & Add New Instance', 'Save & Go To Next Form' and 'Save & Stay'
			// options, and place in that order on the button and dropdown.

?>
    var vBtnDropDown = $('[id="submit-btn-dropdown"]')
    if ( vBtnDropDown.length > 0 )
    {
      $.each( vBtnDropDown, function( vCount, vDropDownInstance )
      {
        var vBtnOptions = $(vDropDownInstance).siblings('.dropdown-menu').find('a')
        var vBtnInstance = vDropDownInstance.previousElementSibling
        var vBtnList = []
        $.each( [ 'submit-btn-savenextinstance', 'submit-btn-savenextform',
                  'submit-btn-savecontinue' ], function( vCount2, vBtnID )
        {
          if ( vBtnInstance.id == vBtnID )
          {
            var vBtnItem = { 'id' : vBtnInstance.id, 'innerHTML' : vBtnInstance.innerHTML,
                             'onclick' : vBtnInstance.onclick, 'name' : vBtnInstance.name }
            vBtnList.push( vBtnItem )
          }
          else
          {
            $.each( vBtnOptions, function( vCount3, vBtnOption )
            {
              if ( vBtnOption.id == vBtnID )
              {
                var vBtnItem = { 'id' : vBtnOption.id, 'innerHTML' : vBtnOption.innerHTML,
                                 'onclick' : vBtnOption.onclick, 'name' : vBtnOption.name }
                vBtnList.push( vBtnItem )
              }
            } )
          }
        } )
        vBtnInstance.id = vBtnList[0].id
        vBtnInstance.name = vBtnList[0].name
        vBtnInstance.onclick = vBtnList[0].onclick
        vBtnInstance.innerHTML = vBtnList[0].innerHTML
        for ( vCount2 = 1; vCount2 < vBtnList.length; vCount2++ )
        {
          vBtnOptions[ vCount2 - 1 ].id = vBtnList[vCount2].id
          vBtnOptions[ vCount2 - 1 ].name = vBtnList[vCount2].name
          vBtnOptions[ vCount2 - 1 ].onclick = vBtnList[vCount2].onclick
          vBtnOptions[ vCount2 - 1 ].innerHTML = vBtnList[vCount2].innerHTML
        }
        for ( vCount2 = vBtnList.length - 1; vCount2 < vBtnOptions.length; vCount2++ )
        {
          vBtnOptions[ vCount2 ].remove()
        }
      } )
    }
<?php

		}

?>
  })
</script>
<?php

	}




	// Return the URL for the specified alternate icon image.

	function getIconUrl( $icon )
	{
		return preg_replace( '/&pid=[1-9][0-9]*/', '',
		                     $this->getUrl( "status_icon.php?icon=$icon" ) );
	}




	// Check that the module is enabled system-wide.

	function validateSettings( $settings )
	{
		$project_id = $this->getProjectID();

		// System-level settings.
		if ( $project_id === null )
		{
			if ( ! $settings['enabled'] )
			{
				return 'This module must be enabled in all projects.';
			}
			if ( $settings['discoverable-in-project'] || $settings['user-activate-permission'] )
			{
				return 'This module does not need to be discoverable.';
			}
			return null;
		}

		// Project-level settings.

		// If settings are valid, check if change reasons are required for changes to complete forms
		// and if so enable the REDCap setting to require change reasons. The module setting will
		// override the REDCap setting anyway, but this ensures change reasons are displayed
		// e.g. on the Logging page.
		if ( $settings['require-change-reason-complete'] )
		{
			$this->query( 'UPDATE redcap_projects SET require_change_reason = ? ' .
			              'WHERE project_id = ?', [ 1, $project_id ] );
		}
		return null;
	}


}

