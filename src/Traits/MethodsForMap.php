<?php declare(strict_types=1);
namespace Precise;

trait MethodsForMap
{
  /**
   * Change case of keys. Works only with string keys.
   * @param int $case CASE_UPPER or CASE_LOWER (default)
   * @return self the current instance
   */
  public function changeKeyCase(int $case = CASE_LOWER): self
  {
    $keysType = $this->_type[1];
    if ($keysType != 'string') {
      err(__FUNCTION__ . ' can be called only to maps with string keys.', MAP_MUST_HAVE_STRING_KEYS);
    }
    $this->_ir['keys'] = array_map($case == CASE_LOWER ? strtolower(...) : strtoupper(...), $this->_ir['keys']);
    $this->_ir['strKeys'] = array_map(self::keyToString(...), $this->_ir['keys']);
    $this->_ir['values'] = array_combine($this->_ir['strKeys'], array_values($this->_ir['values']));
    return $this;
  }

  /**
   * Split map into chunks
   * @return ListOf list of maps
   */
  // public function chunk(int $length): ListOf
  // {
  // }

  /**
   * Return a column from map of maps or map of records
   * @param mixed $key the column of values to return
   * @return Map
   */
  // public function column(mixed $key): Map
  // {
  // }
}
