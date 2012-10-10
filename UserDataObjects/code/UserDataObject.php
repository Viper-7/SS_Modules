<?php
class UserDataObject_Column extends DataObject {
	public static $singular_name = "Question";
	
	public static $db = array(
		'Label' => 'Varchar(255)',
		'FieldName' => 'Varchar(255)',
		'FieldType' => "Enum(ClassInfo::subclassesFor('UserDataColumn', true))",
		'ShowInSummary' => 'Boolean',
		'Searchable' => 'Boolean',
		'Encrypted' => 'Boolean',
		'DefaultValue' => 'Text',
		'MetaData' => 'Varchar(4000)',
	);

	public static $has_one = array(
		'UserDataObject' => 'UserDataObject',
		'PageElement' => 'UserDefinedElement',
		'Page' => 'Page'
	);
}

class UserDataObject extends DataObject {
	public static $db = array(
		'Title' => 'Varchar(255)',
		'TableName' => 'Varchar(255)',
		'ExtensionClasses' => "MultiEnum(ClassInfo::subclassesFor('UserDataDecorator', true))",
		'DOClassName' => 'Varchar(400)',
		'DOClassFile' => 'Varchar(400)',
	);

	public static $has_many = array(
		'UDOFields' => 'UserDataObject_Column',
	);

	public static $generated_class_path = '/data/nimbler_cms/generated';

	public function generateClass() {
		if(!$this->DOClassName) {
			if(!$this->TableName) {
				throw new Exception('Cannot build a class file for a UDO that has not been saved.');
			}
			$this->DOClassName = $this->TableName;
		}

		$site = NSite::current_site();
		
		if(!file_exists($fd = $this->stat('generated_class_path') . '/' . $site->PathName . '/')) {
			mkdir($fd);
		}
		if(!file_exists($fd = $this->stat('generated_class_path') . '/' . $site->PathName . '/code')) {
			mkdir($fd);
		}

		if(!file_exists($fn = $this->stat('generated_class_path') . '/' . $site->PathName . '/_config.php')) {
			file_put_contents($fn, '');
		}
		
		$this->DOClassFile = $this->stat('generated_class_path') . '/' . $site->PathName . '/code/' . $this->DOClassName . '.UDO.php';

		if(!$this->UDOFields())
			return false; // No fields to define

		$data = <<<EOI
<?php
class {$this->DOClassName} extends DataObject {
	public static \$db = array(
EOI;
		foreach($this->UDOFields() as $field) {
			if(!singleton($field->FieldType) instanceof UDC_RelatedRecord) {

				$fieldType = Object::combined_static($field->FieldType, 'datatype');
				$data .= "\t\t'{$field->FieldName}' => '{$fieldType}',";
			} else {
				// Relations
			}
		}

		$data .= <<<EOI
	);

	public static \$summary_fields = array(
EOI;
		foreach($this->UDOFields() as $field) {
			if(!singleton($field->FieldType) instanceof UDC_RelatedRecord && $field->ShowInSummary) {
				$data .= "\t\t'{$field->FieldName}' => '{$field->Label}',";
			}
		}

		$data .= <<<EOI
	);

	public static \$defaults = array(
EOI;
		foreach($this->UDOFields() as $field) {
			if(!singleton($field->FieldType) instanceof UDC_RelatedRecord) {
				$data .= "\t\t'{$field->FieldName}' => '{$field->DefaultValue}',";
			}
		}

		$data .= <<<EOI
	);
}
EOI;

		$exists = file_exists($this->DOClassFile);
		file_put_contents($this->DOClassFile, $data);
		
		if($exists && function_exists('apc_clear_cache')) apc_clear_cache();
		
		$host = NSite::primary_host();
		
		$http = new VHTTP(VHTTP::CURL);
		$result = $http->get("http://{$host->Hostname}/dev/build");
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if($this->TableName) {
			$this->generateClass();
		}
	}
}
