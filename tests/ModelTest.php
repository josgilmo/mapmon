<?php

namespace Mapmon\Tests;

use PHPUnit\Framework\TestCase;

use \Mapmon\Model;

class ModelTest extends TestCase
{

    public function testFill() {

        $model = new Model();
        $model->fill(['field1'=> 'value1']);
        $this->assertEquals('value1', $model->field1);

    }

    public function testFillComplex() {

        $model = new Model();
        $model->fill(['field1'=>'value1',  'field2' => ['fieldIn1' => "valueIn1"]]);
        $this->assertEquals('value1', $model->field1);
        $this->assertEquals('valueIn1', $model->field2['fieldIn1']);
    }

    public function testFillWithId() {
        $model = new Model();
        $model->_id = "DFKDF";
        $model->fill(['field1' => 'value1'] );
        $this->assertEquals('value1', $model->field1);
        $this->assertEquals('DFKDF', $model->id);
    }

    public function testGetMapper() {

        $connection = new \MongoDB\Client("mongodb://mongo:27017");
        \Mapmon\Mapper::setDatabase( $connection->sample );

        $model = new Model();
        $mapper = $model->getMapper();
        $this->assertInstanceOf(\Mapmon\Mapper::class, $mapper);
    }

    public function testGetCollectionName() {
        $this->markTestIncomplete();
    }

	/**
	 * @expectedException Exception
	 */	
    public function testGetCollectionNameWithException() {
        $model = new Model();
        $model->getCollectionName();
    }

    public function testRemove() {

        $connection = new \MongoDB\Client("mongodb://mongo:27017");
        \Mapmon\Mapper::setDatabase( $connection->sample );

   
        $model = new Model();
        $this->markTestIncomplete();

        $model->remove();
    }
}
