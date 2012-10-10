<?php
class UserDataStep extends DataObject {
	public static $db = array(
		'Label' => 'Varchar(255)',
	);
	
	public static $has_one = array(
		'Workflow' => 'UserDataWorkflow',
	);
	
	public static $has_many = array(
		'Outcomes' => 'UserDataStep_Outcome',
	);

	public static $outcomes = array(
	);
	
	public static $default_label = 'Workflow step';
	
	public static $blocking = false;
	public $cart;
	
	public function handleRequest($request) {
		return new SS_HTTPResponse('Empty workflow step class ' . $this->ClassName);
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->Label)
			$this->Label = $this->stat('default_label');
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		if($this->Outcomes()->Count() == 0) {
			foreach($this->stat('outcomes') as $outcome => $label) {
				$obj = new UserDataStep_Outcome();
				$obj->Outcome = $outcome;
				$obj->ParentID = $this->ID;
				$obj->write();
			}
		}
	}
}
class UserDataStep_Outcome extends DataObject {
	public static $db = array(
		'Outcome' => 'Varchar(255)',
		'NextStepID' => 'Int',
	);
	
	public static $has_one = array(
		'Parent' => 'UserDataStep',
	);
}