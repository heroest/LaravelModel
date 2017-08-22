<?php namespace Heroest\LaravelModel\Traits;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\ConnectionNotFoundException;
use Heroest\LaravelModel\Query;

use PDO, Exception;

trait Model
{
    private static $connection_pool = [];

    private $connection = null;

    private $query = null;

    public $primaryKeyValue = null;

    private $data = [];

    private $saved = [];


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
     * @param [string] $connection_name
     * @param array $config || PDO instance
     * @return $this
     */
    public function addConnection($name, $mixed)
    {
        if(!isset(self::$connection_pool[$name])) {
            if(!is_array($mixed) and !($mixed instanceof PDO)) 
                throw new InvalidParameterException("Model->addConnection(), the 2nd parameter expects an array or a PDO instance");
            
            self::$connection_pool[$name] = is_array($mixed) 
                                                ? $this->buildPdo($mixed)
                                                : $mixed;
        }
        $this->connection = $name;

        return $this;
    }


    /**
     * Set connection name
     *
     * @param [string] $name
     * @return $this
     */
    public function connection($connection)
    {
        if(!isset(self::$connection_pool[$connection])) throw new ConnectionNotFoundException("Model->connection(): connection [{$connection}] is not defined");
        $this->connection = $name;
        return $this;
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
     * add a SELECT clause in the query
     *
     * @return $this
     */
    public function select()
    {
        $params = func_get_args();
        $this->getQuery()->select($params);
        return $this;
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
     * Find a record where matched primary_key_value
     *
     * @param [mixed] $primary_key_value
     * @return void
     */
    public function find($mixed)
    {
        return $this->getQuery()->find($mixed);
    }

    /**
     * Find a record where matched many primary_key_value
     *
     * @param [array] $primary_key_value
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
     * add limit clause to the query
     *
     * @param integer $num
     * @return void
     */
    public function offset($num)
    {
        $this->getQuery()->offset($num);
        return $this;
    }
    public function take($num)
    {
        $this->getQuery()->take($num);
        return $this;
    }
    public function limit()
    {
        $params = func_get_args();
        $this->getQuery()->limit($params);
        return $this;
    }


    /**
     * Add Where clause to Query
     *
     * @return $this
     */
    public function where()
    {
        $params = func_get_args();
        $this->getQuery()->where($params);
        return $this;
    }


    /**
     * Add Or Where clause to Query
     *
     * @return $this
     */
    public function orWhere()
    {
        $params = func_get_args();
        $this->getQuery()->orWhere($params);
        return $this;
    }

    /**
     * Add WhereIn clause to Query
     *
     * @return $this
     */
    public function whereIn($key, array $value_arr)
    {
        $this->getQuery()->whereIn($key, $value_arr);
        return $this;
    }


    /**
     * Add WhereNotIn clause to Query
     *
     * @return $this
     */
    public function whereNotIn($key, array $value_arr)
    {
        $this->getQuery()->whereNotIn($key, $value_arr);
        return $this;
    }

    /**
     * Massive Assignement
     *
     * @return $this
     */
    public function fill(array $params)
    {
        $this->getQuery()->fill($params);
        return $this;
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
     * Add Innser Join Clause into Query
     *
     * @return void
     */
    public function join()
    {
        $params = func_get_args();
        $this->getQuery()->innerJoin($params);
        return $this;
    }

    /**
     * Add Left Join Clause into Query
     *
     * @return void
     */
    public function leftJoin()
    {
        $params = func_get_args();
        $this->getQuery()->leftJoin($params);
        return $this;
    }


    /**
     * Add Right Join Clause into Query
     *
     * @return void
     */
    public function rightJoin()
    {
        $params = func_get_args();
        $this->getQuery()->rightJoin($params);
        return $this;
    }


    /**
     * Add exist clause to Query
     *
     * @return $this
     */
    public function whereHas()
    {
        $params = func_get_args();
        $this->getQuery()->whereHas($this, $params);
        return $this;
    }


    /**
     * return last inserted id
     *
     * @return int or null
     */
    public function lastInsertId()
    {
        return $this->getQuery()->lastInsertId();
    }


    /**
     * return number of row affected from last query
     *
     * @return int or null
     */
    public function rowCount()
    {
        return $this->getQuery()->rowCount();
    }


    /**
     * Start a database transaction on the connection
     *
     * @param string $connection
     * @return void
     */
    public function beginTransaction($connection = '')
    {
        $pdo = $this->getConnection($connection);
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
        $pdo = $this->getConnection($connection);
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
        $pdo = $this->getConnection($connection);
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
        $pdo = $this->getConnection($connection);
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
        return $this;
    }


    /**
     * Convert Model to data array
     *
     * @return array
     */
    public function toArray()
    {
        if(empty($this->hidden)) {
            return $this->data;
        } else {
            $result = [];
            foreach($this->data as $k => $v) {
                if(!in_array($k, $this->hidden)) $result[$k] = $v;
            }
            return $result;
        }
    }


    /**
     * Initialize a PDO object from config
     *
     * @param [array] $config
     * @return PDO object;
     */
    private function buildPdo($config)
    {
        $keys = ['type', 'host', 'username', 'password', 'db_name', 'port'];
        foreach($keys as $key) {
            if( !isset($config[$key]) ) throw new InvalidParameterException("Model->buildPdo(): {$key} is missing from the connection configuration");
        }
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8';
        $dsn = "{$config['type']}:dbname={$config['db_name']};host={$config['host']};port={$config['port']};charset={$charset}";
        
        $base_options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_EMULATE_PREPARES => false,
			PDO::ATTR_PERSISTENT => false,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];
        $options = isset($config['options'])
                    ? array_merge($base_options, $config['options'])
                    : $base_options;

        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

        return $pdo;
    }
    
    /**
     * Return a initialized Query Object;
     *
     * @return /Heroest/LaravelModel/Query;
     */
    private function getQuery()
    {
        if (is_null($this->query)) {

            $this->query = new Query([
                'baseModel' => $this,
                'connection' => $this->connection,
                'pdo' => $this->getConnection(),
                'primaryKey' => isset($this->primaryKey) ? $this->primaryKey : 'id',
                'primaryKeyValue' => !is_null($this->primaryKeyValue) ? $this->primaryKeyValue : null, 
                'table' => isset($this->table) ? $this->table : '',
                'timestamps' => isset($this->timestamps) ? $this->timestamps : false,
                'dateFormat' => isset($this->dateFormat) ? $this->dateFormat : 'U',
                'saved' => empty($this->saved) ? [] : $this->saved,
                'fillable' => isset($this->fillable) ? $this->fillable : [],
                'hidden' => isset($this->hidden) ? $this->hidden : [],
                'guarded' => isset($this->guarded) ? $this->guarded : [],
                'created_at' => isset($this->created_at) ? $this->created_at : '',
                'updated_at' => isset($this->updated_at) ? $this->updated_at : '',
            ]);
        }

        return $this->query;
    }

    /**
     * get a connection (PDO object) by connection_name
     *
     * @param string $name
     * @return PDO object
     */
    private function getConnection($connection = '')
    {
        //if(!empty($this->pdo)) return $this->pdo;

        $connection = empty($connection) ? $this->connection : $connection;

        if (empty($connection) or !isset(self::$connection_pool[$connection]))
            throw new ConnectionNotFoundException("Model->getConnection(): Connection[{$connection}] is missing, maybe use addConnection() before Query?");

        return self::$connection_pool[$connection];
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
            vpd($key);
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
}
