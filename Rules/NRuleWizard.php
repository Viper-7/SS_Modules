<?php
class NewNRule_SetName extends WizardStep {
	public function getWizardFields() {
		$fieldset = parent::getWizardFields();
		
		$fieldset->push(new TextField('Title', 'Title','',50));
		
		return $fieldset;
	}
	
	public function processPost($data) {
		$data = $this->getData() + $data;
		
		$workflow = new NWorkflow();
		$workflow->Title = $data['Title'];
		$workflow->URLSegment = $data['URLSegment'] ?: preg_replace('/\W+/', '_', $data['Title']);
		$workflow->write();
		
		$data['WorkflowID'] = $workflow->ID;
		
		return parent::processPost($data);
	}
}

class NewNRule_AddConditions extends WizardStep {
	public function getWizardFields() {
		$data = $this->getData();
		
		$fieldset = parent::getWizardFields();
		
		
		return $fieldset;
	}
}

class NewNRule_AddActions extends WizardStep {
	public function getWizardFields() {
		$fieldset = parent::getWizardFields();
		
		
		return $fieldset;
	}
}

class NewNRule_Confirm extends WizardStep {
	public function getWizardFields() {
		$fieldset = parent::getWizardFields();
		
		$fieldset->push(new SelectionGroup(
			'Active',
			array(
				'0//Do not activate this rule now, I\'ll review and publish it later.' => new LiteralField('dummy1', ''),
				'1//Activate this rule now, I want to start using it right away.' => new LiteralField('dummy2', '')
			)
		));
		
		return $fieldset;
	}
}
