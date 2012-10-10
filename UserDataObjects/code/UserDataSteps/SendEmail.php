<?php
class UDS_SendEmail extends UserDataStep {
	public static $db = array(
		'EmailAddress' => 'Varchar(255)',
		'Subject' => 'Varchar(255)',
		'IncludeData' => 'Boolean',
	);
	public static $outcomes = array(
		'complete' => 'Complete',
	);
	public static $default_label = 'Send Email';

	public function handleRequest($request) {
		// mail()
		
		return 'complete';
	}
}
