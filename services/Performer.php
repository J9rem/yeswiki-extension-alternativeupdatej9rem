<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-perf-sql
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\Performer as CorePerformer;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Wiki;

class Performer extends CorePerformer
{
    /**
     * default construct copied from parent
     * to be able to use private method
     */
    public function __construct(
        ParameterBagInterface $params,
        TemplateEngine $twig,
        Wiki $wiki
    )
    {
        $this->params = $params;
        $this->twig = $twig;
        $this->wiki = $wiki;

        // get the list of all existing objects (actions, handlers, formatters...)
        $folders = array_merge([''], $wiki->extensions); // root folder + extensions folders
        foreach (Performer::TYPES as $type) {
            $this->objectList[$type] = [];
            foreach ($folders as $folder) {
                foreach (Performer::PATHS[$type] as $path) {
                    $this->findObjectInPath($folder . $path, $type);
                }
            }
        }
    }

    /**
     * Read existing PHP files in the current $dir, and store them inside $this->objectList
     * copy from parent to remove `tools/bazar/handlers/pages/linkrss__.php`
     */
    private function findObjectInPath($dir, $objectType)
    {
        if (file_exists($dir) && $dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                /* === BEGIN of change === */
                if (
                    ($file !== 'linkrss__.php' || $dir !== 'tools/bazar/actions/')
                    && preg_match("/^([a-zA-Z0-9_-]+)(\.class)?\.php$/", $file, $matches)
                    ) {
                        /* === END OF CHANGE === */
                    $baseName = $matches[1]; // __GreetingAction
                    $objectName = strtolower($matches[1]); // __greetingaction
                    $objectName = preg_replace("/^__|__$/", '', $objectName); // greetingaction
                    $isDefinedAsClass = false;
                    if (endsWith($baseName, ucfirst($objectType)) || endsWith($baseName, ucfirst($objectType) . "__")) {
                        $objectName = preg_replace("/{$objectType}$/", '', $objectName); // greeting
                        $isDefinedAsClass = true;
                    }
                    $filePath = $dir . $file;
                    $object = &$this->objectList[$objectType][$objectName];
                    if (startsWith($file, '__')) {
                        if (!isset($object['before_callbacks'])) {
                            $object['before_callbacks'] = [] ;
                        }
                        array_unshift($object['before_callbacks'], [
                            'filePath' => $filePath,
                            'baseName' => $baseName,
                            'isDefinedAsClass' => $isDefinedAsClass
                        ]);
                    } elseif (endsWith($file, '__.php')) {
                        if (!isset($object['after_callbacks'])) {
                            $object['after_callbacks'] = [] ;
                        }
                        array_unshift($object['after_callbacks'], [
                            'filePath' => $filePath,
                            'baseName' => $baseName,
                            'isDefinedAsClass' => $isDefinedAsClass
                        ]);
                    } else {
                        $object = [
                            'filePath' => $filePath,
                            'baseName' => $baseName,
                            'isDefinedAsClass' => $isDefinedAsClass,
                            'before_callbacks' => $object['before_callbacks'] ?? [],
                            'after_callbacks' => $object['after_callbacks'] ?? [],
                        ];
                    }
                }
            }
        }
    }
}
