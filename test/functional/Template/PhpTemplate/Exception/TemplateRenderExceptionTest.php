<?php

namespace Dhii\Output\FuncTest\Template\PhpTemplate\Exception;

use ArrayAccess;
use Dhii\Output\RendererInterface;
use Dhii\Output\Template\PhpTemplate\Exception\TemplateRenderException as TestSubject;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class TemplateRenderExceptionTest extends TestCase
{
    /**
     * Creates a new instance of the test subject.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param Throwable $previous The inner exception.
     * @param RendererInterface $renderer The renderer that caused the exception.
     * @param array|ArrayAccess|ContainerInterface $context The rendering context.
     *
     * @return MockObject|TestSubject The new instance of the test subject.
     */
    public function createInstance(string $message, int $code, Throwable $previous, RendererInterface $renderer, $context)
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setConstructorArgs([$message, $code, $previous, $renderer, $context])
            ->setMethods(null)
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new renderer.
     *
     * @return MockObject|RendererInterface The new renderer instance.
     */
    public function createRenderer()
    {
        $mock = $this->getMockBuilder(RendererInterface::class)
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new rendering context.
     *
     * @return MockObject|ContainerInterface The new context instance.
     */
    public function createContext()
    {
        $mock = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();

        return $mock;
    }

    /**
     * Tests whether the exception subject can expose all the data correctly.
     */
    public function testGetters()
    {
        {
            $message = uniqid('message');
            $code = rand(1, 9);
            $previous = new Exception(uniqid('inner-message'));
            $renderer = $this->createRenderer();
            $context = $this->createContext();
            $subject = $this->createInstance($message, $code, $previous, $renderer, $context);
        }

        {
            try {
                throw $subject;
            } catch (TestSubject $e) {
                $this->assertSame($message, $e->getMessage(), 'Subject returned wrong message');
                $this->assertSame($code, $e->getCode(), 'Subject returned wrong code');
                $this->assertSame($previous, $e->getPrevious(), 'Subject returned wrong inner exception');
                $this->assertSame($renderer, $e->getRenderer(), 'Subject returned wrong renderer');
                $this->assertSame($context, $e->getContext(), 'Subject returned wrong context');
            }
        }
    }
}
