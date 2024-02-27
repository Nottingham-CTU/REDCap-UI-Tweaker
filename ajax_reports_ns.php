<?php

// AJAX endpoint to handle editing/deleting namespaced reports by a user with a namespaced report
// role.

namespace Nottingham\REDCapUITweaker;

if ( ! $module->getProjectSetting( 'report-namespaces' ) )
{
	exit;
}

// Set this variable explicitly to defined strings to avoid Psalm errors.
$includePage = '';
switch ( $_GET['rcpage'] )
{
	case 'report_edit_ajax':
		$includePage = 'report_edit_ajax';
		break;
	case 'report_delete_ajax':
		$includePage = 'report_delete_ajax';
		break;
}

$listReportNS = $module->checkReportNamespaceAuth();

if ( ! empty( $listReportNS ) && $includePage != '' )
{
	$GLOBALS['user_rights']['reports'] = 1;

	if ( $includePage == 'report_edit_ajax' )
	{
		$_POST['user_access_radio'] = 'SELECTED';
		$_POST['user_edit_access_radio'] = 'SELECTED';
		$queryRoles = $module->query( 'SELECT role_id, role_name FROM redcap_user_roles WHERE ' .
		                              'project_id = ?', [ $module->getProjectId() ] );
		$_POST['user_access_roles'] = [];
		$_POST['user_edit_access_roles'] = [];
		while ( $itemRole = $queryRoles->fetch_assoc() )
		{
			if ( in_array( $itemRole['role_name'], $listReportNS[0]['roles'] ) )
			{
				$_POST['user_access_roles'][] = $itemRole['role_id'];
				$_POST['user_edit_access_roles'][] = $itemRole['role_id'];
			}
		}
		unset( $_POST['is_public'], $_POST['user_access_users'], $_POST['user_access_dags'],
		       $_POST['user_edit_access_users'], $_POST['user_edit_access_dags'] );
	}

	require APP_PATH_DOCROOT . '/DataExport/' . $includePage . '.php';

	if ( $includePage == 'report_edit_ajax' && $_GET['report_id'] == 0 )
	{
		// New report
		$listReportFolders = \DataExport::getReportFolders( $module->getProjectId() );
		if ( ! in_array( $listReportNS[0]['name'], $listReportFolders ) )
		{
			$_POST['folder_name'] = $listReportNS[0]['name'];
			\DataExport::reportFolderCreate();
			$listReportFolders = \DataExport::getReportFolders( $module->getProjectId() );
		}
		$_POST['folder_id'] = array_search( $listReportNS[0]['name'], $listReportFolders );
		$_POST['report_id'] = $report_id;
		$_POST['checked'] = '1';
		\DataExport::reportFolderAssign();
		$module->query( 'COMMIT', [] );
	}
}