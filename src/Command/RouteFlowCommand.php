<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

use Clover\Classes\Debug\ControlFlow\AsciiFlowRenderer;
use Clover\Classes\Debug\ControlFlow\MethodFlowExtractor;
use Clover\Classes\Routing\Router;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;

class RouteFlowCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'route:flow';
    }
    public function getDescription(): string
    {
        return 'Display control flow diagram of route controller methods';
    }
    public function configure(): void
    {
    }

    public function run(Input $input): bool
    {
        $filter = method_exists($input, 'getArgument') ? ($input->getArgument(0) ?? null) : null;

        $router = new Router();
        $router->fromDirectory(sprintf('%s/App/Controller', BASE_PATH));
        $routeMap = $router->map();

        $extractor = new MethodFlowExtractor();
        $renderer = new AsciiFlowRenderer();

        foreach ($routeMap as $route) {
            $class = (string) ($route['class'] ?? '');
            $caller = (string) ($route['caller'] ?? '');
            $pattern = (string) ($route['pattern'] ?? '');
            $method = strtoupper((string) ($route['method'] ?? 'ANY'));

            if ($class === '' || $caller === '')
                continue;
            if ($filter !== null && !str_contains($class . '::' . $caller, $filter))
                continue;

            try {
                $ref = new \ReflectionClass($class);
                $file = $ref->getFileName();
            } catch (\ReflectionException) {
                continue;
            }

            if ($file === false || !file_exists($file))
                continue;

            $shortClass = ltrim(strrchr($class, '\\') ?: ('\\' . $class), '\\');
            $node = $extractor->extract($file, $shortClass, $caller);

            if ($node === null) {
                printf("  [WARN] Could not extract: %s::%s\n", $class, $caller);
                continue;
            }

            printf("\n%s %s\n", $method, $pattern);
            echo $renderer->render($node) . "\n";
        }

        return true;
    }
}
