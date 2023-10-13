<?php

namespace YesWiki\Alternativeupdatej9rem\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use Exception;
use YesWiki\Bazar\Service\FieldFactory as BazarFieldFactory;
use YesWiki\Wiki;


/**
 * needed to force rewrite field on bazarfield
 * Feature UUID : auj9-custom-changes
 */

class FieldFactory extends BazarFieldFactory
{
    private const CACHE_PATH = 'cache/';

    public function __construct(Wiki $wiki)
    {
        $this->wiki = $wiki;
        $this->checkCacheFolderExistence();
        $this->loadAvailableField();
    }

    private function checkCacheFolderExistence()
    {
        try {
            if (!file_exists(self::CACHE_PATH) || !is_dir(self::CACHE_PATH)) {
                throw new Exception("ERROR ! : Folder `cache/` not existing in the root folder on the website host ! Can you create it ? ");
            }
            
            if (!is_writable(self::CACHE_PATH)) {
                throw new Exception("ERROR ! : Folder `cache/` is not writable ! Can you give it write acces by ftp for example (code 770) ?");
            }
        } catch (Exception $th) {
            // raw ouput because here TemplateEngine is not ready (services not already compiled and cache folder not available)
            echo "<div style=\"border:1px red solid;background-color: #FFCCCC;margin:3px;padding:5px;border-radius:5px;\">{$th->getMessage()}</div>";
            exit();
        }
    }

    private function loadAvailableField()
    {
        AnnotationRegistry::registerFile('tools/bazar/annotations/Field.php');

        $reader = new CachedReader(
            new AnnotationReader(),
            new PhpFileCache(self::CACHE_PATH . 'fields'),
            $debug = true
        );

        foreach ($this->wiki->extensions as $extensionKey => $extensionDir) {
            $fullExtensionDir = realpath($extensionDir) . '/fields';
            if (is_dir($fullExtensionDir)) {
                $fieldsFiles = array_diff(scandir($fullExtensionDir), ['..', '.']);

                foreach ($fieldsFiles as $fieldFile) {
                    preg_match("/^([a-zA-Z0-9_-]+)Field\.php$/", $fieldFile, $matches);
                    $fieldName = $matches[1];

                    $extensionName = ucfirst($extensionKey);
                    if ($extensionName === 'Helloworld') {
                        $extensionName = 'HelloWorld';
                    }

                    // TODO cache reflection class as this is a costly operation
                    $fieldClass = new \ReflectionClass('YesWiki\\' . $extensionName . '\\Field\\' . $fieldName . 'Field');

                    $annotation = $reader->getClassAnnotation($fieldClass, 'Field');

                    // If there is a Field annotation
                    if ($annotation) {
                        // Add all listed keywords
                        foreach ($annotation->keywords as $keyword) {
                            /* === Feature UUID : auj9-custom-changes === */
                            if (!isset($this->availableFields[$keyword])) {
                                $this->availableFields[$keyword] = $fieldClass->name;
                            }
                            /* === end of Feature UUID : auj9-custom-changes === */
                        }

                        // Also use the field name as a possible keyword
                        if (!isset($this->availableFields[strtolower($fieldName)])) {
                            $this->availableFields[strtolower($fieldName)] = $fieldClass->name;
                        }
                    }
                }
            }
        }
    }
}
