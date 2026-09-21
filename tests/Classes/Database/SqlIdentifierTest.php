<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\SqlIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Boundary tests for the identifier, operator and direction validator.
 *
 * The hostile inputs below are the shapes an attacker reaches for when a column name is
 * interpolated into a statement: closing the quote, ending the statement, appending a UNION,
 * smuggling a comment, or hiding a subquery in what looks like a sort direction.
 */
final class SqlIdentifierTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function acceptedIdentifierProvider(): array
    {
        return [
            'plain column' => ['title', '`title`'],
            'underscored' => ['created_at', '`created_at`'],
            'leading underscore' => ['_internal', '`_internal`'],
            'digits allowed' => ['2fa_enabled', '`2fa_enabled`'],
            'all digits' => ['123', '`123`'],
            'qualified by table' => ['posts.title', '`posts`.`title`'],
            'qualified by alias' => ['p.user_id', '`p`.`user_id`'],
            'surrounding whitespace trimmed' => ['  name  ', '`name`'],
            'whitespace around qualifier' => [' posts . title ', '`posts`.`title`'],
        ];
    }

    #[DataProvider('acceptedIdentifierProvider')]
    public function testQuoteAcceptsAndWrapsLegitimateIdentifiers(string $identifier, string $expected): void
    {
        $this->assertSame($expected, SqlIdentifier::quote($identifier));
        $this->assertTrue(SqlIdentifier::isValid($identifier));
    }

    /** @return array<string, array{string}> */
    public static function rejectedIdentifierProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'statement terminator' => ['id; DROP TABLE users'],
            'union injection' => ['id UNION SELECT password FROM users'],
            'backtick escape' => ['na`me'],
            'backtick closing then expression' => ['id` = 1 OR `1'],
            'inline comment' => ['id -- '],
            'block comment' => ['id/**/'],
            'boolean tautology' => ['1=1'],
            'direction smuggled into column' => ['name DESC'],
            'comma separated list' => ['id, password'],
            'parenthesised subquery' => ['(SELECT password FROM users)'],
            'function call' => ['COUNT(*)'],
            'wildcard' => ['*'],
            'over qualified' => ['schema.table.column'],
            'trailing dot' => ['posts.'],
            'leading dot' => ['.title'],
            'hyphen' => ['user-name'],
            'space inside' => ['user name'],
            'quote character' => ["id'"],
            'newline' => ["id\nDROP"],
            'null byte' => ["id\0"],
        ];
    }

    #[DataProvider('rejectedIdentifierProvider')]
    public function testQuoteRejectsHostileIdentifiers(string $identifier): void
    {
        $this->assertFalse(SqlIdentifier::isValid($identifier));
        $this->expectException(InvalidArgumentException::class);
        SqlIdentifier::quote($identifier);
    }

    public function testQuotedOutputNeverContainsAnUnbalancedBacktick(): void
    {
        foreach (self::acceptedIdentifierProvider() as [$identifier, $expected]) {
            $quoted = SqlIdentifier::quote($identifier);
            $this->assertSame(
                0,
                substr_count($quoted, '`') % 2,
                sprintf('Quoting %s produced an odd number of backticks: %s', $identifier, $quoted)
            );
            $this->assertSame($expected, $quoted);
        }
    }

    public function testQuoteListJoinsEveryMemberQuoted(): void
    {
        $this->assertSame('`id`, `posts`.`title`', SqlIdentifier::quoteList(['id', 'posts.title']));
    }

    public function testQuoteListRejectsAnEmptyList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SqlIdentifier::quoteList([]);
    }

    public function testQuoteListRejectsAListContainingAHostileMember(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SqlIdentifier::quoteList(['id', 'password FROM users --']);
    }

    /** @return array<string, array{string, string}> */
    public static function acceptedOperatorProvider(): array
    {
        return [
            'equals' => ['=', '='],
            'bang equals' => ['!=', '!='],
            'diamond' => ['<>', '<>'],
            'less than' => ['<', '<'],
            'less or equal' => ['<=', '<='],
            'greater than' => ['>', '>'],
            'greater or equal' => ['>=', '>='],
            'like lower case' => ['like', 'LIKE'],
            'not like collapsed whitespace' => ["not   like", 'NOT LIKE'],
            'in' => ['in', 'IN'],
            'not in' => ['NOT IN', 'NOT IN'],
            'is' => ['is', 'IS'],
            'is not' => ['is not', 'IS NOT'],
            'padded' => ['  =  ', '='],
        ];
    }

    #[DataProvider('acceptedOperatorProvider')]
    public function testOperatorCanonicalisesWhitelistedOperators(string $operator, string $expected): void
    {
        $this->assertSame($expected, SqlIdentifier::operator($operator));
    }

    /** @return array<string, array{string}> */
    public static function rejectedOperatorProvider(): array
    {
        return [
            'empty' => [''],
            'union' => ['UNION'],
            'null safe equals is not whitelisted' => ['<=>'],
            'rlike is not whitelisted' => ['RLIKE'],
            'regexp is not whitelisted' => ['REGEXP'],
            'appended condition' => ['= 1 OR 1'],
            'comment' => ['= --'],
            'semicolon' => ['=;'],
            'subquery' => ['IN (SELECT 1)'],
        ];
    }

    #[DataProvider('rejectedOperatorProvider')]
    public function testOperatorRejectsAnythingOutsideTheWhitelist(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);
        SqlIdentifier::operator($operator);
    }

    /** @return array<string, array{string, string}> */
    public static function acceptedDirectionProvider(): array
    {
        return [
            'ascending' => ['ASC', 'ASC'],
            'ascending lower case' => ['asc', 'ASC'],
            'descending' => ['DESC', 'DESC'],
            'descending mixed case' => ['DeSc', 'DESC'],
            'padded' => ['  desc  ', 'DESC'],
        ];
    }

    #[DataProvider('acceptedDirectionProvider')]
    public function testDirectionCanonicalisesAscendingAndDescending(string $direction, string $expected): void
    {
        $this->assertSame($expected, SqlIdentifier::direction($direction));
    }

    /** @return array<string, array{string}> */
    public static function rejectedDirectionProvider(): array
    {
        return [
            'empty' => [''],
            'subquery smuggled after asc' => ['ASC, (SELECT 1)'],
            'appended column' => ['DESC, password'],
            'comment' => ['ASC --'],
            'limit smuggled' => ['ASC LIMIT 1'],
            'arbitrary word' => ['UP'],
            'semicolon' => ['ASC;'],
        ];
    }

    #[DataProvider('rejectedDirectionProvider')]
    public function testDirectionRejectsAnythingElse(string $direction): void
    {
        $this->expectException(InvalidArgumentException::class);
        SqlIdentifier::direction($direction);
    }
}
