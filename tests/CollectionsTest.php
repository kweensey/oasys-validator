<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class CollectionsTest extends TestCase
{
    public function testMaxLengthFails(): void
    {
        $result = (new Validator())
            ->maxLength('tag', 3)
            ->validate(['tag' => '2long']);

        self::assertSame([
            'tag' => 'tag must have a maximum length of 3 characters',
        ], $result);
    }

    public function testLengthRulesSkipOnEmpty(): void
    {
        $result = (new Validator())
            ->minLength('tag', 3)
            ->validate(['tag' => '']);

        self::assertSame([], $result);
    }

    public function testPercentFails(): void
    {
        $result = (new Validator())
            ->percent('discount')
            ->validate(['discount' => 150]);

        self::assertSame([
            'discount' => 'discount must be a valid percentage',
        ], $result);
    }

    public function testInFailsWhenValueNotInHaystack(): void
    {
        $result = (new Validator())
            ->in('role', ['admin', 'editor', 'viewer'])
            ->validate(['role' => 'owner']);

        self::assertSame([
            'role' => 'role must be one of the specified values: admin, editor, viewer',
        ], $result);
    }

    public function testInThrowsOnEmptyHaystack(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Validator())->in('role', []);
    }

    public function testInEnumFailsWhenNotMember(): void
    {
        $result = (new Validator())
            ->inEnum('role', TestRole::class)
            ->validate(['role' => 'owner']);

        self::assertSame([
            'role' => 'role must be one of the specified values: admin, editor, viewer',
        ], $result);
    }

    public function testItemsListFailsOnNonListArray(): void
    {
        $result = (new Validator())
            ->itemsList('categories')
            ->validate([
                'categories' => [0 => 'foo', 2 => 'bar'],
            ]);

        self::assertSame([
            'categories' => 'categories must be a list',
        ], $result);
    }

    public function testItemsInFailsWhenAnyItemIsInvalid(): void
    {
        $result = (new Validator())
            ->itemsIn('categories', ['foo', 'bar'])
            ->validate([
                'categories' => ['foo', 'bar', 'baz'],
            ]);

        self::assertSame([
            'categories' => 'categories items must be one of the specified values: foo, bar',
        ], $result);
    }

    public function testItemsCountConstraintsWork(): void
    {
        $result = (new Validator())
            ->itemsMax('ids', 2)
            ->validate(['ids' => [123, 456, 789]]);

        self::assertSame([
            'ids' => 'ids must have a maximum count of 2 items',
        ], $result);
    }

    public function testItemsRulesSkipOnEmptyArrayWhenEmptyArraysAreConsideredEmpty(): void
    {
        $result = (new Validator())
            ->itemsMin('ids', 1)
            ->validate(['ids' => []]);

        self::assertSame([], $result);
    }

    public function testSchemaFailsOnNonArrayInput(): void
    {
        $result = (new Validator())
            ->schema('address', (new Validator())->require('zip'))
            ->validate(['address' => 'Prague']);

        self::assertArrayHasKey('address', $result);
    }

    public function testSchemaDelegatesFirstNestedErrorIntoMessage(): void
    {
        $result = (new Validator())
            ->schema('address', (new Validator())
                ->require('city')
                ->require('zip'))
            ->validate([
                'address' => ['city' => 'Prague'],
            ]);

        self::assertSame([
            'address' => 'address has invalid value: zip is required',
        ], $result);
    }

    public function testSchemaSupportsCustomMessageTemplateWithNestedErrorPlaceholder(): void
    {
        $result = (new Validator())
            ->schema(
                'address',
                (new Validator())->require('zip'),
                '{field} is invalid: {error}'
            )
            ->validate([
                'address' => ['city' => 'Prague'],
            ]);

        self::assertSame([
            'address' => 'address is invalid: zip is required',
        ], $result);
    }
}

enum TestRole: string
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';
}
