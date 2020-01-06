<?php

namespace Dhii\Output\PhpEvaluator;

use Exception;

interface PhpEvaluatorInterface
{
    /**
     * Evaluates the code with the specified context.
     *
     * @param array $context The name-value map for variables that are available inside the code.
     * @return mixed The return value of the evaluated code.
     *
     * @throws Exception If problem evaluating.
     */
    public function evaluate(array $context);
}