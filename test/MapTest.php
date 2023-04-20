<?php declare(strict_types=1);
namespace Precise;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
  public function test___construct()
  {
    // Construct from pairs
    $map = new Map([[true, 1], [false, 0]], ['map', 'bool', 'int']);
    $this->assertSame(1, $map[true]);
    $this->assertSame(0, $map[false]);

    // Construct from associative array
    $map = new Map(['zero' => 0, 'one' => 1, 'two' => 2], ['map', 'string', 'int']);
    $this->assertSame(0, $map['zero']);
    $this->assertSame(1, $map['one']);
    $this->assertSame(2, $map['two']);

    // Construct with different types of keys
    $object = new \stdClass();
    $map = new Map(
      [
        [42, 'int'],
        ['foo', 'string'],
        [false, 'bool'],
        [3.14, 'float'],
        [null, 'null'],
        [[3, 4, 5], 'array'],
        [$object, 'object'],
      ],
      ['map', 'any', 'string']
    );
    $this->assertSame('int', $map[42]);
    $this->assertSame('string', $map['foo']);
    $this->assertSame('bool', $map[false]);
    $this->assertSame('float', $map[3.14]);
    $this->assertSame('null', $map[null]);
    $this->assertSame('array', $map[[3, 4, 5]]);
    $this->assertSame('object', $map[$object]);
  }

  public function test_toArray()
  {
    $consts = new Map(['pi' => 3.14, 'e' => 2.7], ['map', 'string', 'float']);
    $map = new Map(['consts' => $consts, 42 => false], ['map', 'any', 'any']);
    // Plain call keeps nested map as is
    $this->assertSame([['consts', $consts], [42, false]], $map->toArray());
    // Recursive call converts nested map to array
    $this->assertSame([['consts', [['pi', 3.14], ['e', 2.7]]], [42, false]], $map->toArray(true));
  }

  public function test_canBeConvertedToAssocArray()
  {
    $map = new Map([], ['map', 'int', 'bool']);
    // Keys are integers, can be converted to associative array
    $this->assertTrue($map->canBeConvertedToAssocArray());
    $map = new Map([[0, false], ['one', true]], ['map', 'any', 'bool']);
    // Keys are integers and strings, can be converted to associative array
    $this->assertTrue($map->canBeConvertedToAssocArray());
    $map = new Map([], ['map', 'bool', 'any']);
    // Keys are boolean, impossible to convert
    $this->assertFalse($map->canBeConvertedToAssocArray());
    $map = new Map([[0.45, false], ['one', true]], ['map', 'any', 'bool']);
    // Keys are float and string, impossible to convert
    $this->assertFalse($map->canBeConvertedToAssocArray());
  }

  public function test_toAssocArray()
  {
    $consts = new Map(['pi' => 3.14, 'e' => 2.7], ['map', 'string', 'float']);
    $map = new Map(['consts' => $consts, 42 => false], ['map', 'any', 'any']);
    // Plain call keeps nested map as is
    $this->assertSame(['consts' => $consts, 42 => false], $map->toAssocArray());
    // Recursive call converts nested map to associative array
    $this->assertSame(['consts' => ['pi' => 3.14, 'e' => 2.7], 42 => false], $map->toAssocArray(true));
    $consts = new Map([[3.14, 'pi'], [2.7, 'e']], ['map', 'float', 'string']);
    $map = new Map(['consts' => $consts, 42 => false], ['map', 'any', 'any']);
    // This recursive call converts nested map to array of pairs, because it can't convert it to associative array
    $this->assertSame(['consts' => [[3.14, 'pi'], [2.7, 'e']], 42 => false], $map->toAssocArray(true));
  }

  public function test_toAssocArray_Exception_MAP_CANT_BE_ASSOC()
  {
    // If conversion required to be strict, but there is any map that can't be converted, the exception will be thrown
    $this->expectExceptionCode(MAP_CANT_BE_ASSOC);
    $consts = new Map([[3.14, 'pi'], [2.7, 'e']], ['map', 'float', 'string']);
    $map = new Map(['consts' => $consts, 42 => false], ['map', 'any', 'any']);
    $map->toAssocArray(true, true);
  }

  public function test_checkType()
  {
    $type = ['map', 'int', 'bool'];

    // Not an array
    $this->assertFalse(Map::checkType($type, null));
    $this->assertSame(
      '$value is expected to be an associative array or list of pairs but it is NULL.',
      Map::getLastTypeError()
    );

    // List, but not all of them are pairs
    $this->assertFalse(Map::checkType($type, [[1, 2], 3]));
    $this->assertSame('$value[1] is expected to be a pair of values but it is 3.', Map::getLastTypeError());

    // Associative array, but one key has wrong type
    $this->assertFalse(Map::checkType($type, [5 => true, 'o' => false]));
    $this->assertSame('2nd key of $value is expected to be an integer but it is "o".', Map::getLastTypeError());

    // Associative array, but one value has wrong type
    $this->assertFalse(Map::checkType($type, [5 => true, 0 => 'false']));
    $this->assertSame('$value[0] is expected to be a boolean value but it is "false".', Map::getLastTypeError());

    // Correct list of pairs
    $this->assertTrue(Map::checkType($type, [[5, true], [0, false]]));

    // Correct associative array
    $this->assertTrue(Map::checkType($type, [5 => true, 0 => false]));
  }

  public function test_equal()
  {
    $map = new Map(['pi' => 3.14, 'e' => 2.7], ['map', 'string', 'float']);

    // Equal
    $this->assertTrue($map->equal(new Map(['pi' => 3.14, 'e' => 2.7], ['map', 'string', 'float'])));
    $this->assertTrue($map->equal(['e' => 2.7, 'pi' => 3.14]));
    $this->assertTrue($map->equal([['pi', 3.14], ['e', 2.7]]));

    // Not equal
    $this->assertFalse($map->equal(3));
    $this->assertFalse($map->equal(new Map(['pi' => 3, 'e' => 2], ['map', 'string', 'int'])));
    $this->assertFalse($map->equal(new Map(['pi' => 3.14, 'e' => 2.7, 'f' => 1.6], ['map', 'string', 'float'])));
    $this->assertFalse($map->equal(['pi' => 2.7, 'e' => 3.14]));
  }
}
