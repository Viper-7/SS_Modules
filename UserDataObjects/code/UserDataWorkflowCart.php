<?php
class UserDataWorkflowCart extends DataObject {
	public static $db = array(
		'SessionID' => 'Varchar(255)',
		'RemoteIP' => 'Varchar(255)',
		'Token' => 'Varchar(255)',
		'CartData' => 'Text',
	);
	
	public static $has_one = array(
		'Member' => 'Member',
		'Workflow' => 'UserDataWorkflow',
	);
	
	public static $has_many = array(
		'Steps' => 'UserDataWorkflowCart_Step',
	);
	
	protected $cartData;
	protected static $carts;
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->Token) {
			$this->Token = hash('sha1', uniqid('', true));
		}
	}
	
	public function requireUserIntervention() {
	
	}
	
	public function getWorkflowURL() {
		return '/Workflow/' . $this->Token;
	}

	public function currentStep() {
		if(!$this->WorkflowID)
			throw new Exception('Cannot load step for a cart without a workflow');
		
		$step = DataObject::get_one('UserDataWorkflowCart_Step', '"CartID" = \'' . Convert::raw2sql($this->ID) . '\'', false, '"UserDataWorkflowCart_Step"."ID" DESC');
		
		if($step)
			return $step;
		
		if($this->Steps()->Count() > 0)
			throw new Exception('Failed to load step for cart ' . $this->ID);
		
		$uds = $this->Workflow()->getFirstStep();
		$step = new UserDataWorkflowCart_Step();
		$step->CartID = $this->ID;
		$step->StepID = $uds->ID;
		$step->write();
		
		return $step;
	}
	
	public function progress($outcome) {
		$step = $this->currentStep();
		$step->Outcome = $outcome;
		$step->write();
		
		$udwo = DataObject::get_one('UserDataStep_Outcome', '"ParentID" = \'' . Convert::raw2sql($step->StepID) . '\' AND "Outcome" = \'' . Convert::raw2sql($outcome) . '\'');
		if(!$udwo)
			throw new Exception('Failed to find next step for workflow');
		
		if($udwo->NextStepID) {
			$step = new UserDataWorkflowCart_Step();
			$step->CartID = $this->ID;
			$step->StepID = $udwo->NextStepID;
			$step->write();
			
			return true;
		} else {
			return false;
		}
	}
	
	public static function getCart($workflow, $force_flush = false) {
		$id = session_id();
		
		if(!$force_flush) {
			if(isset(self::$carts[$workflow]))
				return self::$carts[$workflow];
			
			$request = Controller::curr()->getRequest();	// GET or POST "UDWFToken" variable
			if($token = $request->requestVar('UDWFToken')) {
				self::$carts[$workflow] = DataObject::get_one('UserDataWorkflowCart', '"Token" = \'' . Convert::raw2sql($token) . '\' AND "WorkflowID" = \'' . Convert::raw2sql($workflow) . '\'');
				if(self::$carts[$workflow])
					return self::$carts[$workflow];
			}
			
			$request = Controller::curr()->getRequest();	// GET URL Segment /Workflow/Name/<Token>
			if($token = $request->latestParam('ID')) {
				self::$carts[$workflow] = DataObject::get_one('UserDataWorkflowCart', '"Token" = \'' . Convert::raw2sql($token) . '\' AND "WorkflowID" = \'' . Convert::raw2sql($workflow) . '\'');
				if(self::$carts[$workflow])
					return self::$carts[$workflow];
			}	
			
			if($id) {						// Search by PHP Session ID
				self::$carts[$workflow] = DataObject::get_one('UserDataWorkflowCart', '"SessionID" = \'' . Convert::raw2sql($id) . '\' AND "WorkflowID" = \'' . Convert::raw2sql($workflow) . '\'');
				if(self::$carts[$workflow])
					return self::$carts[$workflow];
			}
		}
	
		// If those checks failed, create a new cart
		$cart = new UserDataWorkflowCart();
		$cart->SessionID = $id;
		$cart->RemoteIP = $_SERVER['REMOTE_ADDR'];
		$cart->CartData = serialize(array());
		$cart->MemberID = Member::currentUserId();
		$cart->WorkflowID = $workflow;
		$cart->write();
		self::$carts[$workflow] = $cart;
		
		return $cart;
	}
	
	public function getData($field = null) {
		if(!$this->cartData)
			$this->cartData = unserialize($this->CartData);
			
		if(!$field)
			return $this->cartData;
		
		if(isset($this->cartData[$field]))
			return $this->cartData[$field];
		
		return FALSE;
	}
	
	public function setData($field, $value = null) {
		if(!$this->cartData)
			$this->cartData = unserialize($this->CartData);
		
		if(func_num_args() == 1)
			$this->cartData = $field + $this->cartData;
		else
			$this->cartData[$field] = $value;
		
		$this->CartData = serialize($this->cartData);
		$this->write();
	}
}

class UserDataWorkflowCart_Step extends DataObject {
	public static $db = array(
		'Outcome' => 'Varchar(255)',
	);
	
	public static $has_one = array(
		'Cart' => 'UserDataWorkflowCart',
		'Step' => 'UserDataStep',
	);
}