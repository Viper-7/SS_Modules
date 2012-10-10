<?php
class UDS_UserDataForm extends UserDataStep {
	public static $has_one = array(
		'UserDataForm' => 'UserDataForm',
	);
	
	public static $outcomes = array(
		'complete' => 'Complete',
	);

	public static $default_label = 'Display form';
	public static $blocking = true;

	public function handleRequest($request) {
		$udf = $this->UserDataForm();
		$form = $udf->buildForm();

		if($request->postVar('action_submitForm')) {
			$form->loadDataFrom($request->postVars());
			$val = $form->validate();
			if($val) {
				$this->cart->setData($form->getData());
				return 'complete';
			}
		}

		$form->setFormAction($this->cart->WorkflowURL);
		return $form;
	}
}
