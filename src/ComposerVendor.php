<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

use Phar;

/**
 * Description of ComposerVendor
 *
 * @version $id$
 * @author peter.ho
 */
final class ComposerVendor
{

    /**
     * Perform a cleanup on all kinds of PHAR file extension with name $fileBaseName
     * 
     * The file extensions:
     * <ul>
     * <li>phar</li>
     * <li>zip</li>
     * <li>phar.gz</li>
     * </ul>
     * @param string $fileBaseName
     */
    private static function pharCleanUp($fileBaseName)
    {
        is_file($fileBaseName . '.phar') and unlink($fileBaseName . '.phar');
        is_file($fileBaseName . '.zip') and unlink($fileBaseName . '.zip');
        is_file($fileBaseName . '.phar.gz') and unlink($fileBaseName . '.phar.gz');
    }

    private static function pharStart($fileBaseName)
    {
        static::pharCleanUp($fileBaseName);
        ini_set("phar.readonly", 0); 
        $phar = new Phar($fileBaseName . '.phar');
        $phar->startBuffering();
        $phar->setSignatureAlgorithm(Phar::SHA256);
        $phar->setStub('<?php __HALT_COMPILER(); ?>');
        return $phar;
    }
    private static function pharEnd(Phar $phar, $buildGz)
    {
        $phar->compress($buildGz ? Phar::GZ : Phar::NONE);
        $phar->convertToData(Phar::ZIP);
        $phar->stopBuffering();
        ini_set("phar.readonly", 1);
        return $phar;
    }

    /**
     * Build Phar
     * @param boolean $buildGz
     */
    public static function buildPharWithWorkbench($buildGz = false)
    {
        $phar = static::pharStart('zmsVendorWorkbench');
        $phar->buildFromDirectory(APP_ROOT, '/\/wcms\/(vendor|workbench)+\//');
        static::pharEnd($phar, $buildGz);

        echo "Built zmsVendorWorkbench.phar success.\r\n";
    }

    /**
     * Build Phar with ONLY [vendor]
     * @deprecated since version 1
     * @param boolean $buildGz
     */
    public static function buildPharOnlyVendor($buildGz = false)
    {
        $phar = static::pharStart('vendor');
        $phar->buildFromDirectory(APP_WCMS_ROOT . '/vendor');
        static::pharEnd($phar, $buildGz);

        echo "Built Vendor phar + phar.gz success.\r\n";
    }

    /**
     * <li>Remove non-php item
     * <li>Stripe whitespace in PHP
     */
    public static function backup()
    {
        $finder = new \Symfony\Component\Finder\Finder;
        $fileSys = new \Symfony\Component\Filesystem\Filesystem;
        $base = APP_WCMS_ROOT . 'vendor/';
        $dest = APP_SYSTEM_STORAGE . 'vendor-backup/';
// <editor-fold defaultstate="collapsed" desc="Composer vendor backup">

        $finderIter = $finder->in($base)
            ->files()
            ->filter(function(\Symfony\Component\Finder\SplFileInfo $fileInfo){
                if (stristr($fileInfo->getPathname(), 'composer')) {
                    return false;
                } elseif (strtolower($fileInfo->getExtension()) === 'pem') {
                    return false;
                } elseif (stristr($fileInfo->getPathname(), 'twig/test')) {
                    return true;
                } elseif (stristr($fileInfo->getPathname(), 'twig\\test')) {
                    return true;
                } elseif (stristr($fileInfo->getPathname(), 'twig')) {
                    return false;
                } elseif (stristr($fileInfo->getPathname(), '\\test')) {
                    return true;
                } elseif (stristr($fileInfo->getPathname(), '/test')) {
                    return true;
                } elseif (strtolower($fileInfo->getExtension()) !== 'php') {
                    return true;
                }
                return false;
            })
            ->getIterator();
        $counter = 0;
        foreach ($finderIter as $filePath => $iter) {
            /* @var $iter \Symfony\Component\Finder\SplFileInfo */
            $relative = substr($fileSys->makePathRelative($filePath, $base), 0, -1);
            $fileSys->mkdir(dirname($dest . $relative), 0777);
            $fileSys->copy($filePath, $dest . $relative);
            unlink($filePath);
            $counter ++;
        }

// </editor-fold>
        echo "Vendor Backuped files: {$counter}\r\n";
    }

    /**
     * Minify the php in [vendor]
     */
    public static function minify()
    {
        $finder = new \Symfony\Component\Finder\Finder;
        $fileSys = new \Symfony\Component\Filesystem\Filesystem;
        $base = APP_WCMS_ROOT . 'vendor/';
        $dest = APP_SYSTEM_STORAGE . 'vendor-backup/';

        $phpIter = $finder->in($base)->files()->name('*.php')->getIterator();
        $counter = 0;
        foreach ($phpIter as $filePath => $iter) {
            /* @var $iter \Symfony\Component\Finder\SplFileInfo */
            $relative = substr($fileSys->makePathRelative($filePath, $base), 0, -1);
            $fileSys->mkdir(dirname($dest . $relative), 0777);
            $fileSys->copy($filePath, $dest . $relative);
            file_put_contents($filePath, php_strip_whitespace($filePath));
            $counter ++;
        }
        echo "Vendor striped php: {$counter}\r\n";
    }

    /**
     * Restore from static::backup()
     */
    public static function restore()
    {        
        $finder = new \Symfony\Component\Finder\Finder;
        $fileSys = new \Symfony\Component\Filesystem\Filesystem;
        $dest = APP_WCMS_ROOT . 'vendor/';
        $base = APP_SYSTEM_STORAGE . 'vendor-backup/';
        if (! is_dir($base)) {
            echo "vendor backup folder is not exists";
            exit;
        }

        $finderIter = $finder->in($base)
            ->files()
            ->getIterator();
        $counter = 0;
        foreach ($finderIter as $filePath => $iter) {
            /* @var $iter \Symfony\Component\Finder\SplFileInfo */
            $relative = substr($fileSys->makePathRelative($filePath, $base), 0, -1);
            $fileSys->mkdir(dirname($dest . $relative), 0777);
            is_file($dest . $relative) and unlink($dest . $relative);
            $fileSys->copy($filePath, $dest . $relative);
            unlink($filePath);
            $counter ++;
        }

        echo "Vendor Restored files: {$counter}\r\n";
    }

}
