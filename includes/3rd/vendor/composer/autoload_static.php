<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit068733458311589d7fe3e8dd836c2a7d
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit068733458311589d7fe3e8dd836c2a7d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit068733458311589d7fe3e8dd836c2a7d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}