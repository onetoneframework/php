<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Interpreter\AST;

// =============================================================================
// Base Node
// =============================================================================

abstract class Node
{
    public int $line = 0;
    public int $column = 0;

    abstract public function getType(): string;
}

// =============================================================================
// Literals
// =============================================================================

class IntegerLiteral extends Node
{
    public function __construct(public int $value)
    {
    }
    public function getType(): string
    {
        return 'IntegerLiteral';
    }
}

class FloatLiteral extends Node
{
    public function __construct(public float $value)
    {
    }
    public function getType(): string
    {
        return 'FloatLiteral';
    }
}

class StringLiteral extends Node
{
    public function __construct(public string $value)
    {
    }
    public function getType(): string
    {
        return 'StringLiteral';
    }
}

class BoolLiteral extends Node
{
    public function __construct(public bool $value)
    {
    }
    public function getType(): string
    {
        return 'BoolLiteral';
    }
}

class NullLiteral extends Node
{
    public function getType(): string
    {
        return 'NullLiteral';
    }
}

class ArrayLiteral extends Node
{
    /** @param Node[] $elements */
    public function __construct(public array $elements = [])
    {
    }
    public function getType(): string
    {
        return 'ArrayLiteral';
    }
}

class ObjectLiteral extends Node
{
    /** @param array<string, Node> $properties */
    public function __construct(public array $properties = [])
    {
    }
    public function getType(): string
    {
        return 'ObjectLiteral';
    }
}

// =============================================================================
// Identifiers & Access
// =============================================================================

class Identifier extends Node
{
    public function __construct(public string $name)
    {
    }
    public function getType(): string
    {
        return 'Identifier';
    }
}

class MemberExpression extends Node
{
    public function __construct(
        public Node $object,
        public Node $property,
        public bool $computed = false  // obj[expr] vs obj.prop
    ) {
    }
    public function getType(): string
    {
        return 'MemberExpression';
    }
}

class IndexExpression extends Node
{
    public function __construct(public Node $left, public Node $index)
    {
    }
    public function getType(): string
    {
        return 'IndexExpression';
    }
}

// =============================================================================
// Expressions
// =============================================================================

class BinaryExpression extends Node
{
    public function __construct(
        public string $operator,
        public Node $left,
        public Node $right
    ) {
    }
    public function getType(): string
    {
        return 'BinaryExpression';
    }
}

class UnaryExpression extends Node
{
    public function __construct(
        public string $operator,
        public Node $operand,
        public bool $prefix = true
    ) {
    }
    public function getType(): string
    {
        return 'UnaryExpression';
    }
}

class TernaryExpression extends Node
{
    public function __construct(
        public Node $condition,
        public Node $consequent,
        public Node $alternate
    ) {
    }
    public function getType(): string
    {
        return 'TernaryExpression';
    }
}

class AssignmentExpression extends Node
{
    public function __construct(
        public string $operator,  // =, +=, -=, *=, ...
        public Node $left,
        public Node $right
    ) {
    }
    public function getType(): string
    {
        return 'AssignmentExpression';
    }
}

class CallExpression extends Node
{
    /** @param Node[] $arguments */
    public function __construct(
        public Node $callee,
        public array $arguments = []
    ) {
    }
    public function getType(): string
    {
        return 'CallExpression';
    }
}

class NewExpression extends Node
{
    /** @param Node[] $arguments */
    public function __construct(
        public Node $callee,
        public array $arguments = []
    ) {
    }
    public function getType(): string
    {
        return 'NewExpression';
    }
}

class LambdaExpression extends Node
{
    /** @param string[] $params */
    public function __construct(
        public array $params,
        public Node $body       // BlockStatement or expression
    ) {
    }
    public function getType(): string
    {
        return 'LambdaExpression';
    }
}

class SpreadExpression extends Node
{
    public function __construct(public Node $expression)
    {
    }
    public function getType(): string
    {
        return 'SpreadExpression';
    }
}

class NullCoalesceExpression extends Node
{
    public function __construct(public Node $left, public Node $right)
    {
    }
    public function getType(): string
    {
        return 'NullCoalesceExpression';
    }
}

class OptionalChainExpression extends Node
{
    public function __construct(public Node $object, public Node $property)
    {
    }
    public function getType(): string
    {
        return 'OptionalChainExpression';
    }
}

class PipelineExpression extends Node
{
    public function __construct(public Node $left, public Node $right)
    {
    }
    public function getType(): string
    {
        return 'PipelineExpression';
    }
}

class TypeCastExpression extends Node
{
    public function __construct(public Node $expression, public string $targetType)
    {
    }
    public function getType(): string
    {
        return 'TypeCastExpression';
    }
}

class MatchExpression extends Node
{
    /** @param array<mixed, Node> $arms */
    public function __construct(
        public Node $subject,
        public array $arms,
        public ?Node $default = null
    ) {
    }
    public function getType(): string
    {
        return 'MatchExpression';
    }
}

class RangeExpression extends Node
{
    public function __construct(
        public Node $start,
        public Node $end,
        public bool $inclusive = false
    ) {
    }
    public function getType(): string
    {
        return 'RangeExpression';
    }
}

// =============================================================================
// Statements
// =============================================================================

class Program extends Node
{
    /** @param Node[] $body */
    public function __construct(public array $body = [])
    {
    }
    public function getType(): string
    {
        return 'Program';
    }
}

class BlockStatement extends Node
{
    /** @param Node[] $body */
    public function __construct(public array $body = [])
    {
    }
    public function getType(): string
    {
        return 'BlockStatement';
    }
}

class ExpressionStatement extends Node
{
    public function __construct(public Node $expression)
    {
    }
    public function getType(): string
    {
        return 'ExpressionStatement';
    }
}

class VariableDeclaration extends Node
{
    public function __construct(
        public string $kind,       // var, let, const, integer, string, float, ...
        public string $name,
        public ?Node $initializer = null,
        public ?string $typeAnnotation = null
    ) {
    }
    public function getType(): string
    {
        return 'VariableDeclaration';
    }
}

class FunctionDeclaration extends Node
{
    /** @param array<array{name:string, default:Node|null}> $params */
    public function __construct(
        public string $name,
        public array $params,
        public Node $body,
        public bool $isAsync = false,
        public bool $isStatic = false,
        public bool $isGenerator = false,
        public ?string $returnType = null,
        public ?string $joinerTarget = null
    ) {
    }
    public function getType(): string
    {
        return 'FunctionDeclaration';
    }
}

class ReturnStatement extends Node
{
    public function __construct(public ?Node $value = null)
    {
    }
    public function getType(): string
    {
        return 'ReturnStatement';
    }
}

class IfStatement extends Node
{
    public function __construct(
        public Node $condition,
        public Node $consequent,
        public ?Node $alternate = null
    ) {
    }
    public function getType(): string
    {
        return 'IfStatement';
    }
}

class WhileStatement extends Node
{
    public function __construct(public Node $condition, public Node $body)
    {
    }
    public function getType(): string
    {
        return 'WhileStatement';
    }
}

class DoWhileStatement extends Node
{
    public function __construct(public Node $body, public Node $condition)
    {
    }
    public function getType(): string
    {
        return 'DoWhileStatement';
    }
}

class ForStatement extends Node
{
    public function __construct(
        public ?Node $init,
        public ?Node $condition,
        public ?Node $update,
        public Node $body
    ) {
    }
    public function getType(): string
    {
        return 'ForStatement';
    }
}

class ForOfStatement extends Node
{
    public function __construct(
        public string $variable,
        public Node $iterable,
        public Node $body
    ) {
    }
    public function getType(): string
    {
        return 'ForOfStatement';
    }
}

class ForeachStatement extends Node
{
    public function __construct(
        public string $variable,
        public Node $iterable,
        public Node $body
    ) {
    }
    public function getType(): string
    {
        return 'ForeachStatement';
    }
}

class SwitchStatement extends Node
{
    /** @param SwitchCase[] $cases */
    public function __construct(public Node $subject, public array $cases)
    {
    }
    public function getType(): string
    {
        return 'SwitchStatement';
    }
}

class SwitchCase extends Node
{
    /** @param Node[] $body */
    public function __construct(
        public ?Node $test,   // null = default
        public array $body
    ) {
    }
    public function getType(): string
    {
        return 'SwitchCase';
    }
}

class BreakStatement extends Node
{
    public function getType(): string
    {
        return 'BreakStatement';
    }
}

class ContinueStatement extends Node
{
    public function getType(): string
    {
        return 'ContinueStatement';
    }
}

class ThrowStatement extends Node
{
    public function __construct(public Node $expression)
    {
    }
    public function getType(): string
    {
        return 'ThrowStatement';
    }
}

class TryCatchStatement extends Node
{
    public function __construct(
        public Node $tryBlock,
        public ?string $catchVar = null,
        public ?Node $catchBlock = null,
        public ?Node $finallyBlock = null
    ) {
    }
    public function getType(): string
    {
        return 'TryCatchStatement';
    }
}

class GuardStatement extends Node
{
    public function __construct(public Node $condition, public Node $elseBlock)
    {
    }
    public function getType(): string
    {
        return 'GuardStatement';
    }
}

class YieldStatement extends Node
{
    public function __construct(public ?Node $value = null)
    {
    }
    public function getType(): string
    {
        return 'YieldStatement';
    }
}

// =============================================================================
// Class / OOP
// =============================================================================

class ClassDeclaration extends Node
{
    /** @param ClassMember[] $members */
    public function __construct(
        public string $name,
        public ?string $parent = null,
        public array $members = []
    ) {
    }
    public function getType(): string
    {
        return 'ClassDeclaration';
    }
}

class ClassMember extends Node
{
    public function __construct(
        public string $name,
        public Node $value,     // FunctionDeclaration or VariableDeclaration
        public bool $isStatic = false,
        public string $visibility = 'public'  // public, private, protected
    ) {
    }
    public function getType(): string
    {
        return 'ClassMember';
    }
}

// =============================================================================
// Other declarations
// =============================================================================

class EnumDeclaration extends Node
{
    /** @param array<string, int|null> $members */
    public function __construct(public string $name, public array $members)
    {
    }
    public function getType(): string
    {
        return 'EnumDeclaration';
    }
}

class RecordDeclaration extends Node
{
    /** @param array<string, string> $fields  name => type */
    public function __construct(public string $name, public array $fields)
    {
    }
    public function getType(): string
    {
        return 'RecordDeclaration';
    }
}
