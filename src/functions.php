<?php declare(strict_types=1);
namespace Precise;

/**
 * Create type error message
 * @internal
 * @param string $path the name of variable for error messages
 * @param string $expectedType
 * @param mixed $actualValue
 */
function errMsg(string $path, string $expectedType, $actualValue): string
{
  if (is_scalar($actualValue)) {
    if (is_string($actualValue)) {
      $actualValue = '"' . $actualValue . '"';
    } else {
      $actualValue = (string) $actualValue;
    }
  } else {
    $actualValue = gettype($actualValue);
  }
  return "$path is expected to be $expectedType but it is $actualValue.";
}

/**
 * Array index to ordinal number
 * @internal
 * @param int $index
 * @return string
 */
function nth(int $index): string
{
  return match ($index) {
    0 => '1st',
    1 => '2nd',
    2 => '3rd',
    default => $index . 'th',
  };
}

/**
 * Throw an exception.
 * @internal
 * @param string $msg
 * @param int $code
 */
function err(string $msg, int $code): never
{
  foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $place) {
    if (pathinfo($place['file'], PATHINFO_DIRNAME) != __DIR__) {
      break;
    }
  }
  throw new \Exception(rtrim($msg, '.') . ". Possible reason is here: $place[file]:$place[line].", $code);
}

/**
 * Type cast of the given value.
 * @internal
 * @param mixed $type
 * @param mixed $value IMPORTANT! a value should have correct type.
 * @return mixed
 */
function purify(mixed $type, mixed $value): mixed
{
  if (Type::isScalar($type)) {
    return $type == 'float' ? (float) $value : $value;
  } elseif ($value instanceof TypedValue) {
    return $value;
  } else {
    $typeClass = Type::getComplexTypeClass($type);
    if ($typeClass) {
      return new $typeClass($value, $type, true);
    } else {
      err('Unknown type of elements.', UNKNOWN_TYPE);
    }
  }
}

/**
 * Compare two values of given type
 * @internal
 * @param mixed $a IMPORTANT! $a must have correct type, if $type is complex, $a must be a TypedValue instance
 * @param mixed $b
 * @param mixed $type
 * @return bool
 */
function equal(mixed $a, mixed $b, mixed $type): bool
{
  if (Type::isScalar($type)) {
    return $a === $b;
  } else {
    return $a->equal($b);
  }
}
