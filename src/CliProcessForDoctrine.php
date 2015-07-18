<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Hopeter1018\DeveloperTool;

use Hopeter1018\DoctrineExtension\PathHelper;
use Hopeter1018\Framework\SystemPath;
use Hopeter1018\DoctrineExtension\Connection;

/**
 * Description of DoctrineProcess
 *
 * @version $id$
 * @author peter.ho
 */
final class CliProcessForDoctrine
{

    /**
     * Search all manyToOne relations from YAML
     * 
     * @param type $filesByName
     * @return array|array[]
     */
    private static function preProcessYaml($filesByName)
    {
        $toAddOneToMany = array();
        foreach ($filesByName as $tblName => $file) {
            $yaml = \Symfony\Component\Yaml\Yaml::parse($file);
            if (isset($yaml[$tblName]['manyToOne'])) {
                foreach ($yaml[$tblName]['manyToOne'] as $manyToOne) {
                    if (! isset($toAddOneToMany[ $manyToOne['targetEntity'] ])) {
                        $toAddOneToMany[ $manyToOne['targetEntity'] ] = array();
                    }

                    $value = $key = null;
                    extract(each($manyToOne['joinColumns']));
                    $toAddOneToMany[ $manyToOne['targetEntity'] ][ "" . lcfirst(substr($tblName, 0)) ] = array(
                        "targetEntity" => $tblName,
                        "mappedBy" => lcfirst(substr($manyToOne['targetEntity'], 0)),
                        "joinColumns" => array(
                            $value['referencedColumnName'] => array(
                                "referencedColumnName" => $key,
                            ),
                        ),
                    );
                }
            }
        }

        return $toAddOneToMany;
    }

    /**
     * Get all Files indexed by Table name
     * @return array|string[]
     */
    private static function getFilesByTblName()
    {
        $files = glob(PathHelper::getYamlRoot('*'));
        $filesByTblName = array();
        array_walk($files, function($val) use(&$filesByTblName){
            $filesByTblName[ substr(pathinfo($val, PATHINFO_FILENAME), 0, -4) ] = $val;
        });
        return $filesByTblName;
    }

    private static function hasField($yamlTable, $fieldName)
    {
        return isset($yamlTable['fields'][ $fieldName . "Id" ])
            or isset($yamlTable['fields'][ $fieldName ])
        ;
    }

    private static function hasRelation($yamlTable, $fieldName)
    {
        return isset($yamlTable['manyToOne'][ $fieldName ])
            or isset($yamlTable['oneToMany'][ $fieldName ]);
    }

    private static function injectNamedQueries(&$yamlTable)
    {
        $yamlTable['namedQueries'] = array();
//        if (static::hasField($yamlTable, 'content') and static::hasField($yamlTable, 'charset')) {
//            $yamlTable['namedQueries'][ "getByContentCharset" ] = "SELECT t FROM __CLASS__ t WHERE t.contentId=:contentId AND t.charsetId=:charsetId";
//        } elseif (static::hasRelation($yamlTable, 'content') and static::hasRelation($yamlTable, 'charset')) {
//            $yamlTable['namedQueries'][ "getByContentCharset" ] = "SELECT t FROM __CLASS__ t WHERE t.content=:contentId AND t.charset=:charsetId";
//        }
//        if (static::hasField($yamlTable, 'content') and static::hasField($yamlTable, 'charset') and static::hasField($yamlTable, 'param')) {
//            $yamlTable['namedQueries'][ "getByContentCharsetParam" ] = "SELECT t FROM __CLASS__ t WHERE t.contentId=:contentId AND t.charsetId=:charsetId AND t.param=:param";
//        } elseif (static::hasRelation($yamlTable, 'content') and static::hasRelation($yamlTable, 'charset') and static::hasField($yamlTable, 'param')) {
//            $yamlTable['namedQueries'][ "getByContentCharsetParam" ] = "SELECT t FROM __CLASS__ t WHERE t.content=:contentId AND t.charset=:charsetId AND t.param=:param";
//        }
    }

    /**
     * Check all of the generated yaml files
     * and insert "repositoryClass" if missing.
     * 
     */
    public static function modifyYaml()
    {

        $filesByName = static::getFilesByTblName();
        $toAddOneToMany = static::preProcessYaml($filesByName);

        foreach ($filesByName as $tblName => $file) {



            $yaml = \Symfony\Component\Yaml\Yaml::parse($file);
            if (isset($toAddOneToMany[$tblName])) { $yaml[$tblName]['oneToMany'] = $toAddOneToMany[$tblName]; }
            if (! isset($yaml[$tblName]['repositoryClass'])) { $yaml[$tblName]['repositoryClass'] = "Repository\\" . $tblName . "Repository"; }
            static::injectNamedQueries($yaml[$tblName]);
            file_put_contents($file, \Symfony\Component\Yaml\Yaml::dump($yaml, 10));

        }
//exit;
    }



// <editor-fold defaultstate="collapsed" desc="Hinting Format">
    
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

// </editor-fold>
    
// <editor-fold defaultstate="collapsed" desc="Netbeans hintings">

    /**
     * 
     * @param string $netbeansHint Path of hint file
     */
    private static function removeOldFiles($netbeansHint)
    {
        $allOldFiles = glob('{' . "{$netbeansHint}Alias/*," . "{$netbeansHint}Entities/*" . '}', GLOB_BRACE);





        $fileDeletedCount = 0;
        $fileSys = new \Symfony\Component\Filesystem\Filesystem;
        foreach ($allOldFiles as $file) {
            if (basename($file) !== "README") {
                $fileSys->remove($file);
                $fileSys->remove(dirname($file));
                $fileDeletedCount ++;
            }
        }
        echo "Deleted {$fileDeletedCount} old File(s).<br />";
    }

    /**
     * Get the corresponding type for @return 
     * 
     * @staticvar array $typeMapping
     * @param type $fieldMapping
     * @return string
     */
    public static function getFieldMappingType($fieldMapping)
    {
        static $typeMapping = array(
            "integer" => "int",
            "datetime" => "\\DateTime",
            "string" => "string",
            "boolean" => "boolean",
            "text" => "string",
        );

        if (! isset($typeMapping[$fieldMapping['type']])) {
            var_dump("no Mapping for " . $fieldMapping['type']);
        }
        return $typeMapping[$fieldMapping['type']];
    }

    /**
     * 
     * @param \Doctrine\ORM\Mapping\ClassMetadata $entity
     * @param type $namedQueriesHints
     * @param type $postedMethodHints
     * @param type $aliasMethodHints
     * @param type $repoMethodHints
     */
    private static function getAssociationHints(\Doctrine\ORM\Mapping\ClassMetadata &$entity, &$namedQueriesHints, &$postedMethodHints, &$aliasMethodHints, &$repoMethodHints)
    {
        foreach ($entity->associationMappings as $mapping) {
            $repoMethodHints .= static::methodHints('array|\\' . $entity->name . '[]', 'findBy' . ucfirst($mapping['fieldName']), "int|mixed \${$mapping['fieldName']}");
            $repoMethodHints .= static::methodHints('\\' . $entity->name . '', 'findOneBy' . ucfirst($mapping['fieldName']), "int|mixed \${$mapping['fieldName']}");

            $postedMethodHints .= static::propertyHints('string', $mapping['fieldName'], 'field name');
            $aliasMethodHints .= static::propertyHints('string', '' . $mapping['fieldName'], 'join field name [' . $mapping['fieldName'] . ']');
            $aliasMethodHints .= static::propertyHints('string', 'f' . ucfirst($mapping['fieldName']), 'join field name with alias');
            $aliasMethodHints .= static::methodHints('string', 'w' . ucfirst($mapping['fieldName']), 'string $condition,...', 'join condition in "where"');
        }
    }

    private static function getEntityHints(&$entity, &$namedQueriesHints, &$postedMethodHints, &$aliasMethodHints, &$repoMethodHints)
    {
        foreach ($entity->fieldMappings as $fields) {
            $repoMethodHints .= static::methodHints('array|\\' . $entity->name . '[]', 'findBy' . ucfirst($fields['fieldName']), CliProcessForDoctrine::getFieldMappingType($fields) . ' $' . $fields['fieldName']);
            $repoMethodHints .= static::methodHints('\\' . $entity->name . '', 'findOneBy' . ucfirst($fields['fieldName']), CliProcessForDoctrine::getFieldMappingType($fields) . ' $' . $fields['fieldName']);

            $postedMethodHints .= static::propertyHints('string', $fields['fieldName'], 'field name');
            $aliasMethodHints .= static::propertyHints('string', '' . $fields['fieldName'], 'field name [' . $fields['fieldName'] . ']');
            $aliasMethodHints .= static::propertyHints('string', 'f' . ucfirst($fields['fieldName']), 'field name with alias');
            $aliasMethodHints .= static::methodHints('string', 'w' . ucfirst($fields['fieldName']), 'string $condition,...', 'condition in "where"');
        }
    }

    private static function getNamedQueryHints(&$entity, &$namedQueriesHints, &$postedMethodHints, &$aliasMethodHints, &$repoMethodHints)
    {
        foreach ($entity->namedQueries as $name => $nameQuery) {
            $parameters = \Hopeter1018\DoctrineExtension\DqlHelper::getBindingParameter($nameQuery['dql']);
            array_walk($parameters, function(&$val) use ($entity) {
                $type = "mixed";
                $val = substr($val, 1);

                if (isset($entity->fieldMappings[$val])) {
                    $type = CliProcessForDoctrine::getFieldMappingType($entity->fieldMappings[$val]);
                } elseif (isset($entity->associationMappings[substr($val, 0, -2)])) {
                    $type = 'int';
                }
                $val = "{$type} $" . $val;
            });
            $aliasMethodHints .= static::methodHints('\Doctrine\ORM\Query', 'nq' . ucfirst($name), implode(", ", $parameters));
        }
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
        $entityManager = Connection::em();
        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($entityManager);
        $metadata = $cmf->getAllMetadata();
        $netbeansHint = SystemPath::netbeansHintPath();
        ! is_dir($netbeansHint) and mkdir($netbeansHint, 0777, true);

        static::removeOldFiles($netbeansHint);

        $fileCount = 0;

//        $contentEntities = '';
        $contentAlias = '';
        foreach ($metadata as $entity) {
            $fileCount ++;
            /* @var $entity \Doctrine\ORM\Mapping\ClassMetadata */
            $namedQueriesHints = $postedMethodHints = $aliasMethodHints = $repoMethodHints = '';














            static::getAssociationHints($entity, $namedQueriesHints, $postedMethodHints, $aliasMethodHints, $repoMethodHints);
            static::getEntityHints($entity, $namedQueriesHints, $postedMethodHints, $aliasMethodHints, $repoMethodHints);
            static::getNamedQueryHints($entity, $namedQueriesHints, $postedMethodHints, $aliasMethodHints, $repoMethodHints);

//            var_dump($entity->fieldMappings, $entity->associationMappings, $entity->getTypeOfField($propertyHints));
//            ! is_dir("{$netbeansHint}Entities") and mkdir("{$netbeansHint}Entities", 0777, true);
//            ! is_dir("{$netbeansHint}Alias") and mkdir("{$netbeansHint}Alias", 0777, true);

//            $nsPhpCode = ($entity->namespace === '') ? '' : "namespace \{$entity->namespace} {";
//            $nsPhpCodeClose = ($entity->namespace === '') ? '' : "}";
//            $contentEntities .= static::getEntityPhp($entity, $nsPhpCode, $nsPhpCodeClose);
            $contentRepo .= static::getRepoPhp($entity, $repoMethodHints);
            $contentAlias .= static::getAliasPhp($entity, $aliasMethodHints);

            $entityPath = substr(SystemPath::doctrineFilesPath("Entities/{$entity->getName()}.php"), 0, -1);
            $entityContent = file_get_contents($entityPath);
            $entityContentNew = substr_replace(
                $entityContent,
                static::getEntityStaticMethods($entity) . "\r\n}",
                strrpos($entityContent, "}"),
                strlen("}")
            );
            file_put_contents($entityPath, $entityContentNew);
            
            echo $entityPath, "\r\n\r\n";
        }

        file_put_contents("{$netbeansHint}GeneratedRepositories.php", <<<CONTENT
<?php



/**
 * Auto-generated hinting for Netbeans
 */
die("This is a hinting helper for Netbeans ");



namespace Repository {
    {$contentRepo}
}
CONTENT

        );
        file_put_contents("{$netbeansHint}GeneratedAlias.php", <<<CONTENT
<?php

/**
 * Auto-generated hinting for Netbeans
 */
die("This is a hinting helper for Netbeans ");


namespace NbHintsAlias {
    {$contentAlias}
}
CONTENT
        );

        echo "Generated {$fileCount} pair of Entity and Alias.<br />\r\n\r\n";
    }

    private static function getRepoPhp($entity, $repoMethodHints)
    {
        return <<<PHP
/**
 *{$repoMethodHints}
 */
class {$entity->name}Repository { }

PHP;
    }

    private static function getAliasPhp($entity, $aliasMethodHints)
    {
        return <<<PHP
/**
 *{$aliasMethodHints}
 */
class {$entity->name}Alias extends Hopeter1018\DoctrineExtension\Alias { }

PHP;
    }

    private static function getEntityPhp($entity, $nsPhpCode, $nsPhpCodeClose)
    {
        return <<<PHP
{$nsPhpCode}class {$entity->name} {
    /** @return \Repository\\{$entity->name}Repository */
    public static function repo() { }
    /** @param string \$alias
     * @return \\NbHintsAlias\\{$entity->name}Alias */
    public static function alias(\$alias = null) { }
}{$nsPhpCodeClose}

PHP;
    }

    private static function getEntityStaticMethods($entity)
    {
        return <<<PHP
    /**
     *
     * @return \Repository\\{$entity->name}Repository
     */
    public static function repo() {
        return parent::repo();
    }

    /**
     *
     * @param string \$alias
     * @return \\NbHintsAlias\\{$entity->name}Alias
     */
    public static function alias(\$alias = null) {
        return parent::alias(\$alias);
    }

PHP;
    }

// </editor-fold>

}
