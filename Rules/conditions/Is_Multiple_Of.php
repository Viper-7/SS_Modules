<?php
class NCondition_Is_Multiple_Of extends NCondition {
	public function compare($record) {
		return $record->{$this->FieldName} % $this->Operand == 0;
	}
}
