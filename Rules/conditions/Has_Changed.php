<?php
class NCondition_Has_Changed extends NCondition {
	public function compare($record) {
		$changed = $record->getChangedFields();

		if(isset($changed[$this->FieldName])) {
			return $changed[$this->FieldName]['before'] != $changed[$this->FieldName]['after'];
		}
	}
}