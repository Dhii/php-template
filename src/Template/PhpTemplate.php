<?php

namespace Dhii\Output\Template;

use ArrayAccess;
use Dhii\Output\PhpEvaluator\PhpEvaluatorInterface;
use Dhii\Output\Template\PhpTemplate\Exception\RendererException;
use Dhii\Output\Template\PhpTemplate\Exception\TemplateRenderException;
use Dhii\Util\String\StringableInterface as Stringable;
use Exception;
use InvalidArgumentException;
use OutOfRangeException;
use Psr\Container\ContainerInterface;

/**
 * A template that uses PHP code as an engine.
 */
class PhpTemplate implements TemplateInterface
{
    const VAR_CONTEXT = 'c';
    const VAR_FUNCTION = 'f';

    /**
     * @var \Dhii\Output\PhpEvaluator\PhpEvaluatorInterface
     */
    protected $evaluator;
    /**
     * @var array<string, mixed>
     */
    protected $defaultContext;
    /**
     * @var array<string, callable>
     */
    protected $functions;

    /**
     * @param PhpEvaluatorInterface $evaluator The evaluator of the PHP code that represents the template body.
     * @param array<string, mixed> $defaultContext Default values for context keys.
     *      Context will have those values if they are not supplied at render time.
     * @param array<string, callable> $functions Functions available to the template.
     *      A map of function name to callable implementation
     */
    public function __construct(
        PhpEvaluatorInterface $evaluator,
        array $defaultContext,
        array $functions
    ) {
        $this->evaluator = $evaluator;
        $this->defaultContext = $defaultContext;
        $this->functions = $functions;
    }

    /**
     * @inheritDoc
     */
    public function render($context = null)
    {
        try {
            $vars = $this->getPhpVars($context);
        } catch (Exception $e) {
            // Not related to the rendering process
            throw new RendererException($this->__('Could not retrieve template PHP vars'), 0, $e, $this);
        }

        ob_start();
        try {
            $this->evaluator->evaluate($vars);
        } catch (Exception $e) {
            // End this level of output buffering to maintain correct sequence
            ob_end_clean();
            throw new TemplateRenderException($this->__('Could not render template'), 0, $e, $this, $context);
        }
        $output = ob_get_clean();

        return $output;
    }

    /**
     * @param array|ArrayAccess|ContainerInterface $context The context to get the PHP variables for.
     * @return array A map of PHP variable names to their values.
     *
     * @throws Exception If problem retrieving.
     */
    protected function getPhpVars($context): array
    {
        return [
            static::VAR_CONTEXT => $this->getContextFunction($context),
            static::VAR_FUNCTION => $this->getFunctionsFunction($context),
        ];
    }

    /**
     * Retrieves a function which itself when invoked retrieves a value from a context.
     *
     * That function MUST take a stringable key, and an optional default.
     * It MUST return the value if the key is found.
     * It MUST return the default value if the key is not found.
     * It MAY throw {@see Exception} if there is a problem retrieving the value.
     *
     * @param array|ArrayAccess|ContainerInterface $context The context to get the retrieval function for.
     *
     * @throws Exception If problem retrieving.
     *
     * @return callable The function that retrieves a context value.
     * function (string $key, mixed $default): mixed
     */
    protected function getContextFunction($context): callable
    {
        return function (string $key, $default = null) use ($context) {
            return $this->getContextValue($context, $key, $default);
        };
    }

    /**
     * Retrieves a function which itself invokes a template function by name.
     *
     * That function MUST take a string key, and a variable list of arguments.
     * It MUST invoke an underlying function mapped by name.
     * It MUST pass these arguments to the underlying function , as a variable list.
     * It MUST return the result of the invocation of the underlying function.
     * It MAY throw an {@see Exception} if there is a problem invoking the underlying function.
     *
     * @param array|ArrayAccess|ContainerInterface $context The context to get the functions function for.
     *
     * @throws Exception If problem retrieving.
     *
     * @return callable The function that invokes a template function.
     * function (string $funcName, ...$args): mixed
     */
    protected function getFunctionsFunction($context): callable
    {
        return function (string $funcName, ...$args) {
            return $this->invokeFunction($funcName, $args);
        };
    }

    /**
     * Retrieves a value from a context.
     *
     * @param array|ArrayAccess|ContainerInterface $context The context to retrieve the value from.
     * @param string|Stringable $key The key to retrieve the value for.
     * @param mixed $default The value to retrieve if the key is not found.
     * @throws Exception If problem retrieving value.
     *
     * @return mixed The value that corresponds to the specified key in the context, or the default value if not found.
     */
    protected function getContextValue($context, string $key, $default = null)
    {
        try {
            $this->validateContext($context);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($this->__('Could not retrieve value for key "%1$s": invalid context', $key), 0, $e);
        }

        if ($context instanceof ContainerInterface) {
            if (!$context->has($key)) {
               if (!array_key_exists($key, $this->defaultContext)) {
                   return $default;
               }

               return $this->defaultContext[$key];
            }

            return $context->get($key);
        }

        // It's an array-like
        {
            $isExists = is_array($context)
                ? array_key_exists($key, $context)
                : $context->offsetExists($key);

            if (!$isExists) {
                if (!array_key_exists($key, $this->defaultContext)) {
                    return $default;
                }

                return $this->defaultContext[$key];
            }

            return $context[$key];
        }
    }

    /**
     * Invokes a template function by name.
     *
     * @param string $name The name of the function to invoke.
     * @param array $args The args for the function.
     *
     * @return mixed The invocation result.
     *
     * @throws Exception If problem invoking.
     */
    public function invokeFunction(string $name, array $args)
    {
        if (!isset($this->functions[$name]) || !is_callable($this->functions[$name])) {
            throw new OutOfRangeException($this->__('Could not invoke function "%1$s": function does not exist or is not callable', $name));
        }

        return call_user_func_array($this->functions[$name], $args);
    }

    /**
     * Determines whether the given context is valid.
     *
     * @param array|ArrayAccess|ContainerInterface $context The context to validate.
     * @throws InvalidArgumentException If context is invalid.
     * @throws Exception If problem validating.
     * @return void
     */
    protected function validateContext($context)
    {
        if (!($context instanceof ContainerInterface)
            && !(is_array($context) || $context instanceof ArrayAccess)) {
            throw new InvalidArgumentException($this->__('Context must be a PSR container or an array-like'));
        }
    }

    /**
     * Translates a string and interpolates values.
     *
     * The values in the string may be specified using the {@see sprintf()} style.
     *
     * @param string $string The string to translate.
     * @param array $placeholders Numeric array. The values to replace placeholders in the string with.
     *
     * @throws Exception If problem translating.
     *
     * @return string The translated string, with placeholders replaced.
     */
    protected function __(string $string, $placeholders = []): string
    {
        return vsprintf($string, $placeholders);
    }
}
