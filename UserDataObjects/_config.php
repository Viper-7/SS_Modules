<?php
Director::addRules(50, array('Workflow/$Action/$ID/$Param' => 'UserDataWorkflow_Controller'));
//Director::addRules(50, array('UserDataReport/$Action/$ID/$Param' => 'UserDataReport_Controller'));
SS_Report::register('ReportAdmin', 'UserDataObjectReport', -20);
