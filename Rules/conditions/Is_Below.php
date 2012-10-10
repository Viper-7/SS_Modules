<?php
class NCondition_Is_Below extends NCondition {
	public function compare($record) {
		return $this->Operand > $record->{$this->FieldName};
	}
	
	public function getSQL() {
		return $this->getSQLField() . ' < \'' . Convert::raw2sql($this->Operand) . '\'';
	}
}