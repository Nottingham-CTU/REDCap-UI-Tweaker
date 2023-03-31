<?php

// This page receives AJAX requests to set some of the module settings when the module is first
// installed.

namespace Nottingham\REDCapUITweaker;


header( 'Content-Type: application/json' );

if ( !isset( $_SESSION['module_uitweak_system_enable'] ) )
{
	echo 'false';
	exit;
}

unset( $_SESSION['module_uitweak_system_enable'] );

if ( $module->getSystemSetting( 'field-default-required' ) != '' ||
     $module->getSystemSetting( 'submit-option-tweak' ) != '' )
{
	echo 'false';
	exit;
}

if ( isset( $_POST['fieldtypes'] ) )
{
	// Set field types order to text, notes, yes/no, radio, checkbox, slider, calculated |
	// dropdown, true/false, upload, signature.
	$module->setSystemSetting( 'field-types-order', '1,2,7,5,6,11,3|4,8,10,9' );
}

if ( isset( $_POST['requiredfields'] ) )
{
	// Set new fields to default to required.
	$module->setSystemSetting( 'field-default-required', '1' );
}
else
{
	// Don't set new fields to default to required.
	$module->setSystemSetting( 'field-default-required', '0' );
}

if ( isset( $_POST['fieldannotations'] ) )
{
	// Set predefined field annotations to the DataCat project categories.
	$module->setSystemSetting( 'predefined-annotations',
	                          "Primary Outcome\nPrimary Outcome Not Primary Analysis\n" .
	                          "Secondary Outcome\nOther Outcome\nCore Outcome\nHealth Economics\n" .
	                          "Participant Identifier\nRandomisation\nEligibility\nDemographics\n" .
	                          "Medical History\nData Management\nSafety Data\nRegulatory Data\n" .
	                          "Compliance Data\nProcess Outcome\nUnblinding\nMiscellaneous" );
}

if ( isset( $_POST['dqrealtime'] ) )
{
	// Default data quality rules to execute in real time.
	$module->setSystemSetting( 'dq-real-time', true );
}

if ( isset( $_POST['statusicons'] ) )
{
	// Alternate status icons.
	$module->setSystemSetting( 'alternate-status-icons', true );
}

if ( isset( $_POST['nonextrecord'] ) )
{
	// Remove 'Save and Go To Next Record'.
	$module->setSystemSetting( 'submit-option-tweak', '1' );
}
else
{
	// REDCap default submit options.
	$module->setSystemSetting( 'submit-option-tweak', '0' );
}

echo 'true';
