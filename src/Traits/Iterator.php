<?php declare(strict_types=1);
namespace Precise\Traits;

trait Iterator
{
  /**
   * Iterator::current implementation
   */
  public function current(): mixed
  {
    return current($this->_ir);
  }

  /**
   * Iterator::key implementation
   */
  public function key(): mixed
  {
    return key($this->_ir);
  }

  /**
   * Iterator::next implementation
   */
  public function next(): void
  {
    next($this->_ir);
  }

  /**
   * Iterator::rewind implementation
   */
  public function rewind(): void
  {
    reset($this->_ir);
  }

  /**
   * Iterator::valid implementation
   */
  public function valid(): bool
  {
    return !is_null(key($this->_ir));
  }
}
