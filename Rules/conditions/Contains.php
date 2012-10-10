<?php
class NCondition_Contains extends NCondition {
	public function compare($record) {
		return strpos($record->{$this->FieldName}, $this->Operand) !== FALSE;
	}
	
	public function getSQL() {
		return $this->getSQLField() . ' LIKE \'%' . Convert::raw2sql($this->Operand) . '%\'';
	}
}
