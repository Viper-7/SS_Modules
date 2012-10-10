<?php
class NCondition_Matches_Expression extends NCondition {
	public function compare($record) {
		return eval($this->Operand) == $record->{$this->FieldName};
	}
	
	public function getSQL() {
		return $this->getSQLField() . ' = \'' . Convert::raw2sql(eval($this->Operand)) . '\'';
	}
}