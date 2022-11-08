<?php

// This page receives AJAX requests to set the instrument status display.

header( 'Content-Type: application/json' );

$mode = $_POST['mode'];

if ( $mode == '' || !defined( 'USERID' ) || USERID == '' ||
     !isset( $_SERVER['HTTP_X_RC_UITWEAK_REQ'] ) )
{
	echo 'false';
	exit;
}

if ( $module->getProjectSetting( 'all-status-types' ) )
{
	$listUsers = $module->getProjectSetting( 'all-status-types-userlist' );
	if ( $listUsers === null )
	{
		$listUsers = [];
	}
	else
	{
		$listUsers = json_decode( $listUsers, true );
	}
	if ( $mode == 'on' )
	{
		$listUsers[] = USERID;
	}
	else if ( ( $k = array_search( USERID, $listUsers ) ) !== false )
	{
		unset( $listUsers[$k] );
	}
	$module->setSystemSetting( 'all-status-types-userlist', json_encode( $listUsers ) );
	echo 'true';
	exit;
}

echo 'false';