<?php

use qtism\common\datatypes\QtiIdentifier;
use qtism\common\enums\BaseType;
use qtism\common\enums\Cardinality;
use qtism\data\storage\xml\XmlDocument;
use qtism\runtime\common\MultipleContainer;
use qtism\runtime\common\ResponseVariable;
use qtism\runtime\common\State;
use qtism\runtime\tests\AssessmentItemSession;

require_once(__DIR__ . '/../../vendor/autoload.php');

$itemDoc = new XmlDocument('2.1');
$itemDoc->load(__DIR__ . '/../samples/ims/items/2_1/choice_multiple.xml');
$item = $itemDoc->getDocumentComponent();

$itemSession = new AssessmentItemSession($item);
$itemSession->beginItemSession();
$itemSession->beginAttempt();

$responses = new State(
    [
        new ResponseVariable(
            'RESPONSE',
            Cardinality::MULTIPLE,
            BaseType::IDENTIFIER,
            new MultipleContainer(
                BaseType::IDENTIFIER,
                [
                    new QtiIdentifier('H'),
                    new QtiIdentifier('O'),
                ]
            )
        ),
    ]
);

$itemSession->endAttempt($responses);

echo "numAttempts: " . $itemSession['numAttempts'] . "\n";
echo "completionStatus: " . $itemSession['completionStatus'] . "\n";
echo "RESPONSE: " . $itemSession['RESPONSE'] . "\n";
echo "SCORE: " . $itemSession['SCORE'] . "\n";

$itemSession->endItemSession();
