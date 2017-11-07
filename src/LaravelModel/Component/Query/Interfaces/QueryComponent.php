<?php namespace Heroest\LaravelModel\Component\Query\Interfaces;

interface QueryComponent 
{   
    /**
     * get binded parameters
     *
     * @return void
     */
    public function getValue();

    /**
     * convert query component to string
     *
     * @return string
     */
    public function __toString();
}