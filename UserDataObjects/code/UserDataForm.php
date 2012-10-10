<?php
class UserDataForm extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'ExtensionClasses' => "MultiEnum(ClassInfo::subclassesFor('UserDataDecorator', true))",
		'SubmitCaption' => 'Varchar(255)',
	);
	
	public static $has_one = array(
		'Step' => 'UserDataWorkflow_Step',
	);
	public static $has_many = array(
		'UDFFields' => 'UserDataForm_Field',
	);
	public static $defaults = array(
		'SubmitCaption' => 'Submit',
	);
	
	public static function create_from_udo($udo) {
		$udf = new UserDataForm();
		$udf->Title = $udo->Title;
		$udf->write();
		
		foreach($udo->UDOFields() as $field) {
			$formfield = new UserDataForm_Field();
			$formfield->Name = $field->FieldName;
			$formfield->Label = $field->Label;
			$formfield->FieldType = Object::combined_static($field->FieldType, 'default_udff');
			$formfield->UserDataFormID = $udf->ID;
			$formfield->write();
		}
		
		return $udf;
	}

	public function getRequiredFields() {
		$fields = $this->UDFFields('"Required" = 1');
		return $fields;
	}

	public function getFormFields() {
		$fields = new FieldSet();
		foreach($this->UDFFields() as $field) {
			if($obj = $field->buildFormField()) {
				$fields->push($obj);
			}
		}
		return $fields;
	}

	public function buildForm() {
		$fields = $this->getFormFields();
		$actions = new FieldSet(new FormAction('submitForm', $this->SubmitCaption));
		$validator = new RequiredFields($this->getRequiredFields()->toDropDownMap('ID', 'Name'));
		$form = new Form(
			$this,
			'UserDataForm',
			$fields,
			$actions,
			$validator
		);
		return $form;
	}
}

class UserDataForm_Field extends DataObject {
	public static $db = array(
		'Name' => 'Varchar(255)',
		'Label' => 'Varchar(4000)',
		'FieldType' => "Enum(ClassInfo::subclassesFor('UserDataField', true))",
		'Required' => 'Boolean',
	);

	public static $has_one = array(
		'UserDataForm' => 'UserDataForm',
	);

	public function buildFormField() {
		$class = $this->FieldType;
		return singleton($class)->buildFormField($this);
	}
}