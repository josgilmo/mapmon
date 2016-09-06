<?php

namespace Mapmon;

/**
 * Gets data from database and returns expected results in expected type,
 * specially objects. Allows to perform join like operations.
 *
 * @author José Gil <jose.gilmolina@gmail.com>
 */
class Mapper
{
    /**
     * Mongo connection to database.
     *
     * @var MongoDB
     */
    protected static $_database;

    /**
     * Class to return.
     *
     * @var string
     */
    protected $_modelClassName;

    /**
     * MongoCursor returned with find.
     *
     * @var \MongoCursor
     */
    protected $_cursor;

    /**
     * Keeps information about join like operations to perform.
     *
     * @var array
     */
    protected $_joins = array();

    /**
     * Sest model class name and fetch type.
     *
     * @param string $modelClass
     *
     * @throws \Exception
     */
    public function __construct($modelClass = null)
    {
        if (!empty($modelClass)) {
            $this->_modelClassName = $modelClass;
        } elseif (empty($this->_modelClassName)) {
            throw new \Exception('Mapper needs to know model class.');
        }

        if (empty(static::$_database)) {
            throw new \Exception('Give me some database. You can pass it with setDatabase function.');
        }
    }

    /**
     * Allows to call standard MongoCollection functions
     * like count, update.
     *
     * @param string $functionName
     * @param array  $functionArguments
     */
    public function __call($functionName, $functionArguments)
    {
        return call_user_func_array(
            array($this->_getModelCollection(), $functionName),
            $functionArguments
        );
    }

    /**
     * Acts exactly like MongoCollection find but
     * sets it result to $_cursor and returns this object.
     *
     * @param array $query
     * @param array $fields
     *
     * @return Mapper
     */
    public function find($query = array(), $fields = array())
    {
        $this->_cursor = $this->_getModelCollection()->find($query, $fields);

        return $this;
    }

    /**
     * Acts like MongoCollection function but returns
     * result of expected type.
     *
     * @param array $query
     * @param array $fields
     *
     * @return array|string|Model
     */
    public function findOne($query = array(), $fields = array())
    {
        $result = $this->_getModelCollection()->findOne($query, $fields);

        return $this->_fetchOne($result);
    }

    /**
     * Gets document by its id.
     *
     * @param string|\MongoDB\BSON\ObjectID $id
     *
     * @return array|string|Model
     */
    public function findById($id)
    {
        if (!$id instanceof \MongoDB\BSON\ObjectID) {
            $id = new \MongoDB\BSON\ObjectID($id);
        }

        return $this->findOne(array('_id' => $id));
    }

    /**
     * Updates an object and returns it.
     *
     * @param array $query
     * @param array $update
     * @param array $fields
     * @param array $options
     *
     * @return array|string|Model
     */
    public function findAndModify($query, $update = array(), $fields = array(), $options = array())
    {
        $result = $this->_getModelCollection()->findAndModify($query, $update, $fields, $options);

        return $this->_fetchOne($result);
    }

    /**
     * Fetches result as object.
     *
     * @param array $result
     *
     * @return Model|null
     */
    protected function fetchObject($result)
    {
        return is_array($result) ? new $this->_modelClassName($result) : null;
    }

    /**
     * Performs sort on MongoCursor.
     *
     * @param array $array
     *
     * @return Mapper
     */
    public function sort($array)
    {
        $this->_checkCursor();
        $this->_cursor->sort($array);

        return $this;
    }

    /**
     * Performs limit on MongoCursor.
     *
     * @param int $num
     *
     * @return Mapper
     */
    public function limit($num)
    {
        $this->_checkCursor();
        $this->_cursor->limit($num);

        return $this;
    }

    /**
     * Performs skip on MongoCursor.
     *
     * @param int $num
     *
     * @return Mapper
     */
    public function skip($num)
    {
        $this->_checkCursor();
        $this->_cursor->skip($num);

        return $this;
    }

    /**
     * Writes informations about joins.
     *
     * @param string $variable   Field name in database that keeps \MongoDB\BSON\ObjectID of other object
     * @param string $class      Model name which should be created
     * @param string $toVariable Name od variable to which it should be writen
     *
     * @return Mapper
     */
    public function join($variable, $class, $toVariable = null, $fields = array())
    {
        $this->_joins[] = array('variable' => $variable,
                                  'class' => $class,
                                  'to_variable' => $toVariable,
                                  'fields' => $fields,
            );

        return $this;
    }

    /**
     * Performs proper skip and limit to get
     * data package that can be wraped in paginator.
     *
     * @param int   $perPage
     * @param int   $page
     * @param mixed $options Options you want to pass
     */
    public function getPaginator($perPage = 10, $page = 1, $options = null)
    {
        $this->_checkCursor();
        $total = $this->_cursor->count();
        $this->_cursor->skip(($page -1) * $perPage)->limit($perPage);
        $result = $this->get();

        return $this->_createPaginator($result, $total, $perPage, $page, $options);
    }

    /**
     * Returns the data.
     *
     * @result array|string Array of arrays or json string
     */
    public function get()
    {
        $this->_checkCursor();
        $result = array();
        foreach ($this->_cursor as $key => $item) {
            $d = get_object_vars($item->bsonSerialize());
            $d =  $this->fetchObject($d);

            $result[$key] = $d;
        }

        return $result;
/*
        switch ($this->_fetchType) {
            case self::FETCH_OBJECT_NO_KEYS:
                $result = array();
                foreach ($this->_cursor as $key => $item) {
                    $result[] = $this->fetchObject($item);
                }

                return $this->_performJoins($result);
                break;

            default:
                $result = array();
                foreach ($this->_cursor as $key => $item) {
                    $result[$key] = $this->fetchObject($item);
                }

                return $this->_performJoins($result);
            break;
        }
*/
    }

    /**
     * Gets MongoCursor.
     *
     * @return \MongoCursor
     */
    public function getCursor()
    {
        return $this->_cursor;
    }

    /**
     * Sets database. That needs to be performed before you can get any data.
     *
     * @param \MongoDB|array $database
     *
     * @throws \Exception
     */
    public static function setDatabase($database)
    {
        if ($database instanceof \MongoDB\Database) {
            static::$_database = array('default' => $database);
        } elseif (is_array($database)) {
            static::$_database = $database;
        } else {
            throw new \Exception('Database must be an array fo MongoDb objects or MongoDb object');
        }
    }

    /**
     * Gets database connections.
     *
     * @return \MongoDB
     */
    public static function getDatabase()
    {
        return self::$_database;
    }

    /**
     * Get connection to collection for mapper's model.
     */
    protected function _getModelCollection()
    {
        $modelClass = $this->_modelClassName;
        $collectionName = $modelClass::getCollectionName();
        $connectionName = $modelClass::getConnectionName();
        if (!empty($connectionName)) {
            $database = self::$_database[ $connectionName ];
        } else {
            $database = reset(self::$_database);
        }

        return $database->$collectionName;
    }

    /**
     * Converts document to proper type.
     *
     * @param array $array Document
     *
     * @return Model
     */
    protected function _fetchOne($array)
    {
        if (is_null($array)) {
            return;
        }
        $d = get_object_vars($array->bsonSerialize());
        $d =  $this->fetchObject($d);

        return  $d;

        return;
    }

    /**
     * Used to create your own paginator.
     * Needs to be implemented if you want to use getPaginator function.
     *
     * @param mixed $results
     * @param int   $totalCount
     * @param int   $perPage
     * @param int   $page
     * @param mixed $options
     *
     * @throws \Exception
     */
    protected function _createPaginator($results, $totalCount, $perPage, $page, $options)
    {
        throw new \Exception('If you want to get paginator '.
            'please extend mapper class implementing _createPaginator function. '.
            'You have a set of params. Return whatever you want.');
    }

    /**
     * Checks if cursor already exists so you can perform operations on it.
     *
     * @throws \Exception
     */
    protected function _checkCursor()
    {
        if (empty($this->_cursor)) {
            throw new \Exception('There is no cursor, so you can not get anything');
        }
    }

    /**
     * Magically performs join like operations registered with join function.
     * Allows to connect every document in cursor to the other by \MongoDB\BSON\ObjectID
     * kept as variable in base document.
     *
     * @param array $array
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function _performJoins(array $array)
    {
        foreach ($this->_joins as $join) {
            reset($array);

            $toVariable = !empty($join['to_variable']) ? $join['to_variable'] : $join['variable'];
            $variable = $join['variable'];
            $class = $join['class'];
            $fields = $join['fields'];

            $ids = array();
            if (!empty($array) && is_object(current($array))) {
                foreach ($array as $item) {
                    if (isset($item->$variable) && ($item->$variable instanceof \MongoDB\BSON\ObjectID)) {
                        $ids[] = $item->$variable;
                    }
                }
                if (count($ids)) {
                    $joined = $class::getMapper(Mapper::FETCH_OBJECT)->find(array('_id' => array('$in' => $ids)), $fields)->get();
                    foreach ($array as $item) {
                        if (isset($item->$variable) && ($item->$variable instanceof \MongoDB\BSON\ObjectID)) {
                            $item->$toVariable = $joined[ (string) $item->$variable ];
                        }
                    }
                }
            } elseif (!empty($array)) {
                foreach ($array as $item) {
                    if (isset($item[ $variable ]) && ($item[ $variable ] instanceof \MongoDB\BSON\ObjectID)) {
                        $ids[] = $item[ $variable ];
                    }
                }
                if (count($ids)) {
                    $joined = $class::getMapper()->find(array('_id' => array('$in' => $ids)), $fields)->getArray();
                    foreach ($array as &$item) {
                        if (isset($item[ $variable ]) && ($item[ $variable ] instanceof \MongoDB\BSON\ObjectID)) {
                            $item[$toVariable] = $joined[ (string) $item[ $variable ] ];
                        }
                    }
                }
            }
        }

        return $array;
    }
}
