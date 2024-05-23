<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-duplicate
 * Feature UUID : auj9-fix-4-4-2
 * Feature UUID : auj9-fix-4-4-3
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;;
use YesWiki\Wiki;

/**
 * offer methods to check revision of yeswiki
 */
class RevisionChecker
{
    /**
     * @var ParameterBagInterface $params
     */
    protected $params ;

    /**
     * @var string $release - revision of yeswiki
     */
    protected $release ;

    /**
     * @var string $version - version of yeswiki
     */
    protected $version ;

    /**
     * @var Wiki $wiki
     */
    protected $wiki ;


    public function __construct(
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->wiki = $wiki;
        $this->version = $this->params->get('yeswiki_version');
        if (!is_string($this->version)){
            $this->version = '';
        }
        $this->release = $this->params->get('yeswiki_release');
        if (!is_string($this->release)){
            $this->release = '';
        }
    }

    /**
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @return bool
     */
    public function isWantedRevision(string $version, int $major, int $minor, int $bugfix): bool
    {
        return $this->version === $version
            && $this->release === "$major.$minor.$bugfix";
    }

    /**
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @param bool $included - current revision is included
     * @return bool
     */
    public function isRevisionLowerThan(string $version, int $major, int $minor, int $bugfix, bool $included = true): bool
    {
        $matches = [];
        return $this->version === $version
            && preg_match("/^(\d+)\.(\d+)\.(\d+)\$/", $this->release, $matches)
            && (
                intval($matches[1]) < $major
                || (
                    intval($matches[1]) == $major
                    && (
                        intval($matches[2]) < $minor
                        || (
                            intval($matches[2]) == $minor
                            && (
                                $included
                                ? intval($matches[3]) <= $bugfix
                                : intval($matches[3]) < $bugfix
                            )
                        )
                    )
                )
            );
    }

    /**
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @param bool $included - current revision is included
     * @return bool
     */
    public function isRevisionHigherThan(string $version, int $major, int $minor, int $bugfix, bool $included = true): bool
    {
        $matches = [];
        return $this->version === $version
            && preg_match("/^(\d+)\.(\d+)\.(\d+)\$/", $this->release, $matches)
            && (
                intval($matches[1]) > $major
                || (
                    intval($matches[1]) == $major
                    && (
                        intval($matches[2]) > $minor
                        || (
                            intval($matches[2]) == $minor
                            && (
                                $included
                                ? intval($matches[3]) >= $bugfix
                                : intval($matches[3]) > $bugfix
                            )
                        )
                    )
                )
            );
    }

}
