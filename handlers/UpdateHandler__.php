<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem;

use YesWiki\Alternativeupdatej9rem\Entity\PackageToolLocal;
use YesWiki\Alternativeupdatej9rem\Service\FilesService;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Plugins;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public const SPECIFIC_PAGE_NAME = "GererMisesAJourSpecific";

    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $aclService = $this->wiki->services->get(AclService::class);
        $pageManager = $this->wiki->services->get(PageManager::class);
        $output = '<strong>Extension AlternativeUpdateJ9rem</strong><br/>';
        if (!$pageManager->getOne(self::SPECIFIC_PAGE_NAME)) {
            $output .= 'ℹ️ Adding the <em>'.self::SPECIFIC_PAGE_NAME.'</em> page<br />';
            // save the page with default value
            $body = "{{alternativeupdatej9rem2 versions=\"doryphore\"}}\n";
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'read', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'write', '@admins');
            $aclService->save(self::SPECIFIC_PAGE_NAME, 'comment', 'comments-closed');
            $pageManager->save(self::SPECIFIC_PAGE_NAME, $body);
            $output .= '✅ Done !<br />';
        }

        $filesService = $this->getService(FilesService::class);
        $foldersToRemove = [
            'archive' => false,
            'actionsbuilderfor422' => false,
            'ebook' => false,
            'checkaccesslink' => false,
            'fontautoinstall' => false,
            'alternatepublication' => false,
            'geolocater' => false,
            'tabimprovementmay' => true,
            'featexternalbazarservicecorrespondancefor431' => true
        ];
        if (preg_match('/^([5-9]\.[0-9]+\.[0-9]+|4\.[3-9]+\.[0-9]+*)$/',$this->params->get('yeswiki_release'))){
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
                $deactivate = $deactivate || (DIRECTORY_SEPARATOR === '\\' && is_dir("tools/$folderName") && is_dir("tools/$folderName/.git"));
                if ($deactivate && is_dir("tools/$folderName") && is_file("tools/$folderName/desc.xml")){
                    $info = $this->getInfoFromDesc($folderName);
                    $active = empty($info['active']) ? false : in_array($info['active'], [1,"1",true,"true"]);
                    if ($active){
                        $output .= "ℹ️ Deactivating folder <em>tools/$folderName</em>... ";
                        $package = new PackageToolLocal(
                            $active,
                            $folderName,
                            "",
                            "",
                            null
                        );
                        $package->activate(false);
                        $info = $this->getInfoFromDesc($folderName);
                        if (empty($info['active']) ? false : in_array($info['active'], [1,"1",true,"true"])){
                            $output .= '❌ Error : not deactivated !<br />';
                        } else {
                            $output .= '✅ Done !<br />';
                        }
                    } else {
                        $output .= "ℹ️ Folder <em>tools/$folderName</em> already deactived !<br/>";
                    }
                } else {
                    $output .= "ℹ️ Removing folder <em>tools/$folderName</em>... ";
                    $filesService->delete("tools/$folderName");
                    if (file_exists("tools/$folderName")){
                        $output .= '❌ Error : not deleted !<br />';
                    } else {
                        $output .= '✅ Done !<br />';
                    }
                }
            } else {
                $output .= "ℹ️ Folder <em>tools/$folderName</em> is not present.<br />";
            }
        }

        // set output
        $this->output = str_replace(
            '<!-- end handler /update -->',
            $output.'<!-- end handler /update -->',
            $this->output
        );
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
}
