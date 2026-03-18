<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class FormatTypeTest extends TestCase
{
    public function testRegexFailsOnInvalidString(): void
    {
        $result = (new Validator())
            ->regex('zip', '/^\d{5}$/')
            ->validate(['zip' => '12C45']);

        self::assertSame([
            'zip' => 'zip has invalid format',
        ], $result);
    }

    public function testRegexSkipsOnEmptyString(): void
    {
        $result = (new Validator())
            ->regex('zip', '/^\d{5}$/')
            ->validate(['zip' => '']);

        self::assertSame([], $result);
    }

    public function testRegexFailsForNonStringInputAfterTypeTightening(): void
    {
        $result = (new Validator())
            ->regex('zip', '/^\d{5}$/')
            ->validate(['zip' => 12345]);

        self::assertSame([
            'zip' => 'zip has invalid format',
        ], $result);
    }

    public function testFormatUsesCustomPatternAlias(): void
    {
        $validator = new Validator(patterns: [
            'time' => '/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/',
        ]);

        $result = $validator
            ->format('departure', 'time')
            ->validate(['departure' => '23:60']);

        self::assertSame([
            'departure' => 'departure has invalid format',
        ], $result);
    }

    public function testTypeSupportsNativeAlias(): void
    {
        $result = (new Validator())
            ->type('amount', 'int')
            ->validate(['amount' => 100.5]);

        self::assertSame([
            'amount' => 'amount must be of type integer',
        ], $result);
    }

    public function testTypeSupportsClassInterfaceChecks(): void
    {
        $result = (new Validator())
            ->type('amount', TypeMoneyContract::class)
            ->validate(['amount' => new TypePrice()]);

        self::assertSame([], $result);
    }

    public function testTypeFailsOnWrongClass(): void
    {
        $result = (new Validator())
            ->type('amount', TypeMoney::class)
            ->validate(['amount' => new TypePrice()]);

        self::assertSame([
            'amount' => 'amount must be of type TypeMoney',
        ], $result);
    }

    public function testConstructorThrowsOnInvalidPatternMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Validator(patterns: [
            123 => '/.*/',
        ]);
    }

    public function testConstructorThrowsOnInvalidMessagesMap(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Validator(messages: [
            'require' => 123,
        ]);
    }
}

interface TypeMoneyContract {}
final class TypePrice implements TypeMoneyContract {}
final class TypeMoney {}
