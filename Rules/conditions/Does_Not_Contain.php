<?php
class NCondition_Does_Not_Contain extends NCondition {
	public function compare($record) {
		return strpos($record->{$this->FieldName}, $this->Operand) === FALSE;
	}
	
	public function getSQL() {
		return $this->getSQLField() . ' NOT LIKE \'%' . Convert::raw2sql($this->Operand) . '%\'';
	}
}
