<?php declare(strict_types=1);

namespace Oasys\Validation\Traits;

/**
 * Data validator comparison methods
 */
trait ValidatorCompareTrait
{
  /**
   * Validate that a field's value is greater than a specified value
   *
   * @param string      $field        The name of the field to validate
   * @param mixed       $compareValue The value to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function greaterThan(string $field, mixed $compareValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || self::isEmpty($compareValue)
        || $value > $compareValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is greater than the value of another field
   *
   * @param string      $field        The name of the field to validate
   * @param string      $compareField The name of the field to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function greaterThanField(string $field, string $compareField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || ! array_key_exists($compareField, $this->data)
        || self::isEmpty($this->data[$compareField])
        || $value > $this->data[$compareField],
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareField')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is greater than or equal to a specified value
   *
   * @param string      $field        The name of the field to validate
   * @param mixed       $compareValue The value to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function greaterOrEqualTo(string $field, mixed $compareValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || self::isEmpty($compareValue)
        || $value >= $compareValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is greater than or equal to the value of another field
   *
   * @param string      $field        The name of the field to validate
   * @param string      $compareField The name of the field to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function greaterOrEqualToField(string $field, string $compareField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || ! array_key_exists($compareField, $this->data)
        || self::isEmpty($this->data[$compareField])
        || $value >= $this->data[$compareField],
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareField')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is less than a specified value
   *
   * @param string      $field        The name of the field to validate
   * @param mixed       $compareValue The value to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function lessThan(string $field, mixed $compareValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || self::isEmpty($compareValue)
        || $value < $compareValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is less than the value of another field
   *
   * @param string      $field        The name of the field to validate
   * @param string      $compareField The name of the field to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function lessThanField(string $field, string $compareField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || ! array_key_exists($compareField, $this->data)
        || self::isEmpty($this->data[$compareField])
        || $value < $this->data[$compareField],
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareField')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is less than or equal to a specified value
   *
   * @param string      $field        The name of the field to validate
   * @param mixed       $compareValue The value to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function lessOrEqualTo(string $field, mixed $compareValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || self::isEmpty($compareValue)
        || $value <= $compareValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is less than or equal to the value of another field
   *
   * @param string      $field        The name of the field to validate
   * @param string      $compareField The name of the field to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function lessOrEqualToField(string $field, string $compareField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || ! array_key_exists($compareField, $this->data)
        || self::isEmpty($this->data[$compareField])
        || $value <= $this->data[$compareField],
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareField')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is equal to a specified value
   *
   * @param string      $field        The name of the field to validate
   * @param mixed       $compareValue The value to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function equalTo(string $field, mixed $compareValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || self::isEmpty($compareValue)
        || $value == $compareValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is equal to the value of another field
   *
   * @param string      $field        The name of the field to validate
   * @param string      $compareField The name of the field to compare against
   * @param string|null $message      Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function equalToField(string $field, string $compareField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || ! array_key_exists($compareField, $this->data)
        || self::isEmpty($this->data[$compareField])
        || $value == $this->data[$compareField],
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'compareField')
      ]
    ];

    return $this;
  }
}
