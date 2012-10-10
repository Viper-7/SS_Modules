<?php
class NRuleLimit extends DataObject {
	public static $singular_name = 'No Limit';
	
	public static $require_log = false;

	public function canExecute($rule, $record) {
		return true;
	}
	
	public function log($rule, $record) {
		NRule_LogEntry::log($rule, $record);
	}
}

class NRuleLimit_OncePerRecord extends NRuleLimit {
	public static $singular_name = 'Once per Record';

	public static $require_log = true;
	
	public function canExecute($rule, $record) {
		$log = DataObject::get_one('NRule_LogEntry', 'RuleID = ' . intval($this->ID) . ' AND RecordID = ' . intval($record->ID));
		
		return !$log;
	}
}

class NRuleLimit_OncePerDay extends NRuleLimit {
	public static $singular_name = 'Once per Day';
	
	public static $require_log = true;
	
	public function canExecute($rule, $record) {
		$log = DataObject::get_one('NRule_LogEntry', 'RuleID = ' . intval($this->ID) . ' AND Created > DATE_SUB(NOW(), INTERVAL 1 DAY)');
		
		return !$log;
	}
}

class NRuleLimit_OncePerRuleGroup extends NRuleLimit {
	public static $singular_name = 'Once per Rule Group';

	public function canExecute($context_rule, $record) {
		$match = true;
		
		foreach($rule->RuleGroup()->Rules() as $rule) {
			if($rule->matched[$record->ID] && $rule->ID != $context_rule->ID) {
				$match = false;
				break;
			}
		}
		
		return $match;
	}
}

class NRuleLimit_OncePerSite extends NRuleLimit {
	public static $singular_name = 'Once for the entire Site';
	
	public static $require_log = true;

	public function canExecute($rule, $record) {
		$log = DataObject::get_one('NRule_LogEntry', 'RuleID = ' . intval($this->ID));
		
		return !$log;
	}
}

class NRuleLimit_OncePerUser extends NRuleLimit {
	public static $singular_name = 'Once per User';
	
	public static $require_log = true;

	public function canExecute($rule, $record) {
		$member_id = Member::currentUserID();
		
		if(!$member_id)
			return false;
		
		$log = DataObject::get_one('NRule_LogEntry', 'RuleID = ' . intval($this->ID) . ' AND RelationType = \'Member\' AND RelationValue = ' . intval($member_id));
		
		return !$log;
	}
	
	public function log($rule, $record) {
		NRule_LogEntry::log($rule, $record, array('RelationType' => 'Member', 'RelationValue' => Member::currentUserID()));
	}
}

class NRuleLimit_OncePerVisit extends NRuleLimit {
	public static $singular_name = 'Once per User Visit';
	
	public static $require_log = true;

	public function canExecute($rule, $record) {
		$session_id = session_id();
		
		if(!$session_id)
			return false;
		
		$log = DataObject::get_one('NRule_LogEntry', 'RuleID = ' . intval($this->ID) . ' AND RelationType = \'Session\' AND RelationValue = \'' . Convert::raw2sql($session_id) . '\'');
		
		return !$log;
	}
	
	public function log($rule, $record) {
		NRule_LogEntry::log($rule, $record, array('RelationType' => 'Member', 'Session' => session_id()));
	}
}