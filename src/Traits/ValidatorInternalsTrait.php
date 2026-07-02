<?php declare(strict_types=1);

namespace Oasys\Validation\Traits;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Stringable;

/**
 * Data validator internal helpers
 */
trait ValidatorInternalsTrait
{
  /**
   * Normalize method parameters
   * 
   * @internal
   * 
   * @param callable $callback Callback
   * @param array    $args     Arguments
   * 
   * @return array{0: array<string,mixed>, 1: list<mixed>} Message template variables + method call arguments
   * 
   * @throws InvalidArgumentException on non-reflectable callback
   * @throws InvalidArgumentException on callback missing value parameter
   * @throws InvalidArgumentException on unknown named parameter
   * @throws InvalidArgumentException on too many positional parameters
   * @throws InvalidArgumentException on duplicate parameters
   * @throws InvalidArgumentException on named variadic parameter non-array value
   * @throws InvalidArgumentException on unresolvable default value
   * @throws InvalidArgumentException on missing required parameter
   * 
   */
  protected static function _normalizeParameters(callable $callback, array $args): array
  {
    try {
      $method = match (true) {
        $callback instanceof Closure                                 => new ReflectionFunction($callback),
        is_array($callback)                                          => new ReflectionMethod($callback[0], $callback[1]),
        is_string($callback) && str_contains($callback, '::')        => ReflectionMethod::createFromMethodName($callback),
        is_object($callback) && method_exists($callback, '__invoke') => new ReflectionMethod($callback, '__invoke'),
        default                                                      => new ReflectionFunction($callback)
      };
    } catch (ReflectionException $exception) {
      throw new InvalidArgumentException(
        'Invalid validation callback: not reflectable.',
        previous: $exception
      );
    }

    $parameters = $method->getParameters();

    if ($parameters === []) {
      throw new InvalidArgumentException(sprintf(
        'Invalid validation callback "%s": must have at least one parameter for value.',
        $method->getName()
      ));
    }

    array_shift($parameters);

    $positional = [];
    $named      = [];

    foreach ($args as $key => $value) {
      if (is_int($key)) {
        $positional[] = $value;
      } else {
        $named[$key] = $value;
      }
    }

    $positionalCount = count($positional);
    $parametersCount = count($parameters);

    $names = [];

    foreach ($parameters as $i => $parameter) {
      $name = $parameter->getName();

      $names[$name] = $i;
    }

    foreach (array_keys($named) as $name) {
      if (! isset($names[$name])) {
        throw new InvalidArgumentException(sprintf(
          'Invalid argument for method "%s": unknown named parameter "%s".',
          $method->getName(),
          $name
        ));
      }
    }

    if ($positional !== []
      && ! ($parameters !== [] && $parameters[array_key_last($parameters)]->isVariadic())
      && $positionalCount > $parametersCount
    ) {
      throw new InvalidArgumentException(sprintf(
        'Invalid arguments for method "%s": too many positional parameters (%d extra).',
        $method->getName(),
        $positionalCount - $parametersCount
      ));
    }

    if ($positional !== [] && $named !== []) {
      $limit = min($positionalCount, $parametersCount);
      
      for ($i = 0; $i < $limit; $i++) {
        $name = $parameters[$i]->getName();

        if (array_key_exists($name, $named)) {
          throw new InvalidArgumentException(sprintf(
            'Invalid arguments for method "%s": parameter "%s" supplied both positionally and by name.',
            $method->getName(),
            $name
          ));
        }
      }
    }

    $namedMaxIndex = -1;

    foreach (array_keys($named) as $name) {
      if ($names[$name] > $namedMaxIndex) {
        $namedMaxIndex = $names[$name];
      }
    }

    $positionalMaxIndex = array_key_last($positional) ?? -1;
    $suppliedMaxIndex   = max($positionalMaxIndex, $namedMaxIndex);

    $variables = [];
    $arguments = [];

    foreach ($parameters as $i => $parameter) {
      $name = $parameter->getName();

      if (array_key_exists($name, $named)) {
        $value = $named[$name];

        if ($parameter->isVariadic()) {
          if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidArgumentException(sprintf(
              'Invalid arguments for method "%s": variadic parameter "%s" must be a list array when passed by name.',
              $method->getName(),
              $name
            ));
          }

          $variables[$name] = $value;

          foreach ($value as $item) {
            $arguments[] = $item;
          }
        } else {
          $variables[$name] = $value;
          $arguments[]      = $value;
        }
      } else if (array_key_exists($i, $positional)) {
        if ($parameter->isVariadic()) {
          $items = array_slice($positional, $i);

          $variables[$name] = $items;

          foreach ($items as $item) {
            $arguments[] = $item;
          }
        } else {
          $variables[$name] = $positional[$i];
          $arguments[]      = $positional[$i];
        }
      } else if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
        try {
          $value = $parameter->getDefaultValue();
        } catch (ReflectionException $exception) {
          throw new InvalidArgumentException(
            sprintf(
              'Invalid arguments for method "%s": cannot resolve default value for parameter "%s", must be supplied.',
              $method->getName(),
              $name
            ),
            previous: $exception
          );
        }

        $variables[$name] = $value;

        if ($i < $suppliedMaxIndex) {
          $arguments[] = $value;
        }
      } else {
        throw new InvalidArgumentException(sprintf(
          'Invalid arguments for method "%s": missing required parameter "%s".',
          $method->getName(),
          $name
        ));
      }
    }

    return [$variables, $arguments];
  }

  /**
   * Check if value is a valid regex pattern
   * 
   * @internal
   * 
   * @param string $pattern The regular expression pattern
   * 
   * @return true|string True if valid, otherwise error message
   */
  protected static function _regexPatternValid(string $pattern): true|string
  {
    static $cache = [];

    if (isset($cache[$pattern])) {
      return $cache[$pattern];
    }

    $warning = null;

    set_error_handler(
      static function (int $severity, string $message) use (&$warning): bool {
        $warning = $message;

        return true;
      },
      E_WARNING
    );

    try {
      $result = preg_match($pattern, '');
    } finally {
      restore_error_handler();
    }

    return $cache[$pattern] = $result === false
      ? $warning ?? preg_last_error_msg()
      : true;
  }

  /**
   * Format message from template
   * 
   * @internal
   * 
   * @param string                $template Message template
   * @param array<string, mixed>  $values   Template variables (optional; default = none)
   * 
   * @return string Message template with values
   */
  protected static function _formatMessage(string $template, array $values = []): string
  {
    $variables = [];

    foreach ($values as $key => $value) {
      if (is_array($value)) {
        $value = implode(', ', array_map('strval', $value));
      } elseif (is_bool($value)) {
        $value = $value
          ? 'true'
          : 'false';
      } elseif ($value === null) {
        $value = 'null';
      } else if (is_object($value)) {
        $value = get_debug_type($value);
      } else {
        $value = (string) $value;
      }

      $variables['{' . $key . '}'] = $value;
    }

    return strtr($template, $variables);
  }
}
