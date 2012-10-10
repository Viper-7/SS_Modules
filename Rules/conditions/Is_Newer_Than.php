<?php
class NCondition_Is_Newer_Than extends NTemporalCondition {
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->Operand || !strtotime("+{$this->Operand}", 0)) {
			return $this->Operand = '0 Days';
		}
	}
	
	public function compare($record) {
		return $this->Operand < $record->{$this->FieldName};
	}

	public function getSQL() {
		return '"' . Convert::raw2sql($this->Table) . '"."Created" > DATE_SUB(NOW(), INTERVAL ' . strtotime("+{$this->Operand}", 0) . ' SECOND)';
	}
}