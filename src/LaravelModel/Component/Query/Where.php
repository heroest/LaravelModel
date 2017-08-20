<?php namespace Heroest\LaravelModel\Component\Query;

use Heroest\LaravelModel\Component\Query\Interfaces\QueryComponent;
use Heroest\LaravelModel\Exception\InvalidParameterException;

class Where implements QueryComponent
{
    private $key = null;

    private $op = null;

    private $value = null;


    public function __construct($params)
    {
        $this->key = $params['key'];
        $this->op = isset($params['op']) ? $params['op'] : '=';
        $this->value = $params['value'];
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return "{$this->key} {$this->op} ?";
    }
}