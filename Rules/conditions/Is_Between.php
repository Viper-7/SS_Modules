<?php
class NCondition_Is_Between extends NCondition {
	public static $db = array(
		'Inclusive' => 'Boolean',
		'Operand2' => 'Varchar(255)',
	);
	
	public static $defaults = array(
		'Inclusive' => true,
	);
	
	public function compare($record) {
		$a = array($this->Operand, $this->Operand2);
		$b = $record->{$this->FieldName};
		
		if($this->Inclusive) {
			return $b <= max($a) && $b >= min($a);
		} else {
			return $b < max($a) && $b > min($a);
		}
	}
	
	public function getSQL() {
		$a = min(array($this->Operand, $this->Operand2));
		$b = max(array($this->Operand, $this->Operand2));
		
		if(!$this->Inclusive) {
			$a++; $b--;
		}

		return $this->getSQLField() . ' BETWEEN \'' . Convert::raw2sql($a) . '\' AND \'' . Convert::raw2sql($b) . '\'';
	}
}