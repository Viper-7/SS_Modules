<?php
/**
* One class to Rule them all (i know i know... i had to :P)
*
* Rules can be trigged on record insert (WhenItemCreated), record update (WhenItemSaved), 
* or as a temporal condition with an SQL query watching for the passage of time (WhenTimePassed).
*
* The TargetClass defines what object this rule will attach to. It should be restricted to objects
* that have been decorated with NRuleDecorator.
*
* TargetClass and AllowSelfTrigger have no specific bearing on WhenTimePassed rules, rather each Fact 
* will construct an SQL query based on any joins required for it's conditions. An attached ExecutionLimit
* of NRuleLimit_OncePerRuleGroup can be used to prevent a temporal rule from triggering any
* WhenItemCreated/WhenItemSaved rules.
* 
* Once a rule is triggered (based on it's TriggerType and TargetClass) it follows this process:
*   - Double-check if the Rule is Enabled
*   - Check if all associated Facts matched
*   - Check if AllowSelfTrigger is not set & the Rule has already been triggered for this request/record
*   - Check for an attached NRuleLimit and wether it will allow execution
*   - Progress the attached Workflow as far as possible without user intervention
*	- Check the dataobject for changes, if found, abort this write process and begin a new one
*
* @author Dale Horton
* @date 2011-09-24
**/
class NRule extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'TargetClass' => 'Varchar(255)',
		'TriggerType' => "Enum('WhenItemCreated,WhenItemSaved,WhenTimePassed','WhenItemSaved')",
		'AllowSelfTrigger' => 'Boolean',
		'Enabled' => 'Boolean',
	);
	
	public static $has_one = array(
		'RuleGroup' => 'NRuleGroup',
		'ExecutionLimit' => 'NRuleLimit',
		'Workflow' => 'UserDataWorkflow',
	);
	
	public static $has_many = array(
		'LogEntry' => 'NRule_LogEntry',
	);
	
	public static $many_many = array(
		'Facts' => 'NFact',
	);
	
	public static $defaults = array(
		'TriggerType' => 'WhenItemSaved',
		'AllowSelfTrigger' => false,
		'Enabled' => false,
	);

	public static $changed_on_component_add = true;
	public static $rule_classes = array();

	private $parentCalled = false;		// (bool) Internal flag to force parent::execute() to be called in subclasses
	public static $executed = array();		// (Array) RuleID => array( RecordID => (bool) Flag to prevent self triggering of rules )
	protected static $matched = array();		// (Array) RuleID => array( RecordID => (bool) Flag to show all facts have been satisfied )
	
	/**
	* Adds a class to the list of monitored classes for WhenItemCreated or WhenItemSaved rules
	*
	* @param string Name of the class to add
	* @param string Title of the class type to be displayed in the rules interface
	**/
	public static function add_rule_class($class, $title=null) {
		if(!$title)
			$title = singleton($class)->i18n_singular_name();

		self::$rule_classes[$title] = $class;
		Object::add_extension($class, 'NRuleDecorator');
	}
	
	/**
	* Helper method to add several classes to the rule monitor list at once
	* 
	* @param array Set of Title => ClassName of classes to monitor
	**/
	public static function add_rule_classes($classes) {
		foreach($classes as $title => $class) {
			self::add_rule_class($class, $title);
		}
	}
	
	public static function get_available_facts($triggerType) {
		if($triggerType == 'WhenTimePassed') {
			//temporal
			return DataObject::get('NFact')->toDropdownMap();
		} else {
			//change
			return DataObject::get('NFact', 'ClassName != \'NTemporalFact\'')->toDropdownMap();
		}
	}
	
	public static function get_available_triggers() {
		return DB::getConn()->enumValuesForField('NRule', 'TriggerType');
	}

	public static function get_available_groups() {
		$set = DataObject::get('NRuleGroup');
		if($set) {
			return $set->toDropdownMap();
		} else {
			return array();
		}
	}
	
	public static function get_available_limits() {
		$set = ClassInfo::subclassesFor('NRuleLimit');
		if($set) {
			foreach($set as $key => $value) {
				unset($set[$key]);
				$set[$value] = Object::combined_static($value, 'singular_name');
			}
			return $set;
		} else {
			return array();
		}
	}

	public static function get_available_workflows() {
		$set = DataObject::get('NWorkflow');
		if($set) {
			return $set->toDropdownMap();
		} else {
			return array();
		}
	}

	public static function get_available_targets() {
		return self::$rule_classes;
	}
	
	public function getCMSFields() {
		$fields = new FieldSet(new TabSet('Root', new Tab('Main')));
		
		$fields->addFieldsToTab('Root.Main', array(
			$select = new MultiSelectField(
				'Facts',
				'Facts',
				NRule::get_available_facts($this->TriggerType),
				$this->Facts()->toDropdownMap('ID', 'ID')
			),
		
			new DropdownField(
				'RuleGroup',
				'Rule Group',
				NRule::get_available_groups()
			),
		
			new DropdownField(
				'TriggerType',
				'TriggerType',
				NRule::get_available_triggers()
			),

			new DropdownField(
				'ExecutionLimit',
				'ExecutionLimit',
				NRule::get_available_limits()
			),
		
			new DropdownField(
				'Workflow',
				'Workflow',
				NRule::get_available_workflows()
			),

			new DropdownField(
				'TargetClass',
				'TargetClass',
				NRule::get_available_targets()
			),
			
			new CheckboxField(
				'Enabled',
				'Enabled',
				$this->Enabled
			),
		));
		
		$select->setFromTitle('Available Facts');
		$select->setToTitle('Selected Facts');
		
		return $fields;
	}
	
	/**
	* Runs the Temporal rule engine
	*
	* Gathers all Facts for all Temporal rules,
	* Executes a SQL query for each fact,
	* Finds matching rules, and executes them.
	**/
	public static function run_temporal_rules() {
		list($rules, $facts, $conditions) = self::getRuleManifest(null, 1);
		if(!$rules)
			return;
		
		Profiler::mark('NRule::run_rules::compare_conditions');
		foreach($facts as $rule_id => $rule_facts) {
			foreach($rule_facts as $id => $fact) {
				$fact->compareSQL($rules[$rule_id]);
			}
		}
		Profiler::unmark('NRule::run_rules::compare_conditions');
		
		return self::executeRules($rules);
	}
	
	public static function clear_flags() {
		self::$executed = array();
		self::$matched = array();
		NFact::clear_flags();
	}
	
	/**
	* Runs the change rule engine
	*
	* Captures the state of the object before starting rule processing
	* Gathers all Conditions for all Live rules
	* Evaluates all conditions against the submitted data
	* Collates all matching facts based on those conditions
	* Finds matching rules, and executes them.
	**/
	public static function run_change_rules($manipulation) {
		$results = array();
		$rules = array();
		$isInsert = false;
		
		foreach($manipulation as $class => $data) {
			if(isset($data['object'])) {
				$record = $manipulation[$class]['object'];
				$className = $class;
			}
		}
		
		Profiler::mark('NRule::run_rules::capture_state');
		if(isset($manipulation[$className]['command']) && $manipulation[$className]['command'] == 'insert') {
			$isInsert = true;
		}
		
		$result = self::getRuleManifest($record->ClassName, 0, $isInsert);
		
		list($rules, $facts, $conditions) = $result;
		
		if(!$rules)
			return $manipulation;
		Profiler::unmark('NRule::run_rules::capture_state');
		
		Profiler::mark('NRule::run_rules::compare_conditions');
		foreach($conditions as $id => $condition) {
			if(!$condition->compare($record)) {
				unset($conditions[$id]);
			}
		}
		Profiler::unmark('NRule::run_rules::compare_conditions');
		
		Profiler::mark('NRule::run_rules::apply_fact_matches');
		$processed = array();
		foreach($facts as $rule_id => $fact_set) {
			foreach($fact_set as $fact_id => $cond) {
				if(in_array($fact_id, $processed))
					continue;
				
				$fact = DataObject::get_by_id('NFact', $fact_id);

				if($fact->compare($conditions, $record)) {
					$fact->setMatched($record->ID);
				}
				
				$processed[] = $fact_id;
			}
		}
		Profiler::unmark('NRule::run_rules::apply_fact_matches');
		
		Profiler::mark('NRule::run_rules::execute_rules');
		$result = self::executeRules($rules, $manipulation);
		Profiler::unmark('NRule::run_rules::execute_rules');
		
		return $result;
	}
	
	/**
	* Fetches all applicable Rules, Facts & Conditions from the database
	*
	* @TODO Caching.....
	**/
	public static function getRuleManifest($className = null, $temporal = 0, $insert = 0) {
		$facts = array();
		$conditions = array();
		$rules = array();
		
		Profiler::mark('NRule::get_rule_manifest::fetch_rules');
		$classes = ClassInfo::ancestry($className);
		$classin = array();
		foreach($classes as $class) {
			$classin[] = "'" . Convert::raw2sql($class) . "'";
		}
		$classes = implode(',', $classin);
		
		if($temporal) {
			$rules_set = DataObject::get('NRule', 'TriggerType = \'WhenTimePassed\' AND Enabled = 1');
		} elseif($insert) {
			$rules_set = DataObject::get('NRule', 'TargetClass IN (' . $classes . ') AND TriggerType = \'WhenItemCreated\' AND Enabled = 1');
		} else {
			$rules_set = DataObject::get('NRule', 'TargetClass IN (' . $classes . ') AND TriggerType != \'WhenTimePassed\' AND Enabled = 1');
		}
		Profiler::unmark('NRule::get_rule_manifest::fetch_rules');
		
		if(!$rules_set)
			return array($rules, $facts, $conditions);
		
		// Gather Rules, Facts & Conditions
		Profiler::mark('NRule::get_rule_manifest::gather_components');
		foreach($rules_set as $rule) {
			$rules[$rule->ID] = $rule;
			foreach($rule->Facts() as $fact) {
				$facts[$rule->ID][$fact->ID] = $fact;
				
				if(!$fact->requireSQL && !$temporal) {
					foreach($fact->Conditions() as $condition) {
						if($condition->canLiveMatch()) {
							$conditions[$condition->ID] = $condition;
						} else {
							throw new Exception("Temporal Condition cannot be applied to a Live search on Fact {$fact->Title}");
						}
					}
				} else {
					$sqlfacts[$fact->ID] = $fact;
				}
			}
		}
		Profiler::unmark('NRule::get_rule_manifest::gather_components');
		
		return array($rules, $facts, $conditions);
	}
	
	public function setMatched($record_id) {
		self::$matched[$this->ID][$record_id] = true;
	}
	
	/**
	* Loop over all matched rules, validate they can / should be executed, and run them.
	* Capture any changes made to the object by those rules, and restart the write process if rqeuired
	**/
	public static function executeRules($rules, $manipulation = null) {
		foreach($manipulation as $class => $data) {
			if(isset($data['object'])) {
				$className = $class;
				$record = $manipulation[$class]['object'];
			}
		}
		$before = array();
		foreach($record->getChangedFields() as $fieldName => $changes) {
			$before[$fieldName] = $changes['after'];
		}
		
		foreach($rules as $rule) {
			Profiler::mark('NRule::run_rules::execute_rule');
			if($canexec[$rule->ID] = $rule->canExecute($record)) {
				$results[$rule->ID] = $rule->execute($record);
				if(!$rule->parentCalled) {
					throw new Exception('parent::execute() not called in NRule subclass "' . $rule->ClassName . '"');
				}
			}
			Profiler::unmark('NRule::run_rules::execute_rule');
		}
		
		$after = array();
		foreach($record->getChangedFields() as $fieldName => $changes) {
			$after[$fieldName] = $changes['after'];
		}

		// Detect if the rules made any changes to the current object,
		// since we're now too late in the write process to change data
		$newChanges = array();
		foreach($after as $fieldName => $changes) {
			if(!array_key_exists($fieldName, $before) || $before[$fieldName] != $after[$fieldName]) { 
				// Cancel the current write and re-execute rules
				$manipulation = false;
			}
		}
		
		return $manipulation;
	}
	
	/**
	* Filter to control wether a matched rule will be executed
	**/
	public function canExecute($record) {
		if(!$this->Enabled)
			return false;

		if(!isset(self::$matched[$this->ID][$record->ID]) || !self::$matched[$this->ID][$record->ID])
			return false;
		
		if(isset(self::$executed[$this->ID][$record->ID]) && !$this->AllowSelfTrigger)
			return false;
		
		if($this->ExecutionLimitID) {
			return $this->ExecutionLimit()->canExecute($this, $record);
		} else {
			return true;
		}
	}
	
	/**
	* Executes a rule, Logging it's execution if required
	**/
	public function execute($record) {
		self::$executed[$this->ID][$record->ID] = true;
		$record->Title = $record->Title . ' ruled';
		$this->parentCalled = true;
		
		Profiler::mark('NRule::run_rules::check_log');
		foreach($this->RuleGroup()->Rules() as $rule) {
			foreach($rule->ExecutionLimit() as $limit) {
				if($limit->stat('require_log')) {
					$limit->log($rule, $record);
					break 2;
				}
			}
		}
		Profiler::unmark('NRule::run_rules::check_log');
		
		if($this->WorkflowID)
			return $this->Workflow()->backgroundProgress();
	}
	
	/**
	* Helper to automatically convert between Live and Temporal rules as various Facts are added
	**/
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		switch($this->TriggerType) {
			case 'WhenItemSaved':
				foreach($this->Facts() as $fact) {
					if($fact->Temporal) {
						$this->TriggerType = 'WhenTimePassed';
						break;
					}
				}
				break;
			
			case 'WhenTimePassed':
				$temporal = false;
				foreach($this->Facts() as $fact) {
					if($fact->Temporal) {
						$temporal = true;
						break;
					}
				}
				
				if(!$temporal) {
					$this->TriggerType = 'WhenItemSaved';
				}
				break;
		}
	}
	
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DataObject::get_one('NRule')) {
			$rule = new NRule();
			$rule->Title = 'Test SiteTree Rule';
			$rule->TriggerType = 'WhenItemSaved';
			$rule->TargetClass = 'SiteTree';
			$rule->Enabled = true;
			$rule->RuleGroupID = DataObject::get_one('NRuleGroup')->ID;
			$rule->write();

			$fact = new NFact();
			$fact->Title = 'Page Title is Changed';
			$fact->write();
			$rule->Facts()->add($fact);

			$condition = new NCondition_Has_Changed();
			$condition->FieldName = 'Title';
			$condition->write();
			$fact->Conditions()->add($condition);
		}
	}
}