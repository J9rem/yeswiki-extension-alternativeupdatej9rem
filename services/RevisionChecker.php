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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * offer methods to check revision of yeswiki
 */
class RevisionChecker
{
    public function __construct() {}

    /**
     * @param ParameterBagInterface $params
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @return bool
     */
    public static function isWantedRevision(
        ParameterBagInterface $params,
        string $version,
        int $major,
        int $minor,
        int $bugfix
    ): bool {
        return self::getParam($params, 'yeswiki_version') === $version
            && self::getParam($params, 'yeswiki_release') === "$major.$minor.$bugfix";
    }

    /**
     * @param ParameterBagInterface $params
     * @param bool $isLower
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @param bool $included - current revision is included
     * @return bool
     */
    public static function isRevisionThan(
        ParameterBagInterface $params,
        bool $isLower,
        string $version,
        int $major,
        int $minor,
        int $bugfix,
        bool $included = true
    ): bool {
        return self::InternalIsRevisionThan(
            $isLower,
            self::getParam($params, 'yeswiki_version'),
            self::getParam($params, 'yeswiki_release'),
            $version,
            $major,
            $minor,
            $bugfix,
            $included
        );
    }

    /**
     * @param ParameterBagInterface $params
     * @param string $name
     * @return string
     */
    protected static function getParam(ParameterBagInterface $params, string $name): string
    {
        $value = '';
        if ($params->has($name)) {
            $extractedValue = $params->get($name);
            if (is_string($extractedValue)) {
                $value = $extractedValue;
            }
        }
        return $value;
    }

    /**
     * @param bool $isLower
     * @param string $currentVersion
     * @param string $currentRelease
     * @param string $version - version of wanted YesWiki
     * @param int $major
     * @param int $minor
     * @param int $bugfix
     * @param bool $included - current revision is included
     * @return bool
     */
    protected static function InternalIsRevisionThan(
        bool $isLower,
        string $currentVersion,
        string $currentRelease,
        string $version,
        int $major,
        int $minor,
        int $bugfix,
        bool $included = true
    ): bool {
        $matches = [];
        $modifier = $isLower ? -1 : 1 ;
        return $currentVersion === $version
            && preg_match("/^(\d+)\.(\d+)\.(\d+)\$/", $currentRelease, $matches)
            && (
                (intval($matches[1]) - $major) * $modifier > 0
                || (
                    intval($matches[1]) == $major
                    && (
                        (intval($matches[2]) - $minor) * $modifier > 0
                        || (
                            intval($matches[2]) == $minor
                            && (
                                $included
                                ? (intval($matches[3]) - $bugfix) * $modifier >= 0
                                : (intval($matches[3]) - $bugfix) * $modifier > 0
                            )
                        )
                    )
                )
            );
    }
}
