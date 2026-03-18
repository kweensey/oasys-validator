<?php declare(strict_types=1);

namespace Oasys\Validation;

use Attribute;

/**
 * Represents a validation rule for the field
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class ValidationAttribute
{
  /** @var array Arguments */
  public array $args;

  public function __construct(
    /** @var string Function name */
    public string $function,

    /** @param mixed Arguments <scalar> */
    mixed ...$args
  ) {
    $this->args = $args;
  }
}
