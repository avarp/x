<?php declare(strict_types=1);
namespace Precise;

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
