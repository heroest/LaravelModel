<?php namespace Heroest\LaravelModel\Component\Query;

use Heroest\LaravelModel\Component\Query\Interfaces\QueryComponent;
use Heroest\LaravelModel\Exception\InvalidParameterException;

class WhereIn implements QueryComponent
{
    private $key = null;
    private $nunm_values = 0;
    private $values;

    public function __construct($params)
    {
        if(empty($params['values'])) throw new InvalidParameterException("Error in WhereIn Components: Empty array in WhereIn");
        $this->key = $params['key'];
        $this->nunm_values = count($params['values']);
        $this->values = $params['values'];
    }

    public function getValue()
    {
        return $this->values;
    }

    public function __toString()
    {
        $str = implode(',', array_fill(0 , $this->nunm_values, '?'));
        return "{$this->key} IN ({$str})";
    }
}