<?php

namespace Nottingham\REDCapUITweaker;


class REDCapUITweaker extends \ExternalModules\AbstractExternalModule
{
	const SUBMIT_TYPES = [ 'continue', 'nextinstance', 'nextform', 'nextrecord', 'exitrecord' ];


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
		     ! isset( $_GET['route'] ) && ! isset( $_GET['__redirect'] ) )
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





	// Project home page hook. This is called after page loads so is unsuitable for the home page
	// redirect, but is used to prevent content being displayed in case the redirect has not been
	// triggered.

	function redcap_project_home_page()
	{
		if ( $this->getProjectSetting( 'project-home-redirect' ) != '' )
		{
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



		// All pages, if custom logo/institution name option is used.

		if ( $this->getProjectSetting( 'custom-logo-name-display' ) == 'logo' )
		{
			$this->hideSubheader( '#subheaderDiv1' );
		}
		elseif ( $this->getProjectSetting( 'custom-logo-name-display' ) == 'name' )
		{
			$this->hideSubheader( 'img' );
		}



		// If the codebook page and the simplified view option is enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 35 ) ==
		                                                   'Design/data_dictionary_codebook.php' &&
		     $this->getSystemSetting( 'codebook-simplified-view' ) )
		{
			$this->provideSimplifiedView();
		}



		// If the external modules page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 35 ) ==
		                                                     'ExternalModules/manager/project.php' )
		{
			$this->hideDisableModule();
		}



		// If an instrument designer page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 26 ) == 'Design/online_designer.php' &&
			 isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] != '' )
		{


			// Set field 'required' option on by default.

			if ( $this->getSystemSetting( 'field-default-required' ) != '0' )
			{

?>
<script type="text/javascript">
  $(function() {
    var vEditReqChanged = false
    var vFuncReqChange = function() { vEditReqChanged = true }
    setInterval(function() {
      var vEditID = $('#sq_id')
      if ( vEditID.length > 0 && vEditID.val() == '' )
      {
        if ( $('#field_name').val() == '' && $('#field_label').val() == '' )
        {
          vEditReqChanged = false
          $('#field_req0, #field_req1').off('click.reqdefault')
          $('#field_req0, #field_req1').on('click.reqdefault', vFuncReqChange)
        }
        if ( ! vEditReqChanged )
        {
          var vEditFieldType = $('#field_type').val()
          if ( vEditFieldType == 'descriptive' ||
               vEditFieldType == 'calc' ||
               ( vEditFieldType == 'text' &&
                 ( new RegExp('@CALC(DATE|TEXT)').test($('#field_annotation').val()) ) ) )
          {
            $('#field_req0').prop('checked', true).trigger('click')
          }
          else
          {
            $('#field_req1').prop('checked', true).trigger('click')
          }
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
  })
</script>
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
<script type="text/javascript">
  $(function() {
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
  })
</script>
<?php

			}


		} // end if instrument designer



		// If any data entry page, and alternate status icons enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 10 ) == 'DataEntry/' &&
			 $this->getSystemSetting( 'alternate-status-icons' ) )
		{
			$this->replaceStatusIcons();
		}


	}





	// Perform actions on data entry forms.

	function redcap_data_entry_form( $project_id, $record, $instrument,
	                                 $event_id, $group_id, $repeat_instance )
	{


		// If the 'Unverified' option in the form status dropdown is to be hidden,
		// find it and remove it if it is not the currently selected option.

		if ( $this->getProjectSetting( 'hide-unverified-option' ) === true )
		{
			$this->hideUnverifiedOption();
		}


		// If the form has already been marked as 'complete', then require a reason for any changes.
		// A reason is not required if the form is not marked as complete (including if the form has
		// been previously changed from 'complete' to 'incomplete').

		if ( $this->getProjectSetting( 'require-change-reason-complete' ) === true )
		{

?>
<script type="text/javascript">
  $(function() {
    if ( $('select[name="<?php echo $instrument; ?>_complete"]')[0].value == '2' )
    {
      require_change_reason = 1
    }
    else
    {
      require_change_reason = 0
    }
  })
</script>
<?php

		}


		// If the form navigation fix is enabled, amend the dataEntrySubmit function to perform a
		// save and stay before performing the selected action. A session variable is set (by AJAX
		// request) which triggers the selected action once the page reloads following the save
		// and stay.

		if ( $this->getProjectSetting( 'fix-form-navigation' ) === true )
		{
			if ( isset( $_SESSION['module_uitweak_fixformnav'] ) &&
			     $_SESSION['module_uitweak_fixformnav'] == 'submit-btn-savenextform' &&
			     $_SESSION['module_uitweak_fixformnav_ts'] > time() - 5 )
			{
				$this->runSaveNextForm();
			}
			else
			{
				$this->stopSaveNextForm();
			}

			unset( $_SESSION['module_uitweak_fixformnav'],
			       $_SESSION['module_uitweak_fixformnav_ts'] );
		}


		// Check if custom submit options are enabled and present.
		$customSubmit = false;
		if ( $this->getSystemSetting( 'submit-option-custom' ) )
		{
			$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
			foreach ( $listFields as $infoField )
			{
				if ( preg_match( '/@SAVEOPTIONS=((\'[^\']*\')|("[^"]*")|(\S*))/',
				                 $infoField['field_annotation'], $submitOptions ) &&
				     ( $infoField['branching_logic'] == '' ||
				       \REDCap::evaluateLogic( $infoField['branching_logic'], $project_id, $record,
				                               $event_id, $repeat_instance, $instrument ) ) )
				{
					$submitOptions = $submitOptions[1];
					if ( preg_match( '/(\'[^\']*\')|("[^"]*")/', $submitOptions ) )
					{
						$submitOptions = substr( $submitOptions, 1, -1 );
					}
					$customSubmit = $this->rearrangeSubmitOptions( $submitOptions );
				}
			}
		}
		// If custom submit options are not present, perform the submit option tweak that is
		// selected in the module system settings (if applicable).
		if ( ! $customSubmit )
		{
			if ( $this->getSystemSetting( 'submit-option-tweak' ) == '1' )
			{
				// Identify the 'Save & Go To Next Record' option and remove it.
				$this->removeSubmitOption( 'nextrecord' );
			}
			elseif ( $this->getSystemSetting( 'submit-option-tweak' ) == '2' )
			{
				// Limit the submit options to 'Save & Add New Instance', 'Save & Go To Next Form'
				// and 'Save & Stay'.
				$this->rearrangeSubmitOptions( 'nextinstance,nextform,continue' );
			}
		}


	}





	// Return the URL for the specified alternate icon image.

	function getIconUrl( $icon )
	{
		return preg_replace( '/&pid=[1-9][0-9]*/', '',
		                     $this->getUrl( "status_icon.php?icon=$icon" ) );
	}





	// Output JavaScript to hide the button to disable this module for the project.

	function hideDisableModule()
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





	// Output JavaScript to hide the specified part of the subheader.

	function hideSubheader( $component )
	{

?>
<script type="text/javascript">
  $(function()
  {
    $('#subheader <?php echo $component; ?>').css('display','none')
  })
</script>
<?php

	}





	// Output JavaScript to hide the 'unverified' option on data entry forms.

	function hideUnverifiedOption()
	{

?>
<script type="text/javascript">
  $(function() {
    var vOptUnver = $('select[name="<?php echo $instrument; ?>_complete"] option[value="1"]')
    if ( vOptUnver.length == 1 && vOptUnver[0].parentElement.value != '1' )
    {
      vOptUnver[0].remove()
    }
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the codebook.

	function provideSimplifiedView()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vActivated = false
    var vIsDesigner = ( $('.ReportTableWithBorder th').first().text() != '#' )
    var vFuncSelect = function()
    {
      var vElem = $('.ReportTableWithBorder')[0]
      var vSel = window.getSelection()
      var vRange = document.createRange()
      vRange.selectNodeContents(vElem)
      vSel.removeAllRanges()
      vSel.addRange(vRange)
    }
    var vFuncSimplify = function()
    {
      if ( vActivated )
      {
        vFuncSelect()
        return
      }
      $('.ReportTableWithBorder td[colspan]').attr('colspan','3')
      $('.ReportTableWithBorder td:nth-child(1):not([colspan])').css('display','none')
      $('.ReportTableWithBorder th:nth-child(1)').css('display','none')
      if ( vIsDesigner )
      {
        $('.ReportTableWithBorder td:nth-child(2)').css('display','none')
        $('.ReportTableWithBorder th:nth-child(2)').css('display','none')
      }
      $('.ReportTableWithBorder td[colspan] .btn').css('display','none')
      $('.ReportTableWithBorder td table td').css('display','')
      $('.ReportTableWithBorder i.fa-chalkboard-teacher').removeClass('fa-chalkboard-teacher')
      $('.ReportTableWithBorder td[colspan]').contents().filter(function(){
        return this.nodeType == 3}).remove()
      $('.ReportTableWithBorder td[colspan] span').before('&nbsp;&nbsp;')
      $('.ReportTableWithBorder td[colspan] span').css('margin-left','0px')
      $('.ReportTableWithBorder td[colspan] font').before('&nbsp;&nbsp;&nbsp;&nbsp;')
      $('.ReportTableWithBorder td[colspan] font').css('margin-left','0px')
      $('#simplifiedView').text('Select table')
      vActivated = true
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.css('margin-top','5px')
    vBtnSimplify.click(vFuncSimplify)
    $('.jqbuttonmed[onclick="window.print();"]').after(vBtnSimplify)
  })
</script>
<?php

	}





	// Output JavaScript to rearrange the submit options.
	// Note: '[id=' selectors are used instead of '#' selectors for element IDs, as REDCap is
	// using duplicate element IDs (in violation of HTML spec).

	function rearrangeSubmitOptions( $submitOptions )
	{
		$submitButtonString = '';
		if ( $submitOptions != '' )
		{
			$submitOptions = explode( ',', $submitOptions );
			foreach ( $submitOptions as $i => $submitOption )
			{
				$submitOption = trim( $submitOption );
				if ( ! in_array( $submitOption, self::SUBMIT_TYPES ) )
				{
					return false;
				}
				$submitButtonString .= ( $submitButtonString == '' ? '' : ", " );
				$submitButtonString .= "'submit-btn-save" . $submitOption . "'";
			}
		}

?>
<script type="text/javascript">
  $(function() {
    var vBtnDropDown = $('[id="submit-btn-dropdown"]')
    if ( vBtnDropDown.length > 0 )
    {
      $.each( vBtnDropDown, function( vCount, vDropDownInstance )
      {
        var vBtnOptions = $(vDropDownInstance).siblings('.dropdown-menu').find('a')
        var vBtnInstance = vDropDownInstance.previousElementSibling
        var vBtnList = []
        $.each( [ <?php echo $submitButtonString; ?> ], function( vCount2, vBtnID )
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
        if ( vBtnList.length == 0 )
        {
          vBtnInstance.parentElement.style.display = 'none'
          return
        }
        vBtnInstance.id = vBtnList[0].id
        vBtnInstance.name = ( vBtnList[0].name == '' ? vBtnList[0].id : vBtnList[0].name )
        vBtnInstance.onclick = vBtnList[0].onclick
        vBtnInstance.innerHTML = vBtnList[0].innerHTML
        $(vBtnInstance).one('click',function()
        {
          $('head').append('<style type="text/css">' +
                           '.popover.fade.show.bs-popover-top{display:none}</style>')
        })
        if ( vBtnList.length == 1 )
        {
          vBtnInstance.style.borderRadius = '0.25rem'
          vDropDownInstance.style.display = 'none'
        }
        for ( vCount2 = 1; vCount2 < vBtnList.length; vCount2++ )
        {
          vBtnOptions[ vCount2 - 1 ].id = vBtnList[vCount2].id
          vBtnOptions[ vCount2 - 1 ].name =
                    ( vBtnList[vCount2].name == '' ? vBtnList[vCount2].id : vBtnList[vCount2].name )
          vBtnOptions[ vCount2 - 1 ].onclick = vBtnList[vCount2].onclick
          vBtnOptions[ vCount2 - 1 ].innerHTML = vBtnList[vCount2].innerHTML
        }
        for ( vCount2 = vBtnList.length - 1; vCount2 < vBtnOptions.length; vCount2++ )
        {
          vBtnOptions[ vCount2 ].remove()
        }
      } )
    }
  })
</script>
<?php

		return true;
	}





	// Output JavaScript to replace the standard REDCap status icons with the alternate icons.

	function replaceStatusIcons()
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

	}





	// Output JavaScript to remove a submit option from the dropdown list. If the option is on the
	// button rather than the dropdown, move the first item in the dropdown to the button.
	// Note: '[id=' selectors are used instead of '#' selectors for element IDs, as REDCap is
	// using duplicate element IDs (in violation of HTML spec).

	function removeSubmitOption( $submitOption )
	{
		if ( in_array( $submitOption, self::SUBMIT_TYPES ) )
		{

?>
<script type="text/javascript">
  $(function() {
    var vBtnSaveOpt = $('button[name="submit-btn-save<?php echo $submitOption; ?>"]')
    if ( vBtnSaveOpt.length > 0 )
    {
      $.each( vBtnSaveOpt, function( vCount, vBtnInstance )
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
      $('[id="submit-btn-save<?php echo $submitOption; ?>"]').remove()
    }
  })
</script>
<?php

			return true;
		}
		return false;
	}





	// Output JavaScript to perform the 'save and go to next form' action on page load.

	function runSaveNextForm()
	{

?>
<script type="text/javascript">
  $(function() {
    dataEntrySubmit( 'submit-btn-savenextform' )
  })
</script>
<?php

	}





	// Output JavaScript to stop the 'save and go to next form' action, and perform the 'save and
	// stay' action instead, while setting the session variable to invoke the 'save and go to next
	// form' action on the next page load.

	function stopSaveNextForm()
	{

?>
<script type="text/javascript">
  $(function() {
    if ( $('.form_menu_selected').prev().attr('title') == 'Incomplete' )
    {
      var vOldDataEntrySubmit = dataEntrySubmit
      dataEntrySubmit = function( vSubmitObj )
      {
        var vSubmitType = ''
        if ( typeof vSubmitObj == 'string' || vSubmitObj instanceof String )
        {
          vSubmitType = vSubmitObj
        }
        else
        {
          vSubmitType = $(vSubmitObj).attr('name')
        }
        if ( vSubmitType == 'submit-btn-savecontinue' &&
             $('[name="save-and-redirect"]').length == 1 )
        {
          vSubmitType = 'submit-btn-savenextform'
          $('[name="save-and-redirect"]').remove()
        }
        if ( vSubmitType == 'submit-btn-savenextform' )
        {
          $.ajax( { url : '<?php echo $this->getUrl( 'ajax_set_formnav.php' ); ?>',
                    method : 'POST',
                    data : { nav : 'submit-btn-savenextform' },
                    headers : { 'X-RC-UITweak-Req' : '1' },
                    dataType : 'json',
                    complete : function() { vOldDataEntrySubmit( 'submit-btn-savecontinue' ) }
                  } )
        }
        else
        {
          return vOldDataEntrySubmit( vSubmitObj )
        }
      }
    }
  })
</script>
<?php

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

		// If the user is not an administrator, check that the home page redirect URL, if specified,
		// is a relative path within the current project.
		if ( SUPER_USER != 1 )
		{
			$oldRedirect = $this->getProjectSetting( 'project-home-redirect' );
			$newRedirect = $settings['project-home-redirect'];
			$oldRedirectAbs = ( $oldRedirect != '' &&
			                    ( preg_match( '!^https?://!', $oldRedirect ) ||
			                      ! preg_match( '/(\\?|\\?.+&)pid=\\*(&|$)/', $oldRedirect ) ||
			                      preg_match( '/(\\?|&)pid=(\\*[^&]|[^*&])/', $oldRedirect ) ) );
			if ( $oldRedirectAbs )
			{
				if ( $oldRedirect != $newRedirect )
				{
					return 'The home page redirect cannot be changed as it has been set to an ' .
					       'absolute path or a location outside this project by an administrator.';
				}
			}
			else
			{
				$newRedirectAbs = ( $newRedirect != '' &&
				                   ( preg_match( '!^https?://!', $newRedirect ) ||
				                     ! preg_match( '/(\\?|\\?.+&)pid=\\*(&|$)/', $newRedirect ) ||
				                     preg_match( '/(\\?|&)pid=(\\*[^&]|[^*&])/', $newRedirect ) ) );
				if ( $newRedirectAbs )
				{
					return 'The home page redirect value can only be set to an absolute path or ' .
					       'a location outside this project by an administrator.';
				}
			}
		}

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

