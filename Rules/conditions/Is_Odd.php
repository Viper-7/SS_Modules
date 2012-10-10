<?php
class NCondition_Is_Odd extends NCondition {
	public function compare($record) {
		return $record->{$this->FieldName} % 2 == 1;
	}
}
