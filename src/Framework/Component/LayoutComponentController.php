<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\File\Functions as FileFunction;
use Clover\Classes\Layout\Menu;
use function sprintf;

/**
 * Layout Component Controller
 * 
 * The LayoutComponentController is responsible for rendering layouts with specified skins and templates. It allows developers to create consistent layouts across different pages of the application by defining a common structure and styling. The controller takes care of loading the appropriate CSS and JavaScript files for the layout, rendering the content using the specified template, and returning the final response with all necessary resources included.
 */
class LayoutComponentController extends BaseController
{
    /**
     * Constructor
     *
     * @param Container|null $container The dependency injection container for managing application services and dependencies, which allows the controller to resolve and utilize various services needed during layout rendering, such as resource management, template rendering, and more. This enables the controller to efficiently handle the rendering process while maintaining a clean separation of concerns and promoting modularity in the application architecture.
     */
    public function __construct(protected ?Container $container = null)
    {
        parent::__construct($container);
    }

    /**
     * Render layout with given skin and template
     *
     * @param string $skin The name of the skin to be used for the layout, which corresponds to a specific directory containing the layout's CSS, JavaScript, and template files. This allows developers to easily switch between different layout styles by simply specifying the desired skin name when rendering the layout.
     * @param string $template The file path to the template that should be rendered for the content of the layout. This path should point to a valid template file that can be processed and rendered by the controller, allowing for dynamic content to be included in the layout based on the provided data.
     * @param array{fold: bool, menus: Menu[], logo: string, subject: string, description: string}|array $templateData An array of data to be passed to the template during rendering, which may include variables and information needed to populate the template with dynamic content. This allows for flexible and customizable layouts, as developers can provide different data for each layout rendering, enabling the display of personalized content, user-specific information, or other relevant data within the layout's structure.
     * @param array $data An array of additional data to be used in the template rendering, which may include variables and information needed to populate the content of the layout. This allows for further customization and dynamic content generation within the layout, as developers can provide different data for each rendering, enabling the display of personalized content, user-specific information, or other relevant data within the layout's structure.
     *
     * @return Response
     */
    public function layout(string $skin = 'Basic', string $template = '', array $templateData = [], array $data = []): Response
    {
        $path = realpath(sprintf("%s/../../Template/Layout/%s", __DIR__, $skin));
        $absolutePath = sprintf("/App/Frontend/Layout/%s", $skin);

        parent::addCssFileToHead(sprintf("%s/layout.css", $absolutePath));
        parent::addJsFileToHead(sprintf("%s/layout.js", $absolutePath));

        /** @var Resource $resource */
        $resource = $this->container->get(Resource::class);
        $extractedRecources = $resource->extract();

        /** @var Renderer $renderer */
        $renderer = $this->container->get(Renderer::class);
        $renderedContent = $renderer->render(BASE_PATH.$template, $data);

        $templateData['content'] = $renderedContent;

        $render = FileFunction::getInterpretedContent(sprintf("%s/main.php", $path), $templateData);

        return $this->response($render, $extractedRecources);
    }
}
