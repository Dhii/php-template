<?php

namespace Dhii\Output\FuncTest\Template;

use Dhii\Output\PhpEvaluator\PhpEvaluatorInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Dhii\Output\Template\PhpTemplate as TestSubject;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class PhpTemplateTest extends TestCase
{
    /**
     * Creates a new instance of the test subject.
     *
     * @return MockObject|TestSubject A new instance of the test subject.
     */
    public function createInstance(PhpEvaluatorInterface $evaluator, array $defaultContext = [], array $functions = [])
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setConstructorArgs([$evaluator, $defaultContext, $functions])
            ->setMethods(null)
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new instance of PHP evaluator.
     *
     * @param callable $php The PHP code that will be evaluated.
     * @return MockObject|PhpEvaluatorInterface The new evaluator.
     */
    public function createEvaluator(callable $php)
    {
        $mock = $this->getMockBuilder(PhpEvaluatorInterface::class)
            ->setMethods(['evaluate'])
            ->getMock();

        $mock->method('evaluate')
            ->will($this->returnCallback($php));

        return $mock;
    }

    public function testRender()
    {
        {
            $keyExisting = uniqid('key-existing');
            $valueExisting = uniqid('val-existing');
            $keyDefault = uniqid('key-default');
            $valueDefault = uniqid('val-default');
            $defaultContext = [
                $keyDefault => $valueDefault,
            ];

            $funcKey = uniqid('func');
            $funcResultSuffix = uniqid('func-result-suffix');
            $functions = [
                $funcKey => function (string $string) use ($funcResultSuffix): string {
                    return $string . $funcResultSuffix;
                },
            ];
            $evaluator = $this->createEvaluator(function ($context) use ($keyExisting, $keyDefault, $funcKey) {
                echo $context['f']($funcKey, $context['c']($keyExisting) . $context['c']($keyDefault));
            });
            $subject = $this->createInstance($evaluator, $defaultContext, $functions);
        }

        {
            $result = $subject->render([
                $keyExisting => $valueExisting,
            ]);

            $this->assertEquals($valueExisting . $valueDefault . $funcResultSuffix, $result, 'Rendering subject produced unexpected result');
        }
    }

    /**
     * Tests that an exception in the template body is handled correctly by the template.
     *
     * @expectedException \Dhii\Output\Exception\TemplateRenderExceptionInterface
     */
    public function testRenderTemplateError()
    {
        {
            $context = [];
            $defaultContext = [];
            $functions = [];
            $evaluator = $this->createEvaluator(function () {
                echo uniqid('misc-output');
                throw new Exception(uniqid('exception-message'));
            });
            $subject = $this->createInstance($evaluator, $defaultContext, $functions);
        }

        {
            $result = $subject->render($context);
        }
    }
}
