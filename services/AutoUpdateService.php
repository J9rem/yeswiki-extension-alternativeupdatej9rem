<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Alternativeupdatej9rem\Entity\CoreRepository; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\Files; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\PackageCollection; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\Messages; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\Release; // Feature UUID : auj9-fix-4-4-3
use YesWiki\Alternativeupdatej9rem\Entity\Repository;
use YesWiki\Alternativeupdatej9rem\Exception\UpgradeException;
use YesWiki\Alternativeupdatej9rem\Service\RevisionChecker;
// use YesWiki\AutoUpdate\Entity\Repository as CoreRepository;
// use YesWiki\AutoUpdate\Entity\Files;
// use YesWiki\AutoUpdate\Entity\PackageCollection;
// use YesWiki\AutoUpdate\Entity\Messages;
// use YesWiki\AutoUpdate\Entity\Release;
use YesWiki\Core\Entity\ConfigurationFile;
use YesWiki\Plugins;
use YesWiki\Wiki;

class AutoUpdateService
{
    public const DEFAULT_REPO = 'https://repository.yeswiki.net/';
    public const DEFAULT_VERS = 'Cercopitheque'; // For old revisions

    public const IGNORED_TOOLS = [
        '.',
        '..',
        'autoupdate',
        'aceditor',
        'attach',
        'bazar',
        'contact',
        'helloworld',
        'lang',
        'login',
        'progressBar',
        'rss',
        'security',
        'syndication',
        'tableau',
        'tags',
        'templates',
        'toc'
    ];

    protected $activated;
    protected $filesService ;
    protected $params ;
    protected $pluginService;
    protected $updatablePackagesViaAlternative;
    protected $wiki ;

    private $cacheRepo ;

    private const FUNCTIONS_NAMES = [
        'themes' => [
            'function' => 'getThemesPackages',
            'altFunction' => 'getAlternativeThemesPackages'],
        'tools' => [
            'function' => 'getToolsPackages',
            'altFunction' => 'getAlternativeToolsPackages'
        ]
    ];

    public function __construct(
        ParameterBagInterface $params,
        RevisionChecker $revisionChecker,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->wiki = $wiki;
        $this->activated =
            !method_exists(RevisionChecker::class, 'isRevisionThan')
            || RevisionChecker::isRevisionThan($params, false, 'doryphore', 4, 2, 2);
        $this->filesService = new Files();
        $this->pluginService = null;
        $this->updatablePackagesViaAlternative = $this->params->has("updatablePackagesViaAlternative")
            ? $this->params->get("updatablePackagesViaAlternative")
            : [];
        $this->updatablePackagesViaAlternative = is_array($this->updatablePackagesViaAlternative)
            ? array_filter($this->updatablePackagesViaAlternative, 'is_string')
            : [];
        $this->cacheRepo = [];
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * Parameter $requestedVersion contains the name of the YesWiki version
     * requested by version parameter of {{update}} action
     * if empty, no specifc version is requested
     * @param string $requestedVersion
     * @param array $packagesData
     * @return null|Repository
     * @throws Exception if trouble to load a repository
     */
    public function initRepository(string $requestedVersion = '', array $packagesData = []): ?Repository
    {
        $address = $this->repositoryAddress($requestedVersion);
        if (empty(filter_var($address, FILTER_VALIDATE_URL))) {
            throw new Exception("'yeswiki_repository' param is bad formatted ; got '$address'!");
        }
        $alternativeAddresses = $this->alternativeRepositoryAddresses($requestedVersion);
        foreach ($alternativeAddresses as $key => $addr) {
            if (empty(filter_var($addr, FILTER_VALIDATE_URL))) {
                throw new Exception("'alternative_yeswiki_repository' param is bad formatted for key $key ; got '$addr' !");
            }
        }
        $localKey = $address . implode("", $alternativeAddresses);
        if (!isset($this->cacheRepo[$localKey])) {
            $repository = new Repository(
                $address,
                $alternativeAddresses
            );

            $this->loadRepository($repository, $packagesData);

            $this->cacheRepo[$localKey] = $repository;
        }

        return $this->cacheRepo[$localKey];
    }

    public function isAdmin()
    {
        return $this->wiki->UserIsAdmin();
    }

    public function getWikiConfiguration()
    {
        $configuration = new ConfigurationFile(
            $this->getWikiDir() . '/wakka.config.php'
        );
        $configuration->load();
        return $configuration;
    }

    public function baseUrl()
    {
        return $this->params->get('base_url') . $this->wiki->tag;
    }

    private function getWikiDir()
    {
        $curExtDirName = basename(dirname(dirname(__FILE__)));
        $curDir = "tools/$curExtDirName";
        return dirname(dirname($curDir));
    }

    /*	Parameter $requestedVersion contains the name of the YesWiki version
        requested by version parameter of {{update}} action
        if empty, no specifc version is requested
    */
    private function repositoryAddress($requestedVersion = '')
    {
        $repositoryAddress = $this::DEFAULT_REPO;

        if ($this->params->has('yeswiki_repository')) {
            $repositoryAddress = $this->params->get('yeswiki_repository');
        }

        if (substr($repositoryAddress, -1, 1) !== '/') {
            $repositoryAddress .= '/';
        }

        if ($requestedVersion != '') {
            $repositoryAddress .= strtolower($requestedVersion);
        } else {
            $repositoryAddress .= $this->getYesWikiVersion();
        }
        return "$repositoryAddress/";
    }

    private function getYesWikiVersion()
    {
        $version = $this::DEFAULT_VERS;
        if ($this->params->has('yeswiki_version') && !empty($this->params->get('yeswiki_version'))) {
            $version = $this->params->get('yeswiki_version');
        }
        return strtolower($version);
    }

    /**
     * get alternativeRepository
     * @param string $requestedVersion
     * @return array
     */
    private function alternativeRepositoryAddresses(string $requestedVersion = ''): array
    {
        if ($this->params->has('alternative_yeswiki_repository')) {
            $param = $this->params->get('alternative_yeswiki_repository');
            if (is_string($param)) {
                $param = [$param];
            }
            if (is_array($param)) {
                return array_map(
                    function ($addr) use ($requestedVersion) {
                        if (substr($addr, -1, 1) !== '/') {
                            $addr .= '/';
                        }
                        if ($requestedVersion != '') {
                            $addr .= strtolower($requestedVersion);
                        } else {
                            $addr .= $this->getYesWikiVersion();
                        }
                        return "$addr/";
                    },
                    array_filter($param, function ($addr) {
                        return filter_var($addr, FILTER_VALIDATE_URL);
                    })
                );
            }
        }
        return [];
    }

    /**
     * load Repository
     * @param Repository $repository
     * @param array $packagesData
     * @throws Exception
     */
    private function loadRepository(Repository $repository, array $packagesData = [])
    {
        $repository->initLists();

        $this->loadARepo($repository, $repository->getAddress(), false);
        foreach ($repository->getAlternativeAddresses() as $key => $addr) {
            $this->loadARepo($repository, $addr, true, $key, $packagesData);
        }
        $this->loadLocalTools($repository);
        $this->loadLocalThemes($repository);
    }

    /**
     * load a repo in repository
     * @param Repository $repository
     * @param string $address
     * @param bool $isAlternative
     * @param mixed $key
     * @param array $packagesData
     * @throws Exception
     */
    private function loadARepo(Repository $repository, string $address, bool $isAlternative, $key = "", array $packagesData = [])
    {
        $repoInfosFile = $address . CoreRepository::INDEX_FILENAME;
        if (!empty($packagesData[$repoInfosFile])) {
            $data = $packagesData[$repoInfosFile];
        } else {
            $file = $this->filesService->download($repoInfosFile);
            $data = json_decode(file_get_contents($file), true);
            // release tmp file
            unlink($file);
        }

        if (is_null($data)) {
            return false;
        }

        foreach ($data as $packageInfos) {
            if (!isset($packageInfos['description'])) {
                $packageInfos['description'] = _t('AU_NO_DESCRIPTION');
            }
            $release = new Release($packageInfos['version']);
            if ($isAlternative) {
                $repository->addAlternative(
                    $key,
                    $release,
                    $address,
                    $packageInfos['file'],
                    $packageInfos['description'],
                    $packageInfos['documentation'],
                    $packageInfos['minimal_php_version'] ?? null
                );
            } else {
                $repository->add(
                    $release,
                    $address,
                    $packageInfos['file'],
                    $packageInfos['description'],
                    $packageInfos['documentation'],
                    $packageInfos['minimal_php_version'] ?? null
                );
            }
        }
    }

    /**
     * create fake package for local tools
     * @param Repository $repository
     */
    private function loadLocalTools(Repository $repository)
    {
        $packagesNames = $this->getAffectedToolsNames($repository);
        foreach (scandir('tools/') as $dirName) {
            if (is_dir("tools/$dirName") && !in_array($dirName, self::IGNORED_TOOLS) && !in_array(strtolower($dirName), $packagesNames)) {
                $info = $this->getInfoFromDesc($dirName);
                $repository->addPackageToolLocal(
                    empty($info['active']) ? false : in_array($info['active'], [1,"1",true,"true"]),
                    $dirName,
                    empty($info['desc']) ? "" : $info['desc']
                );
            }
        }
    }

    /**
     * create fake package for local themes
     * @param Repository $repository
     */
    private function loadLocalThemes(Repository $repository)
    {
        $packagesNames = $this->getAffectedThemesNames($repository);
        foreach (scandir('themes/') as $dirName) {
            if (is_dir("themes/$dirName") && !in_array($dirName, ['tools','.','..']) && !in_array(strtolower($dirName), $packagesNames)) {
                $repository->addPackageThemeLocal(
                    $dirName
                );
            }
        }
    }

    /**
     * get tools names already affected to a repo
     * @param Repository $repository
     * @return array
     */
    protected function getAffectedToolsNames(Repository $repository): array
    {

        list('packagesNames' => $packagesNames) = $this->getReposRaw(['tools'], $repository);

        return array_map('strtolower', $packagesNames);
    }

    /**
     * get themes names already affected to a repo
     * @param Repository $repository
     * @return array
     */
    protected function getAffectedThemesNames(Repository $repository): array
    {
        list('packagesNames' => $packagesNames) = $this->getReposRaw(['themes'], $repository);

        return array_map('strtolower', $packagesNames);
    }

    /**
     * retrieve info from desc file for tools
     * @param string $dirName
     * @return array
     */
    protected function getInfoFromDesc(string $dirName)
    {
        if (is_null($this->pluginService)) {
            include_once 'includes/YesWikiPlugins.php';
            $this->pluginService = new Plugins('tools/');
        }
        if (is_file("tools/$dirName/desc.xml")) {
            return $this->pluginService->getPluginInfo("tools/$dirName/desc.xml");
        }
        return [];
    }

    /**
     * update alternative package
     * @param Repository $repository
     * @param string $packageName
     * @param string $packageFile
     * @param string $packageFileMD5
     * @return null|Messages $message
     */
    public function upgradeAlternativeIfNeeded(
        Repository $repository,
        string $packageName,
        string $packageFile = "",
        string $packageFileMD5 = ""
    ): ?Messages {
        if (empty($packageName) || $packageName == "yeswiki") {
            return null;
        }

        if (!in_array($packageName, $this->updatablePackagesViaAlternative)
            && !empty($repository->getPackage($packageName))) {
            // leave core manage it
            return null;
        }
        list('key' => $key, 'package' => $package) = $repository->getAlternativePackage($packageName);
        if (empty($package) || (
            !in_array($packageName, $this->updatablePackagesViaAlternative)
            && get_class($package) === PackageCollection::CORE_CLASS
            && get_parent_class($package) === PackageCollection::CORE_CLASS
        )) {
            // not found for alternative repository or core
            return null;
        }

        // update alternative package
        $messages = new Messages();

        // Remise a zéro des messages
        $messages->reset();

        try {
            if (empty($packageFile)) {
                // Téléchargement de l'archive
                $file = $package ? $package->getFile() : false;
                if (false === $file) {
                    $messages->add('AU_DOWNLOAD', 'AU_ERROR');
                    throw new UpgradeException("");
                }
            } else {
                $file = $packageFile;
                $package->setdownloadedFile($packageFile);
            }

            $messages->add('AU_DOWNLOAD', 'AU_OK');
            // Vérification MD5
            if (empty($packageFileMD5)) {
                if (!$package->checkIntegrity($file)) {
                    $messages->add('AU_INTEGRITY', 'AU_ERROR');
                    throw new UpgradeException("");
                }
            } else {
                $md5Repo = explode(' ', file_get_contents($packageFileMD5))[0];
                $md5File = md5_file($file);
                $package->setMD5File($packageFileMD5);
                if ($md5File !== $md5Repo) {
                    $messages->add('AU_INTEGRITY', 'AU_ERROR');
                    throw new UpgradeException("");
                }
            }

            $messages->add('AU_INTEGRITY', 'AU_OK');

            // Extraction de l'archive
            $path = $package->extract();
            if (false === $path) {
                $messages->add('AU_EXTRACT', 'AU_ERROR');
                throw new UpgradeException("");
            }

            $messages->add('AU_EXTRACT', 'AU_OK');

            // Vérification des droits sur le fichiers
            if (!$package->checkACL()) {
                $messages->add('AU_ACL', 'AU_ERROR');
                throw new UpgradeException("");
            }
            $messages->add('AU_ACL', 'AU_OK');

            // Mise à jour du paquet
            if (!$package->upgrade()) {
                $messages->add(
                    _t('AU_UPDATE_PACKAGE') . $packageName,
                    'AU_ERROR'
                );
                throw new UpgradeException("");
            }
            $messages->add(_t('AU_UPDATE_PACKAGE') . $packageName, 'AU_OK');

            // Mise à jour de la configuration de YesWiki
            if (!$package->upgradeInfos()) {
                $messages->add('AU_UPDATE_INFOS', 'AU_ERROR');
                throw new UpgradeException("");
            }
            $messages->add('AU_UPDATE_INFOS', 'AU_OK');

            $package->cleanTempFiles();
        } catch (UpgradeException $ex) {
            $package->cleanTempFiles();
        }

        return $messages;
    }

    /** delete a package
     * @param Repository $repository
     * @param string $packageName
     * @return null|Messages $message
     */
    public function deleteAlternativeOrLocal(
        Repository $repository,
        string $packageName
    ): ?Messages {
        if (empty($packageName) || $packageName == "yeswiki") {
            return null;
        }

        if (!empty($repository->getPackage($packageName))) {
            // leave core manage it
            return null;
        }
        list('key' => $key, 'package' => $package) = $repository->getAlternativePackage($packageName);
        if (!empty($package)
            && get_class($package) === PackageCollection::CORE_CLASS
            && get_parent_class($package) === PackageCollection::CORE_CLASS
        ) {
            return null;
        } elseif (empty($package)) {
            $package = $repository->getLocalPackage($packageName);
            if (empty($package)
                || get_class($package) === PackageCollection::CORE_CLASS
                || get_parent_class($package) === PackageCollection::CORE_CLASS
            ) {
                return null;
            }
        }

        // update alternative package
        $messages = new Messages();

        // Remise a zéro des messages
        $messages->reset();

        if (false === $package->deletePackage()) {
            $messages->add('AU_DELETE', 'AU_ERROR');
        } else {
            $messages->add('AU_DELETE', 'AU_OK');
        }

        return $messages;
    }

    /**
     * deactive local ext
     * @param Repository $repository
     * @param string $packageName
     * @param bool $activation
     * @return bool
     */
    public function activationLocal(Repository $repository, string $packageName, bool $activation = true): bool
    {
        if (!empty($packageName) && $packageName != "yeswiki") {
            $package = $repository->getLocalPackage($packageName);
            if (!empty($package)
                && get_class($package) !== PackageCollection::CORE_CLASS
                && get_parent_class($package) !== PackageCollection::CORE_CLASS
                && $package->activate($activation)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Repository $repository
     * @param null|callable $callable
     * @return array $repos
     */
    public function getReposForAlternative(Repository $repository, $callable = null): array
    {
        list('repos' => $repos) = $this->getReposRaw(['themes','tools'], $repository, $callable);
        return $repos;
    }


    /**
     * @param array $types
     * @param Repository $repository
     * @param null|callable $callable
     * @return array [$repos,$packagesNames]
     */
    public function getReposRaw(array $types, Repository $repository, $callable = null): array
    {
        $repos = [];
        $packagesNames = [];
        foreach ($types as $type) {
            if (!empty(self::FUNCTIONS_NAMES[$type])) {
                $info = self::FUNCTIONS_NAMES[$type];
                $corePackages = $repository->{$info['function']}();
                $packagesNames = [];
                foreach ($corePackages as $package) {
                    if (!in_array($package->name, $this->updatablePackagesViaAlternative)) {
                        $packagesNames[] = $package->name;
                    }
                }
                $alternativePackages = $repository->{$info['altFunction']}();
                foreach ($alternativePackages as $key => $packages) {
                    foreach ($packages as $package) {
                        if (!in_array($package->name, $packagesNames)) {
                            if (!isset($repos[$key])) {
                                $repos[$key] = [];
                            }
                            if (!isset($repos[$key][$type])) {
                                $repos[$key][$type] = [];
                            }
                            $repos[$key][$type][$package->name] = is_callable($callable)
                                ? $callable($package)
                                : $package;
                            $packagesNames[] = $package->name;
                        }
                    }
                }
            }
        }
        return compact(['repos','packagesNames']);
    }
}
