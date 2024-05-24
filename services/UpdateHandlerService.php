<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;
use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Plugins;
use YesWiki\Wiki;

class UpdateHandlerService
{
    /**
     * @var string SPECIFIC_PAGE_NAME
     */
    public const SPECIFIC_PAGE_NAME = "GererMisesAJourSpecific";

    /**
     * @var AclService $aclService injected service
     */
    protected $aclService;
    /**
     * @var DbService $dbService injected service
     */
    protected $dbService;
    /**
     * @var FormManager $formManager injected service
     */
    protected $formManager;
    /**
     * @var PageManager $pageManager injected service
     */
    protected $pageManager;
    /**
     * @var ParameterBagInterface $params injected service
     */
    protected $params;
    /**
     * @var Wiki $wiki injected service
     */
    protected $wiki;

    public function __construct(
        AclService $aclService,
        DbService $dbService,
        FormManager $formManager    ,
        PageManager $pageManager,
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->aclService = $aclService;
        $this->dbService = $dbService;
        $this->formManager = $formManager;
        $this->pageManager = $pageManager;
        $this->params = $params;
        $this->wiki = $wiki;
    }

    /**
     * add specific page
     * @param array $messages
     * @return void
     */
    public function addSpecifiPage(array &$messages)
    {
        if (!$this->pageManager->getOne(self::SPECIFIC_PAGE_NAME)) {
            $lines = [];
            $lines[] = 'ℹ️ Adding the <em>' . self::SPECIFIC_PAGE_NAME . '</em> page';
            // save the page with default value
            $body = "{{alternativeupdatej9rem2 versions=\"doryphore\"}}\n";
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'read', '@admins');
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'write', '@admins');
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'comment', 'comments-closed');
            $this->pageManager->save(self::SPECIFIC_PAGE_NAME, $body);
            $lines[] = '✅ Done !';
            $messages[] = [
                'lines' => $lines,
                'text' => 'Adding the \'' . self::SPECIFIC_PAGE_NAME . '\' page for \'alternativeupdatej9rem2\' extension',
                'status' => _t('AU_OK')
            ];
        }
    }

    /**
     * remove not up-to-date tools
     * @param array $messages
     * @return void
     */
    public function removeNotUpToDateTools(array &$messages)
    {
        $foldersToRemove = [
            'archive' => false,
            'actionsbuilderfor422' => false,
            'bellerecherche' => false,
            'ebook' => false,
            'events' => false,
            'checkaccesslink' => false,
            'fontautoinstall' => false,
            'alternatepublication' => false,
            'geolocater' => false,
            'securitypatch422' => true,
            'tabimprovementmay' => true,
            'dkim' => true,
            'featexternalbazarservicecorrespondancefor431' => true
        ];
        if (RevisionChecker::isRevisionThan($this->params, false, 'doryphore', 4, 3, 0)) {
            $foldersToRemove['zfuture43'] = false;
        }
        if (RevisionChecker::isRevisionThan($this->params, false, 'doryphore', 4, 4, 1)) {
            $foldersToRemove['tabdyn'] = false;
        }
        if (array_key_exists('maintenance', $this->wiki->extensions)) {
            $foldersToRemove['multideletepages'] = false;
        }
        foreach($foldersToRemove as $folderName => $deactivate) {
            if (file_exists("tools/$folderName")) {
                $message = [
                    'lines' => [''],
                    'text' => '',
                    'status' => _t('AU_ERROR')
                ];
                if ($deactivate || $this->shouldDeactivateInsteadOfDeleting($folderName)) {
                    if (is_file("tools/$folderName/desc.xml")) {
                        if ($this->isActive($folderName)){
                            $message['lines'][0] .= "ℹ️ Deactivating folder <em>tools/$folderName</em>... ";
                            $message['text'] = "Deactivating folder 'tools/$folderName'";
                            if ($this->deactivate($folderName)){
                                $message['lines'][0] .= '✅ Done !';
                                $message['status'] = _t('AU_OK');
                            } else {
                                $message['lines'][0] .= '❌ Error : not deactivated !';
                            }
                        } else {
                            $message['lines'][0] .= "ℹ️ Folder <em>tools/$folderName</em> already deactived !";
                            $message['text'] = "Folder 'tools/$folderName' already deactived !";
                            $message['status'] = _t('AU_OK');
                        }
                    } else {
                        $message['lines'][0] .= "❌ Error <em>tools/$folderName</em> can not be deactivated : remove it manually !";
                        $message['text'] = "Error 'tools/$folderName' can not be deactivated : remove it manually !";
                    }
                } else {
                    $message['lines'][0] .= "ℹ️ Removing folder <em>tools/$folderName</em>... ";
                    $message['text'] = "Removing folder 'tools/$folderName'";
                    if ($this->deleteTool($folderName)){
                        $message['lines'][0] .= '✅ Done !';
                        $message['status'] = _t('AU_OK');
                    } else {
                        $message['lines'][0] .= '❌ Error : not deleted !';
                    }
                }
                $messages[] = $message;
            }
        }
    }

    /**
     * retrieve info from desc file for tools
     * @param string $dirName
     * @return array
     */
    protected function getInfoFromDesc(string $dirName)
    {
        include_once 'includes/YesWikiPlugins.php';
        $pluginService = new Plugins('tools/');
        if (is_file("tools/$dirName/desc.xml")) {
            return $pluginService->getPluginInfo("tools/$dirName/desc.xml");
        }
        return [];
    }

    /**
     * test if on Windows and prefer deactive to prevent git folder to be deleted
     * @param string $folderName
     * @return bool
     */
    protected function shouldDeactivateInsteadOfDeleting(string $folderName): bool
    {
        return (DIRECTORY_SEPARATOR === '\\' && is_dir("tools/$folderName") && is_dir("tools/$folderName/.git"));
    }

    /**
     * check if a tool is active
     * @param string $folderName
     * @return bool
     */
    protected function isActive(string $folderName): bool
    {
        $info = $this->getInfoFromDesc($folderName);
        return  empty($info['active']) ? false : in_array($info['active'], [1,"1",true,"true"]);

    }

    /**
     * deactivate a tool
     * @param string $dirName
     * @return bool
     */
    protected function deactivate(string $dirName): bool
    {
        $xmlPath = "tools/$dirName/desc.xml";
        if (is_file($xmlPath)) {
            $xml = file_get_contents($xmlPath);
            $newXml = preg_replace("/(active=)\"([^\"]+)\"/", "$1\"0\"", $xml);
            if (!empty($newXml) && $newXml != $xml) {
                file_put_contents($xmlPath, $newXml);
                return !$this->isActive($dirName);
            }
        }
        return false;
    }

    /**
     * delete a tool
     * @param string $dirName
     * @return bool
     */
    protected function deleteTool(string $dirName): bool
    {
        return (!$this->delete("tools/$dirName"))
            ? false
            : !file_exists("tools/$dirName");
    }

    /**
     * delete a path
     * @param string $path
     * @return bool
     */
    protected function delete($path)
    {
        if (empty($path)) {
            return false;
        }
        if (is_file($path)) {
            if (unlink($path)) {
                return true;
            }
            return false;
        }
        if (is_dir($path)) {
            return $this->deleteFolder($path);
        }
    }

    /**
     * delete a folder by deleting recursively sub folders and files
     * @param string $path
     * @return bool
     */
    private function deleteFolder($path)
    {
        $file2ignore = array('.', '..');
        if (is_link($path)) {
            unlink($path);
        } else {
            if ($res = opendir($path)) {
                while (($file = readdir($res)) !== false) {
                    if (!in_array($file, $file2ignore)) {
                        $this->delete($path . '/' . $file);
                    }
                }
                closedir($res);
            }
            rmdir($path);
        }
        return true;
    }

    /**
     * Clean unused metadata
     * @param array $messages
     * @return void
     * Feature UUID : auj9-fix-edit-metadata
     */
    public function cleanUnusedMetadata(array &$messages)
    {
        if (in_array($this->params->get('cleanUnusedMetadata'), [true,'true'], true)) {
            
            $message = [
                'lines' => ['ℹ️ Clean unused metadata'],
                'text' => 'Clean unused metadata',
                'status' => _t('AU_ERROR')
            ];
            $selectSQL = <<<SQL
            SELECT `id`,`resource` FROM {$this->dbService->prefixTable('triples')}
                WHERE `property`='http://outils-reseaux.org/_vocabulary/metadata'
                  AND NOT (`resource` IN (
                    SELECT `tag` FROM {$this->dbService->prefixTable('pages')}
                  ))
            SQL;
            $triples = $this->dbService->loadAll($selectSQL);
            if (empty($triples)) {
                $message['lines'][] = '✅ No triple to delete !';
                $message['status'] = _t('AU_OK');
                $message['text'] .= '. No triple to delete !';
            } else {
                $message['lines'][] = '&nbsp;&nbsp;ℹ️ ' . count($triples) . ' triples to delete !';
                $message['text'] .= '&nbsp;&nbsp;' . count($triples) . ' triples to delete !';
                $msg = '';
                for ($i = 0; $i < count($triples) && $i <= 10; $i++) {
                    if ($i == 10) {
                        $msg .= <<<HTML
                        <li>...</li>
                        HTML;
                    } else {
                        $values = $triples[$i];
                        $msg .= <<<HTML
                        <li>{$values['resource']} ({$values['id']})</li>
                        HTML;
                    }
                }
                $message['lines'][] = "<ul>$msg</ul>";
                $deleteSQL = <<<SQL
                DELETE FROM {$this->dbService->prefixTable('triples')}
                    WHERE `property`='http://outils-reseaux.org/_vocabulary/metadata'
                      AND NOT (`resource` IN (
                        SELECT `tag` FROM {$this->dbService->prefixTable('pages')}
                      ))
                SQL;
                try {
                    $this->dbService->query($deleteSQL);
                } catch (Throwable $th) {
                    //throw $th;
                }
                $triples = $this->dbService->loadAll($selectSQL);
                if (empty($triples)) {
                    $message['lines'][] = '&nbsp;&nbsp;✅ All triples deleted !';
                    $message['status'] = _t('AU_OK');
                } else {
                    $message['lines'][] = '&nbsp;&nbsp;❌ Error : ' . count($riples) . ' triples are not deleted !';
                }
            }
            $messages[] = $message;
        }
    }

    
    /**
     * transform video field definition in forms in url field
     * @param array $messages
     * @return void
     * Feature UUID : auj9-video-field
     */
    public function transformVideoFieldToUrlField(array &$messages)
    {
        $forms = $this->formManager->getAll();
        foreach($forms as $form) {
            if (!empty($form['template']) && is_array($form['template'])) {
                $toSave = false;
                foreach($form['template'] as $key => $fieldTemplate) {
                    if ($fieldTemplate[0] === 'video') {
                        $toSave = true;
                    }
                }
                if ($toSave) {
                    $message = [
                        'lines' => ["ℹ️ Converting videofield to urlfield in form {$form['bn_id_nature']}"],
                        'text' => "Converting videofield to urlfield in form {$form['bn_id_nature']}",
                        'status' => _t('AU_ERROR')
                    ];
                    $messages[] = "ℹ️ Converting videofield to urlfield in form {$form['bn_id_nature']}";
                    $separator = preg_quote('***', '/');
                    $form['bn_template'] = preg_replace(
                        "/\nvideo$separator([^*]+)$separator([^|]+)$separator([^*]+)$separator([^*]+)$separator([^|]+)$separator([^*]+)((?:{$separator}[^*]+){4}(?:$separator(?:[^*]+| \\* )){2}(?:{$separator}[^*]*){4,}\r?\n)/",
                        "\nlien_internet***$1***$2***displayvideo*** ***$5***$3|$4|$6$7",
                        $form['bn_template']
                    );
                    $this->formManager->update($form);
                    $message['lines'][] = "&nbsp;&nbsp; ✅";
                    $message['status'] = "ok";
                    $messages[] = $message;
                }

            }
        }
    }
}
