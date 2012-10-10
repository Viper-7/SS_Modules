<?php
class NCondition_Is_After extends NCondition {
	public function compare($record) {
		return $this->Operand > $record->{$this->FieldName};
	}

	public function getSQL() {
		return $this->getSQLField() . ' > FROM_UNIXTIME(' . strtotime($this->Operand) . ')';
	}
}