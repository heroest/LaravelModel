<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4ff88d3405934eea52753eac3b37918a
{
    public static $files = array (
        '1626a200bfb0da086de9f4cd07c00350' => __DIR__ . '/../..' . '/src/LaravelModel/Helper/Functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'H' => 
        array (
            'Heroest\\LaravelModel\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Heroest\\LaravelModel\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/LaravelModel',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4ff88d3405934eea52753eac3b37918a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4ff88d3405934eea52753eac3b37918a::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
