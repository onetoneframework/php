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
use Clover\Interpreter\AST;

// Signal classes for non-local control flow
class ReturnSignal extends \Exception
{
    public function __construct(public readonly mixed $value)
    {
        parent::__construct();
    }
}
class BreakSignal extends \Exception
{
}
class ContinueSignal extends \Exception
{
}

/**
 * Interpreter
 * Tree-walking AST evaluator.
 * Not constructed directly — use InterpreterBuilder.
 */
class Interpreter
{
    private array $namespaces = [];
    private array $builtins = [];
    private array $constants = [];
    private array $middleware = [];
    private Environment $globals;
    private int $callDepth = 0;

    public function __construct(
        private readonly Tokenizer $tokenizer,
        private readonly Parser $parser,
        private readonly int $maxDepth = 1000,
        private readonly bool $strictMode = false,
    ) {
        $this->globals = new Environment();
    }

    // =========================================================================
    // Public API
    // =========================================================================

    public function run(string $source): mixed
    {
        $tokens = $this->tokenizer->tokenize($source);
        $program = $this->parser->parse($tokens);
        return $this->executeProgram($program, $this->globals);
    }

    public function registerNamespace(string $name, object $binding): void
    {
        $this->namespaces[$name] = $binding;
    }

    public function registerBuiltin(string $name, callable $fn): void
    {
        $this->builtins[$name] = $fn;
    }

    public function registerConstant(string $name, mixed $value): void
    {
        $this->constants[$name] = $value;
        $this->globals->define($name, $value);
    }

    public function addMiddleware(callable $fn): void
    {
        $this->middleware[] = $fn;
    }

    // =========================================================================
    // Program / Block
    // =========================================================================

    private function executeProgram(AST\Program $program, Environment $env): mixed
    {
        $result = null;
        foreach ($program->body as $node) {
            $result = $this->execute($node, $env);
        }
        return $result;
    }

    private function execute(AST\Node $node, Environment $env): mixed
    {
        // Run middleware hooks
        foreach ($this->middleware as $fn) {
            $fn($node, $env);
        }

        return match ($node->getType()) {
            // Declarations
            'FunctionDeclaration' => $this->execFunctionDecl($node, $env),
            'ClassDeclaration' => $this->execClassDecl($node, $env),
            'VariableDeclaration' => $this->execVarDecl($node, $env),
            'EnumDeclaration' => $this->execEnumDecl($node, $env),
            'RecordDeclaration' => $this->execRecordDecl($node, $env),

            // Statements
            'Program' => $this->executeProgram($node, $env),
            'BlockStatement' => $this->execBlock($node, $env),
            'ExpressionStatement' => $this->evaluate($node->expression, $env),
            'ReturnStatement' => $this->execReturn($node, $env),
            'IfStatement' => $this->execIf($node, $env),
            'WhileStatement' => $this->execWhile($node, $env),
            'DoWhileStatement' => $this->execDoWhile($node, $env),
            'ForStatement' => $this->execFor($node, $env),
            'ForOfStatement' => $this->execForOf($node, $env),
            'ForeachStatement' => $this->execForeach($node, $env),
            'SwitchStatement' => $this->execSwitch($node, $env),
            'TryCatchStatement' => $this->execTryCatch($node, $env),
            'ThrowStatement' => throw new \RuntimeException((string) $this->evaluate($node->expression, $env)),
            'BreakStatement' => throw new BreakSignal(),
            'ContinueStatement' => throw new ContinueSignal(),
            'GuardStatement' => $this->execGuard($node, $env),
            'YieldStatement' => $this->evaluate($node->value, $env),   // simplified

            default => $this->evaluate($node, $env),
        };
    }

    private function execBlock(AST\BlockStatement $node, Environment $env): mixed
    {
        $scope = $env->child();
        $result = null;
        foreach ($node->body as $stmt) {
            $result = $this->execute($stmt, $scope);
        }
        return $result;
    }

    // =========================================================================
    // Declarations
    // =========================================================================

    private function execFunctionDecl(AST\FunctionDeclaration $node, Environment $env)
    {
        $env->define($node->name, [
            '__type' => 'function',
            '__node' => $node,
            '__closure' => $env,
        ]);
        return null;
    }

    private function execClassDecl(AST\ClassDeclaration $node, Environment $env)
    {
        $env->define($node->name, [
            '__type' => 'class',
            '__node' => $node,
            '__parent' => $node->parent,
            '__closure' => $env,
        ]);
        return null;
    }

    private function execVarDecl(AST\VariableDeclaration $node, Environment $env)
    {
        $value = $node->initializer !== null ? $this->evaluate($node->initializer, $env) : null;
        $env->define($node->name, $value);
        return null;
    }

    private function execEnumDecl(AST\EnumDeclaration $node, Environment $env)
    {
        $obj = ['__type' => 'enum'];
        foreach ($node->members as $name => $val) {
            $obj[$name] = $val;
        }
        $env->define($node->name, $obj);
        return null;
    }

    private function execRecordDecl(AST\RecordDeclaration $node, Environment $env)
    {
        $env->define($node->name, [
            '__type' => 'record',
            '__fields' => $node->fields,
        ]);
        return null;
    }

    // =========================================================================
    // Control flow
    // =========================================================================

    private function execReturn(AST\ReturnStatement $node, Environment $env): never
    {
        $value = $node->value !== null ? $this->evaluate($node->value, $env) : null;
        throw new ReturnSignal($value);
    }

    private function execIf(AST\IfStatement $node, Environment $env): mixed
    {
        if ($this->isTruthy($this->evaluate($node->condition, $env))) {
            return $this->execute($node->consequent, $env);
        } elseif ($node->alternate !== null) {
            return $this->execute($node->alternate, $env);
        }
        return null;
    }

    private function execWhile(AST\WhileStatement $node, Environment $env)
    {
        while ($this->isTruthy($this->evaluate($node->condition, $env))) {
            try {
                $this->execute($node->body, $env);
            } catch (BreakSignal) {
                break;
            } catch (ContinueSignal) {
                continue;
            }
        }
        return null;
    }

    private function execDoWhile(AST\DoWhileStatement $node, Environment $env)
    {
        do {
            try {
                $this->execute($node->body, $env);
            } catch (BreakSignal) {
                break;
            } catch (ContinueSignal) {
                // continue to condition check
            }
        } while ($this->isTruthy($this->evaluate($node->condition, $env)));
        return null;
    }

    private function execFor(AST\ForStatement $node, Environment $env)
    {
        $scope = $env->child();
        if ($node->init)
            $this->execute($node->init, $scope);

        while (true) {
            if ($node->condition && !$this->isTruthy($this->evaluate($node->condition, $scope)))
                break;

            try {
                $this->execute($node->body, $scope);
            } catch (BreakSignal) {
                break;
            } catch (ContinueSignal) {
                // fall through to update
            }

            if ($node->update)
                $this->evaluate($node->update, $scope);
        }
        return null;
    }

    private function execForOf(AST\ForOfStatement $node, Environment $env)
    {
        $iterable = $this->evaluate($node->iterable, $env);
        $items = $this->toIterable($iterable);

        foreach ($items as $item) {
            $scope = $env->child();
            $scope->define($node->variable, $item);
            try {
                $this->execute($node->body, $scope);
            } catch (BreakSignal) {
                break;
            } catch (ContinueSignal) {
                continue;
            }
        }
        return null;
    }

    private function execForeach(AST\ForeachStatement $node, Environment $env)
    {
        $iterable = $this->evaluate($node->iterable, $env);
        $items = $this->toIterable($iterable);

        foreach ($items as $item) {
            $scope = $env->child();
            $scope->define($node->variable, $item);
            try {
                $this->execute($node->body, $scope);
            } catch (BreakSignal) {
                break;
            } catch (ContinueSignal) {
                continue;
            }
        }
        return null;
    }

    private function execSwitch(AST\SwitchStatement $node, Environment $env)
    {
        $subject = $this->evaluate($node->subject, $env);
        $matched = false;

        foreach ($node->cases as $case) {
            if (!$matched) {
                if ($case->test === null) {       // default
                    $matched = true;
                } else {
                    $matched = ($this->evaluate($case->test, $env) == $subject);
                }
            }

            if ($matched) {
                try {
                    foreach ($case->body as $stmt) {
                        $this->execute($stmt, $env);
                    }
                } catch (BreakSignal) {
                    break;
                }
            }
        }
        return null;
    }

    private function execTryCatch(AST\TryCatchStatement $node, Environment $env)
    {
        try {
            $this->execute($node->tryBlock, $env);
        } catch (ReturnSignal | BreakSignal | ContinueSignal $e) {
            if ($node->finallyBlock)
                $this->execute($node->finallyBlock, $env);
            throw $e;
        } catch (\Throwable $e) {
            if ($node->catchBlock !== null) {
                $scope = $env->child();
                if ($node->catchVar)
                    $scope->define($node->catchVar, $e->getMessage());
                $this->execute($node->catchBlock, $scope);
            }
        } finally {
            if ($node->finallyBlock)
                $this->execute($node->finallyBlock, $env);
        }
        return null;
    }

    private function execGuard(AST\GuardStatement $node, Environment $env)
    {
        if (!$this->isTruthy($this->evaluate($node->condition, $env))) {
            $this->execute($node->elseBlock, $env);
        }
        return null;
    }

    // =========================================================================
    // Expression evaluation
    // =========================================================================

    private function evaluate(AST\Node $node, Environment $env): mixed
    {
        return match ($node->getType()) {
            'IntegerLiteral' => $node->value,
            'FloatLiteral' => $node->value,
            'StringLiteral' => $node->value,
            'BoolLiteral' => $node->value,
            'NullLiteral' => null,
            'ArrayLiteral' => $this->evalArray($node, $env),
            'ObjectLiteral' => $this->evalObject($node, $env),
            'Identifier' => $this->evalIdentifier($node, $env),
            'BinaryExpression' => $this->evalBinary($node, $env),
            'UnaryExpression' => $this->evalUnary($node, $env),
            'AssignmentExpression' => $this->evalAssignment($node, $env),
            'TernaryExpression' => $this->isTruthy($this->evaluate($node->condition, $env))
            ? $this->evaluate($node->consequent, $env)
            : $this->evaluate($node->alternate, $env),
            'NullCoalesceExpression' => $this->evalNullCoalesce($node, $env),
            'CallExpression' => $this->evalCall($node, $env),
            'NewExpression' => $this->evalNew($node, $env),
            'MemberExpression' => $this->evalMember($node, $env),
            'IndexExpression' => $this->evalIndex($node, $env),
            'OptionalChainExpression' => $this->evalOptionalChain($node, $env),
            'LambdaExpression' => $this->evalLambda($node, $env),
            'SpreadExpression' => $this->evaluate($node->expression, $env),
            'PipelineExpression' => $this->evalPipeline($node, $env),
            'TypeCastExpression' => $this->evalTypeCast($node, $env),
            'MatchExpression' => $this->evalMatch($node, $env),
            'RangeExpression' => $this->evalRange($node, $env),

            // Statements that may appear as expressions
            default => $this->execute($node, $env),
        };
    }

    private function evalArray(AST\ArrayLiteral $node, Environment $env): array
    {
        $result = [];
        foreach ($node->elements as $el) {
            if ($el->getType() === 'SpreadExpression') {
                $spread = $this->evaluate($el->expression, $env);
                foreach ($this->toIterable($spread) as $v)
                    $result[] = $v;
            } else {
                $result[] = $this->evaluate($el, $env);
            }
        }
        return $result;
    }

    private function evalObject(AST\ObjectLiteral $node, Environment $env): array
    {
        $obj = ['__type' => 'object'];
        foreach ($node->properties as $key => $val) {
            $obj[$key] = $this->evaluate($val, $env);
        }
        return $obj;
    }

    private function evalIdentifier(AST\Identifier $node, Environment $env): mixed
    {
        // Check constants
        if (isset($this->constants[$node->name]))
            return $this->constants[$node->name];

        // Check native namespaces
        if (isset($this->namespaces[$node->name]))
            return $this->namespaces[$node->name];

        // Check built-in functions
        if (isset($this->builtins[$node->name]))
            return $this->builtins[$node->name];

        return $env->get($node->name);
    }

    private function evalBinary(AST\BinaryExpression $node, Environment $env): mixed
    {
        // Short-circuit for && and ||
        if ($node->operator === '&&') {
            $left = $this->evaluate($node->left, $env);
            return $this->isTruthy($left) ? $this->evaluate($node->right, $env) : $left;
        }

        if ($node->operator === '||') {
            $left = $this->evaluate($node->left, $env);
            return $this->isTruthy($left) ? $left : $this->evaluate($node->right, $env);
        }

        $left = $this->evaluate($node->left, $env);
        $right = $this->evaluate($node->right, $env);

        return match ($node->operator) {
            '+' => is_string($left) || is_string($right) ? ((string) $left . (string) $right) : $left + $right,
            '-' => $left - $right,
            '*' => $left * $right,
            '/' => $right != 0 ? $left / $right : throw new \RuntimeException('Division by zero'),
            '%' => $left % $right,
            '**' => $left ** $right,
            '==' => $left == $right,
            '!=' => $left != $right,
            '===' => $left === $right,
            '!==' => $left !== $right,
            '<' => $left < $right,
            '<=' => $left <= $right,
            '>' => $left > $right,
            '>=' => $left >= $right,
            '&' => (int) $left & (int) $right,
            '|' => (int) $left | (int) $right,
            '^' => (int) $left ^ (int) $right,
            '<<' => (int) $left << (int) $right,
            '>>' => (int) $left >> (int) $right,
            'instanceof' => $this->evalInstanceOf($left, $right),
            'in' => $this->evalIn($left, $right),
            default => throw new \RuntimeException("Unknown operator: {$node->operator}"),
        };
    }

    private function evalUnary(AST\UnaryExpression $node, Environment $env): mixed
    {
        if ($node->operator === '++' || $node->operator === '--') {
            return $this->evalIncDec($node, $env);
        }

        $val = $this->evaluate($node->operand, $env);

        return match ($node->operator) {
            '!' => !$this->isTruthy($val),
            'not' => !$this->isTruthy($val),
            '-' => -$val,
            '~' => ~(int) $val,
            'typeof' => $this->typeOf($val),
            'await' => $val,   // simplified - no async runtime
            default => $val,
        };
    }

    private function evalIncDec(AST\UnaryExpression $node, Environment $env): mixed
    {
        $operand = $node->operand;
        $old = $this->evaluate($operand, $env);
        $new = $node->operator === '++' ? $old + 1 : $old - 1;

        $this->assignTo($operand, $new, $env);

        return $node->prefix ? $new : $old;
    }

    private function evalAssignment(AST\AssignmentExpression $node, Environment $env): mixed
    {
        $right = $this->evaluate($node->right, $env);

        if ($node->operator !== '=') {
            $left = $this->evaluate($node->left, $env);
            $op = substr($node->operator, 0, -1); // '+=' → '+'
            $right = $this->evalBinary(
                new AST\BinaryExpression($op, $node->left, $node->right),
                $env
            );
        }

        $this->assignTo($node->left, $right, $env);
        return $right;
    }

    private function assignTo(AST\Node $target, mixed $value, Environment $env): void
    {
        if ($target instanceof AST\Identifier) {
            $env->set($target->name, $value);
            return;
        }

        if ($target instanceof AST\MemberExpression) {
            $obj = $this->evaluate($target->object, $env);
            $key = $target->property instanceof AST\Identifier
                ? $target->property->name
                : $this->evaluate($target->property, $env);
            if (is_array($obj)) {
                $obj[$key] = $value;
                $this->assignTo($target->object, $obj, $env);
            }
            return;
        }

        if ($target instanceof AST\IndexExpression) {
            $obj = $this->evaluate($target->left, $env);
            $idx = $this->evaluate($target->index, $env);
            if (is_array($obj)) {
                $obj[$idx] = $value;
                $this->assignTo($target->left, $obj, $env);
            }
        }
    }

    private function evalNullCoalesce(AST\NullCoalesceExpression $node, Environment $env): mixed
    {
        $left = $this->evaluate($node->left, $env);
        return $left !== null ? $left : $this->evaluate($node->right, $env);
    }

    private function evalCall(AST\CallExpression $node, Environment $env): mixed
    {
        // Native namespace call:  console.printLine(...)
        if ($node->callee instanceof AST\MemberExpression) {
            $obj = $this->evaluate($node->callee->object, $env);
            $prop = $node->callee->property instanceof AST\Identifier
                ? $node->callee->property->name
                : $this->evaluate($node->callee->property, $env);

            $args = array_map(fn($a) => $this->evaluate($a, $env), $node->arguments);

            // Native namespace binding
            if (is_object($obj) && method_exists($obj, $prop)) {
                return $obj->$prop(...$args);
            }

            // Array methods
            if (is_array($obj)) {
                return $this->callArrayMethod($obj, $prop, $args, $node->callee->object, $env);
            }

            // String methods
            if (is_string($obj)) {
                return $this->callStringMethod($obj, $prop, $args);
            }

            // Class instance method
            if (is_array($obj) && isset($obj['__type']) && $obj['__type'] === 'instance') {
                return $this->callInstanceMethod($obj, $prop, $args, $env);
            }
        }

        // Direct function / built-in call
        $callee = $this->evaluate($node->callee, $env);
        $args = array_map(fn($a) => $this->evaluate($a, $env), $node->arguments);

        return $this->callFunction($callee, $args, $env);
    }

    private function callFunction(mixed $callee, array $args, Environment $env): mixed
    {
        // PHP callable (built-in)
        if (is_callable($callee)) {
            return $callee(...$args);
        }

        // User-defined function / lambda
        if (is_array($callee) && isset($callee['__type'])) {
            if ($callee['__type'] === 'function') {
                return $this->callUserFunction($callee, $args);
            }
            if ($callee['__type'] === 'lambda') {
                return $this->callLambda($callee, $args);
            }
        }

        throw new \RuntimeException('Not a callable: ' . gettype($callee));
    }

    private function callUserFunction(array $fn, array $args): mixed
    {
        if (++$this->callDepth > $this->maxDepth) {
            throw new \RuntimeException('Maximum call depth exceeded');
        }

        /** @var AST\FunctionDeclaration $node */
        $node = $fn['__node'];
        $scope = ($fn['__closure'] instanceof Environment ? $fn['__closure'] : $this->globals)->child();

        foreach ($node->params as $i => $param) {
            $value = $args[$i] ?? ($param['default'] !== null ? $this->evaluate($param['default'], $scope) : null);
            $scope->define($param['name'], $value);
        }

        try {
            $this->execute($node->body, $scope);
            return null;
        } catch (ReturnSignal $r) {
            return $r->value;
        } finally {
            $this->callDepth--;
        }
    }

    private function callLambda(array $fn, array $args): mixed
    {
        /** @var AST\LambdaExpression $node */
        $node = $fn['__node'];
        $scope = ($fn['__closure'] instanceof Environment ? $fn['__closure'] : $this->globals)->child();

        foreach ($node->params as $i => $param) {
            $scope->define($param, $args[$i] ?? null);
        }

        try {
            if ($node->body instanceof AST\BlockStatement) {
                $this->execute($node->body, $scope);
                return null;
            }
            return $this->evaluate($node->body, $scope);
        } catch (ReturnSignal $r) {
            return $r->value;
        }
    }

    private function evalNew(AST\NewExpression $node, Environment $env): array
    {
        $className = $node->callee instanceof AST\Identifier
            ? $node->callee->name
            : $this->evaluate($node->callee, $env);

        $classDef = $env->get($className);
        if (!is_array($classDef) || $classDef['__type'] !== 'class') {
            throw new \RuntimeException("'{$className}' is not a class");
        }

        $args = array_map(fn($a) => $this->evaluate($a, $env), $node->arguments);
        $instance = ['__type' => 'instance', '__class' => $className, '__def' => $classDef];

        // Initialize properties
        /** @var AST\ClassDeclaration $classNode */
        $classNode = $classDef['__node'];
        foreach ($classNode->members as $member) {
            if ($member->value instanceof AST\VariableDeclaration && !$member->isStatic) {
                $instance[$member->name] = $member->value->initializer
                    ? $this->evaluate($member->value->initializer, $env)
                    : null;
            }
        }

        // Call constructor
        $ctor = $this->findMethod($classNode, 'constructor');
        if ($ctor !== null) {
            $scope = ($classDef['__closure'] instanceof Environment ? $classDef['__closure'] : $this->globals)->child();
            $scope->define('this', $instance);

            foreach ($ctor->params as $i => $param) {
                $scope->define($param['name'], $args[$i] ?? null);
            }

            try {
                $this->execute($ctor->body, $scope);
            } catch (ReturnSignal) {
            }

            // Sync 'this' mutations back
            $instance = $scope->get('this');
        }

        return $instance;
    }

    private function callInstanceMethod(array $instance, string $method, array $args, Environment $env): mixed
    {
        $classDef = $instance['__def'];
        $classNode = $classDef['__node'];
        $fn = $this->findMethod($classNode, $method);

        if ($fn === null) {
            throw new \RuntimeException("Method '{$method}' not found on {$instance['__class']}");
        }

        $scope = ($classDef['__closure'] instanceof Environment ? $classDef['__closure'] : $this->globals)->child();
        $scope->define('this', $instance);

        foreach ($fn->params as $i => $param) {
            $scope->define($param['name'], $args[$i] ?? null);
        }

        try {
            $this->execute($fn->body, $scope);
            $result = null;
        } catch (ReturnSignal $r) {
            $result = $r->value;
        }

        // Sync this
        $updatedThis = $scope->get('this');
        if ($fn->joinerTarget) {
            $joiner = $env->get($fn->joinerTarget);
            if ($joiner)
                $result = $this->callFunction($joiner, [$result], $env);
        }

        return $result;
    }

    private function findMethod(AST\ClassDeclaration $class, string $name): ?AST\FunctionDeclaration
    {
        foreach ($class->members as $member) {
            if ($member->name === $name && $member->value instanceof AST\FunctionDeclaration) {
                return $member->value;
            }
        }
        return null;
    }

    private function evalMember(AST\MemberExpression $node, Environment $env): mixed
    {
        $obj = $this->evaluate($node->object, $env);
        $prop = $node->property instanceof AST\Identifier
            ? $node->property->name
            : $this->evaluate($node->property, $env);

        // Native namespace object
        if (is_object($obj)) {
            return property_exists($obj, $prop) ? $obj->$prop : null;
        }

        if (is_array($obj)) {
            // Array length shorthand
            if ($prop === 'length' && isset($obj[0]))
                return count($obj);
            return $obj[$prop] ?? null;
        }

        // String .length
        if (is_string($obj) && $prop === 'length')
            return strlen($obj);

        return null;
    }

    private function evalIndex(AST\IndexExpression $node, Environment $env): mixed
    {
        $obj = $this->evaluate($node->left, $env);
        $idx = $this->evaluate($node->index, $env);

        if (is_array($obj))
            return $obj[$idx] ?? null;
        if (is_string($obj))
            return $obj[$idx] ?? null;

        return null;
    }

    private function evalOptionalChain(AST\OptionalChainExpression $node, Environment $env): mixed
    {
        $obj = $this->evaluate($node->object, $env);
        if ($obj === null)
            return null;

        $prop = $node->property instanceof AST\Identifier
            ? $node->property->name
            : $this->evaluate($node->property, $env);

        if (is_array($obj))
            return $obj[$prop] ?? null;
        if (is_object($obj))
            return property_exists($obj, $prop) ? $obj->$prop : null;

        return null;
    }

    private function evalLambda(AST\LambdaExpression $node, Environment $env): array
    {
        return ['__type' => 'lambda', '__node' => $node, '__closure' => $env];
    }

    private function evalPipeline(AST\PipelineExpression $node, Environment $env): mixed
    {
        $value = $this->evaluate($node->left, $env);
        $fn = $this->evaluate($node->right, $env);
        return $this->callFunction($fn, [$value], $env);
    }

    private function evalTypeCast(AST\TypeCastExpression $node, Environment $env): mixed
    {
        $val = $this->evaluate($node->expression, $env);
        return match ($node->targetType) {
            'integer', 'int' => (int) $val,
            'float', 'double' => (float) $val,
            'string' => (string) $val,
            'bool', 'boolean' => (bool) $val,
            default => $val,
        };
    }

    private function evalMatch(AST\MatchExpression $node, Environment $env): mixed
    {
        $subject = $this->evaluate($node->subject, $env);

        foreach ($node->arms as $arm) {
            if ($this->evaluate($arm['test'], $env) == $subject) {
                return $this->evaluate($arm['value'], $env);
            }
        }

        return $node->default !== null ? $this->evaluate($node->default, $env) : null;
    }

    private function evalRange(AST\RangeExpression $node, Environment $env): array
    {
        $start = (int) $this->evaluate($node->start, $env);
        $end = (int) $this->evaluate($node->end, $env);
        $end = $node->inclusive ? $end : $end - 1;
        return range($start, $end);
    }

    // =========================================================================
    // Array / String built-in methods
    // =========================================================================

    private function callArrayMethod(array &$arr, string $method, array $args, AST\Node $target, Environment $env): mixed
    {
        return match ($method) {
            'length' => count($arr),
            'put' => (function () use (&$arr, $args, $target, $env) {
                    $arr[] = $args[0];
                    $this->assignTo($target, $arr, $env);
                    return null;
                })(),
            'pop' => (function () use (&$arr, $target, $env) {
                    $v = array_pop($arr);
                    $this->assignTo($target, $arr, $env);
                    return $v;
                })(),
            'shift' => (function () use (&$arr, $target, $env) {
                    $v = array_shift($arr);
                    $this->assignTo($target, $arr, $env);
                    return $v;
                })(),
            'unshift' => (function () use (&$arr, $args, $target, $env) {
                    array_unshift($arr, $args[0]);
                    $this->assignTo($target, $arr, $env);
                    return null;
                })(),
            'indexOf' => array_search($args[0], $arr, true) !== false ? array_search($args[0], $arr, true) : -1,
            'contains', 'includes' => in_array($args[0], $arr, true),
            'join' => implode($args[0] ?? '', array_map('strval', $arr)),
            'reverse' => array_reverse($arr),
            'slice' => array_slice($arr, $args[0] ?? 0, ($args[1] ?? count($arr)) - ($args[0] ?? 0)),
            'concat' => array_merge($arr, $args[0] ?? []),
            'sort' => (function () use ($arr) {
                    sort($arr);
                    return $arr; })(),
            'flat', 'flatten' => array_merge(...array_map(fn($v) => is_array($v) ? $v : [$v], $arr)),
            'map' => array_values(array_map(fn($v) => $this->callFunction($args[0], [$v], $env), $arr)),
            'filter' => array_values(array_filter($arr, fn($v) => $this->isTruthy($this->callFunction($args[0], [$v], $env)))),
            'reduce' => array_reduce($arr, fn($acc, $v) => $this->callFunction($args[0], [$acc, $v], $env), $args[1] ?? null),
            'find' => current(array_filter($arr, fn($v) => $this->isTruthy($this->callFunction($args[0], [$v], $env)))) ?: null,
            'findIndex' => (function () use ($arr, $args, $env) {
                    foreach ($arr as $i => $v) {
                        if ($this->isTruthy($this->callFunction($args[0], [$v], $env)))
                            return $i;
                    }
                    return -1;
                })(),
            'every' => !in_array(false, array_map(fn($v) => $this->isTruthy($this->callFunction($args[0], [$v], $env)), $arr), true),
            'some' => in_array(true, array_map(fn($v) => $this->isTruthy($this->callFunction($args[0], [$v], $env)), $arr), true),
            'forEach' => (function () use ($arr, $args, $env) {
                    foreach ($arr as $v)
                        $this->callFunction($args[0], [$v], $env);
                    return null; })(),
            default => throw new \RuntimeException("Array method '{$method}' not found"),
        };
    }

    private function callStringMethod(string $str, string $method, array $args): mixed
    {
        return match ($method) {
            'length' => strlen($str),
            'toUpperCase' => strtoupper($str),
            'toLowerCase' => strtolower($str),
            'trim' => trim($str),
            'trimStart' => ltrim($str),
            'trimEnd' => rtrim($str),
            'indexOf' => ($p = strpos($str, $args[0] ?? '')) !== false ? $p : -1,
            'includes' => str_contains($str, $args[0] ?? ''),
            'startsWith' => str_starts_with($str, $args[0] ?? ''),
            'endsWith' => str_ends_with($str, $args[0] ?? ''),
            'substring' => substr($str, $args[0] ?? 0, ($args[1] ?? strlen($str)) - ($args[0] ?? 0)),
            'slice' => substr($str, $args[0] ?? 0, $args[1] ?? null),
            'split' => explode($args[0] ?? '', $str),
            'replace' => str_replace($args[0] ?? '', $args[1] ?? '', $str),
            'replaceAll' => str_replace($args[0] ?? '', $args[1] ?? '', $str),
            'repeat' => str_repeat($str, $args[0] ?? 1),
            'charAt' => $str[$args[0] ?? 0] ?? '',
            'charCodeAt' => ord($str[$args[0] ?? 0] ?? ''),
            'padStart' => str_pad($str, $args[0] ?? 0, $args[1] ?? ' ', STR_PAD_LEFT),
            'padEnd' => str_pad($str, $args[0] ?? 0, $args[1] ?? ' ', STR_PAD_RIGHT),
            'toString' => $str,
            default => throw new \RuntimeException("String method '{$method}' not found"),
        };
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function evalInstanceOf(mixed $obj, mixed $className): bool
    {
        if (!is_array($obj) || $obj['__type'] !== 'instance')
            return false;
        return $obj['__class'] === (string) $className;
    }

    private function evalIn(mixed $needle, mixed $haystack): bool
    {
        if (is_array($haystack))
            return in_array($needle, $haystack, true);
        if (is_string($haystack))
            return str_contains($haystack, (string) $needle);
        return false;
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === false || $value === 0 || $value === '')
            return false;
        return true;
    }

    private function toIterable(mixed $value): array
    {
        if (is_array($value))
            return array_values($value);
        if (is_string($value))
            return str_split($value);
        return [];
    }

    private function typeOf(mixed $value): string
    {
        if ($value === null)
            return 'null';
        if (is_bool($value))
            return 'boolean';
        if (is_int($value) || is_float($value))
            return 'number';
        if (is_string($value))
            return 'string';
        if (is_array($value)) {
            if (isset($value['__type']) && $value['__type'] === 'function')
                return 'function';
            if (isset($value['__type']) && $value['__type'] === 'lambda')
                return 'function';
            return 'array';
        }
        if (is_callable($value))
            return 'function';
        return 'object';
    }
}
