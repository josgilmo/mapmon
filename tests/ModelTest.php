<?php

namespace Mapmon;

use PHPUnit\Framework\TestCase;

use \Mapmon\Model;

class ModelTest extends TestCase
{
    public function testFill()
    {
        $model = Model::create(['field1'=> 'value1']);
        $this->assertEquals('value1', $model->field1);
    }

    public function testFillComplex()
    {
        $model = Model::create(['field1'=>'value1',  'field2' => ['fieldIn1' => "valueIn1"]]);
        $this->assertEquals('value1', $model->field1);
        $this->assertEquals('valueIn1', $model->field2['fieldIn1']);
    }

    public function testFillWithId()
    {
        $model = Model::create(["_id" => "DFKDF", 'field1' => 'value1']);
        $this->assertEquals('value1', $model->field1);
        $this->assertEquals('DFKDF', $model->id);
    }


    public function testCreateWithEmbebedObject()
    {
        require 'SampleEmbebedModel.php';
        require 'Address.php';

        $model = SampleEmbebedModel::create(
            ["name" => "My name", "address" => ["city" => "Málaga", "country" => "Spain"]]
        );
        
        $this->assertEquals("My name", $model->name);
        $this->assertEquals('Málaga', $model->address->city);
        $this->assertInstanceOf(Address::class, $model->address);
    }

    public function testCreateWithEmbebedListObject()
    {
        require 'SampleEmbebedModel.php';
        require 'Address.php';

        $model = SampleEmbebedModel::create(
            ["name" => "My name", "addressList" => [
                ["city" => "Málaga", "country" => "Spain"],
                ["city" => "Barcelona", "country" => "Spain"]
             ]
             ]
        );
        
        $this->assertEquals("My name", $model->name);
        $this->assertEquals('Málaga', $model->addressList[0]->city);
        $this->assertInstanceOf(Address::class, $model->addressList[0]);
    }

    public function testGetMapper()
    {
        $connection = new \MongoDB\Client("mongodb://mongo:27017");
        \Mapmon\Mapper::setDatabase($connection->sample);

        $model = new Model();
        $mapper = $model->getMapper();
        $this->assertInstanceOf(\Mapmon\Mapper::class, $mapper);
    }



    /**
     * @expectedException Exception
     */
    public function testGetCollectionNameWithException()
    {
        $model = new Model();
        $model->getCollectionName();
    }


    public function testRemove()
    {
        $connection = new \MongoDB\Client("mongodb://mongo:27017");
        \Mapmon\Mapper::setDatabase($connection->sample);

   
        $model = new Model();
        $this->markTestIncomplete();

        $model->remove();
    }
}
