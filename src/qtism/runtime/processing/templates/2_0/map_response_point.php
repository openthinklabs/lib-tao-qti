<?php
$string_0 = "";
$string_1 = "";
$string_2 = "RESPONSE";
$string_3 = "";
$variable_0 = new qtism\data\expressions\Variable($string_2, $string_3);
$array_0 = array($variable_0);
$expressioncollection_0 = new qtism\data\expressions\ExpressionCollection($array_0);
$isnull_0 = new qtism\data\expressions\operators\IsNull($expressioncollection_0);
$string_4 = "SCORE";
$integer_0 = 2;
$integer_1 = 0;
$basevalue_0 = new qtism\data\expressions\BaseValue($integer_0, $integer_1);
$setoutcomevalue_0 = new qtism\data\rules\SetOutcomeValue($string_4, $basevalue_0);
$array_1 = array($setoutcomevalue_0);
$responserulecollection_0 = new qtism\data\rules\ResponseRuleCollection($array_1);
$responseif_0 = new qtism\data\rules\ResponseIf($isnull_0, $responserulecollection_0);
$array_2 = array();
$responseelseifcollection_0 = new qtism\data\rules\ResponseElseIfCollection($array_2);
$string_5 = "SCORE";
$string_6 = "RESPONSE";
$mapresponsepoint_0 = new qtism\data\expressions\MapResponsePoint($string_6);
$setoutcomevalue_1 = new qtism\data\rules\SetOutcomeValue($string_5, $mapresponsepoint_0);
$array_3 = array($setoutcomevalue_1);
$responserulecollection_1 = new qtism\data\rules\ResponseRuleCollection($array_3);
$responseelse_0 = new qtism\data\rules\ResponseElse($responserulecollection_1);
$responsecondition_0 = new qtism\data\rules\ResponseCondition($responseif_0, $responseelseifcollection_0, $responseelse_0);
$array_4 = array($responsecondition_0);
$responserulecollection_2 = new qtism\data\rules\ResponseRuleCollection($array_4);
$rootcomponent = new qtism\data\processing\ResponseProcessing($responserulecollection_2);
$rootcomponent->setTemplate($string_0);
$rootcomponent->setTemplateLocation($string_1);
