<?php
declare(strict_types=1);

namespace Dhii\Output\PhpEvaluator;

use Exception;

/**
 * A PHP evaluator that directly evaluates PHP files via `include`.
 */
class FilePhpEvaluator implements PhpEvaluatorInterface
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @param string $filePath Path to the template PHP file that will be evaluated.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(array $context)
    {
        $eval = $this->_isolateFileScope($this->filePath);
        $result = $eval($context);

        return $result;
    }

    /**
     * Isolates a scope of a PHP file, such that only variables from a specific map are accessible inside.
     *
     * @since [*next-version*]
     *
     * @param string $filePath The path to the file, the scope of which to isolate.
     *
     * @throws Exception If a problem occurs while isolating.
     *
     * @return callable The callable which isolates file scope.
     */
    protected function _isolateFileScope($filePath)
    {
        $____file = $filePath;

        $fn = function (array $____vars) use ($____file) {
            extract($____vars, EXTR_SKIP);

            return include $____file;
        };
        $fn->bindTo(null);

        return $fn;
    }
}
