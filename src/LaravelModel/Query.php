<?php namespace Heroest\LaravelModel;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\FunctionNotExistsException;
use Heroest\LaravelModel\Exception\RelationNotExistException;
use Heroest\LaravelModel\Component\Query\Interfaces\QueryComponent;
use Heroest\LaravelModel\Collection;
use Heroest\LaravelModel\Component\Factory;
use Closure;
use Exception;

class Query
{
    const SQL_AND = 'AND';

    const SQL_OR = 'OR';

    const SQL_LEFTP = '(';

    const SQL_RIGHTP = ')';

    /**
     * BaseModel
     *
     * @var Object
     */
    private $baseModel = null;

    /**
     * Query Log
     *
     * @var array
     */
    private static $queryLog = [];

    /**
     * Connection name
     *
     * @var string
     */
    private $connection = '';

    /**
     * PDO object
     *
     * @var PDO
     */
    private $pdo;

    /**
     * Primary Key of the table
     *
     * @var string
     */
    private $primaryKey = '';

    /**
     * Primary Key value
     *
     * @var string
     */
    private $primaryKeyValue = null;

    /**
     * Last inserted id;
     *
     * @var integer
     */
    private $lastInsertId = null;

    /**
     * Determine whether enable handle created_at and updated_at
     *
     * @var boolean
     */
    private $timestamps = false;

    /**
     * Column name for updated_at
     *
     * @var string
     */
    private $updated_at = '';

    /**
     * Column name for created_at field
     *
     * @var string
     */    
    private $created_at = '';

    /**
     * Column name for updated_at field
     *
     * @var string
     */
    private $dateFormat = 'U';

    /**
     * Table name
     *
     * @var string
     */
    private $table = '';

    /**
     * Columns that will treated as dates data
     *
     * @var array
     */
    private $dates = [];

    /**
     * Indicate which columns can be filled using massive assignment
     *
     * @var array
     */
    private $fillable = [];

    /**
     * Indicate which columns should be hidden in the result
     *
     * @var array
     */
    private $hidden = [];

    /**
     * Indicate which columns should not be filled using massive assignment
     *
     * @var array
     */
    private $guarded = [];

    /**
     * SELECT components
     *
     * @var array
     */
    private $select = [];

    /**
     * SELECT components that must be select
     *
     * @var array
     */
    private $mustSelect = [];

    /**
     * WHERE components
     *
     * @var array
     */
    private $where = [];

    /**
     * JOIN components
     *
     * @var array
     */
    private $join = [];

    /**
     * On Components
     *
     * @var array
     */
    private $on = [];

     /**
     * Indicate limit caluse in the query
     *
     * @var array
     */
    private $take = null;
    private $offset = null;

    /**
     * Indicate with relationships in the subquery
     *
     * @var array
     */
    private $with = [];

    /**
     * Store scope between relationships
     *
     * @var array
     */
    private $scope = [];

    /**
     * Map config for selection with relationships
     *
     * @var string 'one' or 'many'
     */
    private $map = null;
    private $local_key = '';
    private $remote_key = '';
    private $join_table = '';

    /**
     * Number of row affected after Update Or Delete Query
     *
     * @var [int]
     */
    private $rowCount = null;

    /**
     * Data Storage
     *
     * @var array
     */
    private $data = [];

    /**
     * Data that saved
     *
     * @var array
     */
    private $saved = [];

    /**
     * Model function name prefix
     *
     * @var string
     */
    private $function_prefix = '';

    public function __construct($params = [])
    {
        //if user want simple SQl query
        if(empty($params)) return;

        $this->baseModel = $params['baseModel'];
        $this->connection = $params['connection'];
        $this->pdo = $params['pdo'];
        $this->table = $params['table'];
        $this->primaryKey = $params['primaryKey'];
        $this->primaryKeyValue = $params['primaryKeyValue'];
        $this->updated_at = $params['updated_at'];
        $this->created_at = $params['created_at'];
        $this->dateFormat = $params['dateFormat'];
        $this->saved = $params['saved'];
        $this->fillable = $params['fillable'];
        $this->hidden = $params['hidden'];
        $this->guarded = $params['guarded'];
        $this->function_prefix = $params['function_prefix'];
        $this->dates = isset($params['dates'])
                            ? array_merge([$this->updated_at, $this->created_at], $prams['dates'])
                            : [$this->updated_at, $this->created_at];
        
        //default guarded
        if(!empty($this->primaryKey)) $this->guarded[] = $this->primaryKey;
    }

    /**
     * Set datatable name
     *
     * @param [string] $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set Connection for this Query
     *
     * @param [type] $name
     * @return void
     */
    public function connection($name)
    {
        if(!ConnectionPool::has($name)) throw new InvalidParameterException("Error in Query->connection() can not found connection [{$name}]");
        $this->pdo = ConnectionPool::get($name);
        return $this;
    }

    /**
     * Add select clause in the Query
     *
     * @return void
     */
    public function select()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $this->select = array_unique(array_merge($this->mustSelect, $params));
        return $this;
    }

    /**
     * add (must) select clause in the Query
     *
     * @return void
     */
    public function mustSelect()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $this->mustSelect = $params;
        return $this;
    }

    /**
     * return count of records
     *
     * @return int
     */
    public function count()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $this->select = ["COUNT(*) as database_record_count"];
        $this->offset = null;
        $this->take = null;
        $result = $this->executeSelectQuery();
        $result = object2Array($result);
        return $result['database_record_count'];
    }

    /**
     * Get Result from the Query
     *
     * @return $result
     */
    public function get()
    {
        $result = $this->executeSelectQuery();
        $result = $this->buildQueryResult($result);
        
        //handle With Relationships
        if(!empty($this->with)) $result = $this->nextWithScope($result);

        $this->afterQuery();
        return $result;
    }

    /**
     * Return data line by line
     *
     * @param int $chunk_size
     * @return collection
     */
    public function each($chunk_size = 2)
    {
        if ($chunk_size < 2) throw new InvalidParameterException("each(): chunk size must be greater or equal to 2");
        $offset = 0;
        $query = clone $this;
        $query->offset($offset * $chunk_size)->take($chunk_size);
        $chunk = $query->get();

        while(!$chunk->isEmpty()) {
            foreach($chunk as $row) {
                yield $row;
            }
            if (count($chunk) < $chunk_size) break;
            $offset++;
            $query = clone $this;
            $chunk = $query->offset($offset * $chunk_size)->take($chunk_size)->get();
        }
    }

    /**
     * Get Result from the Query with Scope
     *
     * @return $result
     */
    public function getWithScope($scope, $name)
    {
        $this->scope = $scope;

        $withIn = [];
        foreach($scope as $item) {
            $remote = $this->remote_key;
            $withIn[] = is_object($item) ? $item->$remote : $item[$remote];
        }
        
        //if none record founded.. return scope
        if(empty($withIn)) return $scope;

        if(empty($this->join_table)) {
            $this->whereIn($this->local_key, $withIn);
        } else {
            $this->whereIn("{$this->join_table}.{$this->local_key}", $withIn);
        }

        $result = $this->executeSelectQuery();
        $result = $this->buildQueryResult($result);
        
        //handle With Relationships
        if(!empty($this->with)) $result = $this->nextWithScope($result);

        //after all done...
        if(!empty($this->scope)) $result = $this->backWithResult($result, $name);
        
        $this->afterQuery();
        return $result;
    }

    /**
     * get first row of the result
     *
     * @return $row
     */
    public function first()
    {
        $this->limit(1);
        $result = $this->executeSelectQuery();
        $this->setPrimaryKeyValue($result);
        $result = $this->buildQueryResult($result);

        //handle With Relationships
        if(!empty($this->with)) $result = $this->nextWithScope($result);
        
        $this->afterQuery();
        return $result;
    }

    /**
     * Find a record where matched primary_key_value
     *
     * @param [mixed] $primary_key_value
     * @return void
     */
    public function find($mixed)
    {
        $this->where([$this->primaryKey, '=', $mixed]);
        $result = $this->executeSelectQuery();
        $this->setPrimaryKeyValue($result);
        $result = $this->buildQueryResult($result);

        //handle With Relationships
        if(!empty($this->with)) $result = $this->nextWithScope($result);
        
        $this->afterQuery();
        return $result;
    }

    /**
     * Find a record where matched many primary_key_value
     *
     * @param [array] $primary_key_value
     * @return void
     */
    public function findMany(array $value_arr)
    {
        $this->whereIn($this->primaryKey, $value_arr);
        $result = $this->executeSelectQuery();
        $result = $this->buildQueryResult($result);

        //handle With Relationships
        if(!empty($this->with)) $result = $this->nextWithScope($result);
        $result = $this->buildQueryResult($result);
        
        $this->afterQuery();
        return $result;
    }

    /**
     * Insert Query
     *
     * @param array $mixed
     * @return int lastInsertedId
     */
    public function insert(array $mixed)
    {
        return $this->executeInsertQuery($mixed);
    }

    /**
     * Update Query
     *
     * @param array $mixed
     * @return int row_effected
     */
    public function update(array $mixed)
    {
        return $this->executeUpdateQuery($mixed);
    }

    /**
     * Add Limit Clause to Query
     *
     * @param [type] $num
     * @return $this
     */
    public function take($num)
    {
        if(!only_int($num)) throw new InvalidParameterException("Query->take() expects integer parameter");
        $this->take = $num;
        return $this;
    }

    public function offset($num)
    {
        if(!only_int($num)) throw new InvalidParameterException("Query->offset() expects integer parameter");
        $this->offset = $num;
        return $this;
    }

    public function limit()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        if($count === 1) {
            $offset = 0;
            $take = array_pop($params);
        } elseif ($count === 2) {
            $offset = array_shift($params);
            $take = array_pop($params);
        }

        if(!only_int($offset) or !only_int($take)) {
            throw new InvalidParameterException("Query->limit() expectes integer parameters");
        } else {
            $this->offset = $offset;
            $this->take = $take;
        }

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
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;

        $count = count($params);

        if($count === 3) {

            list($key, $op, $value) = $params;
            $where_component = Factory::build(
                                    'Query', 
                                    'Where', 
                                    [
                                        'key' => $key,
                                        'op' => $op,
                                        'value' => $value,
                                    ]);

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP)  $this->where[] = self::SQL_AND;
            $this->where[] = $where_component;

        } elseif ($count === 2) {

            list($key, $value) = $params;

            $where_component = Factory::build(
                                'Query', 
                                'Where', 
                                [
                                    'key' => $key,
                                    'op' => '=',
                                    'value' => $value
                                ]);

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = $where_component;
                


        } elseif ($count === 1 and ($func = array_shift($params)) instanceof Closure) {

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = self::SQL_LEFTP;
            $func($this);
            $this->where[] = self::SQL_RIGHTP;

        } elseif($count == 1 and is_array($params[0])) {

            foreach($params[0] as $key => $value) {
                $this->where($key, $value);
            }

        } else {
            throw new InvalidParameterException("Query->where(): Invalid number of parameters");
        }
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
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        if ($count == 3) {

            list($key, $op, $value) = $params;
            $where_component = Factory::build(
                                    'Query', 
                                    'Where', 
                                    [
                                        'key' => $key,
                                        'op' => $op,
                                        'value' => $value
                                    ]);

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_OR;
            $this->where[] = $where_component;
            
        } elseif ($count === 2) {

            list($key, $value) = $params;
            $where_component = Factory::build(
                                'Query', 
                                'Where', 
                                [
                                    'key' => $key,
                                    'op' => '=',
                                    'value' => $value
                                ]);

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_OR;
            $this->where[] = $where_component;
            

        } elseif ($count === 1 and ($func = array_shift($params)) instanceof Closure) {

            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_OR;
            $this->where[] = self::SQL_LEFTP;
            $func($this);
            $this->where[] = self::SQL_RIGHTP;

        } elseif($count == 1 and is_array($params[0])) {

            foreach($params[0] as $key => $value) {
                $this->where($key, $value);
            }
            
        } else {

            throw new InvalidParameterException("Query->where(): Invalid number of parameters");

        }

        return $this;
    }
    
    /**
     * Add WhereIn clause to Query
     *
     * @return $this
     */
    public function whereIn($name, $value_arr)
    {
        $count = count($value_arr);
        $whereIn_component = Factory::build(
                            'Query', 
                            'WhereIn', 
                            [
                                'key' => $name,
                                'values' => $value_arr
                            ]);

        if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
        $this->where[] = $whereIn_component;

        return $this;
    }

    /**
     * Add WhereNotIn clause to Query
     *
     * @return $this
     */
    public function whereNotIn($name, $value_arr)
    {
        $count = count($value_arr);
        $whereIn_component = Factory::build(
                            'Query', 
                            'WhereNotIn',
                            [
                                'key' => $name,
                                'values' => $value_arr
                            ]);

        if(empty($this->where)) {
            $this->where[] = $whereIn_component;
        } else {
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = $whereIn_component;
        }

        return $this;
    }

    /**
     * add where statement to join table
     *
     * @return object
     */
    public function wherePivot()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);
        if($count === 3) {
            list($key, $op, $value) = $params;
            $where_component = Factory::build(
                                    'Query', 
                                    'Where', 
                                    [
                                        'key' => "{$this->join_table}.{$key}",
                                        'op' => $op,
                                        'value' => $value,
                                    ]);
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP)  $this->where[] = self::SQL_AND;
            $this->where[] = $where_component;
        } elseif ($count === 2) {
            list($key, $value) = $params;
            $where_component = Factory::build(
                                'Query', 
                                'Where', 
                                [
                                    'key' => "{$this->join_table}.{$key}",
                                    'op' => '=',
                                    'value' => $value
                                ]);
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = $where_component;
        } elseif ($count === 1 and ($func = array_shift($params)) instanceof Closure) {
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = self::SQL_LEFTP;
            $func($this);
            $this->where[] = self::SQL_RIGHTP;
        } elseif($count == 1 and is_array($params[0])) {
            foreach($params[0] as $key => $value) {
                $this->wherePivot($key, $value);
            }
        } else {
            throw new InvalidParameterException("Query->wherePivot(): Invalid number of parameters");
        }
        return $this;
    }

    public function orWherePivot()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);
        if($count === 3) {
            list($key, $op, $value) = $params;
            $where_component = Factory::build(
                                    'Query', 
                                    'Where', 
                                    [
                                        'key' => "{$this->join_table}.{$key}",
                                        'op' => $op,
                                        'value' => $value,
                                    ]);
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP)  $this->where[] = self::SQL_OR;
            $this->where[] = $where_component;
        } elseif ($count === 2) {
            list($key, $value) = $params;
            $where_component = Factory::build(
                                'Query', 
                                'Where', 
                                [
                                    'key' => "{$this->join_table}.{$key}",
                                    'op' => '=',
                                    'value' => $value
                                ]);
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_OR;
            $this->where[] = $where_component;
        } elseif ($count === 1 and ($func = array_shift($params)) instanceof Closure) {
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_OR;
            $this->where[] = self::SQL_LEFTP;
            $func($this);
            $this->where[] = self::SQL_RIGHTP;
        } elseif($count == 1 and is_array($params[0])) {
            foreach($params[0] as $key => $value) {
                $this->wherePivot($key, $value);
            }
        } else {
            throw new InvalidParameterException("Query->wherePivot(): Invalid number of parameters");
        }
        return $this;
    }

    public function wherePivotIn($name, $value_arr)
    {
        $count = count($value_arr);
        $whereIn_component = Factory::build(
                            'Query', 
                            'WhereIn', 
                            [
                                'key' => "{$this->join_table}.{$name}",
                                'values' => $value_arr
                            ]);

        if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
        $this->where[] = $whereIn_component;

        return $this;
    }

    public function wherePivotNotIn($name, $value_arr)
    {
        $count = count($value_arr);
        $whereIn_component = Factory::build(
                            'Query', 
                            'WhereNotIn',
                            [
                                'key' => "{$this->join_table}.{$name}",
                                'values' => $value_arr
                            ]);

        if(empty($this->where)) {
            $this->where[] = $whereIn_component;
        } else {
            if(!empty($this->where) and end($this->where) !== self::SQL_LEFTP) $this->where[] = self::SQL_AND;
            $this->where[] = $whereIn_component;
        }

        return $this;
    }

    /**
     * add inner join
     *
     * @return object
     */
    public function innerJoin()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        if($count === 4) {

            list($table, $column_base, $op, $column_join) = $params;
            $this->join[] = "INNER JOIN {$table} ON {$column_base} {$op} {$column_join}";

        } elseif ($count === 3) {

            list($table, $column_base,$column_join) = $params;
            $this->join[] = "INNER JOIN {$table} ON {$column_base} = {$column_join}";

        } elseif ($count === 2) {

            $this->on = [];
            list($table, $closure) = $params;
            if(!$closure instanceof Closure) throw new InvalidParameterException("Query->InnerJoin(): 2nd parameter expects a callable object");
            $this->join[] = "INNER JOIN {$table} ON";
            $closure($this);
            $this->join[] = implode(' ', $this->on);
            $this->on = [];

        } else {

            throw InvalidParameterException("Query->innerJoin(): Invalid number of parameters");

        }
    }

    /**
     * add left join
     *
     * @return object
     */
    public function leftJoin()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        if($count === 4) {

            list($table, $column_base, $op, $column_join) = $params;
            $this->join[] = "LEFT JOIN {$table} ON {$column_base} {$op} {$column_join}";

        } elseif ($count === 3) {

            list($table, $column_base,$column_join) = $params;
            $this->join[] = "LEFT JOIN {$table} ON {$column_base} = {$column_join}";

        } elseif ($count === 2) {

            $this->on = [];
            list($table, $closure) = $params;
            if(!$closure instanceof Closure) throw new InvalidParameterException("Query->leftJoin(): 2nd parameter expects a callable object");
            $this->join[] = "LEFT JOIN {$table} ON";
            $closure($this);
            $this->join[] = implode(' ', $this->on);
            $this->on = [];

        } else {

            throw InvalidParameterException("Query->leftJoin(): Invalid number of parameters");

        }
    }

    /**
     * add right join
     *
     * @return object
     */
    public function rightJoin()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        if($count === 4) {

            list($table, $column_base, $op, $column_join) = $params;
            $this->join[] = "RIGHT JOIN {$table} ON {$column_base} {$op} {$column_join}";

        } elseif ($count === 3) {

            list($table, $column_base, $column_join) = $params;
            $this->join[] = "RIGHT JOIN {$table} ON {$column_base} = {$column_join}";

        } elseif ($count === 2) {

            $this->on = [];
            list($table, $closure) = $params;
            if(!$closure instanceof Closure) throw new InvalidParameterException("Query->rightJoin(): 2nd parameter expects a callable object");
            $this->join[] = "RIGHT JOIN {$table} ON";
            $closure($this);
            $this->join[] = implode(' ', $this->on);
            $this->on = [];

        } else {

            throw new InvalidParameterException("Query->rightJoin(): Invalid number of parameters");

        }
    }

    /**
     * add ON statement
     *
     * @return object
     */
    public function on()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);
        
        if(!empty($this->on) and end($this->on) !== self::SQL_LEFTP) $this->on[] = self::SQL_AND;
        if($count === 3) {

            list($column_base, $op, $column_join) = $params;
            if(is_string($column_join) and empty($column_join)) $column_join = "''";
            $this->on[] = "{$column_base} {$op} {$column_join}";

        } elseif ($count === 2) {
            
            list($column_base, $column_join) = $params;
            if(is_string($column_join) and empty($column_join)) $column_join = "''";
            $this->on[] = "{$column_base} = {$column_join}";

        } elseif($count === 1) {
            
            $closure = array_pop($params);
            if(!$closure instanceof Closure) throw new InvalidParameterException("Query->on(): the parameter type expects to be callable");
            $this->on[] = self::SQL_LEFTP;
            $closure($this);
            $this->on[] = self::SQL_RIGHTP;

        } else {

            throw new InvalidParameterException("Query->on(): Invalid number of parameters");

        }

        return $this;
    }

    /**
     * add orOn statement
     *
     * @return object
     */
    public function orOn()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);
        
        if(!empty($this->on) and end($this->on) !== self::SQL_LEFTP) $this->on[] = self::SQL_OR;
        if($count === 3) {

            list($column_base, $op, $column_join) = $params;
            if(is_string($column_join) and empty($column_join)) $column_join = '""';
            $this->on[] = "{$column_base} {$op} {$column_join}";

        } elseif ($count === 2) {
            
            list($column_base, $column_join) = $params;
            if(is_string($column_join) and empty($column_join)) $column_join = '""';
            $this->on[] = "{$column_base} = {$column_join}";

        } elseif($count === 1) {

            $closure = array_pop($params);
            if(!$closure instanceof Closure) throw new InvalidParameterException("Query->orOn(): the parameter type expects to be callable");
            $this->on[] = self::SQL_LEFTP;
            $closure($this);
            $this->on[] = self::SQL_RIGHTP;

        } else {

            throw new InvalidParameterException("Query->orOn(): Invalid number of parameters");

        }

        return $this;
    }

    /**
     * Massive Assignment
     *
     * @param [type] $params
     * @return void
     */
    public function fill(array $params)
    {
        if(!empty($this->fillable)) {

            foreach($params as $key => $val) {
                if((!empty($this->guarded) and in_array($key, $this->guarded)) or !in_array($key, $this->fillable)) continue;
                $this->data[$key] = $val;
            }

        } elseif(!empty($this->guarded)) {

            foreach($params as $key => $val) {
                if(!in_array($key, $this->guarded)) $this->data[$key] = $val;
            }

        } else {

            $this->data = $params;

        }
    }

    /**
     * Update or Create a Record in the database
     *
     * @return void
     */
    public function save()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $count = count($params);

        $data_diff = array_compare($this->saved, $this->data);
        $primary_value = !empty($this->primaryKeyValue) 
                                ? $this->primaryKeyValue 
                                : (isset($this->saved[$this->primaryKey])
                                        ? $this->saved[$this->primaryKey] 
                                        : (isset($this->data[$this->primaryKey]) 
                                                ? $this->data[$this->primaryKey] : null));
        
        if(empty($data_diff)) {

            $this->afterQuery();
            return 0;

        } elseif (!empty($primary_value)) {

            $result = $this->where($this->primaryKey, $primary_value)->executeUpdateQuery($data_diff);
            $this->baseModel->populate($this->saved);
            $this->afterQuery();
            return $this->baseModel;
            
        } else {

            $result = $this->executeInsertQuery($data_diff);
            $this->baseModel->populate($this->saved);
            $this->afterQuery();
            return $this->baseModel;
            
        }
    }

    /**
     * Get all executed Query statement and parameters
     *
     * @return array
     */
    public function getQueryLog()
    {
        return self::$queryLog;
    }

    /**
     * get last inserted id from previous query
     *
     * @return int or null
     */
    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * get count of row affected from previous query
     *
     * @return void
     */
    public function rowCount()
    {
        return $this->rowCount;
    }

    /**
     * Sync updated Data
     *
     * @param array $updated_data
     * @return void
     */
    public function syncData(array $updated_data)
    {
        $this->data = array_merge($this->data, $updated_data);
    }

    /**
     * Set map mode and relation keys for scope
     *
     * @param string $type
     * @param string $local
     * @param string $remote
     * @return object
     */
    public function map($type, $local, $remote, $join_table = '')
    {
        $this->map = $type;

        $this->local_key = $local;

        $this->remote_key = $remote;

        $this->join_table = $join_table;

        return $this;
    }

    /**
     * add eager-load 
     *
     * @return object
     */
    public function with()
    {
        $params = func_get_args();
        $params = (count($params) === 1 and is_array($params[0])) ? $params[0] : $params;
        $this->with = $params;
        return $this;
    }

    /**
     * Execute a select Query
     *
     * @return result
     */
    private function executeSelectQuery()
    {
        $this->beforeQuery();
        $components = [];

        //SELECT
        $components[] = 'SELECT ' . (empty($this->select) ? '*' : implode(',', $this->select));
        
        //FROM
        $components[] = "FROM {$this->table}";

        //JOIN
        if(!empty($this->join)) $components = array_add($components, $this->join);

        //WHERE
        if(!empty($this->where)) {
            $components[] = 'WHERE';
            $components = array_add($components, $this->where);
        }

        //LIMIT
        $limit = $this->buildLimitClause();
        if(!empty($limit)) $components[] = $limit; 
        
        //compile
        list($sql, $parameters) = $this->compile($components);  
        $this->addQueryLog($sql, $parameters);

        //prepare and execute
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parameters);
        } catch (Exception $e) {
            throw $e;
        }
        

        if($this->take === 1) {
            $result = $stmt->fetch();
            $this->populate(object2Array($result));
        } else {
            $result = $stmt->fetchAll();
        }

        return $result;
    }

    /**
     * Execute a update query
     *
     * @param array $params
     * @return void
     */
    private function executeUpdateQuery(array $params)
    {
        $this->beforeQuery();

        //Timestamps
        if(!empty($this->updated_at)) $params[$this->updated_at] = date($this->dateFormat, time());

        $components = [];

        //UPDATE Table
        $components[] = "UPDATE {$this->table} SET";

        //SET FIELD
        $components[] = Factory::build('Query', 'Set', $params);

        //WHERE
        $components[] = "WHERE";
        $components = array_merge($components, $this->where);

        //LIMIT
        $limit = $this->buildLimitClause();
        if(!empty($limit)) $components[] = $limit;

        //COMPILE
        list($sql, $parameters) = $this->compile($components);
        $this->addQueryLog($sql, $parameters);

        //PREPARE AND EXECUTE
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parameters);
        } catch (Exception $e) {
            throw $e;
        }   
        
        $this->rowCount = $stmt->rowCount();
        return $this->rowCount;
    }

    /**
     * Execute a INSERT Query
     *
     * @param array $params
     * @return int
     */
    private function executeInsertQuery(array $params)
    {
        $this->beforeQuery();
        $components = [];

        //Timestamps
        if(!empty($this->created_at)) $params[$this->created_at] = date($this->dateFormat, time());
        if(!empty($this->updated_at)) $params[$this->updated_at] = date($this->dateFormat, time());

        //Insert
        $components[] = "INSERT INTO {$this->table}";

        //VALUES components
        $components[] = Factory::build('Query', 'Values', $params);

        //COMPILE
        list($sql, $parameters) = $this->compile($components);
        $this->addQueryLog($sql, $parameters);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($parameters);
        } catch (Exception $e) {
            throw $e;
        }
        
        $id = $this->pdo->lastInsertId();
        $this->primaryKeyValue = $id;
        $this->lastInsertId = $id;
        return $id;
    }

    /**
     * Build a Limit clause after a query statement
     *
     * @return string
     */
    private function buildLimitClause()
    {
        if(is_null($this->take)) return '';

        return is_null($this->offset) 
                ? "LIMIT {$this->take}" 
                : "LIMIT {$this->offset}, {$this->take}";
    }

    /**
     * Compile a colleciton of query components to generate a SQL and a set of parameters
     *
     * @param array $arr
     * @return array
     */
    private function compile(array $arr)
    {
        $sql = [];
        $parameters = [];
        foreach($arr as $item) {
            if($item instanceof QueryComponent) {
                $parameters = array_add($parameters, $item->getValue());
            }
                
            $sql[] = $item;
        }

        return [implode(' ', $sql), $parameters];
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
            return;
        } elseif (is_object($mixed)) {
            $this->primaryKeyValue = isset($mixed->$key) ? $mixed->$key : null;
        } elseif (is_array($mixed)) {
            $this->primaryKeyValue = isset($mixed[$key]) ? $mixed[$key] : null;
        }
    }

    private function buildQueryResult($mixed)
    {
        if(empty($this->function_prefix)) {
            $populate_func = 'populate';
            $toArray_func = 'toArray';
        } else {
            $populate_func = "{$this->function_prefix}populate";
            $toArray_func = "{$this->functioin_prefix}toArray";
        }

        if($this->take === 1) {

            $data = (is_object($mixed)) ? (method_exists($mixed, $toArray_func) ? $mixed->$toArray_func() : object2Array($mixed)) : $mixed;
            if($this->baseModel !== null) {
                $this->setPrimaryKeyValue($data); 
                $this->baseModel->$populate_func($data);
                return $this->baseModel;
            } else {
                return $data;
            }

        } else {

            $collection = new Collection();
            if(!empty($this->function_prefix)) $collection->setFunctionPrefix($this->function_prefix);
            
            foreach($mixed as $row) {
                $data = (is_object($row)) ? (method_exists($row, $toArray_func) ? $row->$toArray_func() : object2Array($row)) : $row;
                if($this->baseModel !== null) {
                    $object = clone $this->baseModel;
                    $object->$populate_func($data);
                    $collection[] = $object;
                } else {
                    $collection[] = $data;
                }
            }
            return $collection;

        }
    }


    private function populate(array $data)
    {
        if(isset($data[$this->primaryKey])) $this->primaryKeyValue = $data[$this->primaryKey];
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Reset class attribute for coming Query
     *
     * @return void
     */
    private function beforeQuery()
    {

        $this->lastInsertId = null;

        $this->rowCount = 0;

    }


    /**
     * Reset class attributes for next Query
     *
     * @return void
     */
    private function afterQuery()
    {
        $this->where = [];

        $this->select = [];

        $this->join = [];

        $this->on = [];

        $this->with = [];

        $this->offset = null;

        $this->take = null;

        $this->saved = array_merge($this->saved, $this->data);
        $this->data = [];
    }


    /**
     * Push a Query statement with parameters to Log
     *
     * @param [string] $sql
     * @param [array] $params
     * @return void
     */
    private function addQueryLog($sql, $params)
    {
        self::$queryLog[] = ['sql' => $sql, 'parameters' => $params];
    }


    private function nextWithScope($scope)
    {
        foreach($this->with as $key => $val) {
            if(only_int($key)) {
                $name = $val;
                if(!method_exists($this->baseModel, $val)) throw new RelationNotExistException(get_class($this->baseModel) . ' has no relation called ' . $val);
                $queryWith = $this->baseModel->$val();
            } else {
                $name = $key;
                if(!method_exists($this->baseModel, $key)) throw new RelationNotExistException(get_class($this->baseModel) . ' has no relation called ' . $key);
                $queryWith = $this->baseModel->$key();
                $val($queryWith);
            }
            $func = empty($this->function_prefix) ? 'getWithScope' : "{$this->function_prefix}getWithScope";
            $scope = $queryWith->$func($scope, $name);
        }
        return $scope;
    }


    private function backWithResult($result, $name)
    {
        $dict = [];
        $local = $this->local_key;
        $remote = $this->remote_key;
        foreach($result as $item) {
            if(!isset($dict[$item->$local])) $dict[$item->$local] = new Collection();
            $dict[$item->$local][] = $item;
        }
        $scope = $this->scope;
        if($this->map === 'one') {
            foreach($scope as $k => $v) {
                $item = &$scope[$k];
                $remote_item = !empty($dict[$item->$remote]) ? $dict[$item->$remote][0] : null;
                if(is_object($item)) {
                    $item->$name = $remote_item;
                    $item->markSaved();
                } else {
                    $item[$name] = $remote_item;
                }
            }
        } elseif($this->map === 'many') {
            foreach($scope as $k => $v) {
                $item = &$scope[$k];
                $remote_item = !empty($dict[$item->$remote]) ? $dict[$item->$remote] : new Collection();
                if(is_object($item)) {
                    $item->$name = $remote_item;
                    $item->markSaved();
                } else {
                    $item[$name] = $remote_item;
                }
            }
        }
        return $scope;
    }

    public function __call($func_name, $params)
    {
        $func_short = (!empty($this->function_prefix) and strpos($func_name, $this->function_prefix) === 0)
                        ? substr($func_name, strlen($this->function_prefix))
                        : $func_name;

        if(method_exists($this, $func_short)) {
            return call_user_func_array([$this, $func_short], $params);
        } elseif(method_exists($this, $func_name)) {
            return call_user_func_array([$this, $func_name], $params);
        } else {
            throw new FunctionNotExistsException("Error in Query->__call(): [$func_name] does not exists");
        }
    }

}