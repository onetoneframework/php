<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Interpreter;

use Clover\Classes\TokenObject;
use Clover\Interpreter\AST;

use function in_array;
use function is_int;

class Parser
{
    private array $tokens = [];
    private int $pos = 0;

    // Operator precedence table
    private const PRECEDENCE = [
        '||' => 1,
        '&&' => 2,
        '|' => 3,
        '^' => 4,
        '&' => 5,
        '==' => 6,
        '!=' => 6,
        '===' => 6,
        '!==' => 6,
        '<' => 7,
        '<=' => 7,
        '>' => 7,
        '>=' => 7,
        '<<' => 8,
        '>>' => 8,
        '+' => 9,
        '-' => 9,
        '*' => 10,
        '/' => 10,
        '%' => 10,
        '**' => 11,
        '??' => 0,
        '|>' => 0,
        '..' => 0,
        '...' => 0,
    ];

    private const ASSIGN_OPS = ['=', '+=', '-=', '*=', '/=', '%=', '**=', '&=', '|=', '^=', '<<=', '>>='];

    private const TYPE_KEYWORDS = [
        'integer',
        'float',
        'string',
        'bool',
        'boolean',
        'array',
        'object',
        'var',
        'let',
        'const',
    ];

    public function parse(array $tokens): AST\Program
    {
        $this->tokens = $tokens;
        $this->pos = 0;

        $body = [];
        while (!$this->isEOF()) {
            $this->skipSemicolons();
            if ($this->isEOF()) {
                break;
            }
            $body[] = $this->parseStatement();
            $this->skipSemicolons();
        }

        return new AST\Program($body);
    }

    // =========================================================================
    // Statements
    // =========================================================================

    private function parseStatement(): AST\Node
    {
        $tok = $this->peek();

        if ($tok->isKeyword('function'))
            return $this->parseFunctionDeclaration();
        if ($tok->isKeyword('async'))
            return $this->parseAsyncFunction();
        if ($tok->isKeyword('class'))
            return $this->parseClassDeclaration();
        if ($tok->isKeyword('return'))
            return $this->parseReturn();
        if ($tok->isKeyword('if'))
            return $this->parseIf();
        if ($tok->isKeyword('while'))
            return $this->parseWhile();
        if ($tok->isKeyword('do'))
            return $this->parseDoWhile();
        if ($tok->isKeyword('for'))
            return $this->parseFor();
        if ($tok->isKeyword('foreach'))
            return $this->parseForeach();
        if ($tok->isKeyword('switch'))
            return $this->parseSwitch();
        if ($tok->isKeyword('try'))
            return $this->parseTryCatch();
        if ($tok->isKeyword('throw'))
            return $this->parseThrow();
        if ($tok->isKeyword('break')) {
            $this->advance();
            return new AST\BreakStatement();
        }
        if ($tok->isKeyword('continue')) {
            $this->advance();
            return new AST\ContinueStatement();
        }
        if ($tok->isKeyword('guard'))
            return $this->parseGuard();
        if ($tok->isKeyword('yield'))
            return $this->parseYield();
        if ($tok->isKeyword('enum'))
            return $this->parseEnum();
        if ($tok->isKeyword('record'))
            return $this->parseRecord();
        if ($tok->isPunct('{'))
            return $this->parseBlock();

        // generator function
        if ($tok->is(TokenObject::T_IDENTIFIER) && $tok->getValue() === 'generator') {
            return $this->parseFunctionDeclaration(isGenerator: true);
        }

        // Type keyword  → variable declaration
        if (
            in_array($tok->getValue(), self::TYPE_KEYWORDS, true) &&
            ($tok->is(TokenObject::T_KEYWORD) || $tok->is(TokenObject::T_IDENTIFIER))
        ) {
            return $this->parseVariableDeclaration();
        }

        return new AST\ExpressionStatement($this->parseExpression());
    }

    private function parseBlock(): AST\BlockStatement
    {
        $this->expect('{');
        $body = [];
        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            $this->skipSemicolons();
            if ($this->peek()->isPunct('}'))
                break;
            $body[] = $this->parseStatement();
            $this->skipSemicolons();
        }
        $this->expect('}');
        return new AST\BlockStatement($body);
    }

    private function parseVariableDeclaration(): AST\VariableDeclaration
    {
        $kind = $this->advance()->getValue();  // var / let / const / integer / ...
        $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();

        $init = null;
        if ($this->peek()->isOperator('=')) {
            $this->advance();
            $init = $this->parseExpression();
        }

        return new AST\VariableDeclaration($kind, $name, $init, $kind);
    }

    private function parseFunctionDeclaration(bool $isAsync = false, bool $isGenerator = false): AST\FunctionDeclaration
    {
        if ($this->peek()->getValue() === 'generator')
            $this->advance();
        $this->expectKeyword('function');

        // optional * for generator
        if ($this->peek()->isOperator('*'))
            $this->advance();

        $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        $params = $this->parseFunctionParams();

        // Return type annotation
        $returnType = null;
        if ($this->peek()->isPunct(':')) {
            $this->advance();
            $returnType = $this->advance()->getValue();
        }

        // Joiner  ~> targetFunction
        $joiner = null;
        if ($this->peek()->isOperator('~>')) {
            $this->advance();
            $joiner = $this->advance()->getValue();
        }

        $body = $this->parseBlock();
        return new AST\FunctionDeclaration($name, $params, $body, $isAsync, false, $isGenerator, $returnType, $joiner);
    }

    private function parseAsyncFunction(): AST\FunctionDeclaration
    {
        $this->expectKeyword('async');
        return $this->parseFunctionDeclaration(isAsync: true);
    }

    private function parseFunctionParams(): array
    {
        $this->expect('(');
        $params = [];

        while (!$this->peek()->isPunct(')') && !$this->isEOF()) {
            // Optional type hint
            $typeHint = null;
            $next = $this->peek();

            if (
                in_array($next->getValue(), self::TYPE_KEYWORDS, true) ||
                $next->is(TokenObject::T_IDENTIFIER)
            ) {
                // Could be type hint or param name - look ahead
                if ($this->peekAt(1)->is(TokenObject::T_IDENTIFIER)) {
                    $typeHint = $this->advance()->getValue();
                }
            }

            $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
            $default = null;

            if ($this->peek()->isOperator('=')) {
                $this->advance();
                $default = $this->parseExpression();
            }

            $params[] = ['name' => $name, 'type' => $typeHint, 'default' => $default];

            if (!$this->peek()->isPunct(','))
                break;
            $this->advance(); // consume ','
        }

        $this->expect(')');
        return $params;
    }

    private function parseReturn(): AST\ReturnStatement
    {
        $this->expectKeyword('return');
        $value = null;
        if (!$this->peek()->isPunct(';') && !$this->peek()->isPunct('}') && !$this->isEOF()) {
            $value = $this->parseExpression();
        }
        return new AST\ReturnStatement($value);
    }

    private function parseIf(): AST\IfStatement
    {
        $this->expectKeyword('if');
        $this->expect('(');
        $condition = $this->parseExpression();
        $this->expect(')');

        $consequent = $this->parseBlock();
        $alternate = null;

        if ($this->peek()->isKeyword('else')) {
            $this->advance();
            $alternate = $this->peek()->isKeyword('if')
                ? $this->parseIf()
                : $this->parseBlock();
        }

        return new AST\IfStatement($condition, $consequent, $alternate);
    }

    private function parseWhile(): AST\WhileStatement
    {
        $this->expectKeyword('while');
        $this->expect('(');
        $condition = $this->parseExpression();
        $this->expect(')');
        return new AST\WhileStatement($condition, $this->parseBlock());
    }

    private function parseDoWhile(): AST\DoWhileStatement
    {
        $this->expectKeyword('do');
        $body = $this->parseBlock();
        $this->expectKeyword('while');
        $this->expect('(');
        $condition = $this->parseExpression();
        $this->expect(')');
        return new AST\DoWhileStatement($body, $condition);
    }

    private function parseFor(): AST\Node
    {
        $this->expectKeyword('for');
        $this->expect('(');

        // for (item of iterable) or for (item in iterable)
        if (
            $this->peek()->is(TokenObject::T_IDENTIFIER) &&
            ($this->peekAt(1)->isKeyword('of') || $this->peekAt(1)->isKeyword('in'))
        ) {
            $var = $this->advance()->getValue();
            $this->advance(); // 'of' or 'in'
            $iterable = $this->parseExpression();
            $this->expect(')');
            return new AST\ForOfStatement($var, $iterable, $this->parseBlock());
        }

        // for (type varName of iterable)
        if (
            in_array($this->peek()->getValue(), self::TYPE_KEYWORDS, true) &&
            $this->peekAt(1)->is(TokenObject::T_IDENTIFIER) &&
            ($this->peekAt(2)->isKeyword('of') || $this->peekAt(2)->isKeyword('in'))
        ) {
            $this->advance(); // type
            $var = $this->advance()->getValue();
            $this->advance(); // of / in
            $iterable = $this->parseExpression();
            $this->expect(')');
            return new AST\ForOfStatement($var, $iterable, $this->parseBlock());
        }

        // Standard for (init; cond; update)
        $init = null;
        if (!$this->peek()->isPunct(';')) {
            $stmt = $this->parseStatement();
            $init = $stmt;
        }
        $this->skipSemicolons();

        $cond = null;
        if (!$this->peek()->isPunct(';')) {
            $cond = $this->parseExpression();
        }
        $this->skipSemicolons();

        $update = null;
        if (!$this->peek()->isPunct(')')) {
            $update = $this->parseExpression();
        }
        $this->expect(')');

        return new AST\ForStatement($init, $cond, $update, $this->parseBlock());
    }

    private function parseForeach(): AST\ForeachStatement
    {
        $this->expectKeyword('foreach');
        $this->expect('(');
        $var = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        $this->expect(':');
        $iterable = $this->parseExpression();
        $this->expect(')');
        return new AST\ForeachStatement($var, $iterable, $this->parseBlock());
    }

    private function parseSwitch(): AST\SwitchStatement
    {
        $this->expectKeyword('switch');
        $this->expect('(');
        $subject = $this->parseExpression();
        $this->expect(')');
        $this->expect('{');

        $cases = [];
        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            if ($this->peek()->isKeyword('case')) {
                $this->advance();
                $test = $this->parseExpression();
                $this->expect(':');
                $body = $this->parseSwitchBody();
                $cases[] = new AST\SwitchCase($test, $body);
            } elseif ($this->peek()->isKeyword('default')) {
                $this->advance();
                $this->expect(':');
                $body = $this->parseSwitchBody();
                $cases[] = new AST\SwitchCase(null, $body);
            } else {
                break;
            }
        }

        $this->expect('}');
        return new AST\SwitchStatement($subject, $cases);
    }

    private function parseSwitchBody(): array
    {
        $body = [];
        while (
            !$this->peek()->isKeyword('case') &&
            !$this->peek()->isKeyword('default') &&
            !$this->peek()->isPunct('}') &&
            !$this->isEOF()
        ) {
            $this->skipSemicolons();
            if (
                $this->peek()->isKeyword('case') ||
                $this->peek()->isKeyword('default') ||
                $this->peek()->isPunct('}')
            )
                break;
            $body[] = $this->parseStatement();
            $this->skipSemicolons();
        }
        return $body;
    }

    private function parseTryCatch(): AST\TryCatchStatement
    {
        $this->expectKeyword('try');
        $try = $this->parseBlock();
        $catchVar = null;
        $catchBlock = null;
        $finallyBlock = null;

        if ($this->peek()->isKeyword('catch')) {
            $this->advance();
            $this->expect('(');
            $catchVar = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
            $this->expect(')');
            $catchBlock = $this->parseBlock();
        }

        if ($this->peek()->isKeyword('finally')) {
            $this->advance();
            $finallyBlock = $this->parseBlock();
        }

        return new AST\TryCatchStatement($try, $catchVar, $catchBlock, $finallyBlock);
    }

    private function parseThrow(): AST\ThrowStatement
    {
        $this->expectKeyword('throw');
        return new AST\ThrowStatement($this->parseExpression());
    }

    private function parseGuard(): AST\GuardStatement
    {
        $this->expectKeyword('guard');
        $condition = $this->parseExpression();
        $this->expectKeyword('else');
        return new AST\GuardStatement($condition, $this->parseBlock());
    }

    private function parseYield(): AST\YieldStatement
    {
        $this->expectKeyword('yield');
        $value = null;
        if (!$this->peek()->isPunct(';') && !$this->peek()->isPunct('}')) {
            $value = $this->parseExpression();
        }
        return new AST\YieldStatement($value);
    }

    private function parseEnum(): AST\EnumDeclaration
    {
        $this->expectKeyword('enum');
        $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        $this->expect('{');

        $members = [];
        $autoVal = 0;

        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            $memberName = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
            $val = null;

            if ($this->peek()->isOperator('=')) {
                $this->advance();
                $val = (int) $this->expect(TokenObject::T_INTEGER)->getValue();
                $autoVal = $val + 1;
            } else {
                $val = $autoVal++;
            }

            $members[$memberName] = $val;

            if ($this->peek()->isPunct(','))
                $this->advance();
        }

        $this->expect('}');
        return new AST\EnumDeclaration($name, $members);
    }

    private function parseRecord(): AST\RecordDeclaration
    {
        $this->expectKeyword('record');
        $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        $this->expect('{');

        $fields = [];
        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            $type = $this->advance()->getValue();
            $fieldName = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
            $fields[$fieldName] = $type;
            $this->skipSemicolons();
        }

        $this->expect('}');
        return new AST\RecordDeclaration($name, $fields);
    }

    private function parseClassDeclaration(): AST\ClassDeclaration
    {
        $this->expectKeyword('class');
        $name = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        $parent = null;

        if ($this->peek()->isKeyword('extends')) {
            $this->advance();
            $parent = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        }

        $this->expect('{');
        $members = [];

        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            $this->skipSemicolons();
            if ($this->peek()->isPunct('}'))
                break;
            $members[] = $this->parseClassMember();
            $this->skipSemicolons();
        }

        $this->expect('}');
        return new AST\ClassDeclaration($name, $parent, $members);
    }

    private function parseClassMember(): AST\ClassMember
    {
        $visibility = 'public';
        $isStatic = false;

        if ($this->peek()->isKeyword('public') || $this->peek()->isKeyword('private') || $this->peek()->isKeyword('protected')) {
            $visibility = $this->advance()->getValue();
        }

        if ($this->peek()->getValue() === 'static') {
            $isStatic = true;
            $this->advance();
        }

        $isFn = $this->peek()->isKeyword('function');

        if ($isFn) {
            $fn = $this->parseFunctionDeclaration(isAsync: false);
            return new AST\ClassMember($fn->name, $fn, $isStatic, $visibility);
        }

        // Property
        $decl = $this->parseVariableDeclaration();
        return new AST\ClassMember($decl->name, $decl, $isStatic, $visibility);
    }

    // =========================================================================
    // Expressions  (Pratt / precedence climbing)
    // =========================================================================

    private function parseExpression(): AST\Node
    {
        return $this->parseAssignment();
    }

    private function parseAssignment(): AST\Node
    {
        $left = $this->parseTernary();

        if (
            $this->peek()->is(TokenObject::T_OPERATOR) &&
            in_array($this->peek()->getValue(), self::ASSIGN_OPS, true)
        ) {
            $op = $this->advance()->getValue();
            $right = $this->parseAssignment();
            return new AST\AssignmentExpression($op, $left, $right);
        }

        return $left;
    }

    private function parseTernary(): AST\Node
    {
        $cond = $this->parseBinary(0);

        if ($this->peek()->isOperator('?')) {
            $this->advance();
            $consequent = $this->parseExpression();
            $this->expect(':');
            $alternate = $this->parseTernary();
            return new AST\TernaryExpression($cond, $consequent, $alternate);
        }

        return $cond;
    }

    private function parseBinary(int $minPrec): AST\Node
    {
        $left = $this->parseUnary();

        while (true) {
            $tok = $this->peek();
            $op = $tok->getValue();

            if (!isset(self::PRECEDENCE[$op]))
                break;
            $prec = self::PRECEDENCE[$op];
            if ($prec <= $minPrec)
                break;

            $this->advance();

            // null coalesce
            if ($op === '??') {
                $right = $this->parseBinary($prec);
                $left = new AST\NullCoalesceExpression($left, $right);
                continue;
            }

            // pipeline
            if ($op === '|>') {
                $right = $this->parseBinary($prec);
                $left = new AST\PipelineExpression($left, $right);
                continue;
            }

            // range
            if ($op === '..' || $op === '...') {
                $right = $this->parseBinary($prec);
                $left = new AST\RangeExpression($left, $right, $op === '...');
                continue;
            }

            $right = $this->parseBinary($prec);
            $left = new AST\BinaryExpression($op, $left, $right);
        }

        return $left;
    }

    private function parseUnary(): AST\Node
    {
        $tok = $this->peek();

        // Prefix unary
        if (
            $tok->isOperator('!') || $tok->isOperator('-') || $tok->isOperator('~') ||
            $tok->isOperator('++') || $tok->isOperator('--') ||
            $tok->isKeyword('typeof') || $tok->isKeyword('not')
        ) {
            $op = $this->advance()->getValue();
            $expr = $this->parseUnary();
            return new AST\UnaryExpression($op, $expr, true);
        }

        // await
        if ($tok->isKeyword('await')) {
            $this->advance();
            return new AST\UnaryExpression('await', $this->parseUnary(), true);
        }

        return $this->parsePostfix();
    }

    private function parsePostfix(): AST\Node
    {
        $expr = $this->parseCallMember();

        // Post-increment / decrement
        if ($this->peek()->isOperator('++') || $this->peek()->isOperator('--')) {
            $op = $this->advance()->getValue();
            return new AST\UnaryExpression($op, $expr, false);
        }

        // Type cast:  expr as type
        if ($this->peek()->isKeyword('as')) {
            $this->advance();
            $type = $this->advance()->getValue();
            return new AST\TypeCastExpression($expr, $type);
        }

        // instanceof
        if ($this->peek()->isKeyword('instanceof')) {
            $this->advance();
            $type = $this->advance()->getValue();
            return new AST\BinaryExpression('instanceof', $expr, new AST\Identifier($type));
        }

        // in
        if ($this->peek()->isKeyword('in')) {
            $this->advance();
            $right = $this->parsePrimary();
            return new AST\BinaryExpression('in', $expr, $right);
        }

        return $expr;
    }

    private function parseCallMember(): AST\Node
    {
        $expr = $this->parsePrimary();

        while (true) {
            $tok = $this->peek();

            // function call
            if ($tok->isPunct('(')) {
                $args = $this->parseArguments();
                $expr = new AST\CallExpression($expr, $args);
                continue;
            }

            // member access  obj.prop
            if ($tok->isOperator('.')) {
                $this->advance();
                $prop = $this->expect(TokenObject::T_IDENTIFIER);
                $expr = new AST\MemberExpression($expr, new AST\Identifier($prop->getValue()), false);
                continue;
            }

            // optional chain  obj?.prop
            if ($tok->isOperator('?.')) {
                $this->advance();
                $prop = $this->expect(TokenObject::T_IDENTIFIER);
                $expr = new AST\OptionalChainExpression($expr, new AST\Identifier($prop->getValue()));
                continue;
            }

            // index access  obj[expr]
            if ($tok->isPunct('[')) {
                $this->advance();
                $index = $this->parseExpression();
                $this->expect(']');
                $expr = new AST\IndexExpression($expr, $index);
                continue;
            }

            break;
        }

        return $expr;
    }

    private function parsePrimary(): AST\Node
    {
        $tok = $this->peek();

        // Literals
        if ($tok->is(TokenObject::T_INTEGER)) {
            return new AST\IntegerLiteral((int) $this->advance()->getValue());
        }

        if ($tok->is(TokenObject::T_FLOAT)) {
            return new AST\FloatLiteral((float) $this->advance()->getValue());
        }

        if ($tok->is(TokenObject::T_STRING)) {
            return new AST\StringLiteral($this->advance()->getValue());
        }

        if ($tok->is(TokenObject::T_BOOL)) {
            return new AST\BoolLiteral($this->advance()->getValue() === 'true');
        }

        if ($tok->is(TokenObject::T_NULL)) {
            $this->advance();
            return new AST\NullLiteral();
        }

        // Grouping
        if ($tok->isPunct('(')) {
            $this->advance();
            $expr = $this->parseExpression();
            $this->expect(')');
            return $expr;
        }

        // Array literal
        if ($tok->isPunct('[')) {
            return $this->parseArrayLiteral();
        }

        // Object literal
        if ($tok->isPunct('{')) {
            return $this->parseObjectLiteral();
        }

        // Lambda:  (params) => expr
        if ($tok->isPunct('(') || ($tok->is(TokenObject::T_IDENTIFIER) && $this->isLambdaAhead())) {
            return $this->parseLambda();
        }

        // new
        if ($tok->isKeyword('new')) {
            $this->advance();
            $callee = new AST\Identifier($this->expect(TokenObject::T_IDENTIFIER)->getValue());
            $args = $this->peek()->isPunct('(') ? $this->parseArguments() : [];
            return new AST\NewExpression($callee, $args);
        }

        // match
        if ($tok->getValue() === 'match') {
            return $this->parseMatch();
        }

        // Spread
        if ($tok->isOperator('...')) {
            $this->advance();
            return new AST\SpreadExpression($this->parseExpression());
        }

        // Anonymous function
        if ($tok->isKeyword('function')) {
            return $this->parseAnonymousFunction();
        }

        // Identifier
        if ($tok->is(TokenObject::T_IDENTIFIER) || $tok->is(TokenObject::T_KEYWORD)) {
            $name = $this->advance()->getValue();
            return new AST\Identifier($name);
        }

        throw new \RuntimeException(
            "Unexpected token '{$tok->getValue()}' at line {$tok->getLine()}"
        );
    }

    private function parseArrayLiteral(): AST\ArrayLiteral
    {
        $this->expect('[');
        $elements = [];

        while (!$this->peek()->isPunct(']') && !$this->isEOF()) {
            if ($this->peek()->isOperator('...')) {
                $this->advance();
                $elements[] = new AST\SpreadExpression($this->parseExpression());
            } else {
                $elements[] = $this->parseExpression();
            }

            if (!$this->peek()->isPunct(','))
                break;
            $this->advance();
        }

        $this->expect(']');
        return new AST\ArrayLiteral($elements);
    }

    private function parseObjectLiteral(): AST\ObjectLiteral
    {
        $this->expect('{');
        $props = [];

        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            $key = $this->advance()->getValue();
            $this->expect(':');
            $props[$key] = $this->parseExpression();

            if (!$this->peek()->isPunct(','))
                break;
            $this->advance();
        }

        $this->expect('}');
        return new AST\ObjectLiteral($props);
    }

    private function parseLambda(): AST\LambdaExpression
    {
        $params = [];

        if ($this->peek()->isPunct('(')) {
            $this->advance();
            while (!$this->peek()->isPunct(')') && !$this->isEOF()) {
                // Optional type hint
                if (
                    in_array($this->peek()->getValue(), self::TYPE_KEYWORDS, true) &&
                    $this->peekAt(1)->is(TokenObject::T_IDENTIFIER)
                ) {
                    $this->advance(); // skip type
                }
                $params[] = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
                if (!$this->peek()->isPunct(','))
                    break;
                $this->advance();
            }
            $this->expect(')');
        } else {
            $params[] = $this->expect(TokenObject::T_IDENTIFIER)->getValue();
        }

        $this->expect('=>');

        $body = $this->peek()->isPunct('{')
            ? $this->parseBlock()
            : $this->parseExpression();

        return new AST\LambdaExpression($params, $body);
    }

    private function parseAnonymousFunction(): AST\LambdaExpression
    {
        $this->expectKeyword('function');
        $params = $this->parseFunctionParams();
        $body = $this->parseBlock();

        $paramNames = array_map(fn($p) => $p['name'], $params);
        return new AST\LambdaExpression($paramNames, $body);
    }

    private function parseMatch(): AST\MatchExpression
    {
        $this->advance(); // 'match'
        $this->expect('(');
        $subject = $this->parseExpression();
        $this->expect(')');
        $this->expect('{');

        $arms = [];
        $default = null;

        while (!$this->peek()->isPunct('}') && !$this->isEOF()) {
            if ($this->peek()->isKeyword('default')) {
                $this->advance();
                $this->expect('=>');
                $default = $this->parseExpression();
            } else {
                $test = $this->parseExpression();
                $this->expect('=>');
                $value = $this->parseExpression();
                $arms[] = ['test' => $test, 'value' => $value];
            }

            if (!$this->peek()->isPunct(','))
                break;
            $this->advance();
        }

        $this->expect('}');
        return new AST\MatchExpression($subject, $arms, $default);
    }

    private function parseArguments(): array
    {
        $this->expect('(');
        $args = [];

        while (!$this->peek()->isPunct(')') && !$this->isEOF()) {
            if ($this->peek()->isOperator('...')) {
                $this->advance();
                $args[] = new AST\SpreadExpression($this->parseExpression());
            } else {
                $args[] = $this->parseExpression();
            }

            if (!$this->peek()->isPunct(','))
                break;
            $this->advance();
        }

        $this->expect(')');
        return $args;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function isLambdaAhead(): bool
    {
        // heuristic: identifier followed by =>
        $saved = $this->pos;
        $this->advance();
        $isLambda = $this->peek()->isOperator('=>');
        $this->pos = $saved;
        return $isLambda;
    }

    private function peek(): TokenObject
    {
        return $this->tokens[$this->pos] ?? new TokenObject('', TokenObject::T_EOF);
    }

    private function peekAt(int $offset): TokenObject
    {
        return $this->tokens[$this->pos + $offset] ?? new TokenObject('', TokenObject::T_EOF);
    }

    private function advance(): TokenObject
    {
        $tok = $this->peek();
        $this->pos++;
        return $tok;
    }

    private function isEOF(): bool
    {
        return $this->peek()->isEOF();
    }

    private function skipSemicolons(): void
    {
        while ($this->peek()->isPunct(';'))
            $this->advance();
    }

    private function expect(string|int $value): TokenObject
    {
        $tok = $this->peek();

        if (is_int($value)) {
            if (!$tok->is($value)) {
                throw new \RuntimeException(
                    "Expected token type {$value}, got '{$tok->getValue()}' at line {$tok->getLine()}"
                );
            }
        } else {
            if (!$tok->isValue($value) && !$tok->isPunct($value)) {
                throw new \RuntimeException(
                    "Expected '{$value}', got '{$tok->getValue()}' at line {$tok->getLine()}"
                );
            }
        }

        return $this->advance();
    }

    private function expectKeyword(string $kw): TokenObject
    {
        $tok = $this->peek();
        if (!$tok->isKeyword($kw) && !$tok->isValue($kw)) {
            throw new \RuntimeException(
                "Expected keyword '{$kw}', got '{$tok->getValue()}' at line {$tok->getLine()}"
            );
        }
        return $this->advance();
    }
}
