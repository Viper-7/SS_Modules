<?php
class UDS_CheckCondition extends UserDataStep {
	public static $db = array(
		'Expression' => 'Varchar(4000)',
	);
	public static $outcomes = array(
		'true' => 'True',
		'false' => 'False',
		'error' => 'Error',
	);
	public static $default_label = 'Check condition';

	public function handleRequest() {
		try {
			extract($this->cart->getData());
			if(eval($this->Expression)) {
				return 'true';
			} else {
				return 'false';
			}
		} catch (Exception $e) {
			return 'failure';
		}
	}
}
