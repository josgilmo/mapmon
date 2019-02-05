<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Mapmon\Model;
use Mapmon\Mapper;
use Prophecy\Argument;

class MapperTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $connection = new \MongoDB\Client('mongodb://mongo:27017');
        \Mapmon\Mapper::setDatabase($connection->sample);
        Model::setDafaultCollectionName("default");
    }


    public function testFindOneOnEmptyCollection()
    {
        $model = $this->prophesize(Model::class);
        $mapper = new Mapper(Model::class);
        
        $database = $this->prophesize(\MongoDB\Database::class);
        $collection = $this->prophesize(\MongoDB\Collection::class);
        $database->default = $collection->reveal();

        Mapper::setDatabase($database->reveal());
        Model::setDafaultCollectionName("default");

        $res = $mapper->findOne();
        $this->assertNull($res);
    }

    public function testFindWithoutResultsCollection()
    {
        $model = $this->prophesize(Model::class);
        $mapper = new Mapper(Model::class);
        
        $database = $this->prophesize(\MongoDB\Database::class);
        $collection = $this->prophesize(\MongoDB\Collection::class);

        $cursor = $this->prophesize(MongoDB\Driver\Cursor::class);
        $collection->find(Argument::any(), ['limit' => 1])->willReturn($cursor->reveal());

        $database->default = $collection->reveal();

        Mapper::setDatabase($database->reveal());

        $res = $mapper->find([], ['limit' => 1])->get();
        $this->assertEquals(0, count($res));
    }

   


    public function testFetchObject()
    {
        $this->markTestIncomplete();

        /*
                $data = $this->_getSimpleData();
                $model = $this->prophesize(Model::class);
                //$modelProphecy->_collectionName = "collection";
                $mapper = new Mapper($model->reveal());

                //$result = $mapper->fetchObject($data);
                $this->assertInstanceOf('\Model\Simple', $result);
                $this->assertSame($data, get_object_vars($result));
        */
    }

    protected function getSimpleData()
    {
        return array('test' => 'test');
    }
}
