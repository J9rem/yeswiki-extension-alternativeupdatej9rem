<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Entity;

use AutoUpdate\PackageCollection;
use Exception;
use YesWiki\Alternativeupdatej9rem\Entity\PackageThemeLocal;
use YesWiki\Alternativeupdatej9rem\Entity\PackageToolLocal;
use YesWiki\Alternativeupdatej9rem\Entity\PackageTheme;
use YesWiki\Alternativeupdatej9rem\Entity\PackageTool;

include_once 'tools/autoupdate/vendor/autoload.php';

class Repository extends PackageCollection
{
    const THEME_CLASS = 'YesWiki\Alternativeupdatej9rem\Entity\PackageTheme';
    const TOOL_CLASS = 'YesWiki\Alternativeupdatej9rem\Entity\PackageTool';

    private $address;
    private $alternativeAddresses;

    private $alernativeList ;
    private $localToolsList ;
    private $localThemesList ;

    public function __construct(string $address, array $alternativeAddresses)
    {
        $this->address = $address;
        $this->alternativeAddresses = $alternativeAddresses;
        $this->list = [];
        $this->alernativeList = [];
        $this->localToolsList = [];
        $this->localThemesList = [];
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAlternativeAddresses(): array
    {
        return $this->alternativeAddresses;
    }

    public function initLists()
    {
        $this->list = [];
        $this->alernativeList = [];
        $this->localToolsList = [];
        $this->localThemesList = [];
    }

    public function addAlternative($key, $release, $address, $file, $description, $documentation, $minimalPhpVersion = null)
    {
        $className = $this->getPackageTypeLocal($file);
        $package = new $className(
            $release,
            $address . $file,
            $description,
            $documentation,
            $minimalPhpVersion
        );
        if (!isset($this->alernativeList[$key]) || !is_array($this->alernativeList[$key])) {
            $this->alernativeList[$key] = [];
        }
        $this->alernativeList[$key][$package->name] = $package;
    }

    public function addPackageToolLocal($active, $dirname, $description)
    {
        $package = new PackageToolLocal(
            $active,
            $dirname,
            $description,
            "",
            null
        );
        $this->localToolsList[$package->name] = $package;
    }

    public function addPackageThemeLocal($dirname)
    {
        $package = new PackageThemeLocal(
            "",
            $dirname,
            "",
            "",
            null
        );
        $this->localThemesList[$package->name] = $package;
    }

    public function getAlternativePackage($packageName)
    {
        foreach ($this->alernativeList as $key => $list) {
            if (isset($list[$packageName])) {
                return ['key' => $key,'package' => $list[$packageName]];
            }
        }
        return ['key' => null,'package' => null];
    }

    public function getLocalPackage($packageName)
    {
        return !empty($this->localToolsList[$packageName])
            ? $this->localToolsList[$packageName]
            : (
                !empty($this->localThemesList[$packageName])
                ? $this->localThemesList[$packageName]
                : null
            );
    }

    public function getAlternativeThemesPackages()
    {
        return $this->filterAlternativePackages(self::THEME_CLASS);
    }

    public function getAlternativeToolsPackages()
    {
        return $this->filterAlternativePackages(self::TOOL_CLASS);
    }

    public function getLocalToolsPackages()
    {
        return $this->localToolsList;
    }

    public function getLocalThemesPackages()
    {
        return $this->localThemesList;
    }

    private function filterAlternativePackages($class): array
    {
        $filteredPackages = [];
        foreach ($this->alernativeList as $key => $list) {
            if (!isset($filteredPackages[$key])) {
                $filteredPackages[$key] = new PackageCollection();
            }
            foreach ($list as $package) {
                if (get_class($package) === $class) {
                    $filteredPackages[$key][] = $package;
                }
            }
        }
        return $filteredPackages;
    }

    private function getPackageType($filename)
    {
        $type = explode('-', $filename)[0];
        switch ($type) {
            case 'yeswiki':
                return PackageCollection::CORE_CLASS;
                break;

            case 'extension':
                return PackageCollection::TOOL_CLASS;
                break;

            case 'theme':
                return PackageCollection::THEME_CLASS;
                break;

            default:
                throw new Exception(_t('AU_UNKWON_PACKAGE_TYPE'));
                break;
        }
    }

    private function getPackageTypeLocal($filename)
    {
        $type = explode('-', $filename)[0];
        switch ($type) {
            case 'yeswiki':
                return PackageCollection::CORE_CLASS;
                break;

            case 'extension':
                return self::TOOL_CLASS;
                break;

            case 'theme':
                return self::THEME_CLASS;
                break;

            default:
                throw new Exception(_t('AU_UNKWON_PACKAGE_TYPE'));
                break;
        }
    }
}
