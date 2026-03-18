<?php declare(strict_types=1);

namespace Oasys\Validation;

use Oasys\Validation\Traits\ValidatorInternalsTrait;
use Oasys\Validation\Traits\ValidatorPresenceTrait;
use Oasys\Validation\Traits\ValidatorFormatTrait;
use Oasys\Validation\Traits\ValidatorCompareTrait;
use Oasys\Validation\Traits\ValidatorItemsTrait;
use ReflectionAttribute;
use ReflectionClass;
use InvalidArgumentException;
use ReflectionException;

/**
 * Validates an array against the set of rules
 */
class Validator
{
  use ValidatorInternalsTrait;
  use ValidatorPresenceTrait;
  use ValidatorFormatTrait;
  use ValidatorCompareTrait;
  use ValidatorItemsTrait;

  public const DEFAULT_MESSAGES = [
    'require'               => '{field} is required',
    'allow'                 => '{field} is not allowed',
    'notEmpty'              => '{field} cannot be empty',
    'requireOn'             => '{field} is required when {conditionField} is set',
    'requireWhen'           => '{field} is required when {conditionField} is {conditionValue}',
    'regex'                 => '{field} has invalid format',
    'format'                => '{field} has invalid format',
    'type'                  => '{field} must be of type {type}',
    'schema'                => '{field} has invalid value: {error}',
    'schema.array'          => 'must be an array',
    'in'                    => '{field} must be one of the specified values: {values}',
    'inEnum'                => '{field} must be one of the specified values: {values}',
    'itemsIn'               => '{field} items must be one of the specified values: {values}',
    'itemsCount'            => '{field} must have an exact count of {count} items',
    'itemsMin'              => '{field} must have a minimum count of {minCount} items',
    'itemsMax'              => '{field} must have a maximum count of {maxCount} items',
    'itemsList'             => '{field} must be a list',
    'percent'               => '{field} must be a valid percentage',
    'length'                => '{field} must have an exact length of {length} characters',
    'maxLength'             => '{field} must have a maximum length of {maxLength} characters',
    'minLength'             => '{field} must have a minimum length of {minLength} characters',
    'greaterThan'           => '{field} must be greater than {compareValue}',
    'greaterThanField'      => '{field} must be greater than field {compareField}',
    'greaterOrEqualTo'      => '{field} must be greater than or equal to {compareValue}',
    'greaterOrEqualToField' => '{field} must be greater than or equal to field {compareField}',
    'lessThan'              => '{field} must be less than {compareValue}',
    'lessThanField'         => '{field} must be less than field {compareField}',
    'lessOrEqualTo'         => '{field} must be less than or equal to {compareValue}',
    'lessOrEqualToField'    => '{field} must be less than or equal to field {compareField}',
    'equalTo'               => '{field} must be equal to {compareValue}',
    'equalToField'          => '{field} must be equal to field {compareField}',
    'custom'                => '{field} has invalid value',
    '_MAGIC_'               => '{field} has invalid value'
  ];

  /**
   * @var array<string, mixed> Data to validate
   */
  protected array $data = [];

  /**
   * @var array<string, string> Validation errors
   */
  protected array $errors = [];

  /**
   * @var array<string, callable> Validation methods
   */
  protected array $methods = [];

  /**
   * @var array<string, array<int, mixed>> Validation rules
   */
  protected array $rules = [];

  /**
   * @var list<string> Required fields
   */
  protected array $required = [];

  /**
   * @var list<string> Allowed fields
   */
  protected array $allowed = [];

  /**
   * @var array<string, string> Error messages
   */
  protected array $messages = [];

  /**
   * @var array<string, string> Format patterns
   */
  protected array $patterns = [];

  /**
   * Class constructor
   * 
   * @param array<string, string> Format patterns (optional)
   * @param array<string, string> Error messages override (optional)
   * 
   * @throws InvalidArgumentException on invalid format regex pattern
   */
  public function __construct(array $patterns = [], array $messages = [])
  {
    foreach ($patterns as $type => $pattern) {
      if (! is_string($type) || ! is_string($pattern)) {
        throw new InvalidArgumentException(sprintf(
          'Invalid pattern "%s": name and pattern must be a string.',
          $type
        ));
      }

      $valid = self::_regexPatternValid($pattern);

      if ($valid !== true) {
        throw new InvalidArgumentException(sprintf(
          'Invalid pattern "%s": pattern contains errors (%s).',
          $type,
          $valid
        ));
      }
    }

    $this->patterns = $patterns;
    
    foreach ($messages as $name => $message) {
      if (! is_string($name) || ! is_string($message)) {
        throw new InvalidArgumentException(sprintf(
          'Invalid message "%s": name and message must be a string.',
          $name
        ));
      }
    }

    $this->messages = $messages + self::DEFAULT_MESSAGES;
  }

  /**
   * Validate a field's value using a registered callback function
   * 
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on unregistered method
   * @throws InvalidArgumentException on invalid field name
   */
  public function __call(string $name, array $args): self
  {
    if (! isset($this->methods[$name])) {
      throw new InvalidArgumentException(sprintf(
        'Invalid validation name "%s": method does not exist.',
        $name
      ));
    }

    $field = array_shift($args);

    if (! is_string($field)) {
      throw new InvalidArgumentException(sprintf(
        'Invalid argument for method "%s": field name must be a string as the first parameter.',
        $name
      ));
    }

    [$vars, $params] = self::_normalizeParameters($this->methods[$name], $args);

    $this->rules[$field][] = [
      $this->methods[$name],
      [
        $this->messages[$name] ?? $this->messages['_MAGIC_'],
        compact('field') + $vars
      ],
      $params
    ];

    return $this;
  }

  /**
   * Validate a field's value using a custom callback function
   *
   * @param string      $field    The name of the field to validate
   * @param callable    $callback Validation callback function
   * @param string|null $message  Custom error message (optional; default = from dictionary)
   * @param mixed       ...$args  Arguments (optional) 
   *
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on empty field name
   */
  public function custom(string $field, callable $callback, ?string $message = null, ...$args): self
  {
    [$vars, $params] = self::_normalizeParameters($callback, $args);

    $this->rules[$field][] = [
      $callback,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field') + $vars
      ],
      $params
    ];

    return $this;
  }

  /**
   * Register custom validation
   * 
   * @param string      $name     The name of the validation method
   * @param callable    $callback Validation callback function
   * @param string|null $message  Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on invalid method name
   * @throws InvalidArgumentException on duplicate method name
   */
  public function register(string $name, callable $callback, ?string $message = null): self
  {
    if ($name === '' || preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name) !== 1) {
      throw new InvalidArgumentException(sprintf(
        'Invalid validation name "%s": contains disallowed characters.',
        $name
      ));
    }

    if (method_exists($this, $name) || isset($this->methods[$name])) {
      throw new InvalidArgumentException(sprintf(
        'Invalid validation name "%s": method already exists.',
        $name
      ));
    }

    $this->messages[$name] = $message ?? $this->messages['_MAGIC_'];
    $this->methods[$name]  = $callback;

    return $this;
  }

  /**
   * Import validation rules from attributes
   *
   * @param string $fqcn      Class FQCN
   * @param string $prefix    Property prefix (optional; default = none)
   * @param string $separator Prefix separator (optional; default = dash)
   *
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on invalid class name
   */
  public function bind(string $fqcn, ?string $prefix = null, string $separator = '-'): self
  {
    if (! class_exists($fqcn) && ! trait_exists($fqcn)) {
      throw new InvalidArgumentException(sprintf(
        'Invalid target name "%s": class or trait does not exist.',
        $fqcn
      ));
    }

    try {
      $class = new ReflectionClass($fqcn);
    } catch (ReflectionException $exception) {
      throw new InvalidArgumentException(
        sprintf(
          'Invalid target name "%s": not reflectable.',
          $fqcn
        ),
        previous: $exception
      );
    }

    foreach ($class->getProperties() as $property) {
      $attributes = array_map(
        fn(ReflectionAttribute $attribute): ValidationAttribute => $attribute->newInstance(),
        $property->getAttributes(ValidationAttribute::class)
      );

      foreach ($attributes as $attribute) {
        $key = ($prefix ? $prefix . $separator : '') . $property->name;

        $this->{$attribute->function}($key, ...$attribute->args);
      }
    }

    return $this;
  }

  /**
   * Validate the provided data against the defined rules
   *
   * @param array<string, mixed> $data The data to validate
   *
   * @return array<string, string> An array of validation errors
   */
  public function validate(array $data): array
  {
    $this->data   = $data;
    $this->errors = [];

    $required = array_unique($this->required);
    $allowed  = array_unique($this->allowed);

    if ($required !== []) {
      foreach ($required as $field) {
        if (! isset($data[$field]) || self::isEmpty($data[$field])) {
          $this->errors[$field] = self::_formatMessage(
            $this->messages['require'],
            compact('field')
          );
        }
      }
    }

    if ($allowed !== []) {
      $granted = array_fill_keys(
        [...$allowed, ...$required],
        true
      );

      foreach (array_keys($data) as $field) {
        if (! isset($granted[$field])) {
          $this->errors[$field] = self::_formatMessage(
            $this->messages['allow'],
            compact('field')
          );
        }
      }
    }

    foreach ($this->rules as $field => $rules) {
      if (isset($this->errors[$field])) {
        continue;
      }

      foreach ($rules as $rule) {
        [$callback, [$message, $vars]] = $rule;

        $params = $rule[2] ?? [];

        if (! $callback($data[$field] ?? null, ...$params)) {
          $this->errors[$field] = self::_formatMessage($message, $vars);

          break;
        }
      }
    }

    return $this->errors;
  }

  /**
   * Check if value is considered empty
   * 
   * @internal
   * 
   * @param mixed $value Value to check
   * 
   * @return bool Whether value is considered empty
   */
  public static function isEmpty(mixed $value): bool
  {
    return $value === null
      || $value === ''
      || $value === [];
  }
}
