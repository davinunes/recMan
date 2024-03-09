<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1edaef195d686c33f394eef30b1729d2
{
    public static $prefixLengthsPsr4 = array (
        't' => 
        array (
            'thiagoalessio\\TesseractOCR\\' => 27,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'thiagoalessio\\TesseractOCR\\' => 
        array (
            0 => __DIR__ . '/..' . '/thiagoalessio/tesseract_ocr/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1edaef195d686c33f394eef30b1729d2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1edaef195d686c33f394eef30b1729d2::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1edaef195d686c33f394eef30b1729d2::$classMap;

        }, null, ClassLoader::class);
    }
}