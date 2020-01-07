<?php

namespace Dhii\Output\PhpEvaluator;

/**
 * An evaluator factory that creates PHP evaluators based on a file path.
 */
class FilePhpEvaluatorFactory implements FilePhpEvaluatorFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function fromFilePath(string $filePath): PhpEvaluatorInterface
    {
        return new FilePhpEvaluator($filePath);
    }
}
