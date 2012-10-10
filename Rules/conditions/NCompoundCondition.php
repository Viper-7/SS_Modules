<?php
class NCompoundCondition extends NCondition {
	public static $db = array(
		'Connective' => "Enum('AND,OR','AND')",
	);
	
	public static $defaults = array(
		'Connective' => 'AND',
	);
	
	public static $many_many = array(
		'Conditions' => 'NCondition'
	);
	
	public function compare($record) {
		foreach($this->Conditions() as $cond) {
			if(!$cond->compare($record)) {
				return false;
			}
		}
		
		return true;
	}
	
	public function getSQL() {
		foreach($this->Conditions() as $cond) {
			$sql[] = $cond->getSQL();
		}
		
		return implode(" {$this->Connective} ", $sql);
	}
}