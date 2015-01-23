<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

/**
 * Description of DoctrineProcess
 *
 * @version $id$
 * @author peter.ho
 */
final class CliProcessForDoctrine
{

    /**
     * Check all of the generated yaml files
     * and insert "repositoryClass" if missing.
     * 
     */
    public static function modifyYaml()
    {
        $files = glob(\Zms5Library\DoctrineExtension\PathHelper::getYamlRoot('*'));
        foreach ($files as $file) {
            $className = pathinfo(pathinfo($file, PATHINFO_FILENAME), PATHINFO_FILENAME);
            if (!strstr($content = file_get_contents($file), 'repositoryClass: ')) {
                $content .= "    repositoryClass: Repository\\" . $className . "Repository
";
                file_put_contents($file, $content);
            }
        }
    }

// <editor-fold defaultstate="collapsed" desc="Netbeans hintings">

    /**
     * Return a line of code of PHP doc &#40;@property-read
     * 
     * @param string $type
     * @param string $fieldName
     * @param string $desc
     * @return string
     */
    private static function propertyHints($type, $fieldName, $desc = '')
    {
        return PHP_EOL . " * @property-read {$type} \${$fieldName} {$desc}";
    }

    /**
     * Return a line of code of PHP doc &#40;method
     * 
     * @param string $type
     * @param string $fieldName
     * @param string $methodParam
     * @param string $desc
     * @return string
     */
    private static function methodHints($type, $fieldName, $methodParam, $desc = '')
    {
        return PHP_EOL . " * @method {$type} {$fieldName}({$methodParam}) {$desc}";
    }

    /**
     * 
     * @param string $netbeansHint Path of hint file
     */
    private static function removeOldFiles($netbeansHint)
    {
        $allOldFiles = glob('{'
            . "{$netbeansHint}Alias/*,"
            . "{$netbeansHint}Entities/*"
            . '}',
            GLOB_BRACE
        );
        $fileDeletedCount = 0;
        $fileSys = new \Symfony\Component\Filesystem\Filesystem;
        foreach ($allOldFiles as $file) {
            $fileSys->remove($file);
            $fileSys->remove(dirname($file));
            $fileDeletedCount ++;
        }
        echo "Deleted {$fileDeletedCount} old File(s).<br />";
    }

    /**
     * This function generate non-runable netbeans hinting php
     * <ol>
     * <li>Dummy Entities</li>
     * <li>Dummy Alias</li>
     * </ol>
     * @todo Exceptions
     */
    public static function generateHints()
    {
        $entityManager = \Zms5Library\DoctrineExtension\Connection::em();
        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $metadata = $cmf->getAllMetadata();
        $netbeansHint = \Zms5Library\Framework\SystemPath::netbeansHintPath();
        ! is_dir($netbeansHint) and mkdir($netbeansHint, 0777, true);
echo $netbeansHint, "\r\n\r\n";
        static::removeOldFiles($netbeansHint);

        $fileCount = 0;

        $contentEntities = '';
        $contentAlias = '';
        foreach ($metadata as $entity) {
            $fileCount ++;
            /* @var $entity \Doctrine\ORM\Mapping\ClassMetadata */
            $postedMethodHints = $aliasMethodHints = $propertyHints = '';
            foreach ($entity->associationMappings as $mapping) {
                $propertyHints .= static::propertyHints('integer', $mapping['fieldName']);
                $postedMethodHints .= static::propertyHints('string', $mapping['fieldName'], 'field name');
                $aliasMethodHints .= static::propertyHints('string', '' . $mapping['fieldName'], 'join field name [' . $mapping['fieldName'] . ']');
                $aliasMethodHints .= static::propertyHints('string', 'f' . ucfirst($mapping['fieldName']), 'join field name with alias');
                $aliasMethodHints .= static::methodHints('string', 'w' . ucfirst($mapping['fieldName']), 'string $condition,...', 'join condition in "where"');
            }
            foreach ($entity->fieldMappings as $fields) {
                $propertyHints .= static::propertyHints($fields['type'], $fields['fieldName']);
                $postedMethodHints .= static::propertyHints('string', $fields['fieldName'], 'field name');
                $aliasMethodHints .= static::propertyHints('string', '' . $fields['fieldName'], 'field name [' . $fields['fieldName'] . ']');
                $aliasMethodHints .= static::propertyHints('string', 'f' . ucfirst($fields['fieldName']), 'field name with alias');
                $aliasMethodHints .= static::methodHints('string', 'w' . ucfirst($fields['fieldName']), 'string $condition,...', 'condition in "where"');
            }

//            ! is_dir("{$netbeansHint}Entities") and mkdir("{$netbeansHint}Entities", 0777, true);
//            ! is_dir("{$netbeansHint}Alias") and mkdir("{$netbeansHint}Alias", 0777, true);

            $nsPhpCode = ($entity->namespace === '') ? '' : "namespace \{$entity->namespace} {";
            $nsPhpCodeClose = ($entity->namespace === '') ? '' : "}";
            $contentEntities .= static::getEntityPhp($entity, $nsPhpCode, $nsPhpCodeClose);
            $contentAlias .= static::getAliasPhp($entity, $aliasMethodHints);
        }

        file_put_contents("{$netbeansHint}GeneratedEntities.php", <<<CONTENT
<?php

/**
 * Auto-generated hinting for Netbeans
 */
die("This is a hinting helper for Netbeans ");

$contentEntities
CONTENT
        );
        file_put_contents("{$netbeansHint}GeneratedAlias.php", <<<CONTENT
<?php

/**
 * Auto-generated hinting for Netbeans
 */
die("This is a hinting helper for Netbeans ");
            
namespace NetbeanHintsAlias {
    {$contentAlias}
}
CONTENT
        );

        echo "Generated {$fileCount} pair of Entity and Alias.<br />\r\n\r\n";
    }

    private static function getAliasPhp($entity, $aliasMethodHints)
    {
        return <<<PHP
/**
 *{$aliasMethodHints}
 */
class {$entity->name}Alias extends Zms5\Common\DoctrineAlias { }

PHP;
    }

    private static function getEntityPhp($entity, $nsPhpCode, $nsPhpCodeClose)
    {
        return <<<PHP
{$nsPhpCode}class {$entity->name} {
    /** @return \Repository\\{$entity->name}Repository */
    public static function repo() { }
    /** @param string \$alias
     * @return \\NetbeanHintsAlias\\{$entity->name}Alias */
    public static function alias(\$alias = null) { }
}{$nsPhpCodeClose}

PHP;
    }
    
// </editor-fold>
}
