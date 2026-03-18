<?php declare(strict_types=1);

namespace Oasys\Validation\Traits;

use InvalidArgumentException;

/**
 * Data validator collection methods
 */
trait ValidatorItemsTrait
{
  /**
   * Validate that a field's value passes its own validation
   * 
   * @param string      $field   The name of the field to validate
   * @param self        $schema  Validator
   * @param string|null $message Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
  */
  public function schema(string $field, self $schema, ?string $message = null): self
  {
    $error = null;

    $this->rules[$field][] = [
      function (mixed $value) use ($schema, &$error): bool {
        if (self::isEmpty($value)) {
          return true;
        }

        if (! is_array($value)) {
          $error = $this->messages['schema.array'];

          return false;
        }

        $errors = $schema->validate($value);

        if ($errors === []) {
          return true;
        }

        $error = reset($errors);

        return false;
      }, [
        $message ?? $this->messages[__FUNCTION__],
        [
          'field' => $field,
          'error' => &$error
        ]
      ]
    ];

    return $this;
  }

  /**
   * Validate that field's every value is one of the specified values
   *
   * @param string      $field   The name of the field to validate
   * @param list<mixed> $values  An array of allowed values
   * @param string|null $message Custom error message (optional; default = from dictionary)
   *
   * @return self Self-reference
   */
  public function itemsIn(string $field, array $values, ?string $message = null): self
  {
    if ($values === []) {
      throw new InvalidArgumentException('Invalid argument: haystack cannot be empty.');
    }

    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_array($value) && ! array_filter(
          $value,
          static fn(mixed $item): bool => ! in_array($item, $values, true)
        ),
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'values')
      ]
    ];

    return $this;
  }

  /**
   * Validate that field's item count is exactly specified value
   * 
   * @param string      $field   The name of the field to validate
   * @param int         $count   The required item count
   * @param string|null $message Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
   */
  public function itemsCount(string $field, int $count, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_array($value) && count($value) === $count,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'count')
      ]
    ];

    return $this;
  }

  /**
   * Validate that field's item count is at least specified value
   * 
   * @param string      $field    The name of the field to validate
   * @param int         $minCount The minimal item count
   * @param string|null $message  Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
   */
  public function itemsMin(string $field, int $minCount, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_array($value) && count($value) >= $minCount,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'minCount')
      ]
    ];

    return $this;
  }

  /**
   * Validate that field's item count is at most specified value
   * 
   * @param string      $field    The name of the field to validate
   * @param int         $maxCount The maximal item count
   * @param string|null $message  Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
   */
  public function itemsMax(string $field, int $maxCount, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_array($value) && count($value) <= $maxCount,
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field', 'maxCount')
      ]
    ];

    return $this;
  }

  /**
   * Validate that field is a list
   * 
   * @param string      $field   The name of the field to validate
   * @param string|null $message Custom error message (optional; default = from dictionary)
   * 
   * @return self Self-reference
   */
  public function itemsList(string $field, ?string $message = null): self
  {
    $this->rules[$field][] = [
      fn(mixed $value): bool => self::isEmpty($value)
        || is_array($value) && array_is_list($value),
      [
        $message ?? $this->messages[__FUNCTION__],
        compact('field')
      ]
    ];

    return $this;
  }
}
