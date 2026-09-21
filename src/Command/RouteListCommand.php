<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Component\TableView;
use Clover\Classes\Routing\Router;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;
use Clover\Classes\Routing\Route;

/**
 * Route List Command Class
 *
 * Displays a structured list of all registered routes.
 * Shows route methods, patterns, content types, and associated classes.
 */
class RouteListCommand implements CommandInterface
{
    /**
     * RouteListCommand constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return "route:list";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Display a routing structure";
    }

    /**
     * Configure the command options and arguments.
     * No additional configuration required.
     *
     * @return void
     */
    public function configure(): void
    {
        // No additional configuration required
    }

    /**
     * Executes the command.
     * 
     * @param Input $input The CLI input object
     * 
     * @return bool Returns true on successful execution
     */
    public function run(Input $input): bool
    {
        $router = new Router();
        $router->fromDirectory(sprintf("%s/App/Controller", BASE_PATH));
        $router->fromDirectory(sprintf("%s/../src/Command", BASE_PATH));
        $routeMap = $router->map();

        $this->renderRouteTable($routeMap);

        return true;
    }

    /**
     * Renders the table of routes.
     * 
     * @param Route[]|Traversable $routers An array or iterable of route definitions
     * 
     * @return void
     */
    public function renderRouteTable(array|Traversable $routers): void
    {
        $routeMap = [];

        foreach ($routers as $route) {
            $method = strtoupper((string) ($route['method'] ?? ''));
            $pattern = (string) ($route['pattern'] ?? '');
            $pattern = preg_replace('#^//#', '/', $pattern);
            $contentType = (string) ($route['contentType'] ?? '*');
            $class = (string) ($route['class'] ?? '');
            $caller = (string) ($route['caller'] ?? '');

            $routeMap[] = [
                'caller' => $caller,
                'class' => $class,
                'method' => $method,
                'pattern' => $pattern,
                'contentType' => $contentType,
            ];
        }

        $tableView = new TableView();
        $tableView->setHeaders(['Method', 'Class', 'Name', 'Pattern', 'Content-type']);
        $tableView->setRows($routeMap);
        $tableView->setOrderKeys(['method', 'class', 'caller', 'pattern', 'contentType']);
        $tableView->render();
    }
}