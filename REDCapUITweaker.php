<?php

namespace Nottingham\REDCapUITweaker;


class REDCapUITweaker extends \ExternalModules\AbstractExternalModule
{
	const SUBMIT_TYPES = [ 'record', 'continue', 'nextinstance',
	                       'nextform', 'nextrecord', 'exitrecord', 'compresp' ];
	const SUBMIT_DEFINE = 'record,nextinstance,nextform,continue';

	// Simplified view table styles.
	const STL_HDR = 'font-size:1.1em';
	const STL_CEL = 'border:solid 1px #000;padding:5px;vertical-align:top';
	const STL_DEL = 'color:#fdd';
	const BGC_HDR = '#ddd';
	const BGC_HDR_NEW = '#1dc';
	const BGC_HDR_CHG = '#dd8';
	const BGC_HDR_DEL = '#600';
	const BGC_NEW = '#4fe';
	const BGC_CHG = '#ffa';
	const BGC_DEL = '#b11';

	// Simplified view <br> tag.
	const SVBR = '<br style="mso-data-placement:same-cell">';

	private $customAlerts;
	private $customReports;
	private $extModSettings;
	private $reportNamespacing;





	// Initialise module when enabled.
	// Set default system settings and enable in all projects.

	function redcap_module_system_enable()
	{
		// If settings uninitialised (module installed for first time).
		if ( $this->getSystemSetting( 'field-default-required' ) == '' &&
		     $this->getSystemSetting( 'submit-option-tweak' ) == '' )
		{
			// Prompt the user to activate the default settings.
			$_SESSION['module_uitweak_system_enable'] = true;
		}
		// Upgrade defined submit options from older module version.
		elseif ( $this->getSystemSetting( 'submit-option-tweak' ) == '2' &&
		         $this->getSystemSetting( 'submit-option-define' ) == '' )
		{
			$this->setSystemSetting( 'submit-option-define', self::SUBMIT_DEFINE );
		}
		$this->setSystemSetting( 'enabled', true );
	}





	// Remove the project setting for 'form submit options' if the system setting 'allow custom
	// submit options' is not enabled.

	function redcap_module_configuration_settings( $project_id, $settings )
	{
		if ( $project_id !== null && ! $this->getSystemSetting( 'submit-option-custom' ) )
		{
			foreach ( $settings as $index => $setting )
			{
				if ( $setting['key'] == 'submit-option' )
				{
					unset( $settings[ $index ] );
					break;
				}
			}
			$settings = array_values( $settings );
		}
		return $settings;
	}





	// Perform actions on every page (before page starts loading).

	function redcap_every_page_before_render( $project_id = null )
	{

		// IMPORTANT: This function runs in all contexts, when adding code ensure that it will only
		// run in the desired contexts (system or project).


		// Exit the function here if no user is logged in.

		if ( ! defined( 'USERID' ) || USERID == '' )
		{
			return;
		}


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
				        '/index.php?pid=' . intval( $projIDs ) );
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


		// If custom from addresses are allowed in alerts, and the supplied from address passes
		// the validation, ensure the built-in validation allows the address.

		if ( $this->getSystemSetting( 'custom-alert-sender' ) &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 9 ) == 'index.php' &&
		     isset( $_GET['route'] ) && $_GET['route'] == 'AlertsController:saveAlert' &&
		     isset( $_POST['email-from'] ) &&
		     preg_match( '/' . $this->getSystemSetting( 'custom-alert-sender-regex' ) . '/',
		                 $_POST['email-from'] ) )
		{
			$GLOBALS['user_email'] = $_POST['email-from'];
		}


		// If any data entry page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 10 ) == 'DataEntry/' )
		{
			// If custom data quality notification text enabled, use it.
			$dqCustomHeader = $this->getSystemSetting( 'dq-notify-header' );
			$dqCustomBody = $this->getSystemSetting( 'dq-notify-body' );
			$dqCustomBodyDRW = $this->getSystemSetting( 'dq-notify-body-drw' );
			if ( $dqCustomHeader != '' )
			{
				$GLOBALS['lang']['dataqueries_113'] = $dqCustomHeader;
			}
			if ( $dqCustomBody != '' )
			{
				$GLOBALS['lang']['dataqueries_118'] = $dqCustomBody;
			}
			if ( $dqCustomBodyDRW != '' )
			{
				$GLOBALS['lang']['dataqueries_309'] = $dqCustomBodyDRW;
			}

			// Check if the @SQLCHECKBOX action tag is enabled and provide its functionality if so.
			if($_GET['page'] != '' && $this->getSystemSetting( 'sql-checkbox' ))
			{

				$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
				$listFieldsSQLChkbx = [];

				foreach ( $listFields as $infoField )
				{
					if ( $infoField['field_type'] == 'checkbox' &&
					     str_contains( $infoField['field_annotation'], '@SQLCHECKBOX' ) )
					{
						array_push( $listFieldsSQLChkbx, $infoField );
					}
				}
				if ( ! empty( $listFieldsSQLChkbx ) )
				{
					$this->provideSQLCheckBox( $listFieldsSQLChkbx, $project_id,
					                           $_GET['id'], $_GET['event_id'],
					                           $_GET['page'], $_GET['instance'] );
				}
			}

		}


		// If report namespaces are enabled and this is a report page.
		$this->reportNamespacing = false;
		if ( $this->getProjectSetting( 'report-namespaces' ) &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 20 ) == 'DataExport/index.php' )
		{
			if ( ! empty( $this->checkReportNamespaceAuth() ) )
			{
				$GLOBALS['user_rights']['reports'] = 1;
				$this->reportNamespacing = true;
			}
			elseif ( $GLOBALS['user_rights']['reports'] != 1 &&
			         isset( $_GET['report_id'] ) && isset( $_GET['addedit'] ) )
			{
				header( 'Location: ' . APP_PATH_WEBROOT . '/DataExport/index.php?pid=' .
				        $this->getProjectId() );
				$this->exitAfterHook();
			}
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

		// IMPORTANT: This function runs in all contexts, when adding code ensure that it will only
		// run in the desired contexts (system or project).


		// Provide the versionless URLs.

		$versionlessURL = $this->getSystemSetting( 'versionless-url' );
		if ( $versionlessURL == 'A' )
		{
			$this->provideVersionlessURLs();
		}
		elseif ( in_array( $versionlessURL, [ 'M', 'E' ] ) )
		{
			$pagePath = substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ) ) .
			           ( $_SERVER['QUERY_STRING'] == '' ? '' : ( '?' . $_SERVER['QUERY_STRING'] ) );
			$versionlessURLRegex =
				preg_split( "/(\r?\n)+/", $this->getSystemSetting( 'versionless-url-regex' ) );
			$versionlessURLMatch = ( $versionlessURL == 'M' ? false : true );
			foreach ( $versionlessURLRegex as $versionlessURLItem )
			{
				if ( preg_match( '/' . str_replace( '/', '\\/', $versionlessURLItem ) . '/',
				                 $pagePath ) === 1 )
				{
					$versionlessURLMatch = ! $versionlessURLMatch;
					break;
				}
			}
			if ( $versionlessURLMatch )
			{
				$this->provideVersionlessURLs();
			}
		}


		// Exit the function here if no user is logged in.

		if ( ! defined( 'USERID' ) || USERID == '' )
		{
			return;
		}


		// Provide sorting for the my projects page.

		if ( $project_id === null && $this->getSystemSetting( 'my-projects-alphabetical' ) &&
		     $_GET['action'] == 'myprojects' &&
		     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT_PARENT ), 9 ) == 'index.php' )
		{
			$this->provideProjectSorting();
		}


		// Provide the initial configuration dialog.

		if ( isset( $_SESSION['module_uitweak_system_enable'] ) )
		{
			$this->provideInitialConfig();
		}


		// Exit the function here if a system level page.

		if ( $project_id === null )
		{
			return;
		}


		// All pages, if the option to show the role name is enabled.
		if ( $this->getSystemSetting( 'show-role-name' ) )
		{
			$this->provideUserRoleName();
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


		// All pages, if the contact REDCap administrator links are hidden.

		if ( $this->getSystemSetting( 'hide-contact-admin' ) != '' )
		{
			switch ( $this->getSystemSetting( 'hide-contact-admin' ) )
			{
				case 'first':
					$this->provideHideContactAdmin( '.first()' );
					break;
				case 'last':
					$this->provideHideContactAdmin( '.last()' );
					break;
				case 'all':
					$this->provideHideContactAdmin( '' );
					break;
			}
		}


		// All pages, if the suggest a new feature link is hidden.

		if ( $this->getSystemSetting( 'hide-suggest-feature' ) )
		{
			$this->provideHideSuggestFeature();
		}


		// If the alerts page.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 9 ) == 'index.php' &&
		     isset( $_GET['route'] ) && $_GET['route'] == 'AlertsController:setup' &&
		     ! isset( $_GET['log'] ) )
		{
			// Provide custom alert sender (+ alt alerts submission) if enabled.
			if ( $this->getSystemSetting( 'custom-alert-sender' ) )
			{
				$this->provideCustomAlertSender();
				$this->provideAltAlertsSubmit();
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



		// If the external modules page and the simplified view option is enabled.

		$enableExtModSimplifiedView = $this->getSystemSetting('extmod-simplified-view');
		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 35 ) ==
		                                                   'ExternalModules/manager/project.php' &&
		     ( $enableExtModSimplifiedView == 'E' ||
		       ( $enableExtModSimplifiedView == 'A' &&
		         defined( 'SUPER_USER' ) && SUPER_USER == 1 ) ) )
		{
			$this->provideSimplifiedExternalModules();
		}



		// If the instrument/event mapping page and the simplified view option is enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 26 ) ==
		                                                   'Design/designate_forms.php' &&
		     $this->getSystemSetting( 'instrument-simplified-view' ) )
		{
			$this->provideSimplifiedInstruments();
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


			// Provide the expanded field annotations.

			if ( $this->getSystemSetting( 'expanded-annotations' ) )
			{
				$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
				$listFieldAnnotations = [];

				foreach ( $listFields as $fieldName => $infoField )
				{
					$listFieldAnnotations[ $fieldName ] = $infoField['field_annotation'];
				}
				$this->provideExpandedAnnotations( $listFieldAnnotations );
			}


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



		// If the record status dashboard page, and auto-selection of all status types enabled.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 37 ) ==
		                                        'DataEntry/record_status_dashboard.php' &&
		     $this->getSystemSetting( 'all-status-types' ) )
		{
			$this->provideInstrumentAllStatusTypes();
		}


		// If the 'Unverified' option in the form status dropdown is to be hidden,
		// remove 'Unverified' from the legend for status icons.

		if ( ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 37 ) ==
		                                        'DataEntry/record_status_dashboard.php' ||
		       substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 25 ) ==
		                                        'DataEntry/record_home.php' ) &&
		     $this->getProjectSetting( 'hide-unverified-option' ) )
		{
			$this->hideUnverifiedOption( null );
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
					' order). The format must follow the pattern @SAVEOPTIONS=\'????\', in which ' .
					'the desired value must be a comma separated list of the following options: ' .
					'record (Save and Exit Form), continue (Save and Stay), nextinstance (Save ' .
					'and Add New Instance), nextform (Save and Go To Next Form), nextrecord ' .
					'(Save and Go To Next Record), exitrecord (Save and Exit Record), compresp ' .
					'(Save and Mark Survey as Complete). If this action tag is used on multiple ' .
					'fields on a form, the value from the first field not hidden by branching ' .
					'logic when the form loads, and not suppressed by @IF, will be used.';
			}
			if ( defined( 'SUPER_USER' ) && SUPER_USER &&
			     $this->getSystemSetting( 'sql-descriptive' ) )
			{
				$listActionTags['@SQLDESCRIPTIVE'] =
					'On SQL fields, hide the drop-down and use the text in the selected option ' .
					'as descriptive text. You may want to pair this tag with @DEFAULT or ' .
					'@SETVALUE/@PREFILL to select the desired option. To ensure that the data is ' .
					'handled corectly, you may wish to output it from the database as URL-encoded' .
					' or base64, in which case you can prefix it with url: or b64: respectively ' .
					'to indicate the format. Note: This action tag does not work with @IF.';
			}
			if ($this->getSystemSetting( 'sql-checkbox' ) )
			{
				$listActionTags['@SQLCHECKBOX'] =
					'On checkbox fields, dynamically replace the options with those from a ' .
					'specified SQL field. The format must follow the pattern ' .
					'@SQLCHECKBOX=\'????\', in which the desired value must be the field name of ' .
					'an SQL field in the project. Note: This action tag does not work with @IF. ' .
					'Checkbox options will NOT be replaced if the form, record or project has ' .
					'been locked.';
			}
			$this->provideActionTagExplain( $listActionTags );
		}



		// When a new project has just been created, if the options to enable these features on new
		// projects are enabled: enable the Data Resolution Workflow, set the Missing Data Codes,
		// enable the reason for change, and prevent setting the instrument to locked being counted
		// as a data change.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 22 ) == 'ProjectSetup/index.php' &&
		     $_GET['msg'] == 'newproject' &&
		     $this->getSystemSetting( 'default-prevent-lock-as-change' ) )
		{
			$this->setProjectSetting( 'prevent-lock-as-change', true );
		}
		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 22 ) == 'ProjectSetup/index.php' &&
		     $_GET['msg'] == 'newproject' &&
		     ( $this->getSystemSetting( 'data-res-workflow' ) ||
		       $this->getSystemSetting( 'missing-data-codes' ) != '' ||
		       $this->getSystemSetting( 'require-change-reason' ) != '' ) )
		{
			$_SESSION['module_uitweak_newproject'] = true;
			$this->provideDefaultCustom( $this->getSystemSetting( 'data-res-workflow' ),
			                             $this->getSystemSetting( 'missing-data-codes' ),
			                             $this->getSystemSetting( 'require-change-reason' ) );
		}
		elseif ( isset( $_SESSION['module_uitweak_newproject'] ) &&
		         substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 22 ) == 'ProjectSetup/index.php' )
		{
			unset( $_SESSION['module_uitweak_newproject'] );
			$_GET['msg'] = 'newproject';
		}



		// If the data quality rules page...

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 21 ) == 'DataQuality/index.php' )
		{

			// Default data quality rules to execute in real time
			if ( $this->getSystemSetting( 'dq-real-time' ) )
			{
				$this->provideDQRealTime();
			}

			// Add simplified view option.
			if ( $this->getSystemSetting( 'quality-rules-simplified-view' ) )
			{
				$this->provideSimplifiedQualityRules();
			}

		}



		// If the reports page, add simplified view option.

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 20 ) == 'DataExport/index.php' &&
		     $_GET['addedit'] != '1' && $_GET['other_report_options'] != '1' &&
		     $this->getSystemSetting( 'reports-simplified-view' ) )
		{
			$this->provideSimplifiedReports();
		}



		// If report namespacing is in effect.
		if ( $this->reportNamespacing )
		{
			echo '<script type="text/javascript">',
			     '$(document).on(\'ajaxSend\',function(e,x,s){s.url=s.url.replace(\'DataExport' .
			     '/report_edit_ajax.php?\',\'ExternalModules/?prefix=redcap_ui_tweaker&page=' .
			     'ajax_reports_ns&rcpage=report_edit_ajax&\').replace(\'DataExport/report_delete' .
			     '_ajax.php?\',\'ExternalModules/?prefix=redcap_ui_tweaker&page=' .
			     'ajax_reports_ns&rcpage=report_delete_ajax&\')})',
			     "</script>\n";
		}


	}



	// Perform actions on the user rights page.

	function redcap_user_rights( $project_id )
	{

		// If the simplified view option is enabled.

		if ( $this->getSystemSetting( 'user-rights-simplified-view' ) )
		{
			$this->provideSimplifiedUserRights();
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
			$this->hideUnverifiedOption( $instrument );
		}


		// If the form has already been marked as 'complete', then require a reason for any changes.
		// A reason is not required if the form is not marked as complete (including if the form has
		// been previously changed from 'complete' to 'incomplete').

		if ( $this->getProjectSetting( 'require-change-reason-complete' ) === true )
		{
			$this->provideChangeReason( $instrument );
		}


		// If the 'lock this instrument' option is not to be treated as a data change, amend so the
		// data changed flag is not set by ticking the option.

		if ( $this->getProjectSetting( 'prevent-lock-as-change' ) === true )
		{
			$this->providePreventLockAsChange();
		}


		if ( $this->getProjectSetting( 'dq-notify-hide-eq' ) === true )
		{
			$this->provideDQHideEq();
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
				$fieldAnnotationEIf = $this->replaceIfActionTag( $infoField['field_annotation'],
				                                                 $project_id, $record, $event_id,
				                                                 $instrument, $repeat_instance );
				if ( preg_match( '/@SAVEOPTIONS=((\'[^\']*\')|("[^"]*")|(\S*))/',
				                 $fieldAnnotationEIf, $submitOptions ) &&
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
			if ( ! $customSubmit && $this->getProjectSetting( 'submit-option' ) != '' )
			{
				$customSubmit =
					$this->rearrangeSubmitOptions( $this->getProjectSetting( 'submit-option' ) );
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
				// Limit the submit options to the defined options (if not correctly defined, use
				// 'Save & Exit Form', 'Save & Add New Instance', 'Save & Go To Next Form' and
				// 'Save & Stay').
				if ( $this->getSystemSetting( 'submit-option-define' ) == '' ||
				     ! $this->rearrangeSubmitOptions(
				                               $this->getSystemSetting( 'submit-option-define' ) ) )
				{
					$this->rearrangeSubmitOptions( self::SUBMIT_DEFINE );
				}
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





	// Allows a different module to supply its own alert details for the alerts simplified view.
	// $infoAlert should be an array containing the following keys:
	// 'enabled': true if the alert is enabled, false otherwise
	// 'title': title of the alert (optional)
	// 'type': type of the alert (e.g. Email, SMS)
	// 'form': the instrument to which the alert relates (optional)
	// 'trigger': details of the alert trigger
	// 'schedule': details of the alert schedule
	// 'message': the alert message (include details of sender/recipient as appropriate)
	// the title, type and form fields should be formatted as plain text, all other fields as HTML

	function addCustomAlert( $infoAlert )
	{
		if ( ! is_array( $this->customAlerts ) )
		{
			$this->customAlerts = [];
		}
		$this->customAlerts[] = $infoAlert;
	}





	// Allows a different module to ask this module if it needs to supply its own alert details.

	function areCustomAlertsExpected()
	{
		return $this->isPage( 'ExternalModules/' ) && $_GET['prefix'] == 'redcap_ui_tweaker' &&
		       $_GET['page'] == 'alerts_simplified';
	}





	// Allows a different module to supply its own report details for the reports simplified view.
	// $infoReport should be an array containing the following keys (all fields as plain text unless
	// the second $isHTML parameter is true, in which case HTML <b> and <i> tags can be used for the
	// permissions, definition and options):
	// 'title': title/name of the report
	// 'type': type of the report (e.g. Gantt, SQL)
	// 'description': description of the report
	// 'permissions': details of who can view/edit/download etc. the report
	// 'definition': e.g. report fields, SQL query
	// 'options': any additonal report options which have been set

	function addCustomReport( $infoReport, $isHTML = false )
	{
		if ( ! is_array( $this->customReports ) )
		{
			$this->customReports = [];
		}
		if ( ! $isHTML )
		{
			foreach ( [ 'permissions', 'definition', 'options' ] as $key )
			{
				if ( isset( $infoReport[ $key ] ) )
				{
					$infoReport[ $key ] = $this->customReportsEscapeHTML( $infoReport[ $key ] );
				}
			}
		}
		$this->customReports[] = $infoReport;
	}





	// Escapes text which should not be rendered as HTML for use in the 'permissions', 'definition'
	// and 'options' fields of the reports simplified view.

	function customReportsEscapeHTML( $text )
	{
		$text = preg_replace( '/&(amp;)*lt;(b|i)&(amp;)*gt;/', '&$1amp;lt;$2&$3amp;gt;', $text );
		$text = preg_replace( '/&(amp;)*lt;\\/(b|i)&(amp;)*gt;/',
		                      '&$1amp;lt;/$2&$3amp;gt;', $text );
		$text = str_replace( [ '<b>', '<i>', '</b>', '</i>' ],
					         [ '&lt;b&gt;', '&lt;i&gt;', '&lt;/b&gt;', '&lt;/i&gt;'], $text );
		return $text;
	}





	// Allows a different module to ask this module if it needs to supply its own report details.

	function areCustomReportsExpected()
	{
		return $this->isPage( 'ExternalModules/' ) && $_GET['prefix'] == 'redcap_ui_tweaker' &&
		       $_GET['page'] == 'reports_simplified';
	}





	// Allows a different module to supply a function which will transform the setting names and
	// values used on the external modules simplified view.
	// $module should be the directory name of the module excluding version number.
	// $function is a function which has one parameter which is an array
	// containing the following keys:
	// 'setting': name of the setting
	// 'value': value of the setting
	// The function should return the array with the data manipulated as required,
	// false to discard the setting, or true to retain the original data.

	function addExtModFunc( $module, $function )
	{
		if ( ! is_array( $this->extModSettings ) )
		{
			$this->extModSettings = [];
		}
		$this->extModSettings[ $module ] = $function;
	}





	// Allows a different module to ask this module if it needs to supply a module setting
	// transformation function.

	function areExtModFuncExpected()
	{
		return $this->isPage( 'ExternalModules/' ) && $_GET['prefix'] == 'redcap_ui_tweaker' &&
		       $_GET['page'] == 'extmod_simplified';
	}





	// Check if the user is authorised to edit a namespaced report.

	function checkReportNamespaceAuth()
	{
		$userRights = $this->getUser()->getRights();
		$roleName = ( isset( $userRights ) && isset( $userRights['role_name'] ) &&
		              $userRights['role_name'] != '' ? $userRights['role_name'] : null );
		if ( $roleName !== null )
		{
			$listNS = [];
			$reportNSRoles = $this->getProjectSetting( 'report-namespace-roles' );
			foreach ( $reportNSRoles as $i => $roleNames )
			{
				$roleNames = explode( "\n", str_replace( "\r\n", "\n", $roleNames ) );
				if ( in_array( $roleName, $roleNames ) )
				{
					$listNS[] = [ 'name' => $this->getProjectSetting( 'report-namespace-name' )[$i],
					              'roles' => $roleNames ];
				}
			}
			if ( isset( $_POST['report_id'] ) )
			{
				if ( isset( $_GET['report_id'] ) )
				{
					return [];
				}
				$_GET['report_id'] = $_POST['report_id'];
			}
			if ( ! empty( $listNS ) && isset( $_GET['report_id'] ) && $_GET['report_id'] != 0 )
			{
				$queryReportFolders =
					$this->query( 'SELECT rf.folder_id, rf.name FROM redcap_reports_folders_items' .
					              ' rfi JOIN redcap_reports_folders rf ON rfi.folder_id = ' .
					              'rf.folder_id WHERE rf.project_id = ? AND rfi.report_id = ?',
					              [ $this->getProjectId(), $_GET['report_id'] ] );
				$listReportFolders = [];
				while ( $itemReportFolder = $queryReportFolders->fetch_assoc() )
				{
					$listReportFolders[] = $itemReportFolder['name'];
				}
				foreach ( $listNS as $i => $infoNS )
				{
					if ( ! in_array( $infoNS['name'], $listReportFolders ) )
					{
						unset( $listNS[$i] );
					}
				}
				$listNS = array_values( $listNS );
			}
			return $listNS;
		}
		return [];
	}





	// Escapes text for inclusion in HTML.

	function escapeHTML( $text )
	{
		return htmlspecialchars( $text, ENT_QUOTES );
	}





	// Get the alerts supplied by other modules.

	function getCustomAlerts()
	{
		return is_array( $this->customAlerts ) ? $this->customAlerts : [];
	}





	// Get the alerts supplied by other modules.

	function getCustomReports()
	{
		return is_array( $this->customReports ) ? $this->customReports : [];
	}





	// Get the external module settings functions supplied by other modules.

	function getExtModSettings()
	{
		return is_array( $this->extModSettings ) ? $this->extModSettings : [];
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





	// Output JavaScript to hide the 'unverified' option on data entry forms / status icons legend.

	function hideUnverifiedOption( $instrument )
	{

		// Hide entry on legend for status icons.
		if ( $instrument === null )
		{

?>
<script type="text/javascript">
  $(function() {
    var vRemUnver = setInterval( function()
    {
      if ( $('img[src$="circle_yellow.png"],img[src$="circle_yellow_stack.png"]').length == 0 )
      {
        clearInterval( vRemUnver )
        return
      }
      $('img[src$="circle_yellow.png"]').parents('td').first().html('')
      $('img[src$="circle_yellow_stack.png"]').parent().find('img,span').css('left','')
      $('img[src$="circle_yellow_stack.png"]').remove()
    }, 300 )
  })
</script>
<?php

			return;
		}

		// Hide 'unverified' option on data entry form.

?>
<script type="text/javascript">
  $(function() {
    var vOptUnver = $('select[name="<?php echo $instrument; ?>_complete"] option[value="1"]')
    if ( vOptUnver.length == 1 && vOptUnver.parent().val() != '1' )
    {
      vOptUnver.remove()
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





	// Output JavaScript to change the form action URLs on the alerts page so that custom alert
	// senders are accepted.

	function provideAltAlertsSubmit()
	{
		$submitURL = addslashes( $this->getUrl('alerts_submit.php') );

?>
<script type="text/javascript">
  $(function()
  {
    $('#importAlertForm,#importAlertForm2').attr('action','<?php echo $submitURL; ?>&mode=upload')
  })
</script>
<?php

	}





	// Output JavaScript to set the requirement for a change reason based on the form's previous
	// 'complete' status.

	function provideChangeReason( $instrument )
	{

?>
<script type="text/javascript">
  $(function() {
    if ( $('select[name="<?php echo $instrument; ?>_complete"]').val() == '2' )
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
        vDialog.find('input').val('')
        vDialog.find('span').text('')
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





	// Output JavaScript to enable Data Resolution Workflow / provide default missing data codes.

	function provideDefaultCustom( $dataResolutionWorkflow, $missingDataCodes, $reasonForChange )
	{
		if ( $reasonForChange === '2' )
		{
			$this->setProjectSetting( 'require-change-reason-complete', true );
		}
?>
<script type="text/javascript">
  $(function()
  {
    $('body').css('display','none')
    setTimeout( function()
    {
<?php
		if ( $dataResolutionWorkflow )
		{
?>
      if ( $('#data_resolution_enabled_chkbx').prop('checked') )
      {
        $('#data_resolution_enabled').val('2')
      }
<?php
		}
		if ( $missingDataCodes != '' )
		{
			$defaultCodes =
				str_replace( ["\r\n","\n"], '\\n', $this->escapeHTML( $missingDataCodes ) );
?>
      if ( $('#missing_data_codes').val() == '' )
      {
        $('#missing_data_codes').val('<?php echo $defaultCodes; ?>')
      }
<?php
		}
		if ( $reasonForChange != '' )
		{
?>
      $('#require_change_reason').prop('checked',true)
<?php
		}
?>
      $('#customizeprojectform').submit()
    }, 1000 )
  })
</script>
<?php
	}





	// Output JavaScript to hide equations on the data quality notification popup.

	function provideDQHideEq()
	{
?>
<script type="text/javascript">
  $(function()
  {
    var vEqChkCount = 0
    var vEqChk = setInterval( function()
    {
      vEqChkCount++
      if ( vEqChkCount > 30 )
      {
        clearInterval( vEqChk )
        return
      }
      var vDQEq = $('div#dq_rules_table_single_record div.wrap div[style*="color:#555"]')
      if ( vDQEq.length > 0 )
      {
        vDQEq.css( 'display', 'none' )
        var vDQRuleHdr = $('div#dq_rules_table_single_record div.hDivBox th:eq(1) div')
        var vShowLnk = $('<a href="#" style="font-size:0.8em">show/hide logic</a>').click(function()
        {
          if ( vDQEq.css( 'display' ) == 'none' )
          {
            vDQEq.css( 'display', '' )
          }
          else
          {
            vDQEq.css( 'display', 'none' )
          }
          return false
        })
        vDQRuleHdr.append( '&nbsp;&nbsp;' ).append( vShowLnk )
        clearInterval( vEqChk )
      }
    }, 250)
  })
</script>
<?php
	}





	// Output JavaScript to enable real time execution of data quality rules by default.

	function provideDQRealTime()
	{
?>
<script type="text/javascript">
  $(function()
  {
    setInterval( function()
    {
      if ( $('#input_rulename_id_0').val() == '' && $('#input_rulelogic_id_0').val() == '' )
      {
        $('#rulerte_id_0').prop('checked',true)
      }
    }, 2000 )
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





	// Output JavaScript to provide the expanded annotations on the form designer.

	function provideExpandedAnnotations( $listAnnotations )
	{
		$listAnnotations = array_map( function( $i ) { return trim( $this->escapeHTML( $i ) ); },
		                              $listAnnotations );

?>
<script type="text/javascript">
  $(function()
  {
    var vListAnnotations = <?php echo json_encode( $listAnnotations ), "\n"; ?>
    var vLastField = ''
    var vLastFieldAnnotation = ''
    $('.frmedit_tbl:not(:has(.frmedit.actiontags)):not(:has(.header))'
          ).find('>tbody>tr:last(),>tr:last()'
          ).after('<tr><td class="frmedit actiontags" colspan="2"></td></tr>')
    var vFuncExpAnno = function()
    {
      var vAnnotationElem = $(this).find('.frmedit.actiontags')
      var vFieldName = $(this).attr('id').slice(7)
      if ( vListAnnotations[ vFieldName ] === undefined ) return
      $(this).css('border-collapse','separate')
      vAnnotationElem.css('border-top','1px solid #aaa')
      vAnnotationElem.html('<div class="mod-uitweaker-expanno"><code style="white-space:pre">' +
                           vListAnnotations[ vFieldName ] + '</code></div>')
    }
    $('.frmedit_tbl:not(:has(.header))').each( vFuncExpAnno )
    setInterval( function()
    {
      if ( $('#field_name:visible').length > 0 )
      {
        vLastField = $('#field_name').val()
        vLastFieldAnnotation = $('#field_annotation').val()
        return
      }
      if ( vLastField == '' ) return
      vListAnnotations[ vLastField ] = $('<div></div>').text( vLastFieldAnnotation ).html()
      $('.frmedit_tbl:not(:has(.frmedit.actiontags)):not(:has(.header))'
            ).find('>tbody>tr:last(),>tr:last()'
            ).after('<tr><td class="frmedit actiontags" colspan="2"></td></tr>')
      $('.frmedit_tbl:not(:has(.header)):not(:has(.mod-uitweaker-expanno))').each( vFuncExpAnno )
      vLastField = ''
    }, 1500 )
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





	// Output JavaScript to hide the 'contact REDCap administrator' links.

	function provideHideContactAdmin( $function )
	{
?>
<script type="text/javascript">
  $(function()
  {
    $('.btn-contact-admin')<?php echo $function; ?>.parent().css('display','none')
  })
</script>
<?php
	}





	// Output JavaScript to hide the 'suggest a new feature' link.

	function provideHideSuggestFeature()
	{
?>
<script type="text/javascript">
  $(function()
  {
    $('a[href*="redcap.vanderbilt.edu/enduser_survey"]').parent().css('display','none')
  })
</script>
<?php
	}





	// Output JavaScript to provide the initial configuration dialog for the module.

	function provideInitialConfig()
	{
?>
<script type="text/javascript">
  $(function()
  {
    simpleDialog( '<p>This module will alter some REDCap features by default.<br>You can ' +
                  'deactivate these features now, or later via the module system settings.</p>' +
                  '<form id="uitweak_init_config" method="post"><p><label><input type="checkbox" ' +
                  'name="fieldtypes" value="1" checked> Amend order/placement of field types' +
                  '</label><br><label><input type="checkbox" name="requiredfields" value="1" ' +
                  'checked> Set required status on new fields</label><br><label><input ' +
                  'type="checkbox" name="fieldannotations" value="1" checked> Set predefined ' +
                  'field annotations to the DataCat project categories</label><br><label><input ' +
                  'type="checkbox" name="dqrealtime" value="1" checked> Default data quality ' +
                  'rules to execute in real time</label><br><label><input type="checkbox" ' +
                  'name="statusicons" value="1" checked> Alternate status icons</label><br>' +
                  '<label><input type="checkbox" name="nonextrecord" value="1" checked> Remove ' +
                  '\'Save and Go To Next Record\' submit option</label></p></form>',
                  'REDCap UI Tweaker', null, 600,
                  function() { $.post( '<?php echo $this->getUrl( 'ajax_init_config.php' ); ?>',
                                       $('#uitweak_init_config').serialize() ) } )
  })
</script>
<?php
	}





	// Output JavaScript to provide auto-selection of the 'all status types' instrument status
	// option.

	function provideInstrumentAllStatusTypes()
	{
		if ( !defined( 'USERID' ) || USERID == '' )
		{
			return;
		}
		$selectAll = '';
		$listUsers = $this->getSystemSetting( 'all-status-types-userlist' );
		if ( $listUsers !== null &&
		     ( array_search( USERID, json_decode( $listUsers, true ) ) ) !== false )
		{
			$selectAll = 'vAStatLink.click()';
		}

?>
<script type="text/javascript">
  $(function()
  {
    var vIStatLink = $('[data-rc-lang="data_entry_226"]').parent()
    var vLStatLink = $('[data-rc-lang="data_entry_227"]').parent()
    var vEStatLink = $('[data-rc-lang="data_entry_228"]').parent()
    var vLEStatLink = $('[data-rc-lang="data_entry_230"]').parent()
    var vAStatLink = $('[data-rc-lang="data_entry_229"]').parent()
    if ( vIStatLink.length == 0 )
    {
      var vStatLinks = $('.statuslink_selected, .statuslink_unselected')
      vIStatLink = vStatLinks.eq(0)
      vLStatLink = vStatLinks.eq(1)
      vAStatLink = vStatLinks.eq(2)
      if ( vStatLinks.length == 4 )
      {
        vEStatLink = vStatLinks.eq(2)
        vAStatLink = vStatLinks.eq(3)
      }
      if ( vStatLinks.length == 5 )
      {
        vLEStatLink = vStatLinks.eq(3)
        vAStatLink = vStatLinks.eq(4)
      }
    }
    <?php echo $selectAll, "\n"; ?>
    var vFuncSetSel = function( event )
    {
      $.ajax( { url : '<?php echo $this->getUrl( 'ajax_set_inst_status_display.php' ); ?>',
                method : 'POST',
                data : { mode : event.data.mode },
                headers : { 'X-RC-UITweak-Req' : '1' },
                dataType : 'json'
              } )
    }
    vIStatLink.on( 'click', { mode: 'off' }, vFuncSetSel )
    vLStatLink.on( 'click', { mode: 'off' }, vFuncSetSel )
    if ( vEStatLink.length > 0 )
    {
      vEStatLink.on( 'click', { mode: 'off' }, vFuncSetSel )
    }
    if ( vLEStatLink.length > 0 )
    {
      vLEStatLink.on( 'click', { mode: 'off' }, vFuncSetSel )
    }
    vAStatLink.on( 'click', { mode: 'on' }, vFuncSetSel )
  })
</script>
<?php

	}





	// Output JavaScript to prevent selecting 'lock this instrument' from being treated as a data
	// change.

	function providePreventLockAsChange()
	{

?>
<script type="text/javascript">
  $(function() {
    $('#__LOCKRECORD__').click(function(e)
    {
      setTimeout(function()
      {
        $('#__LOCKRECORD__').prop( 'checked', ! $('#__LOCKRECORD__').prop('checked') )
      }, 100)
      e.preventDefault()
    })
    $('#__ESIGNATURE__').click(function(e)
    {
      setTimeout(function()
      {
        $('#__ESIGNATURE__').prop( 'checked', ! $('#__ESIGNATURE__').prop('checked') )
      }, 100)
      e.preventDefault()
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
      vRows = $('#table-proj_table tr[id^="'+vGroupID+'"]')
      for ( var i = 0; i < vNumRows; i++ )
      {
        vRows.eq(i).removeClass('myprojstripe')
        if ( i % 2 == 1 )
        {
          vRows.eq(i).addClass('myprojstripe')
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
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('codebook_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.css('margin-top','5px')
    vBtnSimplify.click(vFuncSimplify)
    var vButtons = $('<p> </p>')
    vButtons.prepend(vBtnSimplify)
    $('.jqbuttonmed[onclick="window.print();"],' +
      ' .jqbuttonmed[onclick="printCodebook();"]').closest('table').before(vButtons)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the external modules page.

	function provideSimplifiedExternalModules()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('extmod_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.css('margin-top','5px')
    vBtnSimplify.click(vFuncSimplify)
    var vButtons = $('<p> </p>')
    vButtons.prepend(vBtnSimplify)
    $('#external-modules-enable-modules-button').prev().before(vButtons)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the instrument/event mapping.

	function provideSimplifiedInstruments()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('instrument_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.click(vFuncSimplify)
    var vDivSimplify = $('<div style="margin-bottom:10px"></div>')
    vDivSimplify.append(vBtnSimplify)
    $('#table').before(vDivSimplify)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the data quality rules.

	function provideSimplifiedQualityRules()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('quality_rules_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.click(vFuncSimplify)
    var vDivSimplify = $('<div style="margin-bottom:10px"></div>')
    vDivSimplify.append(vBtnSimplify)
    $('#table-rules-parent').before(vDivSimplify)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the reports page.

	function provideSimplifiedReports()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('reports_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.click(vFuncSimplify)
    var vDivSimplify = $('<div style="margin-bottom:10px"></div>')
    vDivSimplify.append(vBtnSimplify)
    $('#report_list_parent_div').before(vDivSimplify)
  })
</script>
<?php

	}





	// Output JavaScript to provide the simplified view option on the user rights page.

	function provideSimplifiedUserRights()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vFuncSimplify = function()
    {
      window.location = '<?php echo addslashes( $this->getUrl('user_rights_simplified.php') ); ?>'
    }
    var vBtnSimplify = $('<button class="jqbuttonmed invisible_in_print ui-button ui-corner-all' +
                         ' ui-widget" id="simplifiedView">Simplified view</button>')
    vBtnSimplify.click(vFuncSimplify)
    var vDivSimplify = $('<div style="margin-bottom:10px"></div>')
    vDivSimplify.append(vBtnSimplify)
    $('#addUsersRolesDiv').after(vDivSimplify)
    setInterval( function()
    {
      if ( $('#simplifiedView').length == 0 )
      {
        vBtnSimplify.click(vFuncSimplify)
        $('#addUsersRolesDiv').after(vDivSimplify)
      }
    }, 1000 )
  })
</script>
<?php

	}





	// Output JavaScript for the simplified view diff highlighting popup.

	function provideSimplifiedViewDiff()
	{
?>
<div id="simplifiedViewDiff" style="display:none">
  <form method="post">
    <p>
     To perform difference highlighting, export the simplified view data from a project and load
     the data into another project.
    <p>
     <input type="hidden" name="simp_view_diff_mode" value="export">
     <input type="submit" value="Export Data">
    </p>
  </form>
  <hr>
  <form method="post" enctype="multipart/form-data">
   <p>File to import:&nbsp; <input type="file" name="simp_view_diff_file" required></p>
   <p>
    This file contains the:&nbsp;
    <label>
     &nbsp;<input type="radio" name="simp_view_diff_mode" value="new" required> New version
    </label>
    <label>
     &nbsp;<input type="radio" name="simp_view_diff_mode" value="old" required> Old version
    </label>
   </p>
   <p><input type="submit" value="Load Data and Perform Highlighting"></p>
  </form>
</div>
<script type="text/javascript">
  $('#simplifiedViewDiffBtn').click( function()
  {
    simpleDialog( null, 'Difference highlight', 'simplifiedViewDiff', 500 )
    $('#simplifiedViewDiff').attr('title','')
  } )
</script>
<?php
	}





	// Output tabs for navigating between the simplified views.

	function provideSimplifiedViewTabs( $active = '' )
	{

?>
<div class="clearfix">
 <div id="sub-nav" class="d-none d-sm-block" style="margin:5px 0 15px;width:98%">
  <ul>
<?php
		if ( $this->getSystemSetting( 'alerts-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'alerts' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('alerts_simplified.php'); ?>">Alerts</a>
   </li>
<?php
		}
		if ( $this->getSystemSetting( 'codebook-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'codebook' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('codebook_simplified.php'); ?>">Codebook</a>
   </li>
<?php
		}
		if ( $this->getSystemSetting( 'quality-rules-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'quality_rules' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('quality_rules_simplified.php'); ?>">Data Quality</a>
   </li>
<?php
		}
		$enableExtModSimplifiedView = $this->getSystemSetting( 'extmod-simplified-view' );
		if ( $enableExtModSimplifiedView == 'E' ||
		     ( $enableExtModSimplifiedView == 'A' && defined( 'SUPER_USER' ) && SUPER_USER == 1 ) )
		{
?>
   <li<?php echo $active == 'extmod' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('extmod_simplified.php'); ?>">External Modules</a>
   </li>
<?php
		}
		if ( \REDCap::isLongitudinal() && $this->getSystemSetting( 'instrument-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'instrument' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('instrument_simplified.php'); ?>">Instruments and Events</a>
   </li>
<?php
		}
		if ( $this->getSystemSetting( 'reports-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'reports' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('reports_simplified.php'); ?>">Reports</a>
   </li>
<?php
		}
		if ( $this->getSystemSetting( 'user-rights-simplified-view' ) )
		{
?>
   <li<?php echo $active == 'user_rights' ? ' class="active"' : ''; ?>>
    <a href="<?php echo $this->getUrl('user_rights_simplified.php'); ?>">User Rights</a>
   </li>
<?php
		}
?>
  </ul>
 </div>
</div>
<script type="text/javascript">$('#sub-nav .active a').css('color','#393733')</script>
<?php

	}





	// Logic to run before page render to provide the SQL checkbox field functionality.

	function provideSQLCheckBox( $listFields, $project_id, $record,
	                             $event_id, $instrument, $instance )
	{
		global $Proj;
		foreach ( $listFields as $infoField )
		{
			$fieldname = $infoField['field_name'];
			$fieldAnnotationEIf = $this->replaceIfActionTag( $infoField['field_annotation'],
			                                                 $project_id, $record, $event_id,
			                                                 $instrument, $instance );
			$sqlfieldname = \Form::getValueInActionTag( $infoField['field_annotation'],
			                                            '@SQLCHECKBOX' );
			try
			{
				if ( ! isset( $Proj->metadata[$sqlfieldname] ) ||
				     $Proj->metadata[$sqlfieldname]['element_type'] !== 'sql' )
				{
					throw new \Exception( 'SQL field (' . $sqlfieldname . ') does not exist.' );
				}
				$SQLValue = $Proj->metadata[$sqlfieldname]['element_enum'];
				if ( $SQLValue == '' )
				{
					throw new \Exception( 'SQL for field (' . $sqlfieldname . ') is not defined.');
				}
				$enum = getSqlFieldEnum( $SQLValue, $project_id, null, null, null, null, null,
				                         $instrument );
				$recordEnum = getSqlFieldEnum( $SQLValue, $project_id, $record, $event_id,
				                               $instance, null, null, $instrument );
				$listEnum = explode( ' \n ', $enum );
				$listRecordEnum = explode( ' \n ', $recordEnum );
				if ( empty( array_diff( $listRecordEnum, $listEnum ) ) )
				{
					// $recordEnum is a subset of $enum, use the values from $enum as the field
					// values enumeration and use the @HIDECHOICE action tag to hide options which
					// are not applicable in the context.
					$listHideEnum = array_diff( $listEnum, $listRecordEnum );
					$listHideChoices = [];
					foreach ( $listHideEnum as $itemEnum )
					{
						$listHideChoices[] = explode( ',', $itemEnum, 2 )[0];
					}
					if ( !empty( $listHideChoices ) )
					{
						$oldHideChoices =
								\Form::getValueInQuotesActionTag( $fieldAnnotationEIf,
								                                  '@HIDECHOICE' );
						$oldHideChoices = ( $oldHideChoices == '' ? '' : ",$oldHideChoices" );
						$Proj->metadata[$fieldname]['misc'] =
								"@HIDECHOICE='" . implode( ',', $listHideChoices ) .
								$oldHideChoices . "' " .$Proj->metadata[$fieldname]['misc'];
					}
				}
				else
				{
					// $recordEnum is not a subset of $enum, use the values from $recordEnum
					$enum = $recordEnum;
				}

				$Locking = new \Locking();
				$Locking->findLocked($Proj, $record, $fieldname, $event_id);
				$Locking->findLockedWholeRecord($project_id, $record);
				$locked = ( ! empty( $Locking->lockedWhole ) ||
				            ! empty( $Locking->locked[$record][$event_id][$instance] ) );

				if ( ! $locked && $this->isModuleEnabled('locking_forms') )
				{
					$Locking = \ExternalModules\ExternalModules::getModuleInstance('locking_forms');
					$locked = $Locking->isHardLocked( $event_id, $instrument );
				}

				if ( $Proj->metadata[$fieldname]['element_enum'] !== $enum && !$locked )
				{
					$Proj->metadata[$fieldname]['element_enum'] = $enum;
					$sql = 'UPDATE redcap_metadata SET element_enum = ? ' .
					       'WHERE project_id = ? AND field_name = ?';
					if ( ! $this->query( $sql, [ $enum, $project_id, $fieldname ] ) )
					{
						\REDCap::logEvent( 'REDCap UI Tweaker',
						                   "Failed to update check box enum\nField=" . $fieldname,
						                   $sql, $record, $event_id, $project_id );
						continue;
					}
					if ( $enum == '' )
					{
						$enum = 'No options returned';
					}
					\REDCap::logEvent( 'REDCap UI Tweaker',
					                   "Update check box options\nField=" . $fieldname . "\n" .
					                   "Options=" . $enum, $sql, $record, $event_id, $project_id );
				}
			}
			catch (\Exception $e)
			{
				\REDCap::logEvent( 'REDCap UI Tweaker',
				                   "Failed to update check box options\nField=" . $fieldname .
				                   "\nError=" . $e->getMessage(),
				                   null, $record, $event_id, $project_id );
			}
		}
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





	// Output the current user role name

	function provideUserRoleName()
	{
		if ( !defined( 'USERID' ) || USERID == '' )
		{
			return;
		}

		$userRights = $this->getUser()->getRights();
		if ( !isset( $userRights ) ||
		     !isset( $userRights['role_name'] ) || $userRights['role_name'] == '' )
		{
			return;
		}

		$roleName = $this->escapeHTML( $userRights['role_name'] );

?>
<script type="text/javascript">
  $(function()
  {
    if ( $('#user-role-name').length > 0 )
    {
      return
    }
    var vUserIcon = $( $('#username-reference').parent().find('i.fa-lock').prop('outerHTML')
                       ).attr('style','opacity:0').prop('outerHTML')
    var vRoleName = $('<div style="' + $('#username-reference').parent().attr('style') + '">' +
                      vUserIcon + 'Role: <span id="user-role-name" style="' +
                      $('#username-reference').attr('style') +
                      '"><?php echo $roleName; ?></span></div>')
    vRoleName.css('margin-top','-5px')
    if ( $('#impersonate-user-select').length == 0 || $('#impersonate-user-select').val() == '' )
    {
      $('#username-reference').parent().after( vRoleName )
    }
    else
    {
      $('#impersonate-user-select').parent().after( vRoleName )
    }
  })
</script>
<?php

	}





	// Output JavaScript to provide versionless URLs.

	function provideVersionlessURLs()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vVersionIndex = window.location.href.indexOf( 'redcap_v' + redcap_version )
    if ( vVersionIndex != -1 )
    {
      var vFullURL = window.location.href
      $('form').each( function()
      {
        if ( $(this).attr('action') === undefined )
        {
          if ( typeof this.action == 'string' )
          {
            this.action = this.action // set implicit action explicitly
          }
          else
          {
            this.action = vFullURL.replace(/#.*$/,'')
          }
        }
      })
      var vBaseElem = $('<base>')
      vBaseElem.attr('href',vFullURL)
      $('head').append( vBaseElem )
      var vOldURL = vFullURL.slice( 0, vVersionIndex + 8 + redcap_version.length )
      var vNewURL = vOldURL.replace( 'redcap_v' + redcap_version, 'redcap' )
      history.replaceState( history.state, '', window.location.href.replace( vOldURL, vNewURL ) )
      var vNewFullURL = window.location.href.match(/^[^#]+/)[0]
      $('a[href^="#"]').each( function()
      {
        $(this).attr( 'href', vNewFullURL + $(this).attr('href') )
      })
      addEventListener('beforeunload', function()
      {
        if ( window.location.href.indexOf( vOldURL ) != 0 )
        {
          history.replaceState( history.state, '',
                                window.location.href.replace( vNewURL, vOldURL ) )
        }
      })
    }
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
      var vTypeValue = vTypeList.val()
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
      if ( vTypeValue != 'file' )
      {
        vTypeList.val( vTypeValue )
      }
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





	// If supported, evaluate the @IF action tag.

	function replaceIfActionTag( $misc, $project_id, $record, $event_id, $instrument, $instance )
	{
		if ( method_exists('\Form', 'replaceIfActionTag') )
		{
			return \Form::replaceIfActionTag( $misc, $project_id, $record,
			                                  $event_id, $instrument, $instance );
		}
		return $misc;
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
    $('img[src$="circle_gray.png"]').attr('src','<?php echo $this->getIconUrl('gray'); ?>')
    $('img[src$="circle_red.png"]').attr('src','<?php echo $this->getIconUrl('red'); ?>')
    $('img[src$="circle_yellow.png"]').attr('src','<?php echo $this->getIconUrl('yellow'); ?>')
    $('img[src$="circle_red_stack.png"]').attr('src','<?php echo $this->getIconUrl('reds'); ?>')
    $('img[src$="circle_yellow_stack.png"]').attr('src', '<?php echo $this->getIconUrl('yellows'); ?>')
    $('img[src$="circle_blue_stack.png"]').attr('src','<?php echo $this->getIconUrl('blues'); ?>')
    $('img[src$="circle_orange_tick.png"]').attr('src','<?php echo $this->getIconUrl('orange'); ?>')
  }
  $(function(){
    $('img[src$="circle_red_stack.png"], img[src$="circle_yellow_stack.png"], ' +
      'img[src$="circle_blue_stack.png"]').on('click',
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
			if ( in_array( $settings['versionless-url'], [ 'M', 'E' ] ) )
			{
				$versionlessURLRegex =
					preg_split( "/(\r?\n)+/", $settings['versionless-url-regex'] );
				foreach ( $versionlessURLRegex as $versionlessURLItem )
				{
					if ( preg_match( '/' . str_replace( '/', '\\/', $versionlessURLItem ) . '/',
					                 '' ) === false )
					{
						return 'One or more regular expressions to match/exclude for the ' .
						       'versionless URLs is invalid.';
					}
				}
			}
			return null;
		}

		// Project-level settings.

		// If the user is not an administrator, check that the home page redirect URL, if specified,
		// is a relative path within the current project.
		if ( ! defined( 'SUPER_USER' ) || SUPER_USER != 1 )
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

