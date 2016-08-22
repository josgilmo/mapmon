<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

use \Mapmon\Model;
use \Mapmon\Mapper;

class MapperTest extends TestCase
{

    public static function setUpBeforeClass() 
    {
        $connection = new \MongoDB\Client("mongodb://mongo:27017");
        \Mapmon\Mapper::setDatabase( $connection->sample );
    }
		
	public function testFetchObject()
	{
        $this->markTestIncomplete();

		$data = $this->_getSimpleData();
		$mapper = new Mapper( '\Model\Simple' );
		$result = $mapper->fetchObject( $data );
		
		$this->assertInstanceOf( '\Model\Simple', $result );
		$this->assertSame( $data, get_object_vars( $result ) );
	}
}
