<?php

// AJAX endpoint to handle deleting Automated Survey Invitations.

namespace Nottingham\REDCapUITweaker;

if ( ! $module->getSystemSetting( 'asi-delete' ) || $module->getUser()->getRights()['design'] != 1 )
{
	exit;
}

$module->query( 'DELETE FROM redcap_surveys_scheduler WHERE survey_id = ? AND event_id = ? LIMIT 1',
                [ $_POST['survey_id'], $_POST['event_id'] ] );
