<?php
class UserDataObjectReport extends SS_Report {
	function title() {
		return 'UserDataObject Report';
	}
	
	function sourceRecords($params, $sort, $limit) {
		if(empty($params['Dataset']))
			return new DataObjectSet(array(new DataObject(array('ds' => 'Please select a dataset to view from the list above'))));
		
		if(!empty($_REQUEST['DetailID'])) {
			return new DataObjectSet(array(DataObject::get_by_id($params['Dataset'], $params['DetailID'])));
		}
		
		$join = '';
		if($sort) {
			$parts = explode(' ', $sort);
			$field = $parts[0];
			$direction = $parts[1];
		}
		$q = DB::USE_ANSI_SQL ? '"' : '`';
		
		return DataObject::get($params['Dataset'], '', $sort, $join, $limit);
	}
	
	function columns() {
		if(empty($_REQUEST['Dataset'])) {
			return array('ds' => array('title' => '', 'formatting' => '$value'));
		}
		
		if(!empty($_REQUEST['DetailID'])) {
			$udo = DataObject::get_by_id('UserDataObject', $_REQUEST['DetailID']);

			$fields = array();
			foreach($udo->UDOFields() as $field) {
				$fields[$field->FieldName] = array(
					'title' => $field->Label,
					'formatting' => '$value'
				);
			}
		} else {
			$udo = DataObject::get_one('UserDataObject', 'DOClassName = \'' . Convert::raw2sql($_REQUEST['Dataset']) . '\'');
			
			$fields = array();

			foreach($udo->UDOFields() as $field) {
				if(!empty($_REQUEST['ShowAllFields']) || $field->ShowInSummary) {
					$fields[$field->FieldName] = array(
						'title' => $field->Label,
						'formatting' => '$value'
					);
				}
			}
		}
		
		return $fields;
	}
	
	function parameterFields() {
		$sets = array();
		foreach(DataObject::get('UserDefinedElement') as $element) {
			$udo = $element->UserDataObject();
			$sets[$udo->DOClassName] = $udo->Title;
		}
		
		return new FieldSet(
			new DropdownField('Dataset', 'Dataset', $sets),
			new CheckboxField('ShowAllFields', 'Show All Fields', !isset($_REQUEST['ShowAllFields']) ? '0' : $_REQUEST['ShowAllFields'])
		);
	}
}
