<?php
class UserDataField extends FormField {
	public function buildFormField($field) {
		return new TextField($field->Name, $field->Label);
	}
}
