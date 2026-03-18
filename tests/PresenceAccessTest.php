<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class PresenceAccessTest extends TestCase
{
    public function testRequireFailsWhenMissing(): void
    {
        $result = (new Validator())
            ->require('email')
            ->validate([]);

        self::assertSame([
            'email' => 'email is required',
        ], $result);
    }

    public function testRequireFailsWhenEmptyString(): void
    {
        $result = (new Validator())
            ->require('email')
            ->validate(['email' => '']);

        self::assertSame([
            'email' => 'email is required',
        ], $result);
    }

    public function testRequireTreatsRequiredFieldAsAllowed(): void
    {
        $result = (new Validator())
            ->require('email')
            ->validate(['email' => 'a@b.c']);

        self::assertSame([], $result);
    }

    public function testRequireCanBeStacked(): void
    {
        $result = (new Validator())
            ->require('email')
            ->require('username')
            ->validate([]);

        self::assertSame([
            'email' => 'email is required',
            'username' => 'username is required',
        ], $result);
    }

    public function testAllowRejectsUnknownFields(): void
    {
        $result = (new Validator())
            ->allow('email')
            ->validate([
                'email' => 'john@example.com',
                'city' => 'Prague',
            ]);

        self::assertSame([
            'city' => 'city is not allowed',
        ], $result);
    }

    public function testAllowOmittedAllowsAllFields(): void
    {
        $result = (new Validator())
            ->validate([
                'email' => 'john@example.com',
                'city' => 'Prague',
            ]);

        self::assertSame([], $result);
    }

    public function testNotEmptyFailsWhenPresentAndEmpty(): void
    {
        $result = (new Validator())
            ->notEmpty('email')
            ->validate(['email' => '']);

        self::assertSame([
            'email' => 'email cannot be empty',
        ], $result);
    }

    public function testNotEmptySkipsWhenMissing(): void
    {
        $result = (new Validator())
            ->notEmpty('email')
            ->validate([]);

        self::assertSame([], $result);
    }

    public function testRequireOnTriggersWhenConditionFieldIsSet(): void
    {
        $result = (new Validator())
            ->requireOn('vat_id', 'company')
            ->validate(['company' => 'MyCorp']);

        self::assertSame([
            'vat_id' => 'vat_id is required when company is set',
        ], $result);
    }

    public function testRequireOnSkipsWhenConditionFieldMissing(): void
    {
        $result = (new Validator())
            ->requireOn('vat_id', 'company')
            ->validate([]);

        self::assertSame([], $result);
    }

    public function testRequireWhenTriggersOnExactMatch(): void
    {
        $result = (new Validator())
            ->requireWhen('state', 'country', 'US')
            ->validate(['country' => 'US']);

        self::assertSame([
            'state' => 'state is required when country is US',
        ], $result);
    }

    public function testRequireWhenSkipsOnDifferentValue(): void
    {
        $result = (new Validator())
            ->requireWhen('state', 'country', 'CZ')
            ->validate(['country' => 'US']);

        self::assertSame([], $result);
    }
}
