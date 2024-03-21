<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-can-force-entry-save-for-specific-group
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\AssetsManager as CoreAssetsManager;

class AssetsManager extends CoreAssetsManager
{
    public const BAZAR_JS_OLD_PATH = 'tools/bazar/libs/bazar.js';
    public const BAZAR_JS_ALTERNATIVE_PATH = 'tools/alternativeupdatej9rem/javascripts/modified-bazar.js';

    public function AddJavascriptFile($file, $first = false, $module = false)
    {
        if (substr($file, -strlen(self::BAZAR_JS_OLD_PATH)) === self::BAZAR_JS_OLD_PATH) {
            $file = str_replace(self::BAZAR_JS_OLD_PATH, self::BAZAR_JS_ALTERNATIVE_PATH, $file);

            $aclService = $this->wiki->services->get(AclService::class);
            $params = $this->wiki->services->get(ParameterBagInterface::class);

            $authorizedGroupToForceEntrySaving = $params->get('authorizedGroupToForceEntrySaving');

            $userIsAuthorizedToForceEntrySaving = json_encode(
                $this->wiki->UserIsAdmin()
                || (
                    !empty($authorizedGroupToForceEntrySaving)
                    && is_string($authorizedGroupToForceEntrySaving)
                    && !empty(trim($authorizedGroupToForceEntrySaving))
                    && substr($authorizedGroupToForceEntrySaving, 0, 1) !=='@'
                    // check if the current connected user is member of the group "@$authorizedGroupToForceEntrySaving"
                    // use tri to remove leading spaces
                    && $aclService->check('@'.trim($authorizedGroupToForceEntrySaving))
                )
            );
            // be carefull here it is used heredoc syntax
            // <https://www.php.net/manual/fr/language.types.string.php#language.types.string.syntax.heredoc>
            $this->AddJavascript(<<<JAVAS
            var userIsAuthorizedToForceEntrySaving = $userIsAuthorizedToForceEntrySaving;
            JAVAS);
        }
        return parent::AddJavascriptFile($file, $first, $module);
    }
}
