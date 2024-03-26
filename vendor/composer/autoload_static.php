<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2d59d844a933cf6c317c96c732b75e9d
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        'e69f7f6ee287b969198c3c9d6777bd38' => __DIR__ . '/..' . '/symfony/polyfill-intl-normalizer/bootstrap.php',
        '25072dd6e2470089de65ae7bf11d3109' => __DIR__ . '/..' . '/symfony/polyfill-php72/bootstrap.php',
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        'f598d06aa772fa33d905e87be6398fb1' => __DIR__ . '/..' . '/symfony/polyfill-intl-idn/bootstrap.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        'a9b805bf529b5a997093b3cddca2af6f' => __DIR__ . '/..' . '/gopay/payments-sdk-php/factory.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php72\\' => 23,
            'Symfony\\Polyfill\\Intl\\Normalizer\\' => 33,
            'Symfony\\Polyfill\\Intl\\Idn\\' => 26,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
            'GoPay\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Php72\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php72',
        ),
        'Symfony\\Polyfill\\Intl\\Normalizer\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-intl-normalizer',
        ),
        'Symfony\\Polyfill\\Intl\\Idn\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-intl-idn',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'GoPay\\' => 
        array (
            0 => __DIR__ . '/..' . '/gopay/payments-sdk-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'GoPay\\Auth' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Auth.php',
        'GoPay\\Definition\\Account\\StatementGeneratingFormat' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Account/StatementGeneratingFormat.php',
        'GoPay\\Definition\\Language' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Language.php',
        'GoPay\\Definition\\Payment\\BankSwiftCode' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/BankSwiftCode.php',
        'GoPay\\Definition\\Payment\\Currency' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/Currency.php',
        'GoPay\\Definition\\Payment\\PaymentInstrument' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/PaymentInstrument.php',
        'GoPay\\Definition\\Payment\\PaymentItemType' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/PaymentItemType.php',
        'GoPay\\Definition\\Payment\\Recurrence' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/Recurrence.php',
        'GoPay\\Definition\\Payment\\VatRate' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Payment/VatRate.php',
        'GoPay\\Definition\\RequestMethods' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/RequestMethods.php',
        'GoPay\\Definition\\Response\\PaymentStatus' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PaymentStatus.php',
        'GoPay\\Definition\\Response\\PaymentSubStatus' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PaymentSubStatus.php',
        'GoPay\\Definition\\Response\\PreAuthState' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/PreAuthState.php',
        'GoPay\\Definition\\Response\\RecurrenceState' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/RecurrenceState.php',
        'GoPay\\Definition\\Response\\Result' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/Response/Result.php',
        'GoPay\\Definition\\TokenScope' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Definition/TokenScope.php',
        'GoPay\\GoPay' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/GoPay.php',
        'GoPay\\Http\\JsonBrowser' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/JsonBrowser.php',
        'GoPay\\Http\\Log\\Logger' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/Logger.php',
        'GoPay\\Http\\Log\\NullLogger' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/NullLogger.php',
        'GoPay\\Http\\Log\\PrintHttpRequest' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Log/PrintHttpRequest.php',
        'GoPay\\Http\\Request' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Request.php',
        'GoPay\\Http\\Response' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Http/Response.php',
        'GoPay\\OAuth2' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/OAuth2.php',
        'GoPay\\Payments' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Payments.php',
        'GoPay\\PaymentsSupercash' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/PaymentsSupercash.php',
        'GoPay\\Token\\AccessToken' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/AccessToken.php',
        'GoPay\\Token\\CachedOAuth' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/CachedOAuth.php',
        'GoPay\\Token\\InMemoryTokenCache' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/InMemoryTokenCache.php',
        'GoPay\\Token\\TokenCache' => __DIR__ . '/..' . '/gopay/payments-sdk-php/src/Token/TokenCache.php',
        'Normalizer' => __DIR__ . '/..' . '/symfony/polyfill-intl-normalizer/Resources/stubs/Normalizer.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2d59d844a933cf6c317c96c732b75e9d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2d59d844a933cf6c317c96c732b75e9d::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2d59d844a933cf6c317c96c732b75e9d::$classMap;

        }, null, ClassLoader::class);
    }
}
