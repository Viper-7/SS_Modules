<?php
class NCondition_Is_Even extends NCondition {
	public function compare($record) {
		return $record->{$this->FieldName} % 2 == 0;
	}
}