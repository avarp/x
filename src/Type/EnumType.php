<?php declare(strict_types=1);
namespace Precise\Type;

class EnumType implements TypeInterface
{
  public static function matchLiteral($literal): bool
  {
    if (is_array($literal) && array_is_list($literal) && count($literal) >= 2) {
      foreach ($literal as $x) {
        if (!is_string($x) || strlen($x) < 2 || $x[0] != ':') return false;
      }
      return true;
    }
    return false;
  }

  
  public static function getTypeDetails($literal, string $context)
  {
    return array_map(function($x) {return ltrim($x, ':');}, $literal);
  }


  public static function toLiteral($enumVars)
  {
    return array_map(function($x) {return ':'.$x;}, $enumVars);
  }


  public static function infer($value): ?TypeSignature
  {
    return null;
  }


  public static function check($enumVars, $value, string $path): string
  {
    if (is_string($value) && !in_array($value, $enumVars)) {
      return Error::typeError($path, 'one of enum variants: "'.implode('", "', $enumVars).'"', $value);
    }
    if (is_int($value) && $value < 0 || $value >= count($enumVars)-1) {
      return Error::typeError($path, 'an integer from 0 to '.(count($enumVars)-1), $value);
    }
    if (!is_int($value) && !is_string($value)) {
      return Error::typeError($path, 'an integer or a string', $value);
    }
    return '';
  }
}