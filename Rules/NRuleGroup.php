<?php
/**
* Groups several rules into a logical set hierarchy
*
* @author Dale Horton
* @date 2011-09-24
**/
class NRuleGroup extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'ExecutionType' => "Enum('WhenAnyMatched,WhenFirstMatched','WhenAnyMatched')",
	);
	
	public static $has_one = array(
		'Parent' => 'NRuleGroup',
	);
	
	public static $has_many = array(
		'Rules' => 'NRule',
	);
	
	public static $defaults = array(
		'ExecutionType' => 'WhenAnyMatched',
	);
	
	public static $root_title = 'Rules';
	
	/**
	* Creates the root RuleGroup if it doesn't already exist
	**/
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DataObject::get_one('NRuleGroup', 'ParentID = 0')) {
			$group = new NRuleGroup();
			$group->Title = "Rules";
			$group->Write();
		}
	}
	
	public function getAddFields() {
		$fields = new FieldSet(new TabSet('Root', new Tab('Main')));
		$fields->addFieldToTab('Root.Main', 
			new WizardField(
				'RuleWizard',
				array(
					'NewNRule_SetName' => 'Create Rule',
					'NewNRule_AddConditions' => 'Add Conditions',
					'NewNRule_AddActions' => 'Add Actions',
					'NewNRule_Confirm' => 'Review Rule'
				)
			)
		);

		return $fields;
	}
	
	public function getAddActions() {
		return new FieldSet(new FormAction('save', 'Save'));
	}

}

class NRuleGroup_Admin_Controller extends NPanel_Controller {
	public function EditForm($request=null) {
		$page = $this->CurrentPage();
		
		if($page && $page->ID) {
			return parent::EditForm($request);
		} else {
			$sng = singleton('NRuleGroup');
			
			return new Form(
				$this,
				'EditForm',
				$sng->getAddFields(),
				$sng->getAddActions(),
				new RequiredFields()
			);
		}
	}
}