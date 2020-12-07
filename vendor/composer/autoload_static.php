<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1093dc6c00024cc6929e730e3042302f
{
    public static $files = array (
        '4654c4e7e9271889645664f6ad66dca9' => __DIR__ . '/../..' . '/src/Helper/Base.php',
    );

    public static $prefixLengthsPsr4 = array (
        't' => 
        array (
            'test\\' => 5,
        ),
        'W' => 
        array (
            'Whoops\\' => 7,
        ),
        'S' => 
        array (
            'Seld\\CliPrompt\\' => 15,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
        'L' => 
        array (
            'League\\CLImate\\' => 15,
        ),
        'G' => 
        array (
            'GFPHP\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'test\\' => 
        array (
            0 => __DIR__ . '/../..' . '/test',
        ),
        'Whoops\\' => 
        array (
            0 => __DIR__ . '/..' . '/filp/whoops/src/Whoops',
        ),
        'Seld\\CliPrompt\\' => 
        array (
            0 => __DIR__ . '/..' . '/seld/cli-prompt/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
        'League\\CLImate\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/climate/src',
        ),
        'GFPHP\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'D' => 
        array (
            'Dflydev\\ApacheMimeTypes' => 
            array (
                0 => __DIR__ . '/..' . '/dflydev/apache-mime-types/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1093dc6c00024cc6929e730e3042302f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1093dc6c00024cc6929e730e3042302f::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit1093dc6c00024cc6929e730e3042302f::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
