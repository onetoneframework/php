<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Interpreter;

use Clover\Classes\Tokenizer;
use Clover\Interpreter\Parser;

/**
 * InterpreterBuilder
 *
 * Fluent builder that wires together Tokenizer → Parser → Interpreter
 * and lets you register native bindings, built-in functions, and middleware
 * before building a ready-to-run Interpreter instance.
 *
 * Usage:
 *   $interp = InterpreterBuilder::create()
 *       ->withNativeNamespace('console', new ConsoleBinding())
 *       ->withBuiltin('sqrt', fn($x) => sqrt($x))
 *       ->withMaxCallDepth(500)
 *       ->build();
 *
 *   $interp->run($sourceCode);
 */
class InterpreterBuilder
{
    private array $nativeNamespaces = [];   // 'console' => object
    private array $builtinFunctions = [];   // 'sqrt'    => callable
    private array $builtinConstants = [];   // 'PI'      => 3.14...
    private array $middleware = [];   // callables run before each statement
    private int $maxCallDepth = 1000;
    private bool $strictMode = false;

    // -------------------------------------------------------------------------

    public static function create(): self
    {
        return new self();
    }

    /** Register a native namespace object  e.g. console.printLine() */
    public function withNativeNamespace(string $name, object $binding): self
    {
        $this->nativeNamespaces[$name] = $binding;
        return $this;
    }

    /** Register a global built-in function */
    public function withBuiltin(string $name, callable $fn): self
    {
        $this->builtinFunctions[$name] = $fn;
        return $this;
    }

    /** Register a global built-in constant */
    public function withConstant(string $name, mixed $value): self
    {
        $this->builtinConstants[$name] = $value;
        return $this;
    }

    /** Register standard math.* , console.* , etc. presets */
    public function withStandardLibrary(): self
    {
        return $this
            ->withNativeNamespace('math', new Stdlib\MathBinding())
            ->withNativeNamespace('console', new Stdlib\ConsoleBinding());
    }

    /** Middleware called before each AST node evaluation: fn(Node $node, Environment $env) */
    public function withMiddleware(callable $fn): self
    {
        $this->middleware[] = $fn;
        return $this;
    }

    public function withMaxCallDepth(int $depth): self
    {
        $this->maxCallDepth = $depth;
        return $this;
    }

    public function withStrictMode(bool $strict = true): self
    {
        $this->strictMode = $strict;
        return $this;
    }

    public function build(): Interpreter
    {
        $tokenizer = new Tokenizer();
        $parser = new Parser();

        $interpreter = new Interpreter(
            tokenizer: $tokenizer,
            parser: $parser,
            maxDepth: $this->maxCallDepth,
            strictMode: $this->strictMode,
        );

        foreach ($this->nativeNamespaces as $name => $binding) {
            $interpreter->registerNamespace($name, $binding);
        }

        foreach ($this->builtinFunctions as $name => $fn) {
            $interpreter->registerBuiltin($name, $fn);
        }

        foreach ($this->builtinConstants as $name => $value) {
            $interpreter->registerConstant($name, $value);
        }

        foreach ($this->middleware as $fn) {
            $interpreter->addMiddleware($fn);
        }

        return $interpreter;
    }
}
