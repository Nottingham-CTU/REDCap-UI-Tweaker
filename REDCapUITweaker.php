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
	const STL_OLD = 'font-size:0.9em;color:#bbb';
	const BGC_HDR = '#ddd';
	const BGC_HDR_NEW = '#1dc';
	const BGC_HDR_CHG = '#dd8';
	const BGC_HDR_DEL = '#600';
	const BGC_NEW = '#4fe';
	const BGC_CHG = '#ffa';
	const BGC_DEL = '#b11';

	// Simplified view <br> tag.
	const SVBR = '<br style="mso-data-placement:same-cell">';
	// Maximum number of lines before Excel cell-splitting is allowed.
	// (In some contexts we want to allow cell-splitting so there isn't too much content in a cell.)
	const SVBR_MAX_LINES = 25;

	// Maximum number of checkboxes for SQLCHECKBOX fields.
	const MAX_SQLCHECKBOX_OPTIONS = 60;

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


		// If the @SQLCHECKBOX action tag is enabled and saving the SQL, add the SQL to provide the
		// combined options.
		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 21 ) == 'Design/edit_field.php' &&
		     $this->getSystemSetting( 'sql-checkbox' ) && $_POST['field_type'] == 'sql' &&
		     \Form::hasActionTag( '@SQLCHECKBOX',
		                          str_replace( "\n", ' ', $_POST['field_annotation'] ) ) )
		{
			$this->sqlCheckboxAddSQL();
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

			// If a submission which includes @SQLCHECKBOX fields, ensure that the selected options
			// are allowed to be submitted.
			$listValuesSQLChkbx = [];
			if ( $_GET['page'] != '' && ! empty( $_POST ) &&
			     $this->getSystemSetting( 'sql-checkbox' ) )
			{
				foreach ( $_POST as $postField => $postVal )
				{
					if ( substr( $postField, -22 ) == ':uitweaker-sqlcheckbox' )
					{
						$listValuesSQLChkbx[] = $_POST[ substr( $postField, 0, -22 ) ];
					}
				}
			}
			if ( empty( $listValuesSQLChkbx ) )
			{
				$this->removeProjectSetting( 'sql-checkbox-submittedvalues' );
			}
			else
			{
				$this->setProjectSetting( 'sql-checkbox-submittedvalues',
				                          json_encode( $listValuesSQLChkbx ) );
			}

			// [DEPRECATED] Check if the @SQLCHECKBOX action tag is enabled and provide its
			// functionality if so.
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
					$this->provideOldSQLCheckbox( $listFieldsSQLChkbx, $project_id,
					                              $_GET['id'], $_GET['event_id'],
					                              $_GET['page'], $_GET['instance'] );
				}
			}

		}


		// If static form names enabled.

		if ( $this->getSystemSetting( 'static-form-names' ) )
		{
			if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 22 ) == 'Design/create_form.php' )
			{
				$formDisplayName = $_POST['form_name'];
				$projectDesigner = new \Vanderbilt\REDCap\Classes\ProjectDesigner($GLOBALS['Proj']);
				// Create the form using the form variable name.
				$formCreated = $projectDesigner->createForm( $_POST['form_var_name'],
				                                             $_POST['after_form'] );
				$createdFormName = $projectDesigner->form;
				unset( $projectDesigner );
				echo $formCreated ? '1' : '0';
				if ( $formCreated )
				{
					// If form created successfully, rename to the chosen display name.
					$this->renameForm( $createdFormName, $formDisplayName, false );
					// Add the version field if required.
					if ( $this->getSystemSetting( 'version-fields' ) )
					{
						$versionAnnotation =
								$this->getSystemSetting( 'version-fields-default-annotation' );
						if ( trim( $versionAnnotation ) == '' )
						{
							$versionAnnotation = '@HIDDEN-SURVEY';
						}
						$versionAnnotation .= " @DEFAULT='1.0' @NOMISSING";
						// Must use a new Project object here.
						$projObj = new \Project( $this->getProjectId(), true );
						$projectDesigner = new \Vanderbilt\REDCap\Classes\ProjectDesigner($projObj);
						$versionField = [ 'field_label' => 'Form Version',
						                  'field_type' => 'select',
						                  'element_enum' => '1.0, 1.0',
						                  'field_annotation' => $versionAnnotation,
						                  'field_name' => $createdFormName . '_version',
						                  'field_req' => '1'
						                ];
						$projectDesigner->createField( $createdFormName, $versionField );
					}
				}
				$this->exitAfterHook();
			}
			elseif ( \REDCap::versionCompare( REDCAP_VERSION, '15.1.0', '<' ) &&
			         isset( $_POST['action'] ) && $_POST['action'] == 'set_menu_name' &&
			         ! isset( $_GET['internal_name'] ) &&
			     substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 24 ) == 'Design/set_form_name.php' )
			{
				$this->renameForm( $_POST['page'], $_POST['menu_description'] );
				echo $this->escapeHTML( strip_tags( $_POST['page'] ) ), "\n",
				     $this->escapeHTML( strip_tags( $_POST['menu_description'] ) );
				$this->exitAfterHook();
			}
			if ( $this->getSystemSetting( 'preserve-form-labels' ) )
			{
				if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 34 ) ==
				     'Design/zip_instrument_download.php' )
				{
					foreach ( ['redcap_metadata', 'redcap_metadata_temp'] as $metadataTable )
					{
						$this->query( 'UPDATE ' . $metadataTable .
						              ' SET misc = concat(\'##UITweaker-FormName:\',' .
						              'form_menu_description,\'\n\',ifnull(misc,\'\')) ' .
						              'WHERE project_id = ? AND form_name = ? ' .
						              'AND form_menu_description IS NOT NULL',
						              [ $project_id, $_GET['page'] ] );
					}
				}
				elseif ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 7 ) == 'Design/' )
				{
					foreach ( ['redcap_metadata', 'redcap_metadata_temp'] as $metadataTable )
					{
						$this->query( 'UPDATE ' . $metadataTable .
						              ' SET form_menu_description = regexp_substr(substring(misc,' .
						              '22),\'[^\n]+$\',1,1,\'m\'), misc = regexp_replace(misc,' .
						              '\'^[^\n]+\n\',\'\') WHERE project_id = ? AND ' .
						              'form_menu_description IS NOT NULL AND left(misc,21) = ' .
						              '\'##UITweaker-FormName:\'', [ $project_id ] );
					}
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





	// PDF hook. Handles the display of the form version fields on PDFs.

	function redcap_pdf( $project_id, $metadata, $data, $instrument = null, $record = null,
	                     $event_id = null, $instance = 1 )
	{
		if ( $this->getSystemSetting( 'static-form-names' ) &&
		     $this->getSystemSetting( 'version-fields' ) )
		{
			for ( $i = 0; $i < count( $metadata ); $i++ )
			{
				if ( $metadata[$i]['field_name'] == $metadata[$i]['form_name'] . '_version' )
				{
					if ( $record == null )
					{
						$metadata[$i]['element_enum'] =
								array_reverse( explode( '\n', $metadata[$i]['element_enum'] ) )[0];
					}
					else
					{
						$metadata[$i]['element_type'] = 'text';
					}
				}
			}
			return [ 'metadata' => $metadata, 'data' => $data ];
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


		// If no user is logged in, add the extra login page logo if set,
		// then exit the function here.

		if ( ! defined( 'USERID' ) || USERID == '' )
		{
			if ( $this->getSystemSetting( 'login-page-logo' ) != '' )
			{
				echo '<script type="text/javascript">$(function(){$(\'#container\')',
				     '.css(\'background\',$(\'#container\').css(\'background\')' ,
				     '.replace(\'rgba(0, 0, 0, 0)\',\'\')+\',url("',
				     $this->escapeHTML( $this->getSystemSetting( 'login-page-logo' ) ),
				     '") top right no-repeat\')})</script>';
			}
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

		if ( substr( PAGE_FULL, strlen( APP_PATH_WEBROOT ), 26 ) == 'Design/online_designer.php' )
		{

			if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] != '' )
			{

				// Provide the expanded field annotations.

				if ( $this->getSystemSetting( 'expanded-annotations' ) )
				{
					$listFields = \REDCap::getDataDictionary( 'array', false, null, $_GET['page'] );
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

				// Hide the first 'add field' buttons above a form version field.
				if ( $this->getSystemSetting( 'static-form-names' ) )
				{
					if ( $this->getSystemSetting( 'version-fields' ) )
					{
						$this->provideHideFirstAddField( $this->escapeHTML( $_GET['page'] ) );
					}
					if ( $this->getSystemSetting( 'fields-form-name-prefix' ) )
					{
						$this->provideDefaultFormVarName();
					}
				}


				// If the @SQLCHECKBOX action tag is enabled, remove the SQL which provides the
				// combined options on the edit field dialog.
				if ( $this->getSystemSetting( 'sql-checkbox' ) )
				{
					$this->sqlCheckboxRemoveSQL();
				}

				// [DEPRECATED] Check if the old @SQLCHECKBOX action tag is used and provide a
				// warning if so.
				if ( $_GET['page'] != '' && $this->getSystemSetting( 'sql-checkbox' ) )
				{
					$listFields = \REDCap::getDataDictionary( 'array', false, null, $_GET['page'] );
					$listFieldsSQLChkbx = [];
					foreach ( $listFields as $infoField )
					{
						if ( $infoField['field_type'] == 'checkbox' &&
						     str_contains( $infoField['field_annotation'], '@SQLCHECKBOX' ) )
						{
							$listFieldsSQLChkbx[] = $infoField['field_name'];
						}
					}
					if ( ! empty( $listFieldsSQLChkbx ) )
					{
						echo '<script type="text/javascript">$(function(){alert(\'The following ' .
						     'fields on this form are using the\\nold @SQLCHECKBOX ' .
						     'implementation:\\n';
						echo $this->escapeHTML( implode( '\\n', $listFieldsSQLChkbx ) );
						echo '\\n\\nIt is recommended that you update this form\\nto use the ' .
						     'new @SQLCHECKBOX implementation.\')})</script>';
					}
				}

			}
			else
			{
				// Provide custom alert sender (for ASI) if enabled.
				if ( $this->getSystemSetting( 'custom-alert-sender' ) )
				{
					$this->provideCustomAlertSender( 'ASI' );
				}

				// Provide static form variable names.
				if ( $this->getSystemSetting( 'static-form-names' ) )
				{
					$this->provideStaticFormVarName();
				}
			}


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



		// Amend the list of action tags when features which provide extra action tags are disabled.

		$listRemoveActionTags = [];
		if ( ! $this->getSystemSetting( 'submit-option-custom' ) )
		{
			$listRemoveActionTags[] = '@SAVEOPTIONS';
		}
		if ( ! defined( 'SUPER_USER' ) || ! SUPER_USER ||
		     ! $this->getSystemSetting( 'sql-descriptive' ) )
		{
			$listRemoveActionTags[] = '@SQLDESCRIPTIVE';
		}
		if ( ! defined( 'SUPER_USER' ) || ! SUPER_USER ||
		     ! $this->getSystemSetting( 'sql-checkbox' ) )
		{
			$listRemoveActionTags[] = '@SQLCHECKBOX';
		}
		if ( ! empty( $listRemoveActionTags ) )
		{
			$this->provideActionTagRemove( $listRemoveActionTags );
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

			// Add link to the enhanced rule H check.
			if ( $this->getSystemSetting( 'dq-enhanced-calc' ) )
			{
				$this->provideDQEnhancedCalc();
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


		// If changed fields are to be listed on the change reason popup, list them.

		if ( $this->getProjectSetting( 'change-reason-show-changes') )
		{
			$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
			foreach ( $listFields as $fieldName => $infoField )
			{
				if ( $fieldName == \REDCap::getRecordIdField() ||
				     $infoField['field_type'] == 'descriptive' )
				{
					unset( $listFields[ $fieldName ] );
					continue;
				}
				$infoField['field_label'] = strip_tags( $infoField['field_label'] );
				$infoField['field_label'] = str_replace( [ "\r\n", "\n" ], ' ',
				                                         $infoField['field_label'] );
				if ( strlen( $infoField['field_label'] ) > 80 )
				{
					$infoField['field_label'] = substr( $infoField['field_label'], 0, 75 ) . '...';
				}
				$listFields[ $fieldName ] = $this->escapeHTML( $infoField['field_label'] );
			}
			$this->provideChangesList( array_keys( $listFields ), $listFields );
		}


		// If the 'lock this instrument' option is not to be treated as a data change, amend so the
		// data changed flag is not set by ticking the option.

		if ( $this->getProjectSetting( 'prevent-lock-as-change' ) === true )
		{
			$this->providePreventLockAsChange();
		}


		// If equations on the data quality popup are to be hidden, hide them.

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
					// If data is being added using the Instance Table external module, only allow
					// blank submit options to be set (i.e. prohibit saving).
					if ( $submitOptions != '' && isset( $_GET['extmod_instance_table'] ) )
					{
						continue;
					}
					$customSubmit = $this->rearrangeSubmitOptions( $submitOptions );
				}
			}
			if ( ! $customSubmit && ! isset( $_GET['extmod_instance_table'] ) &&
			     $this->getProjectSetting( 'submit-option' ) != '' )
			{
				$customSubmit =
					$this->rearrangeSubmitOptions( $this->getProjectSetting( 'submit-option' ) );
			}
		}
		// If custom submit options are not present, perform the submit option tweak that is
		// selected in the module system settings (if applicable).
		if ( ! $customSubmit )
		{
			if ( isset( $_GET['extmod_instance_table'] ) )
			{
				// If data is being added using the Instance Table external module, and blank submit
				// options have not been set on the form, then display *only* the save and exit form
				// option.
				$this->rearrangeSubmitOptions( 'record' );
			}
			elseif ( $this->getSystemSetting( 'submit-option-tweak' ) == '1' )
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

		// Check if autofill links for all users are enabled and this is a development project or
		// server and provide the autofill link if so.
		if ( $this->getSystemSetting( 'show-autofill-development' ) &&
		     ( $this->getProjectStatus() == 'DEV' ||
		       ! empty( $this->query( 'SELECT 1 FROM redcap_config ' .
		                              'WHERE field_name = ? AND `value` = ?',
		                              ['is_development_server', '1'] )->fetch_row() ) ) )
		{
			$this->provideAutofill( false );
		}

		// Check if the lock blank form option is enabled and provide it if this is a blank form.
		// If data is being added using the Instance Table external module then *do not* display
		// the lock blank form option.
		if ( $this->getSystemSetting( 'lock-blank-form' ) &&
		     ! isset( $_GET['extmod_instance_table'] ) &&
		     ! $this->query( 'SELECT 1 FROM ' . \REDCap::getDataTable( $project_id ) .
		                     ' WHERE project_id = ? AND event_id = ? AND record = ? AND ' .
		                     'field_name = ? AND ifnull(instance,1) = ?',
		                     [ $project_id, $event_id, $record, $instrument . '_complete',
		                       $repeat_instance ] )->fetch_assoc() )
		{
			$this->provideLockBlankForm();
		}

		// Check if the @SQLCHECKBOX action tag is enabled and provide its functionality if so.
		if( $this->getSystemSetting( 'sql-checkbox' ) )
		{
			$this->provideSQLCheckbox( $instrument );
		}


	}





	// Perform actions on survey pages.

	function redcap_survey_page( $project_id, $record, $instrument, $event_id, $group_id = null,
	                             $survey_hash = null, $response_id = null, $repeat_instance = 1 )
	{

		// Check if autofill links for all users are enabled and this is a development project or
		// server and provide the autofill link if so.
		if ( $this->getSystemSetting( 'show-autofill-development' ) &&
		     ( $this->getProjectStatus() == 'DEV' ||
		       ! empty( $this->query( 'SELECT 1 FROM redcap_config ' .
		                              'WHERE field_name = ? AND `value` = ?',
		                              ['is_development_server', '1'] )->fetch_row() ) ) )
		{
			$this->provideAutofill( true );
		}

		// Check if the @SQLCHECKBOX action tag is enabled and provide its functionality if so.
		if( $this->getSystemSetting( 'sql-checkbox' ) )
		{
			$this->provideSQLCheckbox( $instrument );
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





	// Echo plain text to output (without Psalm taints).
	// Use only for e.g. JSON or CSV output.

	function echoText( $text )
	{
		$text = htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XHTML );
		$chars = [ '&amp;' => 38, '&quot;' => 34, '&apos;' => 39, '&lt;' => 60, '&gt;' => 62 ];
		$text = preg_split( '/(&(?>amp|quot|apos|lt|gt);)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $text as $part )
		{
			echo isset( $chars[ $part ] ) ? chr( $chars[ $part ] ) : $part;
		}
	}





	// Escapes text for inclusion in HTML.

	function escapeHTML( $text )
	{
		return htmlspecialchars( $text, ENT_QUOTES );
	}





	// Escapes text string for inclusion in JavaScript.

	function escapeJSString( $text )
	{
		return '"' . $this->escapeHTML( substr( json_encode( (string)$text,
		                                                     JSON_HEX_QUOT | JSON_HEX_APOS |
		                                                     JSON_HEX_TAG | JSON_HEX_AMP |
		                                                     JSON_UNESCAPED_SLASHES ),
		                                        1, -1 ) ) . '"';
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
		return preg_replace( '!^https?://[^/]*!', '',
		                     preg_replace( '/&pid=[1-9][0-9]*/', '',
		                                   $this->getUrl( "status_icon.php?icon=$icon" ) ) );
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





	// Output JavaScript to remove action tags from the action tags guide.

	function provideActionTagRemove( $listActionTags )
	{

?>
<script type="text/javascript">
$(function()
{
  if ( typeof actionTagExplainPopup == 'undefined' )
  {
    return
  }
  var vListTagsRemove = <?php echo json_encode( $listActionTags ), "\n"; ?>
  var vActionTagPopup = actionTagExplainPopup
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
      var vRows = vActionTagTable.find('tr')
      for ( var i = 0; i < vRows.length; i++ )
      {
        var vTag = vRows.eq(i).find('td:eq(1)').text()
        if ( vListTagsRemove.includes( vTag ) )
        {
          vRows.eq(i).css('display','none')
        }
      }
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





	// Output JavaScript to add the autofill option for all users if the project is in development
	// status or the server is a development/testing server.

	function provideAutofill( $survey = false )
	{
		if ( $survey )
		{
?>
<script type="text/javascript">
  $(function()
  {
    if ( typeof lang.global_276 == 'undefined' )
    {
      lang.global_276 = <?php echo $this->escapeJSString( $GLOBALS['lang']['global_276'] ), "\n"; ?>
    }
    if ( $('#auto-fill-btn').length == 0 && $('#admin-controls-div').length == 0 )
    {
      $('#pagecontent').append('<div id="admin-controls-div" style="position:absolute;top:0px;' +
                               'left:calc(100%);margin:5px 0px 0px 7px;width:max-content"></div>')
      $('#admin-controls-div').append('<a id="auto-fill-btn" class="btn btn-link btn-xs fs11" ' +
                                      'href="javascript:;" onclick="autoFill();" style="color:' +
                                      'rgb(136, 136, 136)"><i class="fs10 fa-solid ' +
                                      'fa-wand-magic-sparkles"></i> <span data-rc-lang=' +
                                      '"global_276">' + lang.global_276 + '</span></a>')
    }
  })
</script>
<?php
		}
		else
		{
?>
<script type="text/javascript">
  $(function()
  {
    setInterval( function()
    {
      if ( $('#auto-fill-btn').length == 0 )
      {
        $('#formSaveTip').append('<div class=""><button id="auto-fill-btn" class="btn btn-link ' +
                                'btn-xs" style="font-size:11px !important;padding:1px 5px ' +
                                 '!important;margin:0 !important;color:#007bffcc;" onclick=' +
                                 '"autoFill();"><i class="fs10 fa-solid fa-wand-magic-sparkles ' +
                                 'mr-1"></i>' + lang.global_275 + '</button></div>')
      }
    }, 5000 )
  })
</script>
<?php
		}
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





	// Output JavaScript to show the list of field changes on the change reason popup.

	function provideChangesList( $fields, $labels )
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vListFields = <?php echo json_encode( $fields ), "\n"; ?>
    var vListLabels = <?php echo json_encode( $labels ), "\n"; ?>
    var vListValues = {}
    $.each( vListFields, function()
    {
      vListValues[this] = $('[name="' + this + '"]').val()
    })
    var vDataEntrySubmit = dataEntrySubmit
    dataEntrySubmit = function ( vOb )
    {
      if ( $('#change_reason_popup div.listchanges').length == 0 )
      {
        $('#change_reason_popup div:eq(0)').before('<div class="listchanges"></div>')
        $('#change_reason_popup div.listchanges')
          .css('max-height','100px').css('overflow-y','auto').css('border','solid 1px #ccc')
          .before('<div style="font-weight:bold;padding:5px 0">' +
                  '<?php echo $GLOBALS['lang']['data_history_03']; ?>:</div>')
      }
      $('#change_reason_popup div.listchanges').html('<ul style="margin-bottom:0"></ul>')
      $.each( vListFields, function()
      {
        if ( vListValues[this] != $('[name="' + this + '"]').val() )
        {
          var vOldVal = $('<span></span>').text(vListValues[this]).html()
          var vNewVal = $('<span></span>').text($('[name="' + this + '"]').val()).html()
          $('#change_reason_popup div.listchanges ul')
            .append('<li><b>' + vListLabels[this] + '</b><br><span style="font-size:0.9em">' +
                    '<?php echo $GLOBALS['lang']['ws_152']; ?>: ' + vOldVal + '<br>' +
                    '<?php echo $GLOBALS['lang']['data_comp_tool_32']; ?>: ' + vNewVal +
                    '</span></li>')
        }
      })
      vDataEntrySubmit( vOb )
    }
  })
</script>
<?php

	}





	// Output JavaScript to allow a custom from address to be selected in alerts.

	function provideCustomAlertSender( $for = 'alerts' )
	{
		$selectFields = ( $for == 'alerts' ? 'select[name="email-from"], select[name="email-failed"]'
		                                   : 'select[name="email_sender"]' );

?>
<script type="text/javascript">
  $(function()
  {
    var vRegexValidate = /<?php echo $this->getSystemSetting('custom-alert-sender-regex'); ?>/
    var vDialog = $('<div><input type="text" style="width:100%"><br>' +
                    '<span style="color:#c00"></span></div>')
    var vSelectFields = $('<?php echo $selectFields; ?>')
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
<?php

	if ( $for == 'alerts' )
	{

?>
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
<?php

	}
	elseif ( $for == 'ASI' )
	{

?>
    var vInitSurveyReminderSettings = initSurveyReminderSettings
    initSurveyReminderSettings = function()
    {
      vInitSurveyReminderSettings()
      vSelectFields = $('<?php echo $selectFields; ?>')
      vSelectFields.find('[value="999"]').remove()
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
          vDialog.find('input').val('')
          vDialog.find('span').text('')
          vDialog.dialog('open')
        }
      })
    }
<?php

	}

?>

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





	// Output JavaScript to add a link to the enhanced DQ rule H check.

	function provideDQEnhancedCalc()
	{
?>
<script type="text/javascript">
  $(function()
  {
    $('#rulename_pd-10')
      .append('<br><a href="<?php echo addslashes( $this->getUrl('quality_rules_ecalc.php') ); ?>">' +
              '<i class="fas fa-wand-sparkles"></i> Enhanced check</a>')
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





	// Output JavaScript to set field names to be prefixed by the form name by default.

	function provideDefaultFormVarName()
	{

?>
<script type="text/javascript">
  $(function() {
    setInterval(function() {
      var vEditID = $('#sq_id')
      if ( vEditID.length > 0 && vEditID.val() == '' && $('#field_name').val() == '' )
      {
        $('#field_name').val( form_name + '_' )
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
            if ( vMInputLab[ i ].value == '' && vMInputNam[ i ].value == '' )
            {
              vMInputNam[ i ].value = form_name + '_'
            }
          }
        }
      }
    }, 500 )
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
        if ( $('#field_label').val() == '' &&
             ( $('#field_name').val() == '' || $('#field_name').val() == form_name + '_' ) )
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
            if ( vMInputLab[ i ].value == '' &&
                 ( vMInputNam[ i ].value == '' || vMInputNam[ i ].value == form_name + '_' ) )
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
    $('head').append('<style type="text/css">.mod-uitweaker-expanno{overflow:hidden;' +
                     'text-overflow:ellipsis;white-space:nowrap}</style>')
    var vInsertAnnotationRow = '<tr class="frmedit actiontags"><td colspan="2"></td></tr>'
    if ( $('td.frmedit.actiontags').length > 0 )
    {
      vInsertAnnotationRow = '<tr><td class="frmedit actiontags" colspan="2"></td></tr>'
    }
    var vListAnnotations = <?php echo json_encode( $listAnnotations ), "\n"; ?>
    var vLastField = ''
    var vLastFieldAnnotation = ''
    $('.frmedit_tbl:not(:has(.frmedit.actiontags)):not(:has(.header))'
          ).find('>tbody>tr:last(),>tr:last()').after( vInsertAnnotationRow )
    var vFuncExpAnno = function()
    {
      var vAnnotationElem = $(this).find('.frmedit.actiontags')
      if ( vAnnotationElem.prop('tagName').toLowerCase() == 'tr' )
      {
        vAnnotationElem = vAnnotationElem.find('td')
      }
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
            ).find('>tbody>tr:last(),>tr:last()').after( vInsertAnnotationRow )
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





	// Output JavaScript to hide the first 'add field' buttons on the designer if a form version
	// field is present.

	function provideHideFirstAddField( $instrument )
	{
?>
<script type="text/javascript">
  $(function()
  {
    var vFuncHideAddField = function()
    {
      var vFirstAddField = $('div.frmedit').first()
      if ( vFirstAddField.length == 1 &&
           vFirstAddField.next().attr('id') == 'design-<?php echo $instrument; ?>_version' )
      {
        vFirstAddField.css( 'display', 'none' )
      }
    }
    vFuncHideAddField()
    setInterval( vFuncHideAddField, 2000 )
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
    $('a[href*="redcap.vanderbilt.edu/enduser_survey"],a[href*="redcap.vumc.org/enduser_survey"]')
     .parent().css('display','none')
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





	// Output JavaScript to provide a 'lock blank form' option.

	function provideLockBlankForm()
	{

?>
<script type="text/javascript">
  $(function()
  {
    setTimeout(function()
    {
      if ( $('#__LOCKRECORD__:visible:not(:checked)').length > 0 )
      {
        $('#__LOCKRECORD__-tr')
          .after('<tr class="d-print-none"><td class="labelrc col-7"></td><td class="data col-5">' +
                 '<button onclick="lockDisabledForm(this)" class="btn btn-defaultrc btn-xs" ' +
                 'type="button" style="margin:10px 0px">Lock blank form</button></td></tr>')
      }
    },1000)
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

	function provideSimplifiedViewDiff( $ext = '' )
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
   <p>
    File to import:&nbsp;
    <input type="file" name="simp_view_diff_file" accept="<?php echo $ext; ?>.json" required>
   </p>
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





	// Output JavaScript to provide the SQL checkbox field functionality.

	function provideSQLCheckbox( $instrument )
	{
		$listFields = \REDCap::getDataDictionary( 'array', false, null, $instrument );
		$listFieldsSQLChkbx = [];

		foreach ( $listFields as $infoField )
		{
			if ( $infoField['field_type'] == 'sql' &&
			     \Form::hasActionTag( '@SQLCHECKBOX',
			                        str_replace( "\n", ' ', $infoField['field_annotation'] ) ) )
			{
				$listFieldsSQLChkbx[] = [ 'name' => $infoField['field_name'],
				                          'align' => $infoField['custom_alignment'] ];
			}
		}
		if ( empty( $listFieldsSQLChkbx ) )
		{
			return;
		}
		$fieldsJSON = $this->escapeJSString( json_encode( $listFieldsSQLChkbx ) );

?>
<script type="text/javascript">
  $(function()
  {
    setTimeout(function(){
      var vListFields = JSON.parse(<?php echo $fieldsJSON; ?>)
      for ( var i = 0; i < vListFields.length; i++ )
      {
        var vFieldName = vListFields[i].name
        var vIsVertical = ( vListFields[i].align.indexOf('H') == -1 )
        var vSQLField = $('select[name="' + vFieldName + '"]')
        var vFieldOptions = $( vSQLField ).find('option:not([value=""]):not([value*=","])')
        var vSelectedOptions = $( vSQLField ).val().split(',')
        var vContainerField = vSQLField.parent()
        while ( vContainerField.parent().prop('tagName').toLowerCase() == 'span' )
        {
          vContainerField = vContainerField.parent()
        }
        var vContainerChkbx = $('<div></div>')
        for ( var j = 0; j < vFieldOptions.length; j++ )
        {
          var vOption = vFieldOptions.slice( j, j + 1 )
          if ( typeof vOption.parent().attr('data-mlm-mdcs') != 'undefined' ||
               vOption.parent().attr('data-rc-lang-attrs') == 'label=missing_data_04' )
          {
            continue
          }
          var vOptionChkbx = $('<input type="checkbox">').attr('data-sqlcb-field', vFieldName)
                                                         .attr('data-sqlcb-val', vOption.val())
          if ( vSelectedOptions.indexOf( vOption.val() ) > -1 )
          {
            vOptionChkbx.prop('checked', true)
          }
          var vOptionLabel = $('<label></label>').css('margin-bottom','0')
                                                 .append( vOptionChkbx )
                                                 .append( ' ' + vOption.text() )
          vOptionLabel.click( function()
          {
            var vClickedField = $(this).find('input').attr('data-sqlcb-field')
            var vNewValue = $('input[data-sqlcb-field="' + vClickedField + '"]:checked')
                  .map(function(){return $(this).attr('data-sqlcb-val')}).toArray().sort().join(',')
            if ( $('select[name="' + vClickedField + '"] ' +
                   'option[value="' + vNewValue + '"]').length == 0 )
            {
              $('select[name="' + vClickedField + '"]')
              .append('<option value="' + vNewValue + '">' + vNewValue + '</option>')
            }
            $('select[name="' + vClickedField + '"]').val( vNewValue )
          })
          if ( vIsVertical )
          {
            var vOptionWrap = $('<div class="choicevert"></div>')
                              .css('text-indent','0').css('margin-left','0')
          }
          else
          {
            var vOptionWrap = $('<span class="choicehoriz"></span>')
          }
          vOptionWrap.append( vOptionLabel )
          vContainerChkbx.append( vOptionWrap )
        }
        vContainerField.after( vContainerChkbx )
        vContainerField.css('display','none')
        vSQLField.after( '<input type="hidden" name="' + vFieldName +
                         ':uitweaker-sqlcheckbox" value="1">' )
      }
    },100)
  })
</script>
<?php

	}





	// [DEPRECATED] Logic to run before page render to provide the SQL checkbox field functionality.

	function provideOldSQLCheckbox( $listFields, $project_id, $record,
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





	// Provide the options for choosing a form variable name.

	function provideStaticFormVarName()
	{
?>
<script type="text/javascript">
  $(function()
  {
<?php
		if ( \REDCap::versionCompare( REDCAP_VERSION, '15.1.0', '<' ) )
		{
?>
    if ( status == '0' )
    {
      $('#table-forms_surveys tr').find('td:eq(1)').each(function()
      {
        var vFormLink = $(this).find('a.formLink')
        var vFormName = vFormLink.find('span[id^="formlabel-"]').attr('id').substring(10)
        vFormLink.after( ' <i>(' + vFormName + ')</i>' )
        vFormLink.css('display', 'inline-block')
        var vSaveBtn = $(this).find( '#form_menu_save_btn-' + vFormName )
        var vChgName = $('<a href="#"><i class="fas fa-pencil-alt fs8"></i></a>')
        vChgName.attr('title', 'Edit form variable name')
        vChgName.click(function()
        {
          var vNewFormName = prompt( 'New form variable name for ' + vFormName, vFormName )
          if ( vNewFormName !== null && vNewFormName !== '' )
          {
            $.post( app_path_webroot + 'Design/set_form_name.php?internal_name=1&pid=' + pid,
                    { page: vFormName, action: 'set_menu_name', menu_description: vNewFormName },
                    function ( vResult )
                    {
                      var vUpdatedFormName = vResult.replace( /\n.*$/s, '' )
                      var vNewFormLabel = $( '#form_menu_description_input-' + vFormName ).val()
                      $.post( app_path_webroot + 'Design/set_form_name.php?pid=' + pid,
                              { page: vUpdatedFormName, action: 'set_menu_name',
                                menu_description: vNewFormLabel },
                              function ()
                              {
                                window.location.reload()
                              })
                    } )
          }
          return false
        })
        vSaveBtn.after( vChgName )
        vSaveBtn.after( ' &nbsp;&nbsp;' )
      })
    }
<?php
		}
?>
    var vAddNewFormReveal = addNewFormReveal
    var vAddNewForm = addNewForm
    var vClickedBtnName = ''
    addNewFormReveal = function ( vFormName )
    {
      vAddNewFormReveal( vFormName )
      var vCreateBtn = $( '#new-' + vFormName + ' [type="button"]' )
      vCreateBtn.before( '<br><span style="margin:0 5px 0 25px;font-weight:bold">' +
                         'Form variable name:</span>' )
      var vFormVarField = $( '<input type="text" class="x-form-text x-form-field" ' +
                             'style="font-size:13px;" id="new_form_var-' + vFormName + '">' )
      vFormVarField.keyup( function()
      {
        var vVal = $(this).val()
        vVal = vVal.replace( /[^a-z0-9_]/, '' )
        vVal = vVal.replace( /_+/, '_' )
        vVal = vVal.replace( /^[0-9_]+/, '' )
        $(this).val( vVal )
      })
      vFormVarField.change( function()
      {
        var vVal = $(this).val()
        vVal = vVal.replace( /[^a-z0-9_]/, '' )
        vVal = vVal.replace( /_+/, '_' )
        vVal = vVal.replace( /^[0-9_]+/, '' )
        vVal = vVal.replace( /_$/, '' )
        $(this).val( vVal )
      })
      vCreateBtn.before( vFormVarField )
      vCreateBtn.before( ' ' )
    }
    addNewForm = function ( vFormName )
    {
      vClickedBtnName = vFormName
      if ( $('#new_form_var-' + vFormName).val() != '' )
      {
        vAddNewForm( vFormName )
      }
    }
    $(document).on( 'ajaxSend', function(e,x,s)
    {
      if ( s.url == app_path_webroot + 'Design/create_form.php?pid=' + pid )
      {
        s.data += '&form_var_name=' + $( '#new_form_var-' + vClickedBtnName ).val()
      }
    } )
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





	// Rename a form display name, without changing its underlying variable name.

	function renameForm( $formName, $newFormName, $log = true )
	{
		$projectID = $this->getProjectId();
		$newFormName = strip_tags( \label_decode( $newFormName ) );
		$status = \REDCap::getProjectStatus( $projectID );

		// Use temp metadata table if project is in production.
		$metadataTable = ($status > 0) ? "redcap_metadata_temp" : "redcap_metadata";
		// Clear old form display name.
		$this->query( "UPDATE $metadataTable SET form_menu_description = NULL " .
		              "WHERE form_name = ? AND project_id = ?",
		              [ $formName, $projectID ] );
		// Set new form display name.
		$this->query( "UPDATE $metadataTable SET form_menu_description = ? WHERE form_name = ? " .
		              "AND project_id = ? ORDER BY field_order LIMIT 1",
		              [ $newFormName, $formName, $projectID ] );
		// Log the rename.
		if ( $log )
		{
			\Logging::logEvent( "", $metadataTable, "MANAGE", $formName,
			                    "form_name = '" . \db_escape($formName) . "'",
			                    "Rename data collection instrument");
		}
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





	// Add the SQL to an SQLCHECKBOX field to provide the combined options.

	function sqlCheckboxAddSQL()
	{
		unset( $_POST['dropdown_autocomplete'] );
		$_POST['element_enum'] =
			"SELECT * FROM ( WITH RECURSIVE redcap_ui_tweaker_ev AS ( SELECT DISTINCT `value` " .
			"AS v FROM [data-table] WHERE project_id = [project-id] AND field_name = '" .
			preg_replace( '/[^A-Za-z0-9_]/', '', $_POST['field_name'] ) .
			"' UNION SELECT v FROM redcap_external_module_settings JOIN JSON_TABLE( `value`, " .
			"'$[*]' COLUMNS ( v VARCHAR(255) PATH '$' ) ) redcap_ui_tweaker_sv WHERE " .
			"external_module_id = (SELECT external_module_id FROM redcap_external_modules WHERE " .
			"directory_prefix = 'redcap_ui_tweaker' LIMIT 1) AND project_id = [project-id] AND " .
			"`key` = 'sql-checkbox-submittedvalues' ), redcap_ui_tweaker_qy AS (" .
			"SELECT * FROM (\n" .
			"-- END redcap_ui_tweaker\n" .
			rtrim( $_POST['element_enum'], " \n\r\t;" ) .
			"\n-- BEGIN redcap_ui_tweaker\n" .
			") redcap_ui_tweaker_sqy LIMIT " . self::MAX_SQLCHECKBOX_OPTIONS .
			"), redcap_ui_tweaker_cb AS ( SELECT cast(concat(',',redcap_ui_tweaker_qy.`code`) " .
			"AS CHAR(255)) AS codestr, redcap_ui_tweaker_qy.`code`, cast(concat(', '," .
			"redcap_ui_tweaker_qy.`label`) AS CHAR(255)) AS `label` FROM redcap_ui_tweaker_qy " .
			"UNION ALL SELECT concat(redcap_ui_tweaker_cb.codestr,',',redcap_ui_tweaker_qy." .
			"`code`), redcap_ui_tweaker_qy.`code`, concat(redcap_ui_tweaker_cb.`label`,', '," .
			"redcap_ui_tweaker_qy.`label`) FROM redcap_ui_tweaker_cb JOIN redcap_ui_tweaker_qy " .
			"WHERE locate(concat(',',redcap_ui_tweaker_qy.`code`),redcap_ui_tweaker_cb.codestr) " .
			"= 0 AND redcap_ui_tweaker_cb.`code` < redcap_ui_tweaker_qy.`code` AND " .
			"concat(redcap_ui_tweaker_cb.codestr,',',redcap_ui_tweaker_qy.`code`) " .
			"IN ( SELECT substring(concat(',',v),1,length(concat(redcap_ui_tweaker_cb.codestr," .
			"',',redcap_ui_tweaker_qy.`code`))) FROM redcap_ui_tweaker_ev ) ) " .
			"SELECT substring(codestr,2) AS `code`, substring(`label`,3) AS `label` " .
			"FROM redcap_ui_tweaker_cb ) redcap_ui_tweaker_out";
	}





	// JavaScript to remove the SQL which provides the combined options from an SQLCHECKBOX field.

	function sqlCheckboxRemoveSQL()
	{

?>
<script type="text/javascript">
  $(function()
  {
    var vOpenAddQuesFormVisible = openAddQuesFormVisible
    var vRemoveUITweakerCode = function()
    {
      $('#element_enum').val( $('#element_enum').val()
                             .replace(/-- BEGIN redcap_ui_tweaker.*?-- END redcap_ui_tweaker/gs, '')
                             .replace(/-- BEGIN redcap_ui_tweaker.*/gs, '')
                             .replace(/.*-- END redcap_ui_tweaker/gs, '').trim() )
    }
    openAddQuesFormVisible = function(sq_id)
    {
      if ( $('#field_type').val() == 'sql' &&
           $('#field_annotation').val().indexOf('@SQLCHECKBOX') != -1 )
      {
        setTimeout( vRemoveUITweakerCode, 100 )
        setTimeout( vRemoveUITweakerCode, 500 )
        setTimeout( vRemoveUITweakerCode, 1000 )
      }
      return vOpenAddQuesFormVisible(sq_id)
    }
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

