<?php
/**
* A Fact is a certain truth in the system, it can contain several conditions before being 
* considered to be true, and can be subclassed to provide aggregation or other features.
* 
* A Fact with no conditions is always considered true, but will not match any records for
* a temporal rule, and so will effectively disable the rule.
*
* @author Dale Horton
* @date 2011-09-24
**/
class NFact extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'Connective' => "Enum('AND,OR','AND')",
	);
	
	public static $has_many = array(
		'Conditions' => 'NCondition',
	);
	
	public static $belongs_many_many = array(
		'Rules' => 'NRule',
	);
	
	protected static $matched = array();// (Array) FactID => array( RecordID => (bool) Flag to show all conditions have been satisfied )
	protected $matches = array();		// (Array) ID of all records that matched this fact
	public $requireSQL = false;			// (bool) Flag to require SQL comparisons (for operations requiring joins/aggregation)
	
	public static function clear_flags() {
		self::$matched = array();
	}
	
	public function setMatched($matches = array()) {
		$rules = $this->Rules();
		
		if(is_array($matches)) {
			$this->matches = $matches;
		} else {
			$this->matches[] = $matches;
			$matches = array($matches);
		}
		
		foreach($matches as $record_id) {
			self::$matched[$this->ID][$record_id] = true;

			foreach($rules as $rule) {
				$matched = true;
				foreach($rule->Facts() as $subfact) {
					if(empty(self::$matched[$subfact->ID][$record_id])) {
						$matched = false;
						break;
					}
				}
				
				
				if($matched)
					$rule->setMatched($record_id);
			}
		}
	}
	
	public function compare($conditions, $record = null) {
		$condition_set = $this->Conditions();
		
		if($condition_set->Count()) {
			switch($this->Connective) {
				case 'AND':
					$matched = true;
					foreach($condition_set as $condition) {
						if(!isset($conditions[$condition->ID])) {
							$matched = false;
						}
					}
					return $matched;
				case 'OR':
					foreach($condition_set as $condition) {
						if(isset($conditions[$condition->ID])) {
							return true;
						}
					}
					return false;
			}
		} else {
			return true;
		}
	}
	
	public function compareSQL($rule) {
		$className = $rule->TargetClass;
		
		$conditions = $fact->Conditions();
		
		if($conditions->Count()) {
			// Build Query
			$singleton = singleton($className);
			
			$query = $singleton->buildSQL();
			$query->connective = $fact->Connective;
			$tables = array();
			
			// Join & Filter
			foreach($conditions as $condition) {
				if($condition->canTemporalMatch()) {
					$condition->augmentSQL($query);
				} else {
					throw new Exception("Live condition cannot be applied to a Temporal search on Fact {$this->Title}");
				}
			}
			
			// Execute
			$result = $query->execute();

			if($result->numRecords() != 0) {
				$fact->setMatched($result->column());
			}
		}
	}
}