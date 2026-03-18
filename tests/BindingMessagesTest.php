<?php declare(strict_types=1);

namespace Oasys\Validation\Tests;

use Oasys\Validation\ValidationAttribute;
use Oasys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class BindingMessagesTest extends TestCase
{
    public function testBindAppliesAttributeRules(): void
    {
        $result = (new Validator())
            ->bind(BindRegisterUserDto::class)
            ->validate([
                'email' => '',
                'password' => 'secret',
            ]);

        self::assertSame([
            'email' => 'email is required',
            'password' => 'password must have a minimum length of 8 characters',
        ], $result);
    }

    public function testBindSupportsPrefixAndCustomSeparator(): void
    {
        $result = (new Validator())
            ->bind(BindRegisterUserDto::class, 'account', '.')
            ->validate([
                'account.email' => '',
                'account.password' => 'secret',
            ]);

        self::assertSame([
            'account.email' => 'account.email is required',
            'account.password' => 'account.password must have a minimum length of 8 characters',
        ], $result);
    }

    public function testPerRuleMessageOverrideWorks(): void
    {
        $result = (new Validator())
            ->percent('discount', 'Must be a number 0-100')
            ->validate(['discount' => 150]);

        self::assertSame([
            'discount' => 'Must be a number 0-100',
        ], $result);
    }

    public function testGlobalDictionaryOverrideWorks(): void
    {
        $validator = new Validator(messages: [
            'require' => 'Required field',
        ]);

        $result = $validator
            ->require('title')
            ->validate(['title' => '']);

        self::assertSame([
            'title' => 'Required field',
        ], $result);
    }

    public function testOnlyFirstFailedRuleIsReturnedForEachField(): void
    {
        $result = (new Validator())
            ->require('name')
            ->minLength('name', 5)
            ->regex('name', '/^[A-Z]+$/')
            ->validate(['name' => '']);

        self::assertSame([
            'name' => 'name is required',
        ], $result);
    }

    public function testRequireAllowChecksRunBeforeFieldRules(): void
    {
        $result = (new Validator())
            ->allow('email')
            ->regex('city', '/^[A-Z]/')
            ->validate([
                'email' => 'john@example.com',
                'city' => 'prague',
            ]);

        self::assertSame([
            'city' => 'city is not allowed',
        ], $result);
    }
}

final class BindRegisterUserDto
{
    #[ValidationAttribute('require')]
    #[ValidationAttribute('maxLength', 255)]
    public string $email;

    #[ValidationAttribute('minLength', 8)]
    public string $password;
}
