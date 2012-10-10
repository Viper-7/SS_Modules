<?php
class NTemporalCondition extends NCondition {
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->Fact() instanceof NTemporalFact) {
			throw new Exception('Cannot add a temporal condition to a non temporal fact');
		}
	}
}