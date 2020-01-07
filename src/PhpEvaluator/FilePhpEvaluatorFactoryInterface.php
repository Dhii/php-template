<?php

namespace Dhii\Output\PhpEvaluator;

use Exception;

/**
 * Something that can create PHP evaluators which work with files.
 */
interface FilePhpEvaluatorFactoryInterface
{
    /**
     * Creates an evaluator which evaluates a file at the specified path.
     *
     * @param string $filePath Path to the PHP file that the evaluator will evaluate.
     *
     * @throws Exception If cannot create evaluator.
     *
     * @return PhpEvaluatorInterface The evaluator which will evaluate the file at the specified path.
     */
    public function fromFilePath(string $filePath): PhpEvaluatorInterface;
}