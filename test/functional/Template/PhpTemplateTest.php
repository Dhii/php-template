<?php

namespace Dhii\Output\FuncTest\Template;

use ArrayAccess;
use ArrayObject;
use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactory;
use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactoryInterface;
use Dhii\Output\PhpEvaluator\PhpEvaluatorInterface;
use Dhii\Output\Template\PhpTemplate\FilePathTemplateFactory;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Dhii\Output\Template\PhpTemplate as TestSubject;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Container\ContainerInterface;

/**
 * Tests `PhpTemplate`.
 */
class PhpTemplateTest extends TestCase
{
    /** @var vfsStreamDirectory */
    protected $fs;

    public function setUp()
    {
        parent::setUp();
        $this->fs = vfsStream::setup();
    }

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

    /**
     * Creates a new array access object.
     *
     * @param array $data The data for the array access object.
     *
     * @return ArrayAccess|MockObject The new instance of an array access object.
     */
    public function createArrayAccess(array $data): ArrayAccess
    {
        $mock = $this->getMockBuilder(ArrayObject::class)
            ->setConstructorArgs([$data])
            ->setMethods(null)
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new container.
     *
     * @param array $data The data for the container.
     *
     * @return ContainerInterface|MockObject
     */
    public function createContainer(array $data): ContainerInterface
    {
        $mock = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMock();

        $mock->method('get')
            ->will($this->returnCallback(function(string $key) use ($data) {
                if (!isset($data[$key])) {
                    return null;
                }

                return $data[$key];
            }));

        $mock->method('has')
            ->will($this->returnCallback(function (string $key) use ($data) {
                return array_key_exists($key, $data);
            }));

        return $mock;
    }

    /**
     * Creates a new evaluator factory mock instance.
     *
     * @return FilePhpEvaluatorFactory&MockObject The new instance.
     */
    public function createEvaluatorFactory(): FilePhpEvaluatorFactory
    {
        $mock = $this->getMockBuilder(FilePhpEvaluatorFactory::class)
            ->setMethods(null)
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new template factory mock instance.
     *
     * @param FilePhpEvaluatorFactoryInterface $evaluatorFactory
     * @param array                            $defaultContext
     * @param array                            $functions
     *
     * @return FilePathTemplateFactory&MockObject The new factory mock.
     */
    public function createFactory(
        FilePhpEvaluatorFactoryInterface $evaluatorFactory,
        array $defaultContext,
        array $functions
    ): FilePathTemplateFactory {
        $mock = $this->getMockBuilder(FilePathTemplateFactory::class)
            ->setMethods(null)
            ->setConstructorArgs([$evaluatorFactory, $defaultContext, $functions])
            ->getMock();

        return $mock;
    }

    /**
     * Provides data useful for testing rendering with different contexts.
     *
     * @return array<int, array> The data, with following members.
     *                           0. A key.
     *                           1. A value.
     *                           2. A context that contains the key which corresponds to the value.
     */
    public function renderDataProvider(): array
    {
        $key = uniqid('context-key');
        $value = uniqid('context-value');
        $data = [$key => $value];
        return [
            [$key, $value, $data],
            [$key, $value, $this->createArrayAccess($data)],
            [$key, $value, $this->createContainer($data)],
        ];
    }

    /**
     * Creates a virtual file with content, and retrieves its path.
     *
     * @param string $content The content for the file.
     *
     * @return string The path to the file.
     */
    public function getFilePath(string $content): string
    {
        $fileName = uniqid() . '.php';
        $filePath = $this->fs->url() . "/$fileName";

        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Tests that a factory creates a template that produces correct output.
     *
     * This is an end-to-end test which tests all of the functionality related to a PHP template together:
     *
     * - It uses a real evaluator factory and real evaluators, which evaluate a real PHP template file
     *   in a real, albeit virtualized, filesystem.
     * - It uses a real PHP template implementation.
     * - It uses a real template, which has
     *   * Explicit PHP output (by using `echo `).
     *   * Implicit PHP output (by just having content outside of PHP).
     *   * Retrieves a value from context explicitly provided at render time.
     *   * Retrieves a value from default context provided via the factory.
     *   * Passes value through a custom function provided via the factory.
     *
     * Pretty much, this tests the whole happy path from start to finish.
     */
    public function testFactoryE2e()
    {
        {
            $evalFactory = $this->createEvaluatorFactory();
            $defaultKey = uniqid('default-key');
            $defaultValue = uniqid('default-value');
            $contextKey = uniqid('context-key');
            $contextValue = uniqid('context-value');
            $defaultContext = [$defaultKey => $defaultValue];
            $funcName = uniqid('func-name');
            $func = function ($value) { return substr($value, 0, 15); };
            $functions = [$funcName => $func];
            $context = [$contextKey => $contextValue];
            $contentSeparator = uniqid('separator');
            $content = "<?php echo \$c('$defaultKey') ?>$contentSeparator<?php echo \$f('$funcName', \$c('$contextKey')) ?>";
            $funcResult = $func($contextValue);
            $expectedOutput = "{$defaultValue}{$contentSeparator}{$funcResult}";
            $subject = $this->createFactory($evalFactory, $defaultContext, $functions);
            $path = $this->getFilePath($content);
        }

        {
            $result = $subject->fromPath($path);
            $output = $result->render($context);

            $this->assertEquals($expectedOutput, $output);
        }
    }

    /**
     * Tests that rendering works correctly.
     *
     * @dataProvider renderDataProvider
     *
     * @param $keyExisting string A name of a key that exists in the context.
     * @param $valueExisting string A value that corresponds to the existing key in the context.
     * @param $context array|ArrayAccess|ContainerInterface The context.
     *
     * @throws \Dhii\Output\Exception\RendererExceptionInterface
     * @throws \Dhii\Output\Exception\TemplateRenderExceptionInterface
     */
    public function testRender(string $keyExisting, string $valueExisting, $context)
    {
        {
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
            $result = $subject->render($context);

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
