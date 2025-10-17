<?php

namespace Bardh78\LaravelSigma\Renderers;

use Illuminate\Contracts\Foundation\ExceptionRenderer;
use Throwable;

class SigmaExceptionRenderer implements ExceptionRenderer
{
    protected ErrorPageRenderer $errorPageRenderer;

    public function __construct(ErrorPageRenderer $errorPageRenderer)
    {
        $this->errorPageRenderer = $errorPageRenderer;
    }

    public function render($throwable): string
    {
        ob_start();

        $this->errorPageRenderer->render($throwable);

        return ob_get_clean();
    }
}
