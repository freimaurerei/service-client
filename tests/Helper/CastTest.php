<?php

namespace Freimaurerei\ServiceClient\Helper;

class CastTest extends \PHPUnit_Framework_TestCase
{
    public function testCastToFloat()
    {
        $this->assertSame(1.0, Cast::toFloat('1'));
        $this->assertNull(Cast::toFloat(null));
    }

    public function testCastToInt()
    {
        $this->assertSame(1, Cast::toInt('1'));
        $this->assertNull(Cast::toInt(null));
    }

    public function testCastToArray()
    {
        $this->assertSame([1], Cast::toArray('1', 'int'));
        $this->assertSame(['1'], Cast::toArray('1'));
        $this->assertSame(null, Cast::toArray(null));
    }

    public function testCastToAssociativeArray()
    {
        $class = new \stdClass();
        $class->{'0'} = 1;

        $this->assertEquals($class, Cast::toAssociativeArray(['1'], 'int'));

        $class->{'0'} = '1';
        $this->assertEquals($class, Cast::toAssociativeArray(['1']));
    }
}
