<?php
class UDS_SaveUserDataObject extends UserDataStep {
	public static $db = array(
		'UserDataObjectClass' => "Varchar(400)",
	);

	public static $outcomes = array(
		'complete' => 'Complete',
		'failure' => 'Save Failed',
	);

	public static $default_label = 'Save form data';

	public function handleRequest($request) {
		try {
			$class = $this->UserDataObjectClass;
			$obj = new $class();
			foreach($this->cart->getData() as $key => $value) {
				if($obj->hasField($key))
					$obj->$key = $value;
			}
			$obj->write();
			
			return 'complete';
		} catch (Exception $e) {
			return 'failure';
		}
	}
}
