<?php
class UDS_WaitForUser extends UserDataStep {
	public static $db = array(
		'Caption' => 'Varchar(255)',
	);
	public static $outcomes = array(
		'complete' => 'Complete',
	);
	public static $blocking = true;
	public static $default_label = 'Wait for user click';

	public function handleRequest($request) {
		if($request->postVar('Continue'))
			return 'complete';
		
		return '<form method="post" action=""><input type="submit" name="Continue" value="Continue"/></form>';
	}
}
