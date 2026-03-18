<?php declare(strict_types=1);

namespace Oasys\Validation\Traits;

use ReflectionEnum;
use InvalidArgumentException;

/**
 * Data validator format methods
 */
trait ValidatorFormatTrait
{
  /**
   * Validate that a field's value matches a custom regex pattern
   *
   * @param string      $field   The name of the field to validate
   * @param string      $pattern The custom regular expression pattern
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on invalid pattern
   */
  public function regex(string $field, string $pattern, ?string $message = null): self
  {
    $valid = self::_regexPatternValid($pattern);

    if ($valid !== true) {
      throw new InvalidArgumentException(sprintf(
        'Invalid pattern "%s": pattern contains errors - %s.',
        $pattern,
        $valid
      ));
    }

    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_string($value) && preg_match($pattern, $value) === 1,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value matches a predefined format pattern
   *
   * @param string      $field   The name of the field to validate
   * @param string      $type    The name of the predefined format pattern
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   *
   * @throws InvalidArgumentException on unknown format pattern
   */
  public function format(string $field, string $type, ?string $message = null): self
  {
    if (! isset($this->patterns[$type])) {
      throw new InvalidArgumentException(sprintf(
        'Invalid format name "%s": format does not exist.',
        $type
      ));
    }

    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_string($value) && preg_match($this->patterns[$type], $value) === 1,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'type')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is of a specified type
   * 
   * @param string      $field   The name of the field to validate
   * @param string      $type    Expected type (supports short-hands int, bool, float)
   * @param string|null $message Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
   */
  public function type(string $field, string $type, ?string $message = null): self
  {
    static $aliases = [
      'int'   => 'integer',
      'bool'  => 'boolean',
      'float' => 'double'
    ];

    $normalized  = $aliases[strtolower($type)] ?? $type;
    $isClassLike = class_exists($normalized) || interface_exists($normalized);
    $parts       = explode('\\', $normalized);

    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || gettype($value) === $normalized
        || is_object($value) && $isClassLike && $value instanceof $normalized,
      [
        $message ?? $this->messages[__FUNCTION__],
        [
          'field' => $field,
          'type'  => end($parts)
        ]
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is one of the specified values
   *
   * @param string      $field   The name of the field to validate
   * @param list<mixed> $values  An array of allowed values
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function in(string $field, array $values, ?string $message = null): self
  {
    if ($values === []) {
      throw new InvalidArgumentException('Invalid argument: haystack cannot be empty.');
    }

    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || in_array($value, $values, true),
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'values')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is one of the enum cases
   *
   * @param string      $field   The name of the field to validate
   * @param string      $enum    Enum FQCN
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   * 
   * @throws InvalidArgumentException on invalid enum
   */
  public function inEnum(string $field, string $enum, ?string $message = null): self
  {
    if (! enum_exists($enum, true)) {
      throw new InvalidArgumentException(sprintf(
        'Invalid enum "%s": enum does not exist.',
        $enum
      ));
    }

    $type = (new ReflectionEnum($enum))
      ->getBackingType();

    $cases = array_column(
      $enum::cases(),
      is_null($type)
        ? 'name'
        : 'value'
    );

    if ($cases === []) {
      throw new InvalidArgumentException('Invalid argument: haystack enum cannot be empty.');
    }

    return $this->in(
      $field,
      $cases,
      $message ?? $this->messages[__FUNCTION__]
    );
  }

  /**
   * Validate that a field's value is a valid percentage (numeric and between 0 and 100)
   *
   * @param string      $field   The name of the field to validate
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function percent(string $field, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_numeric($value) && (float) $value >= 0 && (float) $value <= 100,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value has an exact length
   *
   * @param string      $field   The name of the field to validate
   * @param int         $length  The required length
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function length(string $field, int $length, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_string($value) && mb_strlen($value) === $length,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'length')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value does not exceed a maximum length
   *
   * @param string      $field     The name of the field to validate
   * @param int         $maxLength The maximum allowed length
   * @param string|null $message   Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function maxLength(string $field, int $maxLength, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_string($value) && mb_strlen($value) <= $maxLength,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'maxLength')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value meets a minimum length
   *
   * @param string      $field     The name of the field to validate
   * @param int         $minLength The minimum required length
   * @param string|null $message   Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function minLength(string $field, int $minLength, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_string($value) && mb_strlen($value) >= $minLength,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'minLength')
      ]
    ];

    return $this;
  }
}
