<?php
/**
* [-]RuleGroup
*    \-Rule
*      |-AllowSelfTrigger
*      |-ClassName
*      |-ExecutionLimit
*      | \-Rule_LogEntry
*      \-Fact
*        |-Connective
*        \-Condition
*          |-Sub-Condition
*          |-Operand
*          |-Table
*          \-Field
**/


/**
*
* Examples:
*
**/


/**
*
* Give a 10% discount when the user has made more than 3 orders over $100 in the store in the last year
*
**/

	$rule = new NRule();
	$rule->Title = 'Frequent customer discount';
	$rule->TriggerType = 'WhenItemCreated';
	$rule->TargetClass = 'Order';
	$rule->Enabled = true;
	$rule->write();

	$fact = new NAggregateFact();
	$fact->Title = 'User is a Frequent Customer';
	$fact->ContextTable = 'Order';
	$fact->Function = 'COUNT';
	$rule->Facts()->add($fact);

	$condition = new NCondition_is_Newer_Than();
	$condition->Field = 'Created';
	$condition->Operand = '31536000';
	$fact->Conditions()->add($condition);

	$condition = new NCondition_Matches_Expression();
	$condition->Field = 'MemberID';
	$condition->Operand = 'Member::currentUserID()';
	$fact->Conditions()->add($condition);

	$condition = new NCondition_is_Above();
	$condition->Table = 'Results';
	$condition->Field = 'AggregationResult';
	$condition->Operand = '3';
	$fact->Conditions()->add($condition);

	$fact = new NFact();
	$fact->Title = 'Has a Total higher than $100';
	$rule->Facts()->add($fact);

	$condition = new NCondition_is_Above();
	$condition->Field = 'Total';
	$condition->Operand = '100';
	$fact->Conditions()->add($condition);

	$workflow = new NWorkflow();
	$rule->Workflow = $workflow;

	$discountstep = new NWorkflowStep_AddOrderDiscount()
	$discountstep->Percentage = 10;
	$workflow->Steps->add($discountstep);

/**
*
* Email me when a product has not been sold in more than a year - but not more than once a day
*
**/

	$rule = new NRule();
	$rule->Title = 'Notify me when products arent selling';
	$rule->TriggerType = 'WhenTimePassed';
	$rule->TargetClass = 'Product';
	$rule->ExecutionLimit()->add(new NRuleLimit_OncePerDay());
	$rule->Enabled = true;
	$rule->write();

	$fact = new NAggregateFact();
	$fact->Title = 'Product is rarely sold';
	$fact->ContextTable = 'OrderItem';
	$fact->Function = 'COUNT';
	$rule->Facts()->add($fact);

	$condition = new NCondition_is_Newer_Than();
	$condition->Field = 'Created';
	$condition->Operand = '31536000';
	$fact->Conditions()->add($condition);

	$condition = new NCondition_is_Empty();
	$condition->Field = 'AggregationResult';
	$fact->Conditions()->add($condition);

	$workflow = new NWorkflow();
	$rule->Workflow = $workflow;

	$emailstep = new NWorkflowStep_SendEmail()
	$emailstep->To = 'viper7@viper-7.com';
	$emailstep->Subject = 'A product is outdated';
	$emailstep->Body = 'There is a product outdated in your store, check http://example.com/outdated_products';
	$workflow->Steps->add($emailstep);


/**
*
* Give a 5% discount when the current order is over $500 and contains less than 5 products
*
**/

	$rule = new NRule();
	$rule->Title = 'Large product shipping bonus';
	$rule->TriggerType = 'WhenItemSaved';
	$rule->TargetClass = 'Order';
	$rule->Enabled = true;
	$rule->write();

	$fact = new NFact();
	$fact->Title = 'Has a Total higher than $500';
	$rule->Facts()->add($fact);

	$condition = new NCondition_is_Above();
	$condition->Field = 'Total';
	$condition->Operand = '500';
	$fact->Conditions()->add($condition);

	$fact = new NAggregationFact();
	$fact->Title = 'Contains less than 5 Products';
	$fact->ContextTable = 'OrderItem';
	$fact->Function = 'COUNT'
	$rule->Facts()->add($fact);

	$condition = new NCondition_is_Below();
	$condition->Field = 'AggregationResult';
	$condition->Operand = '5';
	$fact->Conditions()->add($condition);

	$workflow = new NWorkflow();
	$rule->Workflow = $workflow;

	$discountstep = new NWorkflowStep_AddOrderDiscount()
	$discountstep->Percentage = 5;
	$workflow->Steps->add($discountstep);

