<?php

namespace Dhii\Output\FuncTest\PhpEvaluator;

use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactory as TestSubject;
use Dhii\Output\PhpEvaluator\PhpEvaluatorInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class FilePhpEvaluatorFactoryTest extends TestCase
{
    /**
     * Creates a new instance of the test subject.
     *
     * @return MockObject|TestSubject A new instance of the test subject.
     */
    public function createInstance()
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setConstructorArgs([])
            ->setMethods(null)
            ->getMock();

        return $mock;
    }

    /**
     * Tests whether the subject can correctly create a new instance from a path.
     */
    public function testFromFilePath()
    {
        {
            $subject = $this->createInstance();
        }

        {
            $result = $subject->fromFilePath(uniqid('path'));
            $this->assertInstanceOf(PhpEvaluatorInterface::class, $result, 'Subject did not produce a valid PHP evaluator');
        }
    }
}
