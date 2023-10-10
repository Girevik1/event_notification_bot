<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8dc7adc929e9d6ae55ba59aecc1701b3
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Art\\Code\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Art\\Code\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8dc7adc929e9d6ae55ba59aecc1701b3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8dc7adc929e9d6ae55ba59aecc1701b3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8dc7adc929e9d6ae55ba59aecc1701b3::$classMap;

        }, null, ClassLoader::class);
    }
}
