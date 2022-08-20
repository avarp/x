<?php declare(strict_types=1);
namespace Precise;

class Error extends \Exception
{
  /**
   * Construct error
   * @param string $message human readable message
   * @param int $code the code. Must be one of predefined constants of this class
   * @param ?Throwable $previous
   */
  private function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
  {
    parent::__construct($message, $code, $previous);
  }

  public static function classNotExist(string $message): never {throw new self($message, 70515);}
}