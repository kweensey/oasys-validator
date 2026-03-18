<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ComparisonsTest extends TestCase
{
    public function testGreaterThanFails(): void
    {
        $result = (new Validator())
            ->greaterThan('age', 17)
            ->validate(['age' => 17]);

        self::assertSame([
            'age' => 'age must be greater than 17',
        ], $result);
    }

    public function testGreaterOrEqualToPasses(): void
    {
        $result = (new Validator())
            ->greaterOrEqualTo('age', 17)
            ->validate(['age' => 17]);

        self::assertSame([], $result);
    }

    public function testLessThanFails(): void
    {
        $result = (new Validator())
            ->lessThan('age', 17)
            ->validate(['age' => 17]);

        self::assertNotSame([], $result);
        self::assertArrayHasKey('age', $result);
    }

    public function testEqualToPasses(): void
    {
        $result = (new Validator())
            ->equalTo('status', 'ok')
            ->validate(['status' => 'ok']);

        self::assertSame([], $result);
    }

    public function testComparisonSkipsOnEmptyField(): void
    {
        $result = (new Validator())
            ->greaterThan('age', 18)
            ->validate(['age' => '']);

        self::assertSame([], $result);
    }

    public function testGreaterAsFailsAgainstOtherField(): void
    {
        $result = (new Validator())
            ->greaterThanField('end_date', 'start_date')
            ->validate([
                'start_date' => '2025-02-09',
                'end_date' => '2025-01-01',
            ]);

        self::assertSame([
            'end_date' => 'end_date must be greater than field start_date',
        ], $result);
    }

    public function testFieldToFieldComparisonSkipsIfCompareFieldMissingOrEmpty(): void
    {
        $result1 = (new Validator())
            ->greaterThanField('end_date', 'start_date')
            ->validate(['end_date' => '2025-01-01']);

        $result2 = (new Validator())
            ->greaterThanField('end_date', 'start_date')
            ->validate([
                'start_date' => '',
                'end_date' => '2025-01-01',
            ]);

        self::assertSame([], $result1);
        self::assertSame([], $result2);
    }
}
