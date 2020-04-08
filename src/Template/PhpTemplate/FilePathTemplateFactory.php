<?php
declare(strict_types=1);

namespace Dhii\Output\Template\PhpTemplate;

use Dhii\Output\PhpEvaluator\FilePhpEvaluatorFactoryInterface;
use Dhii\Output\Template\PathTemplateFactoryInterface;
use Dhii\Output\Template\PhpTemplate;
use Dhii\Output\Template\TemplateInterface;

/**
 * @inheritDoc
 */
class FilePathTemplateFactory implements PathTemplateFactoryInterface
{
    /**
     * @var FilePhpEvaluatorFactoryInterface
     */
    protected $evaluatorFactory;
    /**
     * @var array
     */
    protected $defaultContext;
    /**
     * @var array
     */
    protected $functions;

    /**
     * @param FilePhpEvaluatorFactoryInterface $evaluatorFactory A factory that creates PHP file evaluators.
     * @param array                            $defaultContext   A map of keys to values that will be available in template context by default.
     * @param array                            $functions        A map of keys to callables that will be available to templates by default.
     */
    public function __construct(
        FilePhpEvaluatorFactoryInterface $evaluatorFactory,
        array $defaultContext,
        array $functions
    ) {
        $this->evaluatorFactory = $evaluatorFactory;
        $this->defaultContext = $defaultContext;
        $this->functions = $functions;
    }

    /**
     * @inheritDoc
     */
    public function fromPath(string $templatePath): TemplateInterface
    {
        $evaluator = $this->evaluatorFactory->fromFilePath($templatePath);
        $template = new PhpTemplate($evaluator, $this->defaultContext, $this->functions);

        return $template;
    }
}
