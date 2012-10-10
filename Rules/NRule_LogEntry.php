<?php
class NRule_LogEntry extends DataObject {
	public static $db = array(
		'RecordID' => 'Int',
		'ExecutionCount' => 'Int',
		'RelationValue' => 'Varchar(255)',
	);
	
	public static $has_one = array(
		'Rule' => 'NRule',
	);
	
	public static $defaults = array(
		'ExecutionCount' => '0',
	);
	
	public static function log($rule, $record, $extraData) {
		$log = new self();
		$log->RecordID = $record->ID;
		$log->ExecutionCount += 1;
		
		if($extraData) {
			foreach($extraData as $key => $value) {
				$log->$key = $value;
			}
		}
		
		$log->write();
	}
}
