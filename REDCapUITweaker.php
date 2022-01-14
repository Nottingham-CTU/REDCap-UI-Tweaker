<?php

namespace Nottingham\REDCapUITweaker;


class REDCapUITweaker extends \ExternalModules\AbstractExternalModule
{
	const SUBMIT_TYPES = [ 'record', 'continue', 'nextinstance',
	                       'nextform', 'nextrecord', 'exitrecord' ];


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

		// Provide sorting for the my projects page.

		if ( $project_id === null && $this->getSystemSetting( 'my-projects-alphabetical' ) &&
		     $_GET['action'] == 'myprojects' &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT_PARENT ), 9 ) == 'index.php' )
		{
			$this->provideProjectSorting();
		}


		// Exit the function here if a system level page.

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


		// If the alerts page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 9 ) == 'index.php' &&
		     isset( $_GET['route'] ) && $_GET['route'] == 'AlertsController:setup' &&
		     ! isset( $_GET['log'] ) )
		{
			// Provide custom alert sender if enabled.
			if ( $this->getSystemSetting( 'custom-alert-sender' ) )
			{
				$this->provideCustomAlertSender();
			}
			// Provide the simplified view if enabled.
			if ( $this->getSystemSetting( 'alerts-simplified-view' ) )
			{
				$this->provideSimplifiedAlerts();
			}
		}



		// If the codebook page and the simplified view option is enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 35 ) ==
		                                                   'Design/data_dictionary_codebook.php' &&
		     $this->getSystemSetting( 'codebook-simplified-view' ) )
		{
			$this->provideSimplifiedCodebook();
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
				$this->provideDefaultRequired();
			}


			// Rearrange the list of field types.
			$this->rearrangeFieldTypes( $this->getSystemSetting( 'field-types-order' ) );

			// Provide the predefined field annotations.
			$this->provideFieldAnnotations( $this->getSystemSetting( 'predefined-annotations' ) );


		} // end if instrument designer



		// If any data entry page, and alternate status icons enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 10 ) == 'DataEntry/' &&
			 $this->getSystemSetting( 'alternate-status-icons' ) )
		{
			$this->replaceStatusIcons();
		}



		// Amend the list of action tags (accessible from the add/edit field window in the
		// instrument designer) when features which provide extra action tags are enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 26 ) == 'Design/online_designer.php' ||
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 22 ) == 'ProjectSetup/index.php' )
		{
			$listActionTags = [];
			if ( $this->getSystemSetting( 'submit-option-custom' ) )
			{
				$listActionTags['@SAVEOPTIONS'] =
					'Sets the save options on the form to the options specified (in the specified' .
					' order). The format must follow the pattern @SAVEOPTIONS=????, in which ' .
					'the desired value must be a comma separated list of the following options: ' .
					'record (Save and Exit Form), continue (Save and Stay), nextinstance (Save ' .
					'and Add New Instance), nextform (Save and Go To Next Form), nextrecord ' .
					'(Save and Go To Next Record), exitrecord (Save and Exit Record). If this ' .
					'action tag is used on multiple fields on a form, the value from the first ' .
					'field not hidden by branching logic when the form loads will be used.';
			}
			if ( SUPER_USER && $this->getSystemSetting( 'sql-descriptive' ) )
			{
				$listActionTags['@SQLDESCRIPTIVE'] =
					'On SQL fields, hide the drop-down and use the text in the selected option ' .
					'as descriptive text. You may want to pair this tag with @DEFAULT or ' .
					'@PREFILL to select the desired option. To ensure that the data is handled ' .
					'corectly, you may wish to output it from the database as URL-encoded or ' .
					'base64, in which case you can prefix it with url: or b64: respectively to ' .
					'indicate the format.';
			}
			$this->provideActionTagExplain( $listActionTags );
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
			$this->provideChangeReason();
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
				// Limit the submit options to 'Save & Exit Form', 'Save & Add New Instance',
				// 'Save & Go To Next Form' and 'Save & Stay'.
				$this->rearrangeSubmitOptions( 'record,nextinstance,nextform,continue' );
			}
		}

		// Check if the @SQLDESCRIPTIVE action tag is enabled and provide its functionality if so.
		if ( $this->getSystemSetting( 'sql-descriptive' ) )
		{
			$fieldsSQLDesc = [];
			$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
			foreach ( $listFields as $infoField )
			{
				if ( $infoField['field_type'] == 'sql' &&
				     preg_match( '/@SQLDESCRIPTIVE(\s+|$)/', $infoField['field_annotation'] ) )
				{
					$fieldsSQLDesc[] = $infoField['field_name'];
				}
			}
			if ( ! empty( $fieldsSQLDesc ) )
			{
				$this->provideSQLDescriptive( $fieldsSQLDesc );
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





	// Output JavaScript to amend the action tags guide.

	function provideActionTagExplain( $listActionTags )
	{
		if ( empty( $listActionTags ) )
		{
			return;
		}
		$listActionTagsJS = [];
		foreach ( $listActionTags as $t => $d )
		{
			$listActionTagsJS[] = [ $t, $d ];
		}
		$listActionTagsJS = json_encode( $listActionTagsJS );

?>
<script type="text/javascript">
$(function()
{
  var vActionTagPopup = actionTagExplainPopup
  var vMakeRow = function(vTag, vDesc, vTable)
  {
    var vRow = $( '<tr>' + vTable.find('tr:first').html() + '</tr>' )
    var vOldTag = vRow.find('td:eq(1)').html()
    var vButton = vRow.find('button')
    vRow.find('td:eq(1)').html(vTag)
    vRow.find('td:eq(2)').html(vDesc)
    if ( vButton.length != 0 )
    {
      vButton.attr('onclick', vButton.attr('onclick').replace(vOldTag,vTag))
    }
    var vRows = vTable.find('tr')
    var vInserted = false
    for ( var i = 0; i < vRows.length; i++ )
    {
      var vA = vRows.eq(i).find('td:eq(1)').html()
      if ( vTag < vRows.eq(i).find('td:eq(1)').html() )
      {
        vRows.eq(i).before(vRow)
        vInserted = true
        break
      }
    }
    if ( ! vInserted )
    {
      vRows.last().after(vRow)
    }
  }
  actionTagExplainPopup = function(hideBtns)
  {
    vActionTagPopup(hideBtns)
    var vCheckTagsPopup = setInterval( function()
    {
      if ( $('div[aria-describedby="action_tag_explain_popup"]').length == 0 )
      {
        return
      }
      clearInterval( vCheckTagsPopup )
      var vActionTagTable = $('#action_tag_explain_popup table');
      <?php echo $listActionTagsJS; ?>.forEach(function(vItem)
      {
        vMakeRow(vItem[0],vItem[1],vActionTagTable)
      })
    }, 200 )
  }
})
</script>
<?php

	}





	// Output JavaScript to set the requirement for a change reason based on the form's previous
	// 'complete' status.

	function provideChangeReason()
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





	// Output JavaScript to allow a custom from address to be selected in alerts.

	function provideCustomAlertSender()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vRegexValidate = /<?php echo $this->getSystemSetting('custom-alert-sender-regex'); ?>/
    var vDialog = $('<div><input type="text" style="width:100%"><br>' +
                    '<span style="color:#c00"></span></div>')
    var vSelectFields = $('select[name="email-from"], select[name="email-failed"]')
    var vOldVal = null
    var vActiveSelect = null
    vDialog.find('input').on('keypress',function(e)
    {
      if ( e.which == 13 )
      {
        vDialog.parent().find('.ui-dialog-buttonset .ui-button').click()
        e.preventDefault()
      }
    })
    vDialog.dialog(
    {
      autoOpen:false,
      buttons:{
        OK: function()
        {
          var vInputText = vDialog.find('input').val()
          if ( ! vRegexValidate.test(vInputText) )
          {
            vDialog.find('span').text('The email address you entered is invalid or disallowed.')
            return
          }
          vDialog.find('input').val('')
          var vNewOption = $('<option></option>')
          vNewOption.attr('value',vInputText)
          vNewOption.text(vInputText)
          vActiveSelect.find('option[value="*"]').before(vNewOption)
          vActiveSelect.val(vInputText)
          vDialog.dialog('close')
        }
      },
      modal:true,
      resizable:false,
      title:'Enter email address',
      width:400
    })
    vSelectFields.append( '<option value="*">Enter a different email address...</option>' )
    vSelectFields.on('click',function()
    {
      var vField = $(this)
      vOldVal = vField.val()
      vField.find('option[value="*"]').appendTo(vField)
    })
    vSelectFields.on('change',function()
    {
      if ( $(this).val() == '*' )
      {
        vActiveSelect = $(this)
        vActiveSelect.val( vOldVal )
        vDialog.parent().appendTo('#external-modules-configure-modal .modal-content')
        vDialog.dialog('open')
        $('.ui-widget-overlay.ui-front').appendTo('#external-modules-configure-modal .modal-content')
      }
    })
    var vEditEmailAlert = editEmailAlert
    editEmailAlert = function(vModal, vIndex, vAlertNum)
    {
      if ( typeof( vModal['email-failed'] ) != 'undefined' &&
           $('select[name="email-failed"] option[value="'+vModal['email-failed']+'"]').length == 0 )
      {
        var vNewOption = $('<option></option>')
        vNewOption.attr('value',vModal['email-failed'])
        vNewOption.text(vModal['email-failed'])
        $('select[name="email-failed"]').append(vNewOption)
      }
      vEditEmailAlert(vModal, vIndex, vAlertNum)
    }

  })
</script>
<?php

	}





	// Output JavaScript to set fields to be required by default.

	function provideDefaultRequired()
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





	// Output JavaScript to provide the predefined field annotations.

	function provideFieldAnnotations( $predefinedAnnotations )
	{
		if ( $predefinedAnnotations == '' )
		{
			return;
		}

		$listAnnotations = explode( "\n", $predefinedAnnotations );
		array_walk( $listAnnotations, function( &$v ) { $v = trim( $v ); } );
		$listAnnotations = array_values( array_filter( $listAnnotations ) );

		if ( empty( $listAnnotations ) )
		{
			return;
		}
?>
<script type="text/javascript">
  $(function() {
    var vAnnotationField = $('#field_annotation')
    var vAnnotations = <?php echo json_encode( $listAnnotations ), "\n"; ?>
    var vSelect = $('<select><option value="">Choose annotation...</option></select>')
    vSelect.css('font-size','x-small')
    vAnnotations.forEach( function(i)
    {
      var vOption = $('<option></option>')
      vOption.text(i)
      vSelect.append(vOption)
    })
    vSelect.change( function()
    {
      var vValue = vSelect.val()
      if ( vValue == '' )
      {
        return
      }
      var vText = vAnnotationField.val()
      var vPos
      if ( vText == '' )
      {
        vText = vValue
      }
      else if ( vText.substring(0,1) == '@' )
      {
        vText = vValue + '\n' + vText
      }
      else if ( ( vPos = vText.indexOf('\n@') ) != -1 )
      {
        vText = vText.substring(0, vPos) + '\n' + vValue + vText.substring(vPos)
      }
      else
      {
        vText += '\n' + vValue
      }
      vAnnotationField.val(vText)
      vSelect.find('option').first().prop('selected',true)
    })
    vAnnotationField.before(vSelect)
    vAnnotationField.before('<br>')
    $('#div_field_annotation .btn').click(function()
    {
      var vFuncRestore = function()
      {
        var vCloseButtons = $('[aria-describedby=action_tag_explain_popup] .ui-button')
        if ( vCloseButtons.length < 2 )
        {
          setTimeout(vFuncRestore, 250)
          return
        }
        setTimeout(function()
        {
          vCloseButtons.off('click.textrestore')
          var vText = vAnnotationField.val()
          var vPos = vText.indexOf('\n@')
          if ( vPos == -1 && vText.length > 0 && vText.indexOf('@') == -1 )
          {
            vPos = vText.length
            vText += '\n'
          }
          if ( vPos != -1 )
          {
            var vAText = vText.substring(0, vPos + 1)
            vCloseButtons.on('click.textrestore', function()
            {
              var vTText = vAnnotationField.val()
              if ( vTText == '' )
              {
                vAnnotationField.val(vAText.substring(0,vAText.length-1))
              }
              else
              {
                vAnnotationField.val(vAText + vTText)
              }
            })
            vAnnotationField.val(vText.substring(vPos + 1))
          }
        }, 1000)
      }
      setTimeout(vFuncRestore, 250)
    })
  })
</script>
<?php

	}





	// Output JavaScript to provide alphabetical sorting on the My Projects page.

	function provideProjectSorting()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vGroups = []
    $('#table-proj_table tr[id^="f_"]').each(function()
    {
      var vItem = $(this).find('td').first().clone()
      vItem.find('span').remove()
      $(this).attr('data-studyname',vItem.text())
      var vGroupID = this.id.replace(/[0-9]+$/,'')
      if ( ! vGroups.includes( vGroupID ) )
      {
        vGroups.push( vGroupID )
      }
    })
    vGroups.forEach( function( vGroupID )
    {
      var vRows = $('#table-proj_table tr[id^="'+vGroupID+'"]')
      var vNumRows = vRows.length
      for ( var i = 1; i < vNumRows; i++ )
      {
        vRows = $('#table-proj_table tr[id^="'+vGroupID+'"]')
        for ( var j = 0; j < i; j++ )
        {
          if ( vRows.eq(i).attr('data-studyname') < vRows.eq(j).attr('data-studyname') )
          {
            vRows.eq(j).before(vRows.eq(i))
            break
          }
        }
      }
    })
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the alerts.

	function provideSimplifiedAlerts()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('alerts_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.click(vFuncSimplify)
    var vDivSimplify = $('<div style="margin-bottom:10px"></div>')
    vDivSimplify.append(vBtnSimplify)
    $('#center').children().last().before(vDivSimplify)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the codebook.

	function provideSimplifiedCodebook()
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
      var vRowsSelector = '.ReportTableWithBorder tr[class^="toggle-"]'
      $('.ReportTableWithBorder td[colspan]').attr('colspan','4')
      $(vRowsSelector + '>td:nth-child(1)').css('display','none')
      $('.ReportTableWithBorder th:nth-child(1)').css('display','none')
      if ( vIsDesigner )
      {
        $(vRowsSelector + '>td:nth-child(2)').css('display','none')
        $('.ReportTableWithBorder th:nth-child(2)').css('display','none')
      }
      var vHdrAttribute = $('.ReportTableWithBorder th:nth-child(' +
                            ( vIsDesigner ? '5' : '4' ) + ')')
      var vHdrAnnotation = $('<th><?php echo $GLOBALS['lang']['design_527']; ?></th>')
      vHdrAnnotation.css('background-color',vHdrAttribute.css('background-color'))
      vHdrAttribute.css('width','')
      vHdrAttribute.after(vHdrAnnotation)
      $(vRowsSelector + '>td:nth-child(' + ( vIsDesigner ? '5' : '4' ) + ')').each(function()
      {
        var vFieldAttr = $(this).html()
        var vPos = vFieldAttr.indexOf('<br><?php echo $GLOBALS['lang']['design_527']; ?>: ')
        var vAnnotation = ''
        if ( vPos != -1 )
        {
          vAnnotation = vFieldAttr.substring(vPos + 22).replace(/\n/g, '<br>\n')
          vFieldAttr = vFieldAttr.substring(0,vPos)
        }
        vFieldAttr = vFieldAttr.replace('<br><?php echo $GLOBALS['lang']['design_489']; ?> ','<br>')
        $(this).html(vFieldAttr)
        $(this).after($('<td></td>').html(vAnnotation))
      })
      $('.ReportTableWithBorder td[colspan] .btn').css('display','none')
      //$('.ReportTableWithBorder td table td').css('display','')
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





	// Output JavaScript to provide the SQL descriptive field functionality.

	function provideSQLDescriptive( $listFields )
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vListFields = <?php echo json_encode( $listFields ), "\n"; ?>
    vListFields.forEach( function( vFieldName )
    {
      var vData = $('#'+vFieldName+'-tr select option:selected').text()
      if ( vData.substring(0,4) == 'raw:' )
      {
        vData = vData.substring(4)
      }
      else if ( vData.substring(0,4) == 'url:' )
      {
        try { vData = decodeURIComponent( vData.substring(4) ) } catch { vData = '' }
      }
      else if ( vData.substring(0,4) == 'b64:' )
      {
        try { vData = atob( vData.substring(4) ) } catch { vData = '' }
      }
      $('#'+vFieldName+'-tr .data').css('display','none')
      $('#'+vFieldName+'-tr .labelrc').empty()
      $('#'+vFieldName+'-tr .labelrc').removeClass('col-7')
      $('#'+vFieldName+'-tr .labelrc').addClass('col-12')
      $('#'+vFieldName+'-tr .labelrc').attr('colspan','2')
      $('#'+vFieldName+'-tr .labelrc').html( vData )
    })
  })
</script>
<?php

	}





	// Output JavaScript to rearrange the list of field types.

	function rearrangeFieldTypes( $fieldTypesOrder )
	{
		if ( ! preg_match( '/^[1-9][0-9]?([,][1-9][0-9]?)*([|][1-9][0-9]?([,][1-9][0-9]?)*)?$/',
		                   $fieldTypesOrder ) )
		{
			return;
		}

		$fieldTypesAll = explode( '|', $fieldTypesOrder );
		$fieldTypesCommon = explode( ',', $fieldTypesAll[0] );
		$fieldTypesOther = isset( $fieldTypesAll[1] ) ? explode( ',', $fieldTypesAll[1] ) : '';
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
        var vBtn1 = vDropDownInstance.previousElementSibling
        var vBtn0 = vBtn1.parentElement.previousElementSibling
        var vBtnList = []
        $.each( [ <?php echo $submitButtonString; ?> ], function( vCount2, vBtnID )
        {
          if ( vBtn0.id == vBtnID )
          {
            var vBtnItem = { 'id' : vBtn0.id, 'innerHTML' : vBtn0.innerHTML,
                             'onclick' : vBtn0.onclick, 'name' : vBtn0.name }
            vBtnList.push( vBtnItem )
          }
          else if ( vBtn1.id == vBtnID )
          {
            var vBtnItem = { 'id' : vBtn1.id, 'innerHTML' : vBtn1.innerHTML,
                             'onclick' : vBtn1.onclick, 'name' : vBtn1.name }
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
          vBtn0.style.display = 'none'
          vBtn1.parentElement.style.display = 'none'
          return
        }
        vBtn0.id = vBtnList[0].id
        vBtn0.name = ( vBtnList[0].name == '' ? vBtnList[0].id : vBtnList[0].name )
        vBtn0.onclick = vBtnList[0].onclick
        vBtn0.innerHTML = vBtnList[0].innerHTML
        if ( vBtnList.length == 1 )
        {
          vBtn1.parentElement.style.display = 'none'
          return
        }
        vBtn1.id = vBtnList[1].id
        vBtn1.name = ( vBtnList[1].name == '' ? vBtnList[1].id : vBtnList[1].name )
        vBtn1.onclick = vBtnList[1].onclick
        vBtn1.innerHTML = vBtnList[1].innerHTML
        $(vBtn1).one('click',function()
        {
          $('head').append('<style type="text/css">' +
                           '.popover.fade.show.bs-popover-top{display:none}</style>')
        })
        if ( vBtnList.length == 2 )
        {
          vBtn1.style.borderRadius = '0.25rem'
          vDropDownInstance.style.display = 'none'
        }
        for ( vCount2 = 2; vCount2 < vBtnList.length; vCount2++ )
        {
          vBtnOptions[ vCount2 - 2 ].id = vBtnList[vCount2].id
          vBtnOptions[ vCount2 - 2 ].name =
                    ( vBtnList[vCount2].name == '' ? vBtnList[vCount2].id : vBtnList[vCount2].name )
          vBtnOptions[ vCount2 - 2 ].onclick = vBtnList[vCount2].onclick
          vBtnOptions[ vCount2 - 2 ].innerHTML = vBtnList[vCount2].innerHTML
        }
        for ( vCount2 = vBtnList.length - 2; vCount2 < vBtnOptions.length; vCount2++ )
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
			if ( $settings['custom-alert-sender'] &&
			     ( $settings['custom-alert-sender-regex'] == '' ||
			       preg_match( '/'.$settings['custom-alert-sender-regex'].'/', '' ) === false ) )
			{
				return 'The regular expression to validate custom from addresses is invalid.';
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

