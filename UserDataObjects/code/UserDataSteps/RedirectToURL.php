<?php
class UDS_RedirectToURL extends UserDataStep {
	public static $db = array(
		'URL' => 'Varchar(4000)',
	);
	public static $outcomes = array(
		'complete' => 'Complete',
	);
	public static $default_label = 'Redirect To URL';

	public function handleRequest($request) {
		$response = new SS_HTTPResponse();
		$response->redirect($this->URL);
		
		return $response;
	}
}