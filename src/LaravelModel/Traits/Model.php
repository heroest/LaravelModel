<?php namespace Heroest\LaravelModel\Traits;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\ConnectionNotFoundException;
use Heroest\LaravelModel\Exception\FunctionNotExistsException;
use Heroest\LaravelModel\Query;
use Heroest\LaravelModel\ConnectionPool as Pool;

use PDO, Exception;

trait Model
{
    private $connection = null;

    private $query = null;

    public $primaryKeyValue = null;

    private $data = [];

    private $saved = [];

    private $function_prefix = '';


    //public $table = '';


    //public $primaryKey = 'id';


    //public $timestamps = true;


    //public $updated_at = 'updated_at';


    //public $created-at = 'created_at';


    //public $dateFormat = 'U';

    
    public function __construct()
    {
        if (!extension_loaded('PDO')) 
            throw ConnectionNotFoundException('PDO is not loaded');
    }


    /**
     * Initialize a connection(PDO) and save in the connection_pool
     *
     * @param string $connection_name
     * @param array $config || PDO instance
     * @return $this
     */
    public function addConnection($name, $mixed)
    {
        $this->connection = $name;
        Pool::add($name, $mixed);
        return $this;
    }


    /**
     * Set connection name
     *
     * @param string $name
     * @return $this
     */
    public function connection($name)
    {
        if(!Pool::has($name))
            throw new ConnectionNotFoundException("Model->connection(): [{$name}] not found");
        $this->connection = $name;
        return $this;
    }
    
    /**
     * Set datatable name
     *
     * @param [string] $table
     * @return $this
     */
    public function table($table)
    {
        $prefix = isset($this->prefix) ? $this->prefix : '';
        $this->getQuery()->table("{$prefix}{$table}");
        return $this;
    }

    /**
     * set function prefix for compatiable
     *
     * @return void
     */
    public function setFunctionPrefix($prefix)
    {
        $this->function_prefix = $prefix;
    }
    
    /**
     * get first row of result
     *
     * @return void
     */
    public function first()
    {
        $result = $this->getQuery()->first();
        $this->setPrimaryKeyValue($result);
        return $result;
    }
    

    /**
     * Get Query Log
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->getQuery()->getQueryLog();
    }


    /**
     * return count for number of record
     *
     * @return void
     */
    public function count()
    {
        $params = func_get_args();
        return $this->getQuery()->count($params);
    }

    /**
     * Find a record where matched primary_key_value
     *
     * @param mixed $primary_key_value
     * @return void
     */
    public function find($mixed)
    {
        return $this->getQuery()->find($mixed);
    }

    /**
     * Find a record where matched many primary_key_value
     *
     * @param array $primary_key_value
     * @return void
     */
    public function findMany(array $value_arr)
    {
        return $this->getQuery()->findMany($value_arr);
    }

    /**
     * get query result
     *
     * @param void
     * @return void
     */
    public function get()
    {
        return $this->getQuery()->get();
    }


    /**
     * Save new data in the database
     *
     * @return model
     */
    public function save()
    {
        $params = func_get_args();
        $this->getQuery()->syncData($this->data); //sync latest data first
        return $this->getQuery()->save($params);
    }


    /**
     * Start a database transaction on the connection
     *
     * @param string $connection
     * @return void
     */
    public function beginTransaction($connection = '')
    {
        $connection = empty($connection) ? $this->connection : $connection;
        $pdo = Pool::get($connection);
        if(!$this->inTransaction($connection)) $pdo->beginTransaction();
    }


    /**
     * Check if inside a transaction
     *
     * @param string $connection
     * @return void
     */
    public function inTransaction($connection = '')
    {
        $connection = empty($connection) ? $this->connection : $connection;
        $pdo = Pool::get($connection);
        return $pdo->inTransaction();
    }


    /**
     * Rollback a transaction
     *
     * @param string $connection
     * @return void
     */
    public function rollback($connection = '')
    {
        $connection = empty($connection) ? $this->connection : $connection;
        $pdo = Pool::get($connection);
        if($this->inTransaction($connection)) $pdo->rollback();
    }


    /**
     * Commit a transaction
     *
     * @param string $connection
     * @return void
     */
    public function commit($connection = '')
    {
        $connection = empty($connection) ? $this->connection : $connection;
        $pdo = Pool::get($connection);
        if($this->inTransaction($connection)) $pdo->commit();
    }

    /**
     * Binding Data Set
     *
     * @param array $data
     * @return $this
     */
    public function populate(array $data)
    {
        $this->setPrimaryKeyValue($data);
        $this->data = $data;
        $this->saved = $data;
        $this->query = null;
        return $this;
    }


    public function markSaved()
    {
        $this->saved = $this->data;
    }

    /**
     * Convert Model to data array
     *
     * @return array
     */
    public function toArray()
    {
        
        $result = [];
        foreach($this->data as $k => $v) {
            if(!empty($this->hidden) and in_array($k, $this->hidden)) continue;
            
            if(is_object($v)) {
                $result[$k] = $v->toArray();
            } else {
                $result[$k] = $v;
            }
            
        }
        return $result;
        
    }
    
    public function getWithScope($scope, $name)
    {
        return $this->getQuery()->getWithScope($scope, $name);
    }


    /**
     * Build one to one relationship
     *
     * @param mixed $mixed
     * @param string $foreign_key
     * @param string $primary_key
     * @return boolean
     */
    public function hasOne($mixed, $foreign_key, $primary_key)
    {
        if(is_string($mixed)) {
            $obj = new $mixed();
        } elseif(is_object($mixed)) {
            $obj = clone $mixed;
        } else {
            throw new InvalidParameterException('Model->hasOne(): the 1st parameter expects to be string or object type');
        }

        if(!method_exists($obj, 'map')) {
            $class_name = get_class($obj);
            throw new FunctionNotExistsExceptioin("Model->hasOne(): [$class_name] does not use Model Trait");
        }

        return $obj->map('one', $foreign_key, $primary_key);
    }

    public function hasMany($mixed, $foreign_key, $primary_key)
    {
        if(is_string($mixed)) {
            $obj = new $mixed();
        } elseif(is_object($mixed)) {
            $obj = clone $mixed;
        } else {
            throw new InvalidParameterException('Model->hasMany(): the 1st parameter expects to be string or object type');
        }

        if(!method_exists($obj, 'map')) {
            $class_name = get_class($obj);
            throw new FunctionNotExistsExceptioin("Model->hasMany(): [$class_name] does not use Model Trait");
        }

        return $obj->map('many', $foreign_key, $primary_key);
    }

    public function belongsTo($mixed, $foreign_key, $primary_key)
    {
        if(is_string($mixed)) {
            $obj = new $mixed();
        } elseif(is_object($mixed)) {
            $obj = clone $mixed;
        } else {
            throw new InvalidParameterException('Model->belongsTo(): the 1st parameter expects to be string or object type');
        }

        if(!method_exists($obj, 'map')) {
            $class_name = get_class($obj);
            throw new FunctionNotExistsExceptioin("Model->belongsTo(): [$class_name] does not use Model Trait");
        }

        return $obj->map('one', $primary_key, $foreign_key);
    }

    public function belongsToMany($mixed, $table_name, $local, $remote)
    {
        if(is_string($mixed)) {
            $obj = new $mixed();
        } elseif(is_object($mixed)) {
            $obj = clone $mixed;
        } else {
            throw new InvalidParameterException('Model->belongsTo(): the 1st parameter expects to be string or object type');
        }

        if(!method_exists($obj, 'map')) {
            $class_name = get_class($obj);
            throw new FunctionNotExistsExceptioin("Model->belongsToMany(): [$class_name] does not use Model Trait");
        }

        /* to-do */
        foreach($local as $k => $v) {
            $local_from = $k;
            $local_to = $v;
        }
        foreach($remote as $k => $v) {
            $remote_from = $k;
            $remote_to = $v;
        }
        return $obj
                ->map('many', $local_to, $local_from, $table_name)
                ->innerJoin($table_name, "{$obj->table}.{$remote_to}", "{$table_name}.{$local_from}")
                ->select("{$obj->table}.*");
    }

    /**
     * Return a initialized Query Object;
     *
     * @return Query;
     */
    private function getQuery()
    {
        if (is_null($this->query)) {
            $this->query = new Query([
                'baseModel' => $this,
                'connection' => $this->connection,
                'pdo' => Pool::get($this->connection),
                'primaryKey' => isset($this->primaryKey) ? $this->primaryKey : 'id',
                'primaryKeyValue' => !is_null($this->primaryKeyValue) ? $this->primaryKeyValue : null, 
                'table' => isset($this->table) ? $this->table : '',
                'timestamps' => isset($this->timestamps) ? $this->timestamps : false,
                'dateFormat' => isset($this->dateFormat) ? $this->dateFormat : 'U',
                'saved' => empty($this->saved) ? [] : $this->saved,
                'fillable' => isset($this->fillable) ? $this->fillable : [],
                'hidden' => isset($this->hidden) ? $this->hidden : [],
                'guarded' => isset($this->guarded) ? $this->guarded : [],
                'function_prefix' => $this->function_prefix,
                'created_at' => isset($this->created_at) ? $this->created_at : '',
                'updated_at' => isset($this->updated_at) ? $this->updated_at : '',
            ]);
        }
        return $this->query;
    }


    /**
     * set Primary Key Value
     *
     * @param mixed
     * @return value
     */
    private function setPrimaryKeyValue($mixed)
    {
        $key = $this->primaryKey;
        if(empty($key)) {
            throw InvalidParameterException("Model->setPrimaryKeyValue(): PrimaryKey is not defined");
        } elseif (is_object($mixed)) {
            $this->primaryKeyValue = isset($mixed->$key) ? $mixed->$key : null;
        } elseif (is_array($mixed)) {
            $this->primaryKeyValue = isset($mixed[$key]) ? $mixed[$key] : null;
        }
    }

    public function __clone()
    {
        $this->query = null;
    }

    public function __get($key)
    {
        if(isset($this->data[$key]) and (empty($this->hidden) or !in_array($key, $this->hidden))) {
            return $this->data[$key];
        } else {
            $parent = get_parent_class();
            return (!empty($parent) and method_exists($parent, '__get')) ? parent::__get($key) : null;
        }
    }

    public function __set($key, $val)
    {
        $this->data[$key] = $val;
        $parent = get_parent_class();
        if(!empty($parent) and method_exists($parent, '__set')) parent::__set($key, $val);
    }

    public function __isset($key)
    {
        if(isset($this->data[$key]) and (empty($this->hidden) or !in_array($key, $this->hidden))) {
            return true;
        } else {
            $parent = get_parent_class();
            return (!empty($parent) and method_exists($parent, '__isset')) ? parent::__isset($key) : false;
        }
    }

    public function __unset($key)
    {
        if(isset($this->data[$key])) unset($this->data[$key]);
        $parent = get_parent_class();
        if(!empty($parent) and method_exists($parent, '__unset')) parent::__unset($key);
    }

    public function __call($function_name, $params)
    {
        $query = $this->getQuery();
        $function_short = (!empty($this->function_prefix) and strpos($function_name, $this->function_prefix) === 0) 
                    ? substr($function_name, strlen($this->function_prefix))
                    : $function_name;
        if(method_exists($query, $function_short)) {
            call_user_func_array([$query, $function_short], $params);
            return $this;
        } elseif(method_exists($query, $function_name)) {
            call_user_func_array([$query, $function_name], $params);
            return $this;
        } else {
            throw new FunctionNotExistsException("{$function_name} Does not exists, did you forgot to set function_prefix?");
        }
    }
}
