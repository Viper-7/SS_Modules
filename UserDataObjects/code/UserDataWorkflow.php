<?php
class UserDataWorkflow extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'URLSegment' => 'Varchar(255)',
		'Active' => 'Boolean',
		'Persistant' => 'Boolean',
	);

	public static $has_many = array(
		'Steps' => 'UserDataStep',
	);

	public static $defaults = array(
		'Active' => '1',
		'Persistant' => '1',
	);
	
	public function backgroundProgress() {
		$cart = UserDataWorkflowCart::getCart($this);
		$request = new SS_HTTPRequest('GET', $cart->getWorkflowURL(), array('background_task' => '1'));
		
		while($cartstep = $cart->currentStep()) {
			if(!$cartstep) return true;	// If we've run out of steps, our job is done
			
			$step = $cartstep->Step();
			$step->cart = $cart;
			$result = $step->handleRequest($request);

			if(is_string($result) && array_key_exists($result, $step->stat('outcomes'))) {
				$cart->progress($result);
			} else {
				$cart->requireUserIntervention();
				return false;
			}
		}
		
		return true;
	}

	public function getFirstStep() {
		$step = DataObject::get_one('UserDataStep', '"WorkflowID" = \'' . Convert::raw2sql($this->ID) . '\' AND "UserDataStep"."ID" NOT IN (SELECT NextStepID FROM UserDataStep_Outcome)');
		return $step;
	}
	
	public function cleanup() {
		foreach($this->Steps() as $step) {
			if($step instanceof UDS_UserDataForm) {
				$form = $step->UserDataForm();
				foreach($form->UDFFields() as $field) {
					if($field->ID) $field->Delete();
				}
				if($form->ID) $form->Delete();
			}

			foreach($step->Outcomes() as $outcome) {
				if($outcome->ID) $outcome->Delete();
			}
			
			if($step->ID) $step->Delete();
		}
	
	}

	public static function create_from_udo($udo) {
		$workflow = new UserDataWorkflow();
		$workflow->Title = $udo->Title;
		$workflow->URLSegment = $udo->TableName;
		$workflow->write();

		$form = UserDataForm::create_from_udo($udo);

		$form_step = new UDS_UserDataForm();
		$form_step->WorkflowID = $workflow->ID;
		$form_step->UserDataFormID = $form->ID;
		$form_step->write();
		
		$form->StepID = $form_step;

		$save_step = new UDS_SaveUserDataObject();
		$save_step->WorkflowID = $workflow->ID;
		$save_step->UserDataObjectClass = $udo->DOClassName;
		$save_step->write();

		$display_step = new UDS_DisplayContent();
		$display_step->WorkflowID = $workflow->ID;
		$display_step->Content = "Thankyou for your submission.";
		$display_step->write();

		$outcome = $form_step->Outcomes('"Outcome" = \'complete\'')->First();
		$outcome->NextStepID = $save_step->ID;
		$outcome->write();

		$outcome = $save_step->Outcomes('"Outcome" = \'complete\'')->First();
		$outcome->NextStepID = $display_step->ID;
		$outcome->write();

		$outcome = $save_step->Outcomes('"Outcome" = \'failure\'')->First();
		$outcome->NextStepID = $save_step->ID;
		$outcome->write();

		return $workflow;
	}

	public function renderIFrame() {
		return $this->renderWith('Includes/UserDataWorkflow', array('IFrameURL'=>'Workflow/'.$this->URLSegment));
	}
}