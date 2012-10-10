<?php
class UserDataWorkflow_Controller extends Controller {
	// Workflow/$Action/$ID

	public function handleAction($request) {
		
		$action = $request->param('Action');

		ob_start();

		$cart = DataObject::get_one('UserDataWorkflowCart', '"Token" = \'' . Convert::raw2sql($action) . '\'');

		if($cart) {
			$workflow = $cart->Workflow();
		} else {
			$workflow = DataObject::get_one('UserDataWorkflow', '"URLSegment" = \'' . Convert::raw2sql($action) . '\'');
			if(!$workflow) {
				throw new Exception("Workflow for action '{$action}' not found");
			}
			
			$cart = UserDataWorkflowCart::getCart($workflow->ID, !$workflow->Persistant);
		}

		if (DataObject::get_one('UserDefinedElement', "UserDataWorkflowID = {$workflow->ID}")) {
			Requirements::themedCSS('layout');
		}

		while($cartstep = $cart->currentStep()) {
			$step = $cartstep->Step();
			$step->cart = $cart;
			$result = $step->handleRequest($request);

			if(is_string($result) && array_key_exists($result, $step->stat('outcomes'))) {
				$cart->progress($result);
				return $this->handleAction($request);
			} elseif(is_object($result) && $result instanceof SS_HTTPResponse) {
				return $result->getBody();
			} elseif(is_object($result) && method_exists($result, 'forTemplate')) {
				$content = $result->forTemplate();
			} else {
				$content = ob_get_clean() . $result;
			}

			$viewer = $this->getViewer('index');
			return $viewer->process(new ArrayData(array('Content' => $content)));
		}
	}
}
/*
class UserDataReport_Controller extends Controller {
	public function handleAction($request) {
		$action = $request->latestParam('Action');
		$report = SS_Report::get_reports('ReportAdmin');
		if(isset($report['UserDataObjectReport'])) {
			$report = $report['UserDataObjectReport'];
			$viewer = new SSViewer(array('SS_Report'));
			return $viewer->process($report);
		}
	}
}*/