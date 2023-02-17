<?php

// This page receives AJAX requests as part of the form navigation fix.

namespace Nottingham\REDCapUITweaker;


header( 'Content-Type: application/json' );

$nav = $_POST['nav'];

if ( $nav == '' || !isset( $_SERVER['HTTP_X_RC_UITWEAK_REQ'] ) )
{
	echo 'false';
	exit;
}

if ( $module->getProjectSetting( 'fix-form-navigation' ) && $nav == 'submit-btn-savenextform' )
{
	sleep( 1 );
	$_SESSION['module_uitweak_fixformnav'] = $nav;
	$_SESSION['module_uitweak_fixformnav_ts'] = time();
	echo 'true';
	exit;
}

echo 'false';
