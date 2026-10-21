<?php

declare(strict_types=1);

namespace App\View;

/**
 * Renders plain PHP templates from one directory. A template sees its
 * parameters as variables plus $view (this object, for partials); output is
 * escaped with App\View\e().
 */
final readonly class View
{
    public function __construct(private string $templateDir) {}

    /**
     * Renders $template and puts the result into $layout as $content.
     *
     * @param array<string, mixed> $params passed to both templates
     */
    public function page(string $template, array $params = [], string $layout = 'layout'): string
    {
        return $this->render($layout, ['content' => $this->render($template, $params)] + $params);
    }

    /** @param array<string, mixed> $params */
    public function render(string $template, array $params = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \InvalidArgumentException("Template {$template} not found");
        }

        $params['view'] = $this;
        ob_start();
        try {
            (static function (string $__file, array $__params): void {
                extract($__params, \EXTR_SKIP);
                require $__file;
            })($file, $params);

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
