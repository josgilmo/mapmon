<?php

namespace Mapmon;

/**
 * Represents object fetched from database
 *
 * @author José Gil <jose.gilmolina@gmail.com>
 */
class Model
{
    /**
     * Collection name. Set it for each model.
     *
     * @var string
     */
    protected static $collectionName = '';
    
    /**
     * Database connection name set in mapper
     *
     * @var string
     */
    protected static $connectionName = null;

    private static $mapper = null;
    
    /**
     * Specifies if created_at and updated_at fields
     * should be added and serve automatically
     * @var bool
     */
    public static $timestamps = false;
    
    /**
     * Informations about embedded object list
     * that will be created while this object creation.
     * Array key is a name of field that keeps array of objects.
     * Array value specifies Model class to which objects should be mapped.
     *
     * protected static $embeddedObjectList = [
     *   'tags'          => 'HackerTag',
     *   'notifications' => 'Notification',
     *  ];
     *
     * @var array
     */
    protected static $embeddedObjectList = [];

    /**
     * Informations about embedded object
     * that will be created while this object creation.
     * Array key is a name of field that keeps object.
     * Array value specifies Model class to which objects should be mapped.
     *
     * <code>
     *  protected static $embeddedObject = array (
     *    'address' => 'Address',
     *  );
     * </code>
     *
     * @var array
     */
    protected static $embeddedObject = [];
    
    /**
     * Format of date to return with getDate funciton
     *
     * @var string
     */
    public static $dateFormat = 'm/d/y';

    /**
     * Format of time to return with getTime cunftion
     * @var string
     */
    public static $timeFormat = 'm/d/y H:i';

    /**
     * Sets object variables
     *
     * @param array $array
     */
    public static function create($array = [])
    {
        return self::fill($array);
    }
    
    /**
     * Returns null if property doesn't exist
     */
    public function __get($property)
    {
        return null;
    }

    public static function setDafaultCollectionName($name)
    {
        self::$collectionName = $name;
    }
    
    /**
     * Fills object with variables.
     * Sets embedded objects if they are registered in
     * $embeddedObjectLisr or $embeddedObject.
     *
     * @param array $array
     */
    public static function fill($array = [])
    {
        $reflexion = new \ReflectionClass(get_called_class());
        $model = $reflexion->newInstanceWithoutConstructor();
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if (in_array($key, array_keys(static::$embeddedObjectList)) && (is_array($value) || $value instanceof \MongoDB\Model\BSONArray)) {
                    $model ->{$key} = [];
                    
                    foreach ($value as $eKey => $eData) {
                        if (is_array($eData) || $eData instanceof \MongoDB\Model\BSONDocument) {
                            $model ->{$key}[ $eKey ] = static::$embeddedObjectList[ $key ]::create($eData);
                        }
                    }
                } elseif (in_array($key, array_keys(static::$embeddedObject)) && is_array($value)) {
                    $model->{$key} = static::$embeddedObject[$key]::create(($value));
                } else {
                    $model->$key = $value;
                }
            }
            if (!empty($model->_id)) {
                $model->id = (string) $model->_id;
            }
        }

        return $model;
    }
    
    /**
     * Gets proper Mapper for object
     *
     * @param int $fetchType Fetch type as specified in Mapper constants
     * @return Mapper
     */
    public static function getMapper($fetchType = null)
    {
        if (isset(self::$mapper)) {
            return self::$mapper;
        }

        return  new Mapper(get_called_class(), $fetchType);
    }

    public static function setMapper(Mapper $mapper)
    {
        static::$mapper = $mapper;
    }
    
    /**
     * Gets colletion name for model
     * It should be specified in $_collectionName
     *
     * @throws \Exception
     * @return string
     */
    public static function getCollectionName()
    {
        if (empty(static::$collectionName)) {
            throw new \Exception('There\'s no collection name for ' . get_called_class());
        }
        return static::$collectionName;
    }

    
    /**
     * Gets database connection name for model
     * It should be specified in $_databaseConnection
     *
     * @return string
     */
    public static function getConnectionName()
    {
        return static::$connectionName;
    }


    /**
     * Saves object to database.
     * Adds timestamps if wanted in $timestamps.
     *
     * @return mixed MongoCollection save result
     */
    public function save()
    {
        $existingDocument = $this->hasMongoId();
        if (static::$timestamps) {
            if (!$this->hasMongoId()) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
        }

        if (! $this->hasMongoId()) {
            $this->_id = new \MongoDB\BSON\ObjectID();
        }
        
        unset($this->id);

        if ($existingDocument) {
            $result = static::getMapper()->updateOne(["_id" => $this->_id], ['$set' => $this->bsonSerialize()]);
        } else {
            $result = static::getMapper()->insertOne($this->bsonSerialize());
            $this->_id = $result->getInsertedId();
        }

        $this->id = (string) $this->_id;

        return $result;
    }
    
    /**
     * Removes object form database, totally.
     *
     * @return bool|array MongoCollection remove result
     */
    public function remove()
    {
        return static::getMapper()->deleteOne(array( '_id' => $this->_id ));
    }

    /**
     * Gets object by its id.
     *
     * @param string|\MongoDB\BSON\ObjectID $id Can be passed as string or MongoId
     * @return Model
     */
    public static function findById($id)
    {
        if (is_string($id)) {
            $id = new \MongoDB\BSON\ObjectID($id);
        }

        return static::getMapper()->findOne(["_id" => $id]);
    }
    
    /**
     * Gets object by query.
     * Refferer to Mapper's findOne
     *
     * @param array $query Query as for findOne in mongodb driver
     * @param array $fields
     * @return Model
     */
    public static function findOne($query = [], $fields = [])
    {
        return static::getMapper()->findOne($query, $fields);
    }
    
    /**
     * Refferer to Mapper's find.
     * Gets Mapper object with cursor set.
     *
     * @param array $query Query as for find in mongodb driver
     * @param array $fields
     * @return Model
     */
    public static function find($query = [], $fields = [])
    {
        return static::getMapper()->find($query, $fields);
    }
    
    /**
     * Checks if _id is set in object
     * @return boolean
     */
    public function hasMongoId()
    {
        return isset($this->_id) && !empty($this->_id);
    }

    /**
     * Return date as string - converts MongoDate
     *
     * @param string $field Field name that keeps date
     * @param string $format Format for date function
     * @return srting
     */
    public function getDate($field, $format = null)
    {
        $format = $format == null ? static::$dateFormat : $format;
        return isset($this->$field->sec) ? date($format, $this->$field->sec) : null;
    }

    /**
     * Return time as string - converts MongoDate
     *
     * @param string $field Field name that keeps time
     * @param string $format Format for date function
     * @return srting
     */
    public function getTime($field, $format = null)
    {
        $format = $format == null ? static::$timeFormat : $format;
        return isset($this->$field->sec) ? date($format, $this->$field->sec) : null;
    }
    
    /**
     * Sets id (both string and MongoId version)
     * @param string|MongoId $id
     */
    public function setId($id)
    {
        if (!$id instanceof \MongoDB\BSON\ObjectID) {
            $id = new \MongoDB\BSON\ObjectID($id);
        }
        
        $this->_id = $id;
        $this->id = (string) $id;
    }
    
    /**
     * Perform join like operation to many objects.
     * Allows to get all object related to this one
     * by array of mongo ids that is kept in $variable
     *
     * As simple as that
     * <code>
     *   $user->joinMany('posts', 'Post');
     * </code>
     *
     * @param string $variable Field keeping array of mongo ids
     * @param string $class Model class name of joined objects
     * @param string $toVariable Variable that should keep new results, same if null given.
     * @param array $fields Fields to return if you want to limit
     * @return Model
     */
    public function joinMany($variable, $class, $toVariable = null, $fields = [])
    {
        if (isset($this->$variable) && is_array($this->$variable) && !empty($this->$variable)) {
            if (empty($toVariable)) {
                $toVariable = $variable;
            }
            $this->$toVariable = $class::getMapper()->find(['_id' => ['$in' => $this->$variable]], $fields)->get();
        }

        return $this;
    }
    
    /**
     * Perform join like operation to one object.
     * Allows to get object related to this one
     * by MongoId that is kept in $variable
     *
     * As simple as that
     * <code>
     *   $user->joinOne( 'article', 'Article' );
     * </code>
     *
     * @param string $variable Field keeping MongoId of object you want to join
     * @param string $class Model class name of joined object
     * @param string $toVariable Variable that should keep new result, same if null given.
     * @param array $fields Fields to return if you want to limit
     * @return Model
     */
    public function joinOne($variable, $class, $toVariable = null, $fields = [])
    {
        if (isset($this->$variable) && !empty($this->$variable)) {
            if (empty($toVariable)) {
                $toVariable = $variable;
            }
            $this->$toVariable = $class::getMapper()->findOne(array('_id' => $this->$variable ), $fields);
        }
        return $this;
    }
}
