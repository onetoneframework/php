<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\RecordValidator;
use PHPUnit\Framework\TestCase;

/**
 * Characterisation tests for the rule engine behind ActiveRecord::validate().
 *
 * Every branch of the switch is exercised, on both sides, because the engine
 * moved out of ActiveRecord as a body-for-body copy and a rule quietly dropped
 * from the switch would let invalid data through rather than raise anything.
 */
final class RecordValidatorTest extends TestCase
{
	/**
	 * @param array<string, string|array<int, string>> $rules
	 * @param array<string, mixed>                     $data
	 * @param array<string, string>                    $messages
	 *
	 * @return array<string, string[]>
	 */
	private function errorsFor(array $rules, array $data, array $messages = []): array
	{
		$validator = new RecordValidator($rules, $messages);
		$validator->validate($data);

		return $validator->errors();
	}

	public function testNoRulesMeansValid(): void
	{
		$validator = new RecordValidator([], []);

		$this->assertTrue($validator->validate(['anything' => 'goes']));
		$this->assertSame([], $validator->errors());
	}

	/**
	 * @return array<string, array{0: string, 1: mixed, 2: bool}>
	 */
	public static function ruleProvider(): array
	{
		return [
			'required rejects null' => ['required', null, false],
			'required rejects empty' => ['required', '', false],
			'required accepts zero' => ['required', 0, true],
			'min rejects below' => ['min:5', 4, false],
			'min accepts equal' => ['min:5', 5, true],
			'min ignores non-numeric' => ['min:5', 'abc', true],
			'max rejects above' => ['max:5', 6, false],
			'max accepts equal' => ['max:5', 5, true],
			'minLength rejects short' => ['minLength:3', 'ab', false],
			'minLength accepts equal' => ['minLength:3', 'abc', true],
			'maxLength rejects long' => ['maxLength:3', 'abcd', false],
			'numeric rejects letters' => ['numeric', 'abc', false],
			'numeric accepts digits' => ['numeric', '42', true],
			'numeric ignores empty' => ['numeric', '', true],
			'integer rejects float' => ['integer', '1.5', false],
			'integer accepts int' => ['integer', '42', true],
			'email rejects garbage' => ['email', 'not-an-email', false],
			'email accepts address' => ['email', 'a@b.test', true],
			'url rejects garbage' => ['url', 'not a url', false],
			'url accepts address' => ['url', 'https://example.test', true],
			'regex rejects mismatch' => ['regex:/^[0-9]+$/', 'abc', false],
			'regex accepts match' => ['regex:/^[0-9]+$/', '123', true],
			'in rejects outsider' => ['in:a,b,c', 'd', false],
			'in accepts member' => ['in:a,b,c', 'b', true],
			'notIn rejects member' => ['notIn:a,b,c', 'b', false],
			'notIn accepts outsider' => ['notIn:a,b,c', 'd', true],
		];
	}

	/**
	 * @dataProvider ruleProvider
	 */
	public function testEachRule(string $rule, mixed $value, bool $expectedValid): void
	{
		$validator = new RecordValidator(['field' => $rule], []);

		$this->assertSame($expectedValid, $validator->validate(['field' => $value]));
	}

	public function testRulesCanBePipeSeparatedOrAList(): void
	{
		$piped = $this->errorsFor(['field' => 'required|numeric'], ['field' => 'abc']);
		$listed = $this->errorsFor(['field' => ['required', 'numeric']], ['field' => 'abc']);

		$this->assertSame($piped, $listed);
	}

	public function testEveryFailingRuleOnAFieldIsReported(): void
	{
		$errors = $this->errorsFor(['field' => 'numeric|minLength:5'], ['field' => 'ab']);

		$this->assertCount(2, $errors['field']);
	}

	public function testConfirmedComparesTheCompanionField(): void
	{
		$this->assertSame(
			[],
			$this->errorsFor(['password' => 'confirmed'], ['password' => 'x', 'password_confirmation' => 'x'])
		);

		$errors = $this->errorsFor(['password' => 'confirmed'], ['password' => 'x', 'password_confirmation' => 'y']);

		$this->assertSame(['The password confirmation does not match.'], $errors['password']);
	}

	public function testAMissingFieldIsValidatedAsNull(): void
	{
		$errors = $this->errorsFor(['absent' => 'required'], []);

		$this->assertSame(['The absent field is required.'], $errors['absent']);
	}

	#region messages

	public function testAFieldAndRuleMessageWins(): void
	{
		$errors = $this->errorsFor(
			['email' => 'required'],
			['email' => null],
			['email.required' => 'We need your email.']
		);

		$this->assertSame(['We need your email.'], $errors['email']);
	}

	public function testAFieldMessageAppliesToEveryRuleOnIt(): void
	{
		$errors = $this->errorsFor(
			['email' => 'required'],
			['email' => null],
			['email' => 'Something about email.']
		);

		$this->assertSame(['Something about email.'], $errors['email']);
	}

	public function testTheDefaultMessageNamesTheField(): void
	{
		$errors = $this->errorsFor(['age' => 'min:18'], ['age' => 10]);

		$this->assertSame(['The age must be at least 18.'], $errors['age']);
	}

	#endregion

	#region unique

	public function testUniqueIsSkippedWithoutAChecker(): void
	{
		$validator = new RecordValidator(['email' => 'unique'], []);

		$this->assertTrue(
			$validator->validate(['email' => 'a@b.test']),
			'A model with no connection has always reported nothing taken.'
		);
	}

	public function testUniqueReportsATakenValue(): void
	{
		$validator = new RecordValidator(
			['email' => 'unique'],
			[],
			static fn (string $field, mixed $value, ?string $ruleParam): bool => true
		);

		$this->assertFalse($validator->validate(['email' => 'a@b.test']));
		$this->assertSame(['The email has already been taken.'], $validator->errors()['email']);
	}

	public function testUniquePassesTheRuleParameterThrough(): void
	{
		$seen = [];
		$validator = new RecordValidator(
			['email' => 'unique:members.address'],
			[],
			static function (string $field, mixed $value, ?string $ruleParam) use (&$seen): bool {
				$seen = [$field, $value, $ruleParam];

				return false;
			}
		);

		$validator->validate(['email' => 'a@b.test']);

		$this->assertSame(['email', 'a@b.test', 'members.address'], $seen);
	}

	public function testUniqueIsNotConsultedForAnEmptyValue(): void
	{
		$called = false;
		$validator = new RecordValidator(
			['email' => 'unique'],
			[],
			static function () use (&$called): bool {
				$called = true;

				return true;
			}
		);

		$validator->validate(['email' => '']);

		$this->assertFalse($called);
	}

	#endregion

	public function testAnUnknownRuleIsIgnored(): void
	{
		$validator = new RecordValidator(['field' => 'noSuchRule'], []);

		$this->assertTrue($validator->validate(['field' => 'anything']));
	}

	public function testValidatingAgainClearsTheEarlierErrors(): void
	{
		$validator = new RecordValidator(['field' => 'required'], []);

		$this->assertFalse($validator->validate(['field' => null]));
		$this->assertTrue($validator->validate(['field' => 'present']));
		$this->assertSame([], $validator->errors());
	}
}
