<?php

namespace Dhii\Output\Template\PhpTemplate\Exception;

use Dhii\Output\Exception\RendererExceptionInterface;
use Dhii\Output\RendererInterface;
use Exception;
use Throwable;

/**
 * {@inheritDoc}
 */
class RendererException extends Exception implements RendererExceptionInterface
{
    /**
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * {@inheritDoc}
     *
     * @param RendererInterface|null $renderer The renderer that caused the problem, if any.
     */
    public function __construct(
        $message = '',
        $code = 0,
        Throwable $previous = null,
        RendererInterface $renderer = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->renderer = $renderer;
    }

    /**
     * @inheritDoc
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
}