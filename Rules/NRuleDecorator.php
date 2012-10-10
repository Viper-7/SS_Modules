<?php
/**
* Decorator to add rule triggering capabilities to a class
*
* @author Dale Horton
* @date 2011-09-24
**/
class NRuleDecorator extends DataObjectDecorator {
	/**
	* Attaches the rule engine to the DataObject::executeRules extension method
	* Will either return the $manipulation array unaltered, or will empty it to reset rule execution for another pass
	**/
	public function executeRules(&$manipulation) {
		$manipulation[key($manipulation)]['object'] = $this->owner;
		$manipulation = NRule::run_change_rules($manipulation);
	}
	
	public function prepareRules() {
		NRule::clear_flags();
	}
}