<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

/**
 * Description of CliProcess
 *
 * <h3>Prefix:</h3>
 * <ul>
 * <li>d- = doctrine</li>
 * <li>v- = vendor</li>
 * </ul>
 * 
 * zms-install
 * d-cleanup
 * d-yaml
 * d-alias
 * v-backup
 * v-restore
 * v-gen
 * @version $id$
 * @author peter.ho
 */
final class CliProcess
{

    private static $commandList = array(
        1 => "Gen doctrine",
        2 => "Gen Phar",
        3 => "composer update --dev",
        4 => "bower update",
        5 => "composer & bower update",
    );

    private static function execAndPrint($cmd)
    {
        $output = array();
        exec($cmd, $output);
        echo implode("\r\n", $output), "\r\n\r\n";
    }

    public static function start($init = true)
    {
        if (! APP_IS_DEV or PHP_SAPI !== 'cli' or $GLOBALS['argv'][0] !== 'bootstrap.php') {
            return;
        }

        $number = 0;
        if ($init and count($GLOBALS['argv']) === 2 and is_numeric($GLOBALS['argv'][1])) {
            $number = $GLOBALS['argv'][1];
        } else {
            echo <<<REGISTER
==============================
=
= Welcome to ZMS 5 cli
=
==============================
 1.) Gen doctrine
 2.) Gen Phar
 3.) composer update --no-dev
 4.) bower update
 5.) composer & bower update
 6.) doctrine re-generate hints
 7.) composer dump-autoload --optimize

 9.) ZMS install (todo)
 0.) Exit

REGISTER;
            fscanf(STDIN, "%d\n", $number); // reads number from STDIN
        }

        echo "Selected {$number}\r\n";
        $continue = true;
        switch ($number)
        {
            case 1:
                $doctrineExe = 'CALL "wcms/vendor/bin/doctrine.php.bat" ';
                $genBase = APP_WCMS_FOLDER . APP_WORKBENCH_FOLDER. \Zms5Library\Framework\SystemPath::WB_APP_GEN_DOCTRINE;

                echo "Before cleanup\r\n";
                static::doctrineCleanup();
                echo "Before yaml\r\n";
//                echo "\r\n{$genBase}\r\n\r\n";
                static::execAndPrint("{$doctrineExe}orm:convert-mapping --force --from-database yaml {$genBase}yaml/");
                echo "Before modify yaml\r\n";
                static::doctrineModifyYaml();
                static::execAndPrint("{$doctrineExe}orm:generate-entities --extend=\"Zms5Library\DoctrineExtension\BaseEntity\""
                    . " --generate-annotations=true --generate-methods=true --regenerate-entities=true"
                    . " {$genBase}Entities/");
                static::execAndPrint("composer dumpautoload");
                static::execAndPrint("{$doctrineExe}orm:generate-proxies {$genBase}Proxies/");
                static::execAndPrint("{$doctrineExe}orm:generate-repositories {$genBase}Repositories/");
                static::doctrineGenerateHints();

                break;
            case 2:
                ignore_user_abort();
                static::vendorBackupAndMinify();
                static::generatePhar();
                static::vendorRestore();
                break;
            case 3;
                static::execAndPrint("composer update --no-dev");
                break;
            case 4;
                static::execAndPrint("bower update");
                break;
            case 5:
                static::execAndPrint("composer update --no-dev");
                static::execAndPrint("bower update");
                break;
            case 6:
                static::doctrineGenerateHints();
                break;
            case 7:
                static::execAndPrint("composer dump-autoload --optimize");
                break;
            default:
                echo "\r\nBye\r\n";
                $continue = false;
                break;
        }

        if ($continue) {
            echo "\r\n\r\n\r\n";
            static::start(false);
        }
    }

    /**
     * Initial DB, Make folder writable
     * 
     * @todo
     * @return boolean
     */
    public static function zmsInstall()
    {
        
    }

// <editor-fold defaultstate="__collapsed" desc="Doctrine-related">

    /**
     * Cleanup the doctrine-generated folder for next generation
     * 
     * @see \Zms5Library\DoctrineExtension\PathHelper::cleanUp
     * @return boolean
     */
    public static function doctrineCleanup()
    {
        \Zms5Library\DoctrineExtension\PathHelper::cleanUp();
    }

    /**
     * Modify the yaml generated from DB.
     * 
     * @see CliProcessForDoctrine::modifyYaml
     * @return boolean
     */
    public static function doctrineModifyYaml()
    {
        CliProcessForDoctrine::modifyYaml();
    }

    /**
     * Generate customized netbeans hint.
     * 
     * @see CliProcessForDoctrine::generateHints
     * @return boolean
     */
    public static function doctrineGenerateHints()
    {
        CliProcessForDoctrine::generateHints();
    }

// </editor-fold>
// <editor-fold defaultstate="__collapsed" desc="Vendor-related">

    /**
     * Backup and minify the [vendor] folder
     * 
     * @todo Exception handling
     * @see ComposerVendor::backup
     * @see ComposerVendor::minify
     * @return boolean
     */
    public static function vendorBackupAndMinify()
    {
        ComposerVendor::backup();
        ComposerVendor::minify();
    }

    /**
     * Restore the [vendor] folder
     * 
     * @todo Exception handling
     * @see ComposerVendor::restore
     * @return boolean
     */
    public static function vendorRestore()
    {
        return ComposerVendor::restore();
    }

// </editor-fold>
// <editor-fold defaultstate="__collapsed" desc="Phar-related">

    /**
     * Generate a single phar for whole project
     * 
     * @todo Exception handling
     * @see CliProcess::vendorBackupAndMinify
     * @see ComposerVendor::buildPharWithWorkbench
     * @see CliProcess::vendorRestore
     * @return boolean
     */
    public static function generatePhar()
    {
        static::vendorBackupAndMinify();
        ComposerVendor::buildPharWithWorkbench();
        static::vendorRestore();
        return true;
    }

// </editor-fold>

}
