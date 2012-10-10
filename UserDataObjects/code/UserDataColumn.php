<?php
class UserDataColumn extends DBField {
	public function requireField() {
	
	}
}
class UDC_Label extends UserDataColumn {
	public static $datatype = 'Varchar(255)';
	public static $default_udff = 'UDFF_ShortLineOfText';
	public static $singular_name = 'Label or name';
}
class UDC_ShortText extends UserDataColumn {
	public static $datatype = 'Varchar(4000)';
	public static $default_udff = 'UDFF_LongLineOfText';
	public static $singular_name = 'Short line of text';
}
class UDC_LongText extends UserDataColumn {
	public static $datatype = 'Text';
	public static $default_udff = 'UDFF_LargeTextBox';
	public static $singular_name = 'Long block of text';
}
class UDC_HTML extends UserDataColumn {
	public static $datatype = 'Text';
	public static $default_udff = 'UDFF_HTML';
	public static $singular_name = 'HTML Content';
}
class UDC_Number extends UserDataColumn {
	public static $datatype = 'Double';
	public static $default_udff = 'UDFF_ShortLineOfText';
	public static $singular_name = 'Number';
}
class UDC_DateTime extends UserDataColumn {
	public static $datatype = 'DateTime';
	public static $default_udff = 'UDFF_ShortLineOfText';
	public static $singular_name = 'Date & Time';
}
class UDC_OnOff extends UserDataColumn {
	public static $datatype = 'Boolean';
	public static $default_udff = 'UDFF_YesNoChoice';
	public static $singular_name = 'Yes or No Choice';
}
/*
class UDC_MultipleChoice extends UserDataColumn {
	public static $datatype = 'Varchar(4000)';
	public static $default_udff = 'UDFF_MultipleChoice';
	public static $singular_name = 'Multiple Choice';
}
class UDC_RelatedRecord extends UserDataColumn {
	public static $datatype = 'ForeignKey';
	public static $default_udff = 'UDFF_ExistingRecord';
	public static $relationTable = 'DataObject';
	public static $relationField = 'ID';
	public static $singular_name = 'Related Record';
}
class UDC_File extends UDC_RelatedRecord {
	public static $relationTable = 'File';
	public static $relationField = 'ID';
	public static $default_udff = 'UDFF_File';
	public static $singular_name = 'File';
}
class UDC_Image extends UDC_RelatedRecord {
	public static $relationTable = 'Image';
	public static $relationField = 'ID';
	public static $default_udff = 'UDFF_Image';
	public static $singular_name = 'Image';
}
class UDC_Member extends UDC_RelatedRecord {
	public static $relationTable = 'Member';
	public static $relationField = 'ID';
	public static $singular_name = 'Site Member Account';
}
*/