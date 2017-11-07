<?php namespace Heroest\LaravelModel\Component;

class Factory
{
    /**
     * Build Component
     *
     * @param string $type
     * @param string $component_name
     * @param array $params
     * @return object
     */
    public static function build($type, $component, $params)
    {
        $type = ucfirst($type);
        $component = ucfirst($component);
        $model = "\Heroest\LaravelModel\Component\\{$type}\\{$component}";
        $obj = new $model($params);

        return $obj;
    }
}