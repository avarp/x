<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class ListOfTest extends TestCase
{
  public function testConstruct1()
  {
    $list = new ListOf([1], ['int']);
    $this->assertSame(1, $list[0]);
  }

  public function testConstruct2()
  {
    $this->expectExceptionCode(TYPE_NOT_DEFINED);
    $list = new ListOf([true, false]);
  }

  public function testConstruct3()
  {
    $this->expectExceptionCode(TYPE_DOESNT_MATCH);
    $list = new ListOf([true, false], ['bool', 'bool']);
  }

  public function testConstruct4()
  {
    $this->expectExceptionCode(TYPE_ERROR);
    $list = new ListOf([true, 'false'], ['bool']);
  }

  public function testCount()
  {
    $list = new ListOf([1, 2, null], ['any']);
    $this->assertSame(3, count($list));
    $list = new ListOf([], ['any']);
    $this->assertSame(0, count($list));
  }

  public function testOffsetExists()
  {
    $list = new ListOf([1, 2, 3], ['int']);
    $this->assertTrue(isset($list[1]));
    $this->assertFalse(isset($list[5]));
    $this->assertFalse(isset($list[-1]));
    $this->assertFalse(isset($list['foo']));
  }

  public function testOffsetGet1()
  {
    $list = new ListOf([1, 2, 3], ['int']);
    $this->assertSame(1, $list[0]);
  }

  public function testOffsetGet2()
  {
    $this->expectExceptionCode(OFFSET_DOESNT_EXIST);
    $list = new ListOf([1, 2, 3], ['int']);
    $x = $list[3];
  }

  public function testToArray()
  {
    $sample = [[1], [], [3, 4, 5]];
    $list = new ListOf($sample, [['int']]);
    $this->assertSame($sample, $list->toArray(true));
  }

  public function testOffsetSet1()
  {
    $list = new ListOf([1, 2], ['int']);
    $list[0] = 3;
    $list[] = 1;
    $list[3] = 0;
    $this->assertSame([3, 2, 1, 0], $list->toArray());
  }

  public function testOffsetSet2()
  {
    $this->expectExceptionCode(OFFSET_OUT_OF_BOUNDS);
    $list = new ListOf([1, 2, 3], ['int']);
    $list[4] = 5;
  }

  public function testOffsetSet3()
  {
    $this->expectExceptionCode(TYPE_ERROR);
    $list = new ListOf([1, 2, 3], ['int']);
    $list[3] = 5.5;
  }

  public function testOffsetUnset1()
  {
    $list = new ListOf([1, 2, 3, 4, 5, 6, 7], ['int']);
    unset($list[6]);
    unset($list[4]);
    unset($list[2]);
    unset($list[0]);
    $this->assertSame([2, 4, 6], $list->toArray());
  }

  public function testOffsetUnset2()
  {
    $this->expectExceptionCode(OFFSET_OUT_OF_BOUNDS);
    $list = new ListOf([1, 2, 3], ['int']);
    unset($list[5]);
  }

  public function testIterator()
  {
    $list = new ListOf([1, 2, 3, 4], ['int']);
    $sum = 0;
    foreach ($list as $n) {
      $sum += $n;
    }
    $this->assertSame(10, $sum);
  }

  public function testChunk()
  {
    $list = new ListOf([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], ['int']);
    $chunks = $list->chunk(3);
    $this->assertTrue($chunks instanceof ListOf);
    $this->assertSame([['int']], $chunks->getType());
    $this->assertSame([[0, 1, 2], [3, 4, 5], [6, 7, 8], [9]], $chunks->toArray(true));
  }

  public function testFill1()
  {
    $list = ListOf::fill(3, 0.5);
    $this->assertSame(['float'], $list->getType());
    $this->assertSame([0.5, 0.5, 0.5], $list->toArray());
  }

  public function testFill2()
  {
    $list = ListOf::fill(3, 1, ['float']);
    $this->assertSame(['float'], $list->getType());
    $this->assertSame([1.0, 1.0, 1.0], $list->toArray());
  }
}
