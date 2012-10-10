<?php
class NCondition extends DataObject {
	public static $db = array(
		'Table' => 'Varchar(255)',
		'FieldName' => 'Varchar(255)',
		'Operand' => 'Varchar(255)',
	);
	
	public static $has_one = array(
		'Fact' => 'NFact',
	);
	
	public function getName() {
		return $this->i18n_singular_name();
	}
	
	public function getTable() {
		if(!empty($this->Table))
			return $this->Table;
			
	}
	
	public function augmentSQL($query) {
		$tables = array_keys($query->from);
		
		if(!in_array($condition->Table, $tables))
			$this->addJoins($query);
		
		$query->where[] = $this->getSQL();
	}
	
	public function addJoins($query) {
		
	}
	
	public function canTemporalMatch() {
		return method_exists($this, 'getSQL');
	}
	
	public function canLiveMatch() {
		return method_exists($this, 'compare');
	}
	
	public function getSQLField() {
		if($this->Table) {
			return '"' . Convert::raw2sql($this->Table) . '"."' . Convert::raw2sql($this->FieldName) . '"';
		} else {
			return '"' . Convert::raw2sql($this->FieldName) . '"';
		}
	}
}