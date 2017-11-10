<?php namespace Heroest\LaravelModel;

use Heroest\LaravelModel\Exception\InvalidParameterException;
use Heroest\LaravelModel\Exception\ConnectionNotFoundException;
use PDO;

class ConnectionPool
{
    private static $instance = null;

    private static $pool;

    private function __construct(){}


    /**
     * get Intance of the class
     *
     * @return object
     */
    public static function getInstance()
    {
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }


    /**
     * get a connection (PDO object) by connection_name
     *
     * @param string $name
     * @return PDO object
     */
    public static function get($name)
    {
        if(!isset(self::$pool[$name])) 
            throw new ConnectionNotFoundException("Pool->getConnection(): [$name] not found");
        return self::$pool[$name];
    }


    /**
     * Initialize a connection(PDO) and save in the connection_pool
     *
     * @param string $connection_name
     * @param array $config || object PDO instance
     * @return $this
     */
    public static function add($name, $mixed)
    {
        if(isset(self::$pool[$name])) return;

        if(!is_array($mixed) and !($mixed instanceof PDO)) 
            throw new InvalidParameterException("Pool->addConnection(), the 2nd parameter expects an array or a PDO instance");
        self::$pool[$name] = is_array($mixed)
                            ? self::buildPdo($mixed)
                            : $mixed;       
    }


    /**
     * Check whether conneciton exilsts
     *
     * @param string $name
     * @return boolean
     */
    public static function has($name)
    {
        return isset(self::$pool[$name]);
    }


    /**
     * Initialize a PDO object from config
     *
     * @param [array] $config
     * @return PDO object;
     */
    private static function buildPdo($config)
    {
        $keys = ['type', 'host', 'username', 'password', 'db_name', 'port'];
        foreach($keys as $key) {
            if( !isset($config[$key]) ) throw new InvalidParameterException("ConnectionPool->buildPdo(): {$key} is missing from the connection configuration");
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
}