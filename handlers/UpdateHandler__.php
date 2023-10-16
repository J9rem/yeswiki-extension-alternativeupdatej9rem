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

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use Throwable;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\DbService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Plugins;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public const SPECIFIC_PAGE_NAME = "GererMisesAJourSpecific";

    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $aclService = $this->wiki->services->get(AclService::class);
        $pageManager = $this->wiki->services->get(PageManager::class);
        $dbService = $this->wiki->services->get(DbService::class);
        
        $messages = [];
        if (!$pageManager->getOne(self::SPECIFIC_PAGE_NAME)) {
            $message = 'ℹ️ Adding the <em>'.self::SPECIFIC_PAGE_NAME.'</em> page<br />';
            // save the page with default value
            $body = "{{alternativeupdatej9rem2 versions=\"doryphore\"}}\n";
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'read', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'write', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'comment', 'comments-closed');
            $pageManager->save(self::SPECIFIC_PAGE_NAME, $body);
            $message .= '✅ Done !';
            $messages[] = $message;
        }

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
        if (preg_match('/^([5-9]\.[0-9]+\.[0-9]+|4\.[3-9]+\.[0-9]+)$/',$this->params->get('yeswiki_release'))){
            $foldersToRemove['zfuture43'] = false;
        }
        if (preg_match('/^([5-9]\.[0-9]+\.[0-9]+|4\.[5-9]+\.[0-9]+|4\.4\.[1-9][0-9]*)$/',$this->params->get('yeswiki_release'))){
            $foldersToRemove['tabdyn'] = false;
        }
        if (array_key_exists('maintenance',$this->wiki->extensions)){
            $foldersToRemove['multideletepages'] = false;
        }
        foreach($foldersToRemove as $folderName => $deactivate){
            if (file_exists("tools/$folderName")){
                if ($deactivate || $this->shouldDeactivateInsteadOfDeleting($folderName)){
                    if (is_file("tools/$folderName/desc.xml")){
                        $messages[] = $this->isActive($folderName)
                            ? (
                                "ℹ️ Deactivating folder <em>tools/$folderName</em>... "
                                .(
                                    $this->deactivate($folderName)
                                    ? '✅ Done !'
                                    : '❌ Error : not deactivated !'
                                )
                            )
                            : "ℹ️ Folder <em>tools/$folderName</em> already deactived !";
                    } else {
                        $messages[] = "❌ Error <em>tools/$folderName</em> can not be deactivated : remove it manually !";
                    }
                } else {
                    $messages[] = "ℹ️ Removing folder <em>tools/$folderName</em>... "
                        .(
                            $this->deleteTool($folderName)
                            ? '✅ Done !'
                            : '❌ Error : not deleted !'
                        );
                }
            }
        }

        /* === Feature UUID : auj9-fix-edit-metadata === */
        /* Clean unused metadata */
        if (in_array($this->params->get('cleanUnusedMetadata'),[true,'true'],true)){
            $messages[] = 'ℹ️ Clean unused metadata';
            $selectSQL = <<<SQL
            SELECT `id`,`resource` FROM {$dbService->prefixTable('triples')}
                WHERE `property`='http://outils-reseaux.org/_vocabulary/metadata'
                  AND NOT (`resource` IN (
                    SELECT `tag` FROM {$dbService->prefixTable('pages')}
                  ))
            SQL;
            $triples = $dbService->loadAll($selectSQL);
            if (empty($triples)){
                $messages[] = '✅ No triple to delete !';
            } else {
                $messages[] = '&nbsp;&nbsp;ℹ️ '.count($triples).' triples to delete !';
                $message = '';
                for ($i=0; $i < count($triples) && $i <= 10; $i++) { 
                    if ($i == 10){
                        $message .= <<<HTML
                        <li>...</li>
                        HTML;
                    } else {
                        $values = $triples[$i];
                        $message .= <<<HTML
                        <li>{$values['resource']} ({$values['id']})</li>
                        HTML;
                    }
                }
                $messages[] = "<ul>$message</ul>";
                $deleteSQL = <<<SQL
                DELETE FROM {$dbService->prefixTable('triples')}
                    WHERE `property`='http://outils-reseaux.org/_vocabulary/metadata'
                      AND NOT (`resource` IN (
                        SELECT `tag` FROM {$dbService->prefixTable('pages')}
                      ))
                SQL;
                try {
                    $dbService->query($deleteSQL);
                } catch (Throwable $th) {
                    //throw $th;
                }
                $triples = $dbService->loadAll($selectSQL);
                if (empty($triples)){
                    $messages[] = '&nbsp;&nbsp;✅ All triples deleted !';
                } else {
                    $messages[] = '&nbsp;&nbsp;❌ Error : '.count($riples).' triples are not deleted !';
                }
            }
        }
        /* === end of Feature UUID : auj9-fix-edit-metadata === */
        
        if (!empty($messages)){
            $message = implode('<br/>',$messages);
            $output = <<<HTML
            <strong>Extension AlternativeUpdateJ9rem</strong><br/>
            $message<br/>
            <hr/>
            HTML;

            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output.'<!-- end handler /update -->',
                $this->output
            );
        }
        return null;
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
     */
    protected function shouldDeactivateInsteadOfDeleting(string $folderName): bool
    {
        return (DIRECTORY_SEPARATOR === '\\' && is_dir("tools/$folderName") && is_dir("tools/$folderName/.git"));
    }

    protected function isActive(string $folderName): bool
    {
        $info = $this->getInfoFromDesc($folderName);
        return  empty($info['active']) ? false : in_array($info['active'], [1,"1",true,"true"]);

    }
    
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
    
    protected function deleteTool(string $dirName): bool
    {
        return (!$this->delete("tools/$dirName"))
            ? false
            : !file_exists("tools/$dirName");
    }

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
}
