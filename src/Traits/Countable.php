<?php declare(strict_types=1);
namespace Precise\Traits;

trait Countable
{
  /**
   * Countable::count implementation
   */
  public function count(): int
  {
    return count($this->_ir);
  }
}
