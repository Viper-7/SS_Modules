<?php
class UDS_DisplayContent extends UserDataStep {
	public static $db = array(
		'Content' => 'HTMLText',
	);
	public static $outcomes = array(
		'complete' => 'Complete',
	);
	public static $default_label = 'Display content';

	public function handleRequest($request) {
		return $this->Content;
	}
}
