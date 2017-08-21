<?php namespace Heroest\Model\Traits\Compatible;

use Heroest\Model\Exception\InvalidParameterException;
use Heroest\Model\Exception\ConnectionNotFoundException;
use Heroest\Model\Query;

use PDO, Exception;

trait Model
{
    private static $_connection_pool = [];

    private $_connection = null;

    private $_query = null;

    public $_primaryKeyValue = null;

    private $_data = [];

    private $_saved = [];


    //public $_table = '';


    //public $_primaryKey = 'id';


    //public $_timestamps = true;


    //public $_updated_at = 'updated_at';


    //public $_created-at = 'created_at';


    //public $_dateFormat = 'U';

    
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
    public function _addConnection($name, $mixed)
    {
        if(!isset(self::$_connection_pool[$name])) {
            if(!is_array($mixed) and !($mixed instanceof PDO)) 
                throw new InvalidParameterException("Model->addConnection(), the 2nd parameter expects an array or a PDO instance");
            
            self::$_connection_pool[$name] = is_array($mixed) 
                                                ? $this->_buildPdo($mixed)
                                                : $mixed;
        }
        $this->_connection = $name;

        return $this;
    }


    /**
     * Set connection name
     *
     * @param [string] $name
     * @return $this
     */
    public function _connection($connection)
    {
        if(!isset(self::$_connection_pool[$connection])) throw new ConnectionNotFoundException("Model->connection(): connection [{$connection}] is not defined");
        $this->_connection = $name;
        return $this;
    }
    

    /**
     * Get Query Log
     *
     * @return array
     */
    public function _getQueryLog()
    {
        return $this->_getQuery()->getQueryLog();
    }


    /**
     * add a SELECT clause in the query
     *
     * @return $this
     */
    public function _select()
    {
        $params = func_get_args();
        $this->_getQuery()->select($params);
        return $this;
    }


    /**
     * return count for number of record
     *
     * @return void
     */
    public function _count()
    {
        $params = func_get_args();
        return $this->_getQuery()->count($params);
    }


    /**
     * Set datatable name
     *
     * @param [string] $table
     * @return $this
     */
    public function _table($table)
    {
        $prefix = isset($this->prefix) ? $this->prefix : '';
        $this->_getQuery()->table("{$prefix}{$table}");
        return $this;
    }

    /**
     * Find a record where matched primary_key_value
     *
     * @param [mixed] $primary_key_value
     * @return void
     */
    public function _find($mixed)
    {
        return $this->_getQuery()->find($mixed);
    }

    /**
     * Find a record where matched many primary_key_value
     *
     * @param [array] $primary_key_value
     * @return void
     */
    public function _findMany(array $value_arr)
    {
        return $this->_getQuery()->findMany($value_arr);
    }


    /**
     * get query result
     *
     * @param void
     * @return void
     */
    public function _get()
    {
        return $this->_getQuery()->get();
    }

    
    /**
     * get first row of result
     *
     * @return void
     */
    public function _first()
    {
        $result = $this->_getQuery()->first();
        $this->_setPrimaryKeyValue($result);
        return $result;
    }


    /**
     * add limit clause to the query
     *
     * @param integer $num
     * @return void
     */
    public function _offset($num)
    {
        $this->_getQuery()->offset($num);
        return $this;
    }
    public function _take($num)
    {
        $this->_getQuery()->take($num);
        return $this;
    }
    public function _limit()
    {
        $params = func_get_args();
        $this->_getQuery()->limit($params);
        return $this;
    }


    /**
     * Add Where clause to Query
     *
     * @return $this
     */
    public function _where()
    {
        $params = func_get_args();
        $this->_getQuery()->where($params);
        return $this;
    }


    /**
     * Add Or Where clause to Query
     *
     * @return $this
     */
    public function _orWhere()
    {
        $params = func_get_args();
        $this->_getQuery()->orWhere($params);
        return $this;
    }

    /**
     * Add WhereIn clause to Query
     *
     * @return $this
     */
    public function _whereIn($key, array $value_arr)
    {
        $this->_getQuery()->whereIn($key, $value_arr);
        return $this;
    }


    /**
     * Add WhereNotIn clause to Query
     *
     * @return $this
     */
    public function _whereNotIn($key, array $value_arr)
    {
        $this->_getQuery()->whereNotIn($key, $value_arr);
        return $this;
    }

    /**
     * Massive Assignement
     *
     * @return $this
     */
    public function _fill(array $params)
    {
        $this->_getQuery()->fill($params);
        return $this;
    }
    

    /**
     * Save new data in the database
     *
     * @return model
     */
    public function _save()
    {
        $params = func_get_args();
        $this->_getQuery()->syncData($this->data); //sync latest data first
        return $this->_getQuery()->save($params);
    }


    /**
     * Add Innser Join Clause into Query
     *
     * @return void
     */
    public function _join()
    {
        $params = func_get_args();
        $this->_getQuery()->innerJoin($params);
        return $this;
    }

    /**
     * Add Left Join Clause into Query
     *
     * @return void
     */
    public function _leftJoin()
    {
        $params = func_get_args();
        $this->_getQuery()->leftJoin($params);
        return $this;
    }


    /**
     * Add Right Join Clause into Query
     *
     * @return void
     */
    public function _rightJoin()
    {
        $params = func_get_args();
        $this->_getQuery()->rightJoin($params);
        return $this;
    }


    /**
     * Add exist clause to Query
     *
     * @return $this
     */
    public function _whereHas()
    {
        $params = func_get_args();
        $this->_getQuery()->whereHas($this, $params);
        return $this;
    }


    /**
     * return last inserted id
     *
     * @return int or null
     */
    public function _lastInsertId()
    {
        return $this->_getQuery()->lastInsertId();
    }


    /**
     * return number of row affected from last query
     *
     * @return int or null
     */
    public function _rowCount()
    {
        return $this->_getQuery()->rowCount();
    }


    /**
     * Start a database transaction on the connection
     *
     * @param string $connection
     * @return void
     */
    public function _beginTransaction($connection = '')
    {
        $pdo = $this->_getConnection($connection);
        if(!$this->_inTransaction($connection)) $pdo->beginTransaction();
    }


    /**
     * Check if inside a transaction
     *
     * @param string $connection
     * @return void
     */
    public function _inTransaction($connection = '')
    {
        $pdo = $this->_getConnection($connection);
        return $pdo->_inTransaction();
    }


    /**
     * Rollback a transaction
     *
     * @param string $connection
     * @return void
     */
    public function _rollback($connection = '')
    {
        $pdo = $this->_getConnection($connection);
        if($this->_inTransaction($connection)) $pdo->rollback();
    }


    /**
     * Commit a transaction
     *
     * @param string $connection
     * @return void
     */
    public function _commit($connection = '')
    {
        $pdo = $this->_getConnection($connection);
        if($this->_inTransaction($connection)) $pdo->commit();
    }

    /**
     * Binding Data Set
     *
     * @param array $data
     * @return $this
     */
    public function _populate(array $data)
    {
        $this->_setPrimaryKeyValue($data);
        $this->_data = $data;
        $this->_saved = $data;
        return $this;
    }


    /**
     * Convert Model to data array
     *
     * @return array
     */
    public function _toArray()
    {
        if(empty($this->_hidden)) {
            return $this->_data;
        } else {
            $result = [];
            foreach($this->_data as $k => $v) {
                if(!in_array($k, $this->_hidden)) $result[$k] = $v;
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
    private function _buildPdo($config)
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
     * @return /Heroest/Model/Query;
     */
    private function _getQuery()
    {
        if (is_null($this->_query)) {

            $this->_query = new Query([
                'baseModel' => $this,
                'connection' => $this->_connection,
                'pdo' => $this->_getConnection(),
                'primaryKey' => isset($this->_primaryKey) ? $this->_primaryKey : 'id',
                'primaryKeyValue' => !is_null($this->_primaryKeyValue) ? $this->_primaryKeyValue : null, 
                'table' => isset($this->_table) ? $this->_table : '',
                'timestamps' => isset($this->_timestamps) ? $this->_timestamps : false,
                'dateFormat' => isset($this->_dateFormat) ? $this->_dateFormat : 'U',
                'saved' => empty($this->_saved) ? [] : $this->_saved,
                'fillable' => isset($this->_fillable) ? $this->_fillable : [],
                'hidden' => isset($this->_hidden) ? $this->_hidden : [],
                'guarded' => isset($this->_guarded) ? $this->_guarded : [],
                'created_at' => isset($this->_created_at) ? $this->_created_at : '',
                'updated_at' => isset($this->_updated_at) ? $this->_updated_at : '',
            ]);
        }

        return $this->_query;
    }

    /**
     * get a connection (PDO object) by connection_name
     *
     * @param string $name
     * @return PDO object
     */
    private function _getConnection($connection = '')
    {
        //if(!empty($this->pdo)) return $this->pdo;

        $connection = empty($connection) ? $this->_connection : $connection;

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
    private function _setPrimaryKeyValue($mixed)
    {
        $key = $this->_primaryKey;
        if(empty($key)) {
            vpd($key);
        } elseif (is_object($mixed)) {
            $this->_primaryKeyValue = isset($mixed->$key) ? $mixed->$key : null;
        } elseif (is_array($mixed)) {
            $this->_primaryKeyValue = isset($mixed[$key]) ? $mixed[$key] : null;
        }
    }

    public function __clone()
    {
        $this->_query = null;
    }

    public function __get($key)
    {
        if(isset($this->_data[$key]) and (empty($this->_hidden) or !in_array($key, $this->_hidden))) {
            return $this->_data[$key];
        } else {
            $parent = get_parent_class();
            return (!empty($parent) and method_exists($parent, '__get')) ? parent::__get($key) : null;
        }
    }

    public function __set($key, $val)
    {
        $this->_data[$key] = $val;
        $parent = get_parent_class();
        if(!empty($parent) and method_exists($parent, '__set')) parent::__set($key, $val);
    }
}
