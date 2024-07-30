<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-can-force-entry-save-for-specific-group
 * Feature UUID : auj9-fix-4-4-5
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\AssetsManager as CoreAssetsManager;

class AssetsManager extends CoreAssetsManager
{
    public const BAZAR_JS_OLD_PATH = 'tools/bazar/libs/bazar.js';
    public const BAZAR_JS_NEW_PATH = 'tools/bazar/presentation/javascripts/bazar.js';  // Feature UUID : auj9-fix-4-4-5
    public const BAZAR_JS_ALTERNATIVE_PATH = 'tools/alternativeupdatej9rem/javascripts/modified-bazar.js';

    /* === Feature UUID : auj9-fix-4-4-5 === */
    public const BACKWARD_LOCAL_PATH_MAPPING = [
        'tools/bazar/libs/bazar.js' => 'tools/bazar/presentation/javascripts/bazar.js',
        'tools/bazar/libs/bazar.edit_forms.js' => 'tools/bazar/presentation/javascripts/forms-import.js',
        'tools/bazar/libs/bazar.edit_lists.js' => 'tools/bazar/presentation/javascripts/list-import.js',
        'tools/bazar/presentation/javascripts/bazar-edit-tabs-field.js' => 'tools/bazar/presentation/javascripts/inputs/tabs.js',
        'tools/bazar/presentation/javascripts/bazar-fields/conditionschecking.js' => 'tools/bazar/presentation/javascripts/inputs/conditions-checking.js',
        'tools/bazar/presentation/javascripts/bazar-list-dynamic.js' => 'tools/bazar/presentation/javascripts/entries-index-dynamic.js',
        'tools/bazar/presentation/javascripts/bazar-tagsinput.js' => 'tools/bazar/presentation/javascripts/inputs/checkbox-tags.js',
        'tools/bazar/presentation/javascripts/checkbox-drag-and-drop.js' => 'tools/bazar/presentation/javascripts/inputs/checkbox-drag-and-drop.js',
        // 'tools/bazar/presentation/javascripts/components/Panel.js' => 'javascripts/shared-components/Panel.js',
        'tools/bazar/presentation/javascripts/file-field.js' => 'tools/bazar/presentation/javascripts/inputs/file-field.js',
        'tools/bazar/presentation/javascripts/geolocationHelper.js' => 'tools/bazar/presentation/javascripts/inputs/map-geolocation-helper.js',
        'tools/bazar/presentation/javascripts/image-field.js' => 'tools/bazar/presentation/javascripts/inputs/image-field.js',
        'tools/bazar/presentation/javascripts/map-field-autocomplete.js' => 'tools/bazar/presentation/javascripts/inputs/map-autocomplete.js',
        'tools/bazar/presentation/javascripts/map-field-leaflet.js' => 'tools/bazar/presentation/javascripts/inputs/map-leaflet.js',
        'tools/bazar/presentation/javascripts/map-field-map-entry.js' => 'tools/bazar/presentation/javascripts/fields/map-field-map-entry.js',
        'tools/bazar/presentation/javascripts/recurrent-event.js' => 'tools/bazar/presentation/javascripts/inputs/recurrent-event.js',
        'tools/bazar/presentation/javascripts/user-field-update-email.js' => 'tools/bazar/presentation/javascripts/inputs/user-field-update-email.js',
        'tools/bazar/presentation/styles/bazar-list-dynamic.css' => 'tools/bazar/presentation/styles/entries/index-dynamic.css',
        'tools/bazar/presentation/styles/checkbox-drag-and-drop.css' => 'tools/bazar/presentation/styles/inputs/checkbox-drag-and-drop.css',
    ];
    /* === END OF Feature UUID : auj9-fix-4-4-5 === */

    public function AddJavascriptFile($file, $first = false, $module = false)
    {
        $file = $this->localMapFilePath($file); // Feature UUID : auj9-fix-4-4-5
        $replaceBazar = false;
        foreach ([self::BAZAR_JS_OLD_PATH, self::BAZAR_JS_NEW_PATH] as $path) {
            if (!$replaceBazar && substr($file, -strlen($path)) === $path) {
                $file = str_replace($path, self::BAZAR_JS_ALTERNATIVE_PATH, $file);
                $replaceBazar = true;
            }
        }
        if ($replaceBazar) {

            $aclService = $this->wiki->services->get(AclService::class);
            $params = $this->wiki->services->get(ParameterBagInterface::class);

            $authorizedGroupToForceEntrySaving = $params->get('authorizedGroupToForceEntrySaving');

            $userIsAuthorizedToForceEntrySaving = json_encode(
                $this->wiki->UserIsAdmin()
                || (
                    !empty($authorizedGroupToForceEntrySaving)
                    && is_string($authorizedGroupToForceEntrySaving)
                    && !empty(trim($authorizedGroupToForceEntrySaving))
                    && substr($authorizedGroupToForceEntrySaving, 0, 1) !== '@'
                    // check if the current connected user is member of the group "@$authorizedGroupToForceEntrySaving"
                    // use tri to remove leading spaces
                    && $aclService->check('@' . trim($authorizedGroupToForceEntrySaving))
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

    /* =======  Feature UUID : auj9-fix-4-4-5 ======= */
    public function LinkCSSFile($file, $conditionstart = '', $conditionend = '', $attrs = '')
    {
        $file = $this->localMapFilePath($file);
        return parent::LinkCSSFile($file, $conditionstart, $conditionend, $attrs);
    }

    protected function localMapFilePath($file)
    {
        // Handle backward compatibility
        if (
            array_key_exists($file, self::BACKWARD_LOCAL_PATH_MAPPING)
            && is_file(self::BACKWARD_LOCAL_PATH_MAPPING[$file])
        ) {
            $file = self::BACKWARD_LOCAL_PATH_MAPPING[$file];
        }

        return $file;
    }
    /* ==== END OF ==== */
}
