# Oasys Validator

[![Tests](https://github.com/kweensey/oasys-validator/actions/workflows/tests.yml/badge.svg)](https://github.com/kweensey/oasys-validator/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/oasys/validator)](https://packagist.org/packages/oasys/validator)
[![PHP Version Require](https://img.shields.io/packagist/php-v/oasys/validator)](https://packagist.org/packages/oasys/validator)
[![License](https://img.shields.io/packagist/l/oasys/validator)](https://packagist.org/packages/oasys/validator)

Lightweight validator for associative arrays.

- Object-oriented, fluent interface
- Extensible rules and format patterns
- Nested schema validation
- Field-to-field comparison
- Attribute-based rule binding
- Error message templating

---

## Installation

```bash
composer require oasys/validator
```

---

## Quick start

```php
<?php declare(strict_types=1);

use Oasys\Validation\Validator;

$payload = [
    'email' => 'john.doe@email.com',
    'age'   => 17
];

$result = new Validator()
    ->require('email', 'age')
    ->regex('email', '/^[^@]+@[^@]+\.[^@]+$/')
    ->greaterOrEqualTo('age', 18)
    ->validate($payload);
```

```php
[
    'age' => 'age must be greater than or equal to 18'
]
```

---

## Built-in validators

### Presence and access

#### `require(string ...$fields)`

Field must be present and non-empty (`null`, `''`, and `[]` are treated as empty)

Every required field is considered allowed automatically

```php
$payload = [];
// - or -
$payload = [
    'email' => ''
];

$result = new Validator()
    ->require('email')
    ->validate($payload);
```

```php
[
    'email' => 'email is required'
]
```

You can supply multiple fields at once and stack them

```php
$validator = new Validator();

$validator->require('email', 'username');

if (true) {
    $validator->require('role');
}

$result = $validator->validate([]);
```

```php
[
    'email'    => 'email is required',
    'username' => 'username is required',
    'role'     => 'role is required'
]
```

---

#### `allow(string ...$fields)`

Field can be present, unlisted fields will be rejected

If omitted, all fields are considered allowed

You can supply multiple fields at once and stack them

```php
$payload = [
    'email' => 'john.doe@email.com',
    'city'  => 'Prague'
];

$result = new Validator()
    ->allow('email')
    ->validate($payload);
```

```php
[
    'city' => 'city is not allowed'
]
```

---

#### `notEmpty(string ...$fields)`

If present, field must be non-empty

You can supply multiple fields at once and stack them

```php
$payload = [
    'email' => ''
];

$result = new Validator()
    ->notEmpty('email')
    ->validate($payload);
```

```php
[
    'email' => 'email cannot be empty'
]
```

...but

```php
$result = new Validator()
    ->notEmpty('email')
    ->validate([]);
```

```php
[] // valid, no errors
```

---

#### `requireOn(string $field, string $conditionField, ?string $message = null)`

If `$conditionField` is present and non-empty, field must be present and non-empty

```php
$payload = [
    'company' => 'MyCorp Ltd.'
];

$result = new Validator()
    ->requireOn('vat_id', 'company')
    ->validate($payload);
```

```php
[
    'vat_id' => 'vat_id is required when company is set'
]
```

---

#### `requireWhen(string $field, string $conditionField, mixed $conditionValue, ?string $message = null)`

If `$conditionField` equals `$conditionValue`, field must be present and non-empty

```php
$payload = [
    'country' => 'US'
];

$result = new Validator()
    ->requireWhen('state', 'country', 'US')
    ->validate($payload);
```

```php
[
    'state' => 'state is required when country is US'
]
```

---

### Format and type

#### `regex(string $field, string $pattern, ?string $message = null)`

If present and non-empty, field must match given regex pattern

Used for one-off patterns, for repeating patterns use `format()` (see below)

```php
$payload = [
    'zip' => '12C45'
];

$result = new Validator()
    ->regex('zip', '/^\d{5}$/')
    ->validate($payload);
```

```php
[
    'zip' => 'zip has invalid format'
]
```

---

#### `format(string $field, string $type, ?string $message = null)`

If present and non-empty, field must match predefined regex pattern of the given alias

You can supply regex pattern aliases as `array<aliasName, regexPattern>` to the constructor's first parameter

```php
$payload = [
    'departure' => '23:60'
];

$validator = new Validator(
    patterns: [
        'time' => '/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/'
    ]
);

$result = $validator
    ->format('departure', 'time')
    ->validate($payload);
```

```php
[
    'departure' => 'departure has invalid format'
]
```

---

#### `type(string $field, string $type, ?string $message = null)`

If present and non-empty, field must match the given PHP type

Can check against native types, classes, or interfaces

Aliases available:

| alias | type |
| --- | --- |
| int | integer |
| bool | boolean |
| float | double |

##### Native types or aliases

```php
$payload = [
    'amount' => 100.5
];

$result = new Validator()
    ->type('amount', 'int')
    ->validate($payload);
```

```php
[
    'amount' => 'amount must be of type integer'
]
```

##### Classes and interfaces

```php
class Money {}

class Price {}

$payload = [
    'amount' => new Price()
];

$result = new Validator()
    ->type('amount', Money::class)
    ->validate($payload);
```

```php
[
    'amount' => 'amount must be of type Money'
]
```

...but

```php
interface Money {}

class Price implements Money {}

$payload = [
    'amount' => new Price()
];

$result = new Validator()
    ->type('amount', Money::class)
    ->validate($payload);
```

```php
[] // valid, no errors
```

---

### Comparisons

> Comparison rules use native PHP loose-comparison; for strict validation, ensure same type with `type()`

#### `greaterThan(string $field, mixed $compareValue, ?string $message = null)`
#### `greaterOrEqualTo(string $field, mixed $compareValue, ?string $message = null)`
#### `lessThan(string $field, mixed $compareValue, ?string $message = null)`
#### `lessOrEqualTo(string $field, mixed $compareValue, ?string $message = null)`
#### `equalTo(string $field, mixed $compareValue, ?string $message = null)`

If present and non-empty, field must be greater than (greater than or equal to, less than, less than or equal to, or equal to) `$compareValue` (if non-empty)

```php
$payload = [
    'age' => 17
];

$result = new Validator()
    ->greaterThan('age', 17)
    ->validate($payload);
```

```php
[
    'age' => 'age must be greater than 17'
]
```

---

#### `greaterThanField(string $field, string $compareField, ?string $message = null)`
#### `greaterOrEqualToField(string $field, string $compareField, ?string $message = null)`
#### `lessThanField(string $field, string $compareField, ?string $message = null)`
#### `lessOrEqualToField(string $field, string $compareField, ?string $message = null)`
#### `equalToField(string $field, string $compareField, ?string $message = null)`

If present and non-empty, field must be greater than (greater than or equal to, less than, less than or equal to, or equal to) `$compareField`'s value (if present and non-empty)

```php
$payload = [
    'start_date' => '2025-02-09',
    'end_date'   => '2025-01-01'
];

$result = new Validator()
    ->greaterThanField('end_date', 'start_date')
    ->validate($payload);
```

```php
[
    'end_date' => 'end_date must be greater than field start_date'
]
```

---

### Number and string constraints

#### `maxLength(string $field, int $maxLength, ?string $message = null)`
#### `minLength(string $field, int $minLength, ?string $message = null)`
#### `length(string $field, int $length, ?string $message = null)`

If present and non-empty, field must have a maximum of (minimum of, or exactly) specified number of characters

```php
$payload = [
    'tag' => '2long'
];

$result = new Validator()
    ->maxLength('tag', 3)
    ->validate($payload);
```

```php
[
    'tag' => 'tag must have a maximum length of 3 characters'
]
```

---

#### `percent(string $field, ?string $message = null)`

If present and non-empty, field must be a valid percentage (0-100)

```php
$payload = [
    'discount' => 150
];

$result = new Validator()
    ->percent('discount')
    ->validate($payload);
```

```php
[
    'discount' => 'discount must be a valid percentage'
]
```

---

## Array membership and constraints

#### `in(string $field, array $values, ?string $message = null)`

If present and non-empty, field must be one of the specified values

```php
$payload = [
    'role' => 'owner'
];

$result = new Validator()
    ->in('role', ['admin', 'editor', 'viewer'])
    ->validate($payload);
```

```php
[
    'role' => 'role must be one of the specified values: admin, editor, viewer'
]
```

---

#### `inEnum(string $field, string $enum, ?string $message = null)`

If present and non-empty, field must be one of the enum's values

```php
enum Role: string
{
    case ADMIN  = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';
}

$payload = [
    'role' => 'owner'
];

$result = new Validator()
    ->inEnum('role', Role::class)
    ->validate($payload);
```

```php
[
    'role' => 'role must be one of the specified values: admin, editor, viewer'
]
```

---

#### `itemsList(string $field, ?string $message = null)`

If present and non-empty, field must be a list

```php
$payload = [
    'categories' => [
        0 => 'foo',
        1 => 'bar',
        3 => 'baz'
    ]
];

$result = new Validator()
    ->itemsList('categories')
    ->validate($payload);
```

```php
[
    'categories' => 'categories must be a list'
]
```

---

#### `itemsIn(string $field, array $values, ?string $message = null)`

If present and non-empty, all items in the field must be one of the specified values

```php
$payload = [
    'categories' => ['foo', 'bar', 'baz']
];

$result = new Validator()
    ->itemsIn('categories', ['foo', 'bar'])
    ->validate($payload);
```

```php
[
    'categories' => 'categories items must be one of the specified values: foo, bar'
]
```

---

#### `itemsMax(string $field, int $maxCount, ?string $message = null)`
#### `itemsMin(string $field, int $minCount, ?string $message = null)`
#### `itemsCount(string $field, int $count, ?string $message = null)`

If present and non-empty, field must have a maximum (minimum, or exact) specified count of items

```php
$payload = [
    'ids' => [123, 456, 789]
];

$result = new Validator()
    ->itemsMax('ids', 2)
    ->validate($payload);
```

```php
[
    'ids' => 'ids must have a maximum count of 2 items'
]
```

---

#### `schema(string $field, self $schema, ?string $message = null)`

If present and non-empty, field must be an array and pass the nested validation

The first nested error message is delegated into the message template as `{error}` (see _Messages and templating_ section)

```php
$payload = [
    'address' => [
        'city' => 'Prague'
    ]
];

$result = new Validator()
    ->schema('address', new Validator()
        ->require('city')
        ->require('zip'))
    ->validate($payload);
```

```php
[
    'address' => 'address has invalid value: zip is required'
]
```

---

## Custom callbacks

You can define your own rules and apply them to payload fields

### One-off validation

#### `custom(string $field, callable $callback, ?string $message = null, ...$args)`

Validate using provided callback

```php
$payload = [
    'number' => 15
];

$result = new Validator()
    ->custom(
        'number',
        fn (mixed $value): bool => intval($value) % 2 === 0,
        'Must be an even number'
    )
    ->validate($payload);
```

```php
[
    'number' => 'Must be an even number'
]
```

---

### Reusable validation

#### `register(string $name, callable $callback, ?string $message = null)`

Register a new rule...

```php
$validator = new Validator()
    ->register(
        'even',
        fn (mixed $value): bool => intval($value) % 2 === 0,
        'Must be an even number'
    );
```

...and use it like a built-in

```php
$payload = [
    'number' => 15
];

$result = $validator
    ->even('number')
    ->validate($payload);
```

```php
[
    'number' => 'Must be an even number'
]
```

---

### Empty values

Every applied rule validates the field regardless of its presence or value

If you want to skip on empty values just like built-ins do, use `Validator::isEmpty()` check

```php
$payload = [
    'number' => ''
];

$validator = new Validator()
    ->register(
        'even',
        fn (mixed $value): bool => Validator::isEmpty($value)
            || intval($value) % 2 === 0,
        'Must be an even number'
    );

$result = $validator
    ->even('number')
    ->validate($payload);
```

```php
[] // valid, no errors
```

---

### Callback parameters

If a validation callback accepts additional parameters, you can supply them as additional variadic parameters

```php
$validator = new Validator()
    ->custom(
        'alarm',
        fn (mixed $value, bool $format24 = true): bool => is_int($value) && $format24
            ? $value <= 23 && $value >= 0
            : $value <= 12 && $value >= 1,
        'Must be a valid hour',
        false
    );
```

...or

```php
$validator = new Validator()
    ->register(
        'hour',
        fn (mixed $value, bool $format24 = true): bool => is_int($value) && $format24
            ? $value <= 23 && $value >= 0
            : $value <= 12 && $value >= 1,
        'Must be a valid hour',
    );

// ...

$validator->hour('alarm', false);
```

---

#### Positional and named parameters

Registered custom rules with additional parameters can be called with positional and/or named parameters

```php
$validator->hour('alarm', format24: true);
// - or -
$validator->hour('alarm', true);
```

---

#### Variadic parameters

When passing a variadic parameter by name, it must be supplied as an array (even with a single value)

```php
$validator = new Validator()
    ->register(
        'oneOf',
        fn (mixed $value, mixed ...$values): bool => in_array($value, $values, true),
        'Must be one of the allowed values'
    );

// ...

$validator->oneOf('category', values: ['one', 'two', 'three']);
// - or -
$validator->oneOf('category', 'one', 'two', 'three');
```

---

### Callback types

You can supply validation callback in multiple ways

Provided callback must accept value as a first parameter

Applies to both `custom()` and `register()` functions

#### Anonymous function

```php
$validator = new Validator()
    ->register(
        'even',
        fn (mixed $value): bool => intval($value) % 2 === 0,
        'Must be an even number'
    );
```

---

#### Function name string

```php
function is_even(mixed $value): bool
{
    return intval($value) % 2 === 0;
}

$validator = new Validator()
    ->register(
        'even',
        'is_even',
        'Must be an even number'
    );
```

---

#### Static method string

```php
class NumberUtility
{
    public static function is_even(mixed $value): bool
    {
        return intval($value) % 2 === 0;
    }
}

$validator = new Validator()
    ->register(
        'even',
        NumberUtility::class . '::is_even',
        'Must be an even number'
    );
```

---

#### Invokable object

```php
class IsEven
{
    public function __invoke(mixed $value): bool
    {
        return intval($value) % 2 === 0;
    }
}

$validator = new Validator()
    ->register(
        'even',
        new IsEven(),
        'Must be an even number'
    );
```

---

#### Instance method array callable

```php
class NumberUtility
{
    public function is_even(mixed $value): bool
    {
        return intval($value) % 2 === 0;
    }
}

$numberUtility = new NumberUtility();

$validator = new Validator()
    ->register(
        'even',
        [$numberUtility, 'is_even'],
        'Must be an even number'
    );
```

---

#### Static method array callable

```php
class NumberUtility
{
    public static function is_even(mixed $value): bool
    {
        return intval($value) % 2 === 0;
    }
}

$validator = new Validator()
    ->register(
        'even',
        [NumberUtility::class, 'is_even'],
        'Must be an even number'
    );
```

---

## Attribute binding

You can apply rules using attributes and bind them to the validator

### Validation metadata

```php
use Oasys\Validation\ValidationAttribute;

class RegisterUserDto
{
    #[ValidationAttribute('require')]
    #[ValidationAttribute('maxLength', 255)]
    public string $email;

    #[ValidationAttribute('minLength', 8)]
    public string $password;
}
```

---

### Binding rules

#### `bind(string $fqcn, ?string $prefix = null, string $separator = '-')`

```php
$payload = [
    'email'    => '',
    'password' => 'secret'
];

$result = new Validator()
    ->bind(RegisterUserDto::class)
    ->validate($payload);
```

```php
[
    'email'    => 'email is required',
    'password' => 'password must have a minimum length of 8 characters'
]
```

---

### Field prefix

```php
$payload = [
    'account-email'    => '',
    'account-password' => 'secret'
];

$result = new Validator()
    ->bind(RegisterUserDto::class, 'account')
    ->validate($payload);
```

```php
[
    'account-email'    => 'account-email is required',
    'account-password' => 'account-password must have a minimum length of 8 characters'
]
```

...or define your own separator

```php
$payload = [
    'account.email'    => '',
    'account.password' => 'secret'
];

// ...

$validator->bind(RegisterUserDto::class, 'account', '.');
```

---

## Messages and templating

### Per-rule message override

You can override rule's default error message by supplying your own as the last parameter

`require()`, `allow()` and `notEmpty()` don't take custom messages as a parameter, use global dictionary override (see below) instead

```php
$payload = [
    'discount' => 150
];

$result = new Validator()
    ->percent('discount', 'Must be a number 0-100')
    ->validate($payload);
```

```php
[
    'discount' => 'Must be a number 0-100'
]
```

---

### Global dictionary override

You can override selected or all default error messages by supplying your own as `array<functionName, errorMessage>` to the constructor's second parameter

```php
$payload = [
    'title' => ''
];

$validator = new Validator(
    messages: [
        'require' => 'Required field'
    ]
);

$result = $validator
    ->require('title')
    ->validate($payload);
```

```php
[
    'title' => 'Required field'
]
```

---

### Template variables

If a validation callback accepts additional parameters, you can include their values in your error messages using `{parameterName}` notation

Field's name is always available as `{field}`

```php
$payload = [
    'number' => 15
];

$result = new Validator()
    ->custom(
        'number',
        fn (mixed $value, int $min, int $max): bool => intval($value) >= $min && intval($value) <= $max,
        '{field} must be between {min} and {max}',
        20,
        50
    )
    ->validate($payload);
```

```php
[
    'number' => 'number must be between 20 and 50'
]
```

---

## Design notes

- Only the first failed rule is returned for each field
- Require and allow checks run before field rules
- Built-in rules skip empty fields, ensure presence with `require()` or `notEmpty()`
- If key is not present in the data, `null` is passed as value to the callback
- Rules are accumulated on the validator instance, reuse intentionally
