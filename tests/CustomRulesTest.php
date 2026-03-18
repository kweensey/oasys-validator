<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class CustomRulesTest extends TestCase
{
    public function testCustomOneOffRuleFails(): void
    {
        $result = (new Validator())
            ->custom(
                'number',
                fn (mixed $value): bool => ((int) $value) % 2 === 0,
                'Must be an even number'
            )
            ->validate(['number' => 15]);

        self::assertSame([
            'number' => 'Must be an even number',
        ], $result);
    }

    public function testRegisterCreatesReusableRule(): void
    {
        $validator = (new Validator())
            ->register(
                'even',
                fn (mixed $value): bool => ((int) $value) % 2 === 0,
                'Must be an even number'
            );

        $result = $validator->even('number')->validate(['number' => 15]);

        self::assertSame([
            'number' => 'Must be an even number',
        ], $result);
    }

    public function testCustomRuleAcceptsAdditionalArguments(): void
    {
        $result = (new Validator())
            ->custom(
                'number',
                fn (mixed $value, int $min, int $max): bool => ((int) $value) >= $min && ((int) $value) <= $max,
                '{field} must be between {min} and {max}',
                20,
                50
            )
            ->validate(['number' => 15]);

        self::assertSame([
            'number' => 'number must be between 20 and 50',
        ], $result);
    }

    public function testRegisteredRuleAcceptsPositionalAndNamedArgs(): void
    {
        $validator = (new Validator())->register(
            'hour',
            fn (mixed $value, bool $format24 = false): bool => is_int($value)
                && $format24
                    ? $value <= 23 && $value >= 0
                    : $value <= 12 && $value >= 1,
            'Must be a valid hour'
        );

        $resultPositional = $validator
            ->hour('alarm', true)
            ->validate(['alarm' => 14]);

        $resultNamed = $validator
            ->hour('alarm', format24: true)
            ->validate(['alarm' => 14]);

        self::assertSame([], $resultPositional);
        self::assertSame([], $resultNamed);
    }

    public function testRegisteredVariadicRuleWorksByNameArrayAndPositionally(): void
    {
        $validator = (new Validator())->register(
            'oneOf',
            fn (mixed $value, mixed ...$values): bool => in_array($value, $values, true),
            'Must be one of the allowed values'
        );

        $resultNamed = $validator
            ->oneOf('category', values: ['one', 'two', 'three'])
            ->validate(['category' => 'two']);

        $resultPositional = $validator
            ->oneOf('category', 'one', 'two', 'three')
            ->validate(['category' => 'two']);

        self::assertSame([], $resultNamed);
        self::assertSame([], $resultPositional);
    }
}
