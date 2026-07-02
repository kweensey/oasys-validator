<?php declare(strict_types=1);

namespace Oasys\Validation\Traits;

/**
 * Data validator presence methods
 */
trait ValidatorPresenceTrait
{
  /**
   * Allow specified fields to be present in the data
   *
   * @param string ...$fields The names of fields to allow
   *
   * @return self Self-reference
   */
  public function allow(string ...$fields): self
  {
    foreach ($fields as $field) {
      $this->allowed[] = $field;
    }

    return $this;
  }

  /**
   * Require specified fields to be present and non-empty in the data
   *
   * @param string ...$fields The names of fields to require
   *
   * @return self Self-reference
   */
  public function require(string ...$fields): self
  {
    foreach ($fields as $field) {
      $this->required[] = $field;
    }

    return $this;
  }

  /**
   * Require the field to be present when another field is not empty
   *
   * @param string      $field          The name of the field to require
   * @param string      $conditionField The name of the condition field
   * @param string|null $message        Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function requireOn(string $field, string $conditionField, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => ! self::isEmpty($value)
        || ! array_key_exists($conditionField, $this->data)
        || self::isEmpty($this->data[$conditionField]),
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'conditionField')
      ]
    ];

    return $this;
  }

  /**
   * Require the field to be present when another field has specified value
   *
   * @param string      $field          The name of the field to require
   * @param string      $conditionField The name of the condition field
   * @param mixed       $conditionValue The value of the condition field
   * @param string|null $message        Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function requireWhen(string $field, string $conditionField, mixed $conditionValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => ! self::isEmpty($value)
        || ! array_key_exists($conditionField, $this->data)
        || $this->data[$conditionField] !== $conditionValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'conditionField', 'conditionValue')
      ]
    ];

    return $this;
  }

  /**
   * Require the field to be present when another field has not specified value
   *
   * @param string      $field          The name of the field to require
   * @param string      $conditionField The name of the condition field
   * @param mixed       $conditionValue The value of the condition field
   * @param string|null $message        Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function requireWhenNot(string $field, string $conditionField, mixed $conditionValue, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => ! self::isEmpty($value)
        || ! array_key_exists($conditionField, $this->data)
        || self::isEmpty($this->data[$conditionField])
        || $this->data[$conditionField] === $conditionValue,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'conditionField', 'conditionValue')
      ]
    ];

    return $this;
  }

  /**
   * Validate that a field's value is not empty
   *
   * @param string ...$fields The names of fields to validate
   *
   * @return self Self-reference
   */
  public function notEmpty(string ...$fields): self
  {
    foreach ($fields as $field) {
      $this->rules[$field][] = [
        fn(mixed $value): bool => ! array_key_exists($field, $this->data)
          || ! self::isEmpty($value),
        [
          $this->messages[__FUNCTION__],
          compact('field')
        ]
      ];
    }

    return $this;
  }
}
