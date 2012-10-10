<?php
class NAggregateFact extends NFact {
	public static $db = array(
		'Function' => 'Varchar(255)',
		'ContextTable' => 'Varchar(255)',
	);
	
	public $requireSQL = true;
	
	public function compare($conditions, $record = null) {
		
	}

	public function compareSQL($rule) {
		$className = $rule->TargetClass;
		
		$conditions = $fact->Conditions();
		
		if($conditions->Count()) {
			// Build Query
			$singleton = singleton($className);
			
			$query = $singleton->buildSQL();
			//$query->select
			$query->connective = $fact->Connective;
			$tables = array();
			
			// Join & Filter
			foreach($conditions as $condition) {
				$condition->augmentSQL($query);
			}
			
			// Execute
			$result = $query->execute();

			if($result->numRecords() != 0) {
				$fact->setMatched($result->column());
			}
		}
		
	$query = new SQLQuery();
		foreach($this->Conditions as $cond) {
			$query->where($cond->getSQL());
		}
	}
}