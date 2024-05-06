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
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Plugins;
use YesWiki\Wiki;

class UpdateHandlerService {

    /**
     * @var string SPECIFIC_PAGE_NAME
     */
    public const SPECIFIC_PAGE_NAME = "GererMisesAJourSpecific";
    
    /**
     * @var AclService $aclService injected service
     */
    protected $aclService;
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
        PageManager $pageManager,
        ParameterBagInterface $params,
        Wiki $wiki
    )
    {
        $this->aclService = $aclService;
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
            $message = 'ℹ️ Adding the <em>' . self::SPECIFIC_PAGE_NAME . '</em> page<br />';
            // save the page with default value
            $body = "{{alternativeupdatej9rem2 versions=\"doryphore\"}}\n";
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'read', '@admins');
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'write', '@admins');
            $this->aclService->save(self::SPECIFIC_PAGE_NAME, 'comment', 'comments-closed');
            $this->pageManager->save(self::SPECIFIC_PAGE_NAME, $body);
            $message .= '✅ Done !';
            $messages[] = $message;
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
        if (preg_match('/^([5-9]\.[0-9]+\.[0-9]+|4\.[3-9]+\.[0-9]+)$/', $this->params->get('yeswiki_release'))) {
            $foldersToRemove['zfuture43'] = false;
        }
        if (preg_match('/^([5-9]\.[0-9]+\.[0-9]+|4\.[5-9]+\.[0-9]+|4\.4\.[1-9][0-9]*)$/', $this->params->get('yeswiki_release'))) {
            $foldersToRemove['tabdyn'] = false;
        }
        if (array_key_exists('maintenance', $this->wiki->extensions)) {
            $foldersToRemove['multideletepages'] = false;
        }
        foreach($foldersToRemove as $folderName => $deactivate) {
            if (file_exists("tools/$folderName")) {
                if ($deactivate || $this->shouldDeactivateInsteadOfDeleting($folderName)) {
                    if (is_file("tools/$folderName/desc.xml")) {
                        $messages[] = $this->isActive($folderName)
                            ? (
                                "ℹ️ Deactivating folder <em>tools/$folderName</em>... "
                                . (
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
                        . (
                            $this->deleteTool($folderName)
                            ? '✅ Done !'
                            : '❌ Error : not deleted !'
                        );
                }
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
}
