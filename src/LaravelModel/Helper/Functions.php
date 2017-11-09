<?php

if(!function_exists('vp')) {
    function vp($mixed)
    {
        echo '<pre style="border:1px solid #337ab7;padding:7px;line-height:1.5;font-family:Arial">';
        var_dump($mixed);
        echo '</pre>';
    }
}



if(!function_exists('vpd')) {
    function vpd($mixed)
    {
        echo '<pre style="border:1px solid #337ab7;padding:7px;line-height:1.5;font-family:Arial">';
        var_dump($mixed);
        echo '</pre>';
        exit();
    }
}



if(!function_exists('pp')) {
    function pp($mixed)
    {
        echo '<pre style="border:1px solid #337ab7;padding:7px;line-height:1.5;font-family:Arial">' . print_r($mixed, true) . '</pre>';
    }
}



if(!function_exists('ppd')) {
    function ppd($mixed)
    {
        exit('<pre style="border:1px solid #337ab7;padding:7px;line-height:1.5;font-family:Arial">' . print_r($mixed, true) . '</pre>');
    }
}


if(!function_exists('only_int')) {
    /**
     * Check if value containas number only
     *
     * @param [type] $mixed
     * @return void
     */
    function only_int($mixed)
    {
        return ctype_digit(strval($mixed));
    }
}


if(!function_exists('array_add')) {
    /**
     * Append an item or every element from 2nd array to first array
     *
     * @param array $base
     * @param mixed $mixed
     * @return array
     */
    function array_add()
    {
        $params = func_get_args();
        $base = array_shift($params);
        if(!is_array($base)) throw new \Heroest\LaravelModel\Exception\InvalidParameterException("array_add(): expect first parameter is an array");

        foreach($params as $mixed) {
            if(is_array($mixed)) {
                foreach($mixed as $item) {
                    $base[] = $item;
                }
            } else {
                $base[] = $mixed;
            }
        }
        
        return $base;
    }
}


if(!function_exists('array_compare')) {
    /**
     * array_compare the difference between two array
     *
     * @param array $arr_a
     * @param array $arr_b
     * @return array
     */
    function array_compare(array $arr_a, array $arr_b)
    {
        if(empty($arr_a)) return $arr_b;

        $result = [];
        foreach($arr_b as $key => $val) {
            if((!isset($arr_a[$key]) and !is_null($arr_a[$key])) or $arr_a[$key] !== $val) $result[$key] = $val;
        }
        return $result;
    }
}

if(!function_exists('array_column')) {
    /**
     * compatiable array_column function for < php 5.5.0
     *
     * @param array $list
     * @param string $key
     * @param string $index
     * @return array
     */
    function array_column(array $list, $key, $index = null) {
        $result = [];
        foreach($list as $row) {
            $item = ($key === null) ? $row : $row[$key];

            if($index === null) {
                $result[] = $item;
            } else {
                $result[$row[$index]] = $item;
            }
        }
        return $result;
    }
}

if(!function_exists('object2Array')) {
    /**
     * Conevet object to array
     *
     * @param [object] $mixed
     * @return array
     */
    function object2Array($mixed)
    {
        return is_array($mixed) ? $mixed: json_decode(json_encode($mixed), true);
    }
}


if(!function_exists('result2Array')) {
    function result2Array(array $arr)
    {
        $result = [];
        foreach($arr as $key => $item) {
            if(is_object($item)) {
                $result[] = method_exists($item, 'toArray') ? $item->toArray() : object2Array($item);
            } elseif(is_array($item)) {
                vp("nested");
                $result[] = result2Array($item);
            } else {
                $result[$key] = $item;
            }
        }
        return $result;
    }
}