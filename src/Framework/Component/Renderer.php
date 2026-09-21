<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\Data\{ArrayObject, StringObject};
use Clover\Classes\File\Functions as FileFunction;
use RuntimeException;

/**
 * Renderer
 * 
 * The Renderer class is responsible for rendering templates in the Clover Framework. It provides two methods for rendering: `renderWithCTemplate`, which uses the Clover Framework C Template Engine, and `render`, which uses PHP's native templating capabilities. The class checks for the availability of the C Template Engine and its associated function before attempting to render with it, and falls back to PHP's native rendering if the C Template Engine is not available. This allows developers to choose the rendering method that best suits their needs while ensuring compatibility with the framework's features.
 */
class Renderer
{
    /**
     * Render a template using the Clover Framework C Template Engine
     *
     * @param string|StringObject $template The template file path or content
     * @param ArrayObject|array $data The data to be used in the template
     *
     * @return string|StringObject The rendered content
     *
     * @throws RuntimeException If the C template engine is not available
     */
    public function renderWithCTemplate(string|StringObject $template, ArrayObject|array $data = []): string|StringObject
    {
        if (!extension_loaded('template_engine')) {
            throw new RuntimeException('The clover framework c template engine could not be found. Please install it and try again.');
        }

        if (!function_exists('template_render')) {
            throw new RuntimeException('Function `template_render` could not be found.');
        }

        return template_render($template, $data);
    }

    /**
     * Render a template using PHP's native templating capabilities
     *
     * @param string|StringObject $template The template file path or content
     * @param ArrayObject|array $data The data to be used in the template
     *
     * @return string|StringObject The rendered content
     */
    public function render(string|StringObject $template, ArrayObject|array $data = []): string|StringObject
    {
        $render = FileFunction::getInterpretedContent($template, $data);

        return $render;
    }
}
