<?php

// Handle alert uploads, to allow any custom alert senders which conform to the regex
// defined in the module settings.

if ( !isset( $_GET['mode'] ) || !isset( $_GET['pid'] ) ||
     !$module->getSystemSetting( 'custom-alert-sender' ) )
{
	exit;
}

if ( $_GET['mode'] == 'upload' )
{
	if ( isset( $_POST['csv_content'] ) && $_POST['csv_content'] != '' )
	{
		$post_csv_content = $_POST['csv_content'];
	}
	$objAlerts =
		new class() extends Alerts
		{
			private $listExtraEmails;
			public function saveAlert()
			{
				$GLOBALS['user_email'] = $_POST['email-from'];
				return parent::saveAlert();
			}
			public function getFromEmails()
			{
				$fromEmails = parent::getFromEmails();
				return array_merge( $fromEmails, $this->getExtraFromEmails() );
			}
			private function getExtraFromEmails()
			{
				global $module, $post_csv_content;
				if ( is_array( $this->listExtraEmails ) )
				{
					return $this->listExtraEmails;
				}
				$uploadData = '';
				if ( isset( $_FILES['file'] ) && isset( $_FILES['file']['tmp_name'] ) )
				{
					$uploadData = file_get_contents( $_FILES['file']['tmp_name'] );
				}
				elseif ( isset( $post_csv_content ) && $post_csv_content != '' )
				{
					$uploadData = $post_csv_content;
				}
				$this->listExtraEmails = [];
				if ( $uploadData == '' )
				{
					return $this->listExtraEmails;
				}
				preg_match_all( '/[^,;]+@[^,;]+/', $uploadData, $uploadEmails, PREG_PATTERN_ORDER );
				foreach ( $uploadEmails[0] as $uploadEmail )
				{
					if ( preg_match( '/' .
					                 $module->getSystemSetting( 'custom-alert-sender-regex' ) . '/',
					                 $_POST['email-from'] ) )
					{
						$this->listExtraEmails[] = $uploadEmail;
					}
				}
				return $this->listExtraEmails;
			}
		};
	$objAlerts->uploadAlerts();
}