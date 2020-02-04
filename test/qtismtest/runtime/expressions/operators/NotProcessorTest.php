<?php

namespace qtismtest\runtime\expressions\operators;

use qtismtest\QtiSmTestCase;
use qtism\common\datatypes\QtiInteger;
use qtism\common\datatypes\QtiBoolean;
use qtism\runtime\expressions\operators\NotProcessor;
use qtism\runtime\expressions\operators\OperandsCollection;
use qtism\common\datatypes\QtiPoint;
use qtism\common\enums\BaseType;
use qtism\runtime\common\MultipleContainer;

class NotProcessorTest extends QtiSmTestCase
{
    
    public function testNotEnoughOperands()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection();
        $this->setExpectedException('qtism\\runtime\\expressions\\ExpressionProcessingException');
        $processor = new NotProcessor($expression, $operands);
    }
    
    public function testTooMuchOperands()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(new QtiBoolean(true), new QtiBoolean(false)));
        $this->setExpectedException('qtism\\runtime\\expressions\\ExpressionProcessingException');
        $processor = new NotProcessor($expression, $operands);
    }
    
    public function testWrongCardinality()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(new MultipleContainer(BaseType::POINT, array(new QtiPoint(1, 2)))));
        $processor = new NotProcessor($expression, $operands);
        $this->setExpectedException('qtism\\runtime\\expressions\\ExpressionProcessingException');
        $result = $processor->process();
    }
    
    public function testWrongBaseType()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(new QtiInteger(25)));
        $processor = new NotProcessor($expression, $operands);
        $this->setExpectedException('qtism\\runtime\\expressions\\ExpressionProcessingException');
        $result = $processor->process();
    }
    
    public function testNull()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(null));
        $processor = new NotProcessor($expression, $operands);
        $result = $processor->process();
        $this->assertSame(null, $result);
    }
    
    public function testTrue()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(new QtiBoolean(false)));
        $processor = new NotProcessor($expression, $operands);
        $result = $processor->process();
        $this->assertSame(true, $result->getValue());
    }
    
    public function testFalse()
    {
        $expression = $this->createFakeExpression();
        $operands = new OperandsCollection(array(new QtiBoolean(true)));
        $processor = new NotProcessor($expression, $operands);
        $result = $processor->process();
        $this->assertInstanceOf('qtism\\common\\datatypes\\QtiBoolean', $result);
        $this->assertSame(false, $result->getValue());
    }
    
    public function createFakeExpression()
    {
        return $this->createComponentFromXml('
			<not>
				<baseValue baseType="boolean">false</baseValue>
			</not>
		');
    }
}
