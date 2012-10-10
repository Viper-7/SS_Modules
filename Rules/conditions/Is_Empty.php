<?php
class NCondition_Is_Empty extends NCondition {
	public function compare($record) {
		return !!$record->{$this->FieldName};
	}
	
	public function getSQL() {
		return '(' . $this->getSQLField() . ' IS NULL OR ' . $this->getSQLField() . ' = \'\' OR ' . $this->getSQLField() . ' = \'0\')';
	}
}