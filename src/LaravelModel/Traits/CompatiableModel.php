<?php namespace Heroest\LaravelModel\Traits;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\ConnectionNotFoundException;
use Heroest\LaravelModel\Exception\FunctionNotExistsException;
use Heroest\LaravelModel\Query;
use Heroest\LaravelModel\ConnectionPool as Pool;

trait CompatiableModel
{
    private $heroest_function = [];

    private $heroest_parameter = [];

    private $heroest_system_data = [];

    private $heroest_user_data = [];

    public function injectLaravelModel()
    {
        if (!extension_loaded('PDO')) throw ConnectionNotFoundException('PDO is not loaded');

        $that = $this;

        $this->heroest_parameter = [
            'primaryKey'        => 'id',
            'timestamps'        => false,
            'fillable'          => [],
            'hidden'            => [],
            'guarded'           => [],
            'function_prefix'   => ''
        ];

        $this->heroest_system_data['query'] = null;
        $this->heroest_user_data = [
            'data'  => [],
            'saved' => []
        ];
        
        $setPrimaryKeyValue = function($mixed)
        {
            $key = $this->heroest_parameter['primaryKey'];
            if(empty($key)) {
                throw InvalidParameterException("Model->setPrimaryKeyValue(): PrimaryKey is not defined");
            } elseif (is_object($mixed)) {
                $this->heroest_system_data['primaryKeyValue'] = isset($mixed->$key) ? $mixed->$key : null;
            } elseif (is_array($mixed)) {
                $this->heroest_system_data['primaryKeyValue'] = isset($mixed[$key]) ? $mixed[$key] : null;
            }
        };


        $this->heroest_function['getQuery'] = function() {
            if(is_null($this->heroest_system_data['query'])) {
                vp($this->heroest_parameter['table']);
                $this->heroest_system_data['query'] = new Query([
                    'baseModel' => $this,
                    'connection' => $this->heroest_parameter['connection'],
                    'pdo' => Pool::get($this->heroest_parameter['connection']),
                    'primaryKey' => isset($this->heroest_parameter['primaryKey']) ? $this->heroest_parameter['primaryKey'] : 'id',
                    'primaryKeyValue' => isset($this->heroest_parameter['primaryKeyValue']) ? $this->heroest_parameter['primaryKeyValue'] : null,
                    'table' => isset($this->heroest_parameter['table']) ? $this->heroest_parameter['table'] : '',
                    'timestamps' => isset($this->heroest_parameter['timestamps']) ? $this->heroest_parameter['timestamps'] : false,
                    'dateFormat' => isset($this->heroest_parameter['dateFormat']) ? $this->heroest_parameter['dateFormat'] : 'U',
                    'saved' => isset($this->heroest_user_data['saved']) ? $this->heroest_user_data['saved'] : [],
                    'fillable' => isset($this->heroest_parameter['fillable']) ? $this->heroest_parameter['fillable'] : [],
                    'hidden' => isset($this->heroest_parameter['hidden']) ? $this->heroest_parameter['hidden'] : [],
                    'guarded' => isset($this->heroest_parameter['guarded']) ? $this->heroest_parameter['guarded'] : [],
                    'function_prefix' => isset($this->heroest_parameter['function_prefix']) ? $this->heroest_parameter['function_prefix'] : '',
                    'created_at' => isset($this->heroest_parameter['created_at']) ? $this->heroest_parameter['created_at'] : '',
                    'updated_at' => isset($this->heroest_parameter['updated_at']) ? $this->heroest_parameter['updated_at'] : ''                 ]);
            }
            return $this->heroest_system_data['query'];
        };

        $this->heroest_function['setParameter'] = function() {
            $params = func_get_args();
            
            if(count($params) == 2) {
                list($key, $val) = $params;
                $this->heroest_parameter[$key] = $val;
            } elseif(count($params) == 1 and is_array($params)) {
                $this->heroest_parameter = array_merge($this->heroest_parameter, $params[0]);
            } else {
                throw new InvalidParameterException("Unknow parameters in setParameter()");
            }
        };

        $this->heroest_function['addConnection'] = function($name, $mixed) {
            $this->heroest_parameter['connection'] = $name;
            Pool::add($name, $mixed);
            return $this;
        };

        $this->heroest_function['connection'] = function($name) {
            if(!Pool::has($name)) throw new ConnectionNotFoundException("Model->connection(): [{$name}] not found");
            $this->heroest_parameter['connection'] = $name;
            return $this;
        };

        $this->heroest_function['table'] = function($name) {
            $this->heroest_parameter['table'] = $name;
            $this->heroest_function['getQuery']()->table($name);
            return $this;
        };

        $this->heroest_function['first'] = function() use ($setPrimaryKeyValue) {
            $result = $this->heroest_function['getQuery']()->first();
            $setPrimaryKeyValue($result);
            return $result;
        };

        $this->heroest_function['count'] = function() {
            $params = func_get_args();
            return $this->heroest_function['getQuery']()->count($params);
        };

        $this->heroest_function['find'] = function($mixed) {
            return $this->heroest_function['getQuery']()->find($mixed);
        };

        $this->heroest_function['findMany'] = function(array $arr) {
            return $this->heroest_function['getQuery']()->findMany($arr);
        };

        $this->heroest_function['get'] = function() {
            return $this->heroest_function['getQuery']()->get();
        };

        $this->heroest_function['save'] = function() {
            $query = $this->heroest_function['getQuery']();
            $query->syncData($this->heroest_user_data['data']);
            return $query->save(func_get_arg());
        };

        $this->heroest_function['beginTransaction'] = function($connection = '') {
            $connection = empty($connection) ? $this->heroest_parameter['connection'] : $connection;
            $pdo = Pool::get($connection);
            if(!$pdo->inTransaction()) $pdo->beginTransaction();
        };

        $this->heroest_function['inTransaction'] = function($connection = '') {
            $connectioin = empty($connection) ? $this->heroest_parameter['connection'] : $connection;
            $pdo = Pool::get($connection);
            return $pdo->inTransaction();
        };

        $this->heroest_function['commit'] = function($connection = '') {
            $connectioin = empty($connection) ? $this->heroest_parameter['connection'] : $connection;
            $pdo = Pool::get($connection);
            return $pdo->commit();
        };

        $this->heroest_function['rollback'] = function($connection = '') {
            $connectioin = empty($connection) ? $this->heroest_parameter['connection'] : $connection;
            $pdo = Pool::get($connection);
            return $pdo->rollBack();
        };

        $this->heroest_function['populate'] = function(array $data) use ($setPrimaryKeyValue) {
            $setPrimaryKeyValue($data);
            $this->heroest_user_data['data'] = $data;
            $this->heroest_user_data['saved'] = $data;
            $this->heroest_system_data['query'] = null;
            return $this;
        };

        $this->heroest_function['markSaved'] = function() {
            $this->heroest_user_data['saved'] = $this->heroest_user_data['data'];
        };

        $this->heroest_function['toArray'] = function() {
            $result = [];
            foreach($this->heroest_user_data['data'] as $k => $v) {
                if(empty($this->heroest_parameter['hidden']) or !in_array($k, $this->heroest_parameter['hidden'])) {
                    if(is_object($v)) {
                        $result[$k] = methods_exists($v, 'toArray') ? $v->toArray() : object2Array($v);
                    } else {
                        $result[$k] = $v;
                    }
                }
            }
            return $result;
        };

        $this->heroest_function['getWithScope'] = function($scope, $name) {
            return $this->heroest_function['getQuery']()->getWithScope($scope, $name);
        };

        $this->heroest_function['hasOne'] = function($mixed, $foreign_key, $primary_key) {
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

            return $obj->map('one', $foreign_key, $primary_key)->withScope([$this->data]);
        };

        $this->heroest_function['hasMany'] = function($mixed, $foreign_key, $primary_key) {
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

            return $obj->map('many', $foreign_key, $primary_key)->withScope([$this->data]);
        };

        $this->heroest_function['belongsTo'] = function() {
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

            return $obj->map('one', $primary_key, $foreign_key)->withScope($this->data);
        };

        return $this;
    }

    public function setFunctionPrefix($prefix)
    {
        $this->heroest_parameter['function_prefix'] = $prefix;
        return $this;
    }

    public function __clone()
    {
        $this->heroest_system_data['query'] = null;
    }

    public function __get($key)
    {
        if(isset($this->heroest_user_data[$key]) 
            and (empty($this->heroest_parameter['hidden']) 
                    or !in_array($key, $this->heroest_parameter['hidden']))
        ) {
            return $this->heroest_user_data[$key];
        } else {
            $parent = get_parent_class();
            return (!empty($parent) and method_exists($parent, '__get')) 
                        ? parent::__get($key) 
                        : null;
        }
    }

    public function __set($key, $val)
    {
        $this->heroest_user_data[$key] = $val;
        $parent = get_parent_class();
        if(!empty($parent) and method_exists($parent, '__set')) {
            parent::__set($key, $val);
        }
    }

    public function __call($func_name, $params)
    {
        $func_prefix = isset($this->heroest_parameter['function_prefix']) ? $this->heroest_parameter['function_prefix'] : '';
        
        $func_short = (!empty($this->heroest_parameter['function_prefix']) and strpos($func_name, $this->heroest_parameter['function_prefix']) === 0)
                        ? substr($func_name, strlen($this->heroest_parameter['function_prefix']))
                        : $func_name;

        if(isset($this->heroest_function[$func_short])) {
            return call_user_func_array($this->heroest_function[$func_short], $params);
        } elseif(isset($this->heroest_function[$func_name])) {
            return call_user_func_array($this->heroest_function[$func_name], $params);
        } 

        $query = $this->heroest_function['getQuery']();
        if(method_exists($query, $func_short)) {
            call_user_func_array([$query, $func_short], $params);
            return $this;
        } elseif(method_exists($query, $func_name)) {
            call_user_func_array([$query, $func_name], $params);
            return $this;
        } elseif(method_exists(get_parent_class(), '__call')) {
            return parent::__call($func_name, $params);
        } else {
            throw new FunctionNotExistsException("[{$func_name}] does not exist");
        }
    }

} //end trait