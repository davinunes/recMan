<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;

/**
 * This class is copied in every Composer installed project and available to all
 *
 * To require it's presence, you can require `composer-runtime-api ^2.0`
 */
class InstalledVersions
{
    private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-main',
    'version' => 'dev-main',
    'aliases' => 
    array (
    ),
    'reference' => '7ca748d62a84522d949f43a5195e528fe83c92e1',
    'name' => '__root__',
  ),
  'versions' => 
  array (
    '__root__' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
      ),
      'reference' => '7ca748d62a84522d949f43a5195e528fe83c92e1',
    ),
    'brick/math' => 
    array (
      'pretty_version' => '0.12.3',
      'version' => '0.12.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '866551da34e9a618e64a819ee1e01c20d8a588ba',
    ),
    'guzzlehttp/guzzle' => 
    array (
      'pretty_version' => '7.10.0',
      'version' => '7.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b51ac707cfa420b7bfd4e4d5e510ba8008e822b4',
    ),
    'guzzlehttp/promises' => 
    array (
      'pretty_version' => '2.3.0',
      'version' => '2.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '481557b130ef3790cf82b713667b43030dc9c957',
    ),
    'guzzlehttp/psr7' => 
    array (
      'pretty_version' => '2.8.0',
      'version' => '2.8.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '21dc724a0583619cd1652f673303492272778051',
    ),
    'minishlink/web-push' => 
    array (
      'pretty_version' => 'v7.0.0',
      'version' => '7.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8b023f2f5c6df846c0cfb87e13fb7ddf5e62c340',
    ),
    'paragonie/constant_time_encoding' => 
    array (
      'pretty_version' => 'v3.1.3',
      'version' => '3.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd5b01a39b3415c2cd581d3bd3a3575c1ebbd8e77',
    ),
    'paragonie/sodium_compat' => 
    array (
      'pretty_version' => 'v2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4714da6efdc782c06690bc72ce34fae7941c2d9f',
    ),
    'php-http/async-client-implementation' => 
    array (
      'provided' => 
      array (
        0 => '*',
      ),
    ),
    'php-http/client-implementation' => 
    array (
      'provided' => 
      array (
        0 => '*',
      ),
    ),
    'psr/cache' => 
    array (
      'pretty_version' => '3.0.0',
      'version' => '3.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'aa5030cfa5405eccfdcb1083ce040c2cb8d253bf',
    ),
    'psr/clock' => 
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e41a24703d4560fd0acb709162f73b8adfc3aa0d',
    ),
    'psr/container' => 
    array (
      'pretty_version' => '2.0.2',
      'version' => '2.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c71ecc56dfe541dbd90c5360474fbc405f8d5963',
    ),
    'psr/http-client' => 
    array (
      'pretty_version' => '1.0.3',
      'version' => '1.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bb5906edc1c324c9a05aa0873d40117941e5fa90',
    ),
    'psr/http-client-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-factory' => 
    array (
      'pretty_version' => '1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '2b4765fddfe3b508ac62f829e852b1501d3f6e8a',
    ),
    'psr/http-factory-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-message' => 
    array (
      'pretty_version' => '2.0',
      'version' => '2.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '402d35bcb92c70c026d1a6a9883f06b2ead23d71',
    ),
    'psr/http-message-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/log' => 
    array (
      'pretty_version' => '3.0.2',
      'version' => '3.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f16e1d5863e37f8d8c2a01719f5b34baa2b714d3',
    ),
    'psr/log-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0|3.0',
      ),
    ),
    'ralouphie/getallheaders' => 
    array (
      'pretty_version' => '3.0.3',
      'version' => '3.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '120b605dfeb996808c31b6477290a714d356e822',
    ),
    'spomky-labs/base64url' => 
    array (
      'pretty_version' => 'v2.0.4',
      'version' => '2.0.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '7752ce931ec285da4ed1f4c5aa27e45e097be61d',
    ),
    'spomky-labs/pki-framework' => 
    array (
      'pretty_version' => '1.4.1',
      'version' => '1.4.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f0e9a548df4e3942886adc9b7830581a46334631',
    ),
    'symfony/console' => 
    array (
      'pretty_version' => 'v7.4.7',
      'version' => '7.4.7.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e1e6770440fb9c9b0cf725f81d1361ad1835329d',
    ),
    'symfony/deprecation-contracts' => 
    array (
      'pretty_version' => 'v3.6.0',
      'version' => '3.6.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '63afe740e99a13ba87ec199bb07bbdee937a5b62',
    ),
    'symfony/http-client' => 
    array (
      'pretty_version' => 'v7.4.7',
      'version' => '7.4.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '1010624285470eb60e88ed10035102c75b4ea6af',
    ),
    'symfony/http-client-contracts' => 
    array (
      'pretty_version' => 'v3.6.0',
      'version' => '3.6.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '75d7043853a42837e68111812f4d964b01e5101c',
    ),
    'symfony/http-client-implementation' => 
    array (
      'provided' => 
      array (
        0 => '3.0',
      ),
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a3cc8b044a6ea513310cbd48ef7333b384945638',
    ),
    'symfony/polyfill-intl-grapheme' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '380872130d3a5dd3ace2f4010d95125fde5d5c70',
    ),
    'symfony/polyfill-intl-normalizer' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3833d7255cc303546435cb650316bff708a1c75c',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '6d857f4d76bd4b343eac26d6b539585d2bc56493',
    ),
    'symfony/polyfill-php83' => 
    array (
      'pretty_version' => 'v1.33.0',
      'version' => '1.33.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '17f6f9a6b1735c0f163024d959f700cfbc5155e5',
    ),
    'symfony/service-contracts' => 
    array (
      'pretty_version' => 'v3.6.1',
      'version' => '3.6.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '45112560a3ba2d715666a509a0bc9521d10b6c43',
    ),
    'symfony/string' => 
    array (
      'pretty_version' => 'v7.4.6',
      'version' => '7.4.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '9f209231affa85aa930a5e46e6eb03381424b30b',
    ),
    'thiagoalessio/tesseract_ocr' => 
    array (
      'pretty_version' => '2.13.0',
      'version' => '2.13.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '232a8cb9d571992f9bd1e263f2f6909cf6c173a1',
    ),
    'web-token/jwt-key-mgmt' => 
    array (
      'pretty_version' => '3.4.8',
      'version' => '3.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '4d2a5a1a86477dd50b89aff76962816ddbd64590',
    ),
    'web-token/jwt-library' => 
    array (
      'pretty_version' => '3.4.9',
      'version' => '3.4.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '8fe1650bf3a73673a9c520feff8f9a0e9cbbcd8f',
    ),
    'web-token/jwt-signature' => 
    array (
      'pretty_version' => '3.4.8',
      'version' => '3.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'eccfd59e658d4118414cf6d14229aa52eec387e7',
    ),
    'web-token/jwt-signature-algorithm-ecdsa' => 
    array (
      'pretty_version' => '3.4.8',
      'version' => '3.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '28516e170f6ee6d13766d9e2b912c2853e1ac5e4',
    ),
    'web-token/jwt-util-ecc' => 
    array (
      'pretty_version' => '3.4.8',
      'version' => '3.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '667934c5c6e37238f4e67d51aa3ba55abc703e1a',
    ),
  ),
);
    private static $canGetVendors;
    private static $installedByVendor = array();

    /**
     * Returns a list of all package names which are present, either by being installed, replaced or provided
     *
     * @return string[]
     * @psalm-return list<string>
     */
    public static function getInstalledPackages()
    {
        $packages = array();
        foreach (self::getInstalled() as $installed) {
            $packages[] = array_keys($installed['versions']);
        }


        if (1 === \count($packages)) {
            return $packages[0];
        }

        return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
    }

    /**
     * Checks whether the given package is installed
     *
     * This also returns true if the package name is provided or replaced by another package
     *
     * @param  string $packageName
     * @return bool
     */
    public static function isInstalled($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (isset($installed['versions'][$packageName])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether the given package satisfies a version constraint
     *
     * e.g. If you want to know whether version 2.3+ of package foo/bar is installed, you would call:
     *
     *   Composer\InstalledVersions::satisfies(new VersionParser, 'foo/bar', '^2.3')
     *
     * @param VersionParser $parser      Install composer/semver to have access to this class and functionality
     * @param string        $packageName
     * @param string|null   $constraint  A version constraint to check for, if you pass one you have to make sure composer/semver is required by your package
     *
     * @return bool
     */
    public static function satisfies(VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints($constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));

        return $provided->matches($constraint);
    }

    /**
     * Returns a version constraint representing all the range(s) which are installed for a given package
     *
     * It is easier to use this via isInstalled() with the $constraint argument if you need to check
     * whether a given version of a package is installed, and not just whether it exists
     *
     * @param  string $packageName
     * @return string Version constraint usable with composer/semver
     */
    public static function getVersionRanges($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            $ranges = array();
            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
            }
            if (array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
            }
            if (array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
            }
            if (array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
            }

            return implode(' || ', $ranges);
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null If the package is being replaced or provided but is not really installed, null will be returned as version, use satisfies or getVersionRanges if you need to know if a given version is present
     */
    public static function getVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null If the package is being replaced or provided but is not really installed, null will be returned as version, use satisfies or getVersionRanges if you need to know if a given version is present
     */
    public static function getPrettyVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['pretty_version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @param  string      $packageName
     * @return string|null If the package is being replaced or provided but is not really installed, null will be returned as reference
     */
    public static function getReference($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
            }

            return $installed['versions'][$packageName]['reference'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }

    /**
     * @return array
     * @psalm-return array{name: string, version: string, reference: string, pretty_version: string, aliases: string[]}
     */
    public static function getRootPackage()
    {
        $installed = self::getInstalled();

        return $installed[0]['root'];
    }

    /**
     * Returns the raw installed.php data for custom implementations
     *
     * @return array[]
     * @psalm-return array{root: array{name: string, version: string, reference: string, pretty_version: string, aliases: string[]}, versions: list<string, array{pretty_version: ?string, version: ?string, aliases: ?string[], reference: ?string, replaced: ?string[], provided: ?string[]}>}
     */
    public static function getRawData()
    {
        return self::$installed;
    }

    /**
     * Lets you reload the static array from another file
     *
     * This is only useful for complex integrations in which a project needs to use
     * this class but then also needs to execute another project's autoloader in process,
     * and wants to ensure both projects have access to their version of installed.php.
     *
     * A typical case would be PHPUnit, where it would need to make sure it reads all
     * the data it needs from this class, then call reload() with
     * `require $CWD/vendor/composer/installed.php` (or similar) as input to make sure
     * the project in which it runs can then also use this class safely, without
     * interference between PHPUnit's dependencies and the project's dependencies.
     *
     * @param  array[] $data A vendor/composer/installed.php data set
     * @return void
     *
     * @psalm-param array{root: array{name: string, version: string, reference: string, pretty_version: string, aliases: string[]}, versions: list<string, array{pretty_version: ?string, version: ?string, aliases: ?string[], reference: ?string, replaced: ?string[], provided: ?string[]}>} $data
     */
    public static function reload($data)
    {
        self::$installed = $data;
        self::$installedByVendor = array();
    }

    /**
     * @return array[]
     */
    private static function getInstalled()
    {
        if (null === self::$canGetVendors) {
            self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
        }

        $installed = array();

        if (self::$canGetVendors) {
            // @phpstan-ignore-next-line
            foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
                if (isset(self::$installedByVendor[$vendorDir])) {
                    $installed[] = self::$installedByVendor[$vendorDir];
                } elseif (is_file($vendorDir.'/composer/installed.php')) {
                    $installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
                }
            }
        }

        $installed[] = self::$installed;

        return $installed;
    }
}
