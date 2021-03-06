<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0913c6dbe5d1e74fb66a2b65ee894f3c
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Mo\\Accept\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Mo\\Accept\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInit0913c6dbe5d1e74fb66a2b65ee894f3c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0913c6dbe5d1e74fb66a2b65ee894f3c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0913c6dbe5d1e74fb66a2b65ee894f3c::$classMap;

        }, null, ClassLoader::class);
    }
}
