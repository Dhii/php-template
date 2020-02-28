<?php

namespace Dhii\Output\FuncTest\PhpEvaluator;

use Dhii\Output\PhpEvaluator\FilePhpEvaluator as TestSubject;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class FilePhpEvaluatorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    protected $fs;

    /**
     * Creates a new instance of the test subject.
     *
     * @param string $filePath Path to the PHP file that the subject will evaluate.
     * @return TestSubject|MockObject The instance of the test subject.
     */
    public function createInstance(string $filePath): TestSubject
    {
        $mock = $this->getMockBuilder(TestSubject::class)
            ->setMethods(null)
            ->setConstructorArgs([$filePath])
            ->getMock();

        return $mock;
    }

    public function setUp()
    {
        parent::setUp();
        $this->fs = vfsStream::setup();
    }

    public function getFilePath(string $content): string
    {
        $fileName = uniqid() . '.php';
        $filePath = $this->fs->url() . "/$fileName";

        file_put_contents($filePath, $content);

        return $filePath;
    }

    public function createPhpFile(string $result, string $output): string
    {
        $content = <<<EOF
<?php
    echo "$output";
    return $$result;
EOF;
        return $this->getFilePath($content);
    }

    public function testEvaluateNoExtras()
    {
        {
            $echo = uniqid('output');
            $varName = uniqid('var');
            $value = uniqid('value');
            $filePath = $this->createPhpFile($varName, $echo);
            $context = [$varName => $value];
            $subject = $this->createInstance($filePath);
        }

        {
            ob_start();
            $result = $subject->evaluate($context);
            $output = ob_get_clean();
        }

        {
            $this->assertEquals($value, $result, 'Subject did not return expected value');
            $this->assertEquals($echo, $output, 'Subject did not produce expected output');
        }
    }
}
