<?php

namespace Dhii\Output\Template\PhpTemplate\Exception;

use ArrayAccess;
use Dhii\Output\Exception\TemplateRenderExceptionInterface;
use Dhii\Output\RendererInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * {@inheritDoc}
 */
class TemplateRenderException extends CouldNotRenderException implements TemplateRenderExceptionInterface
{
    /**
     * @var array|ArrayAccess|ContainerInterface|null
     */
    protected $context;

    /**
     * {@inheritDoc}
     *
     * @param array|ArrayAccess|ContainerInterface|null $context
     */
    public function __construct(
        $message = '',
        $code = 0,
        Throwable $previous = null,
        RendererInterface $renderer = null,
        $context = null
    ) {
        parent::__construct($message, $code, $previous, $renderer);
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getContext()
    {
        return $this->context;
    }
}