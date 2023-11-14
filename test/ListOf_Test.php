<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class ListOf_Test extends TestCase
{
  public function test___construct()
  {
    $list = new ListOf([1], ['int']);
    $this->assertSame(['int'], getPrivateProp($list, '_type'));
    $this->assertSame([1], getPrivateProp($list, '_ir'));
  }

  public function test___construct_Exception_TYPE_NOT_DEFINED()
  {
    $this->expectExceptionCode(TYPE_NOT_DEFINED);
    $list = new ListOf([true, false]);
  }

  public function test___construct_Exception_TYPE_DOESNT_MATCH()
  {
    $this->expectExceptionCode(TYPE_DOESNT_MATCH);
    $list = new ListOf([true, false], ['bool', 'bool']);
  }

  public function test___construct_Exception_TYPE_MISMATCH()
  {
    $this->expectExceptionCode(TYPE_MISMATCH);
    $list = new ListOf([true, 'false'], ['bool']);
  }

  public function test_count()
  {
    $list = new ListOf([1, 2, null], ['any']);
    $this->assertSame(3, count($list));
    $list = new ListOf([], ['any']);
    $this->assertSame(0, count($list));
  }

  public function test_offsetExists()
  {
    $list = new ListOf([1, 2, 3], ['int']);
    $this->assertTrue(isset($list[1]));
    $this->assertFalse(isset($list[5]));
    $this->assertFalse(isset($list[-1]));
    $this->assertFalse(isset($list['foo']));
  }

  public function test_offsetGet()
  {
    $list = new ListOf([1, 2, 3], ['int']);
    $this->assertSame(1, $list[0]);
  }

  public function test_OffsetGet_Exception_OFFSET_DOESNT_EXIST()
  {
    $this->expectExceptionCode(OFFSET_DOESNT_EXIST);
    $list = new ListOf([1, 2, 3], ['int']);
    $x = $list[3];
  }

  public function test_toArray()
  {
    $sample = [[1], [], [3, 4, 5]];
    $list = new ListOf($sample, [['int']]);
    $this->assertSame($sample, $list->toArray(true));
  }

  public function test_offsetSet()
  {
    $list = new ListOf([1, 2], ['int']);
    $list[0] = 3;
    $list[] = 1;
    $list[3] = 0;
    $this->assertSame([3, 2, 1, 0], $list->toArray());
  }

  public function test_offsetSet_Exception_OFFSET_OUT_OF_BOUNDS()
  {
    $this->expectExceptionCode(OFFSET_OUT_OF_BOUNDS);
    $list = new ListOf([1, 2, 3], ['int']);
    $list[4] = 5;
  }

  public function test_offsetSet_Exception_TYPE_MISMATCH()
  {
    $this->expectExceptionCode(TYPE_MISMATCH);
    $list = new ListOf([1, 2, 3], ['int']);
    $list[3] = 5.5;
  }

  public function test_offsetUnset()
  {
    $list = new ListOf([1, 2, 3, 4, 5, 6, 7], ['int']);
    unset($list[6]);
    unset($list[4]);
    unset($list[2]);
    unset($list[0]);
    $this->assertSame([2, 4, 6], $list->toArray());
  }

  public function test_offsetUnset_Exception_OFFSET_OUT_OF_BOUNDS()
  {
    $this->expectExceptionCode(OFFSET_OUT_OF_BOUNDS);
    $list = new ListOf([1, 2, 3], ['int']);
    unset($list[5]);
  }

  public function test_Interface_Iterator()
  {
    $list = new ListOf([1, 2, 3, 4], ['int']);
    $sum = 0;
    foreach ($list as $n) {
      $sum += $n;
    }
    $this->assertSame(10, $sum);
  }
}
