<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-autoupdate-system
 * Feature UUID : auj9-video-field
 * Feature UUID : auj9-fix-edit-metadata
 */

namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use Throwable;
use YesWiki\Alternativeupdatej9rem\Service\UpdateHandlerService;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Core\Service\DbService;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public function run()
    {
        if ($this->getService(SecurityController::class)->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $updateHandlerService = $this->wiki->services->get(UpdateHandlerService::class);

        $messages = [];

        $updateHandlerService->addSpecifiPage($messages);
        $updateHandlerService->removeNotUpToDateTools($messages);
        $this->cleanUnusedMetadata($messages);
        $this->transformVideoFieldToUrlField($messages);

        if (!empty($messages)) {
            $message = implode('<br/>', $messages);
            $output = <<<HTML
            <strong>Extension AlternativeUpdateJ9rem</strong><br/>
            $message<br/>
            <hr/>
            HTML;

            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output . '<!-- end handler /update -->',
                $this->output
            );
        }
        return null;
    }

    /**
     * Clean unused metadata
     * @param array $messages
     * @return void
     * Feature UUID : auj9-fix-edit-metadata
     */
    protected function cleanUnusedMetadata(array &$messages)
    {
        /**
         * @var DbService $dbService
         */
        $dbService = $this->wiki->services->get(DbService::class);
        if (in_array($this->params->get('cleanUnusedMetadata'), [true,'true'], true)) {
            $messages[] = 'ℹ️ Clean unused metadata';
            $selectSQL = <<<SQL
            SELECT `id`,`resource` FROM {$this->dbService->prefixTable('triples')}
                WHERE `property`='http://outils-reseaux.org/_vocabulary/metadata'
                  AND NOT (`resource` IN (
                    SELECT `tag` FROM {$this->dbService->prefixTable('pages')}
                  ))
            SQL;
            $triples = $this->dbService->loadAll($selectSQL);
            if (empty($triples)) {
                $messages[] = '✅ No triple to delete !';
            } else {
                $messages[] = '&nbsp;&nbsp;ℹ️ ' . count($triples) . ' triples to delete !';
                $message = '';
                for ($i = 0; $i < count($triples) && $i <= 10; $i++) {
                    if ($i == 10) {
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
                    $messages[] = '&nbsp;&nbsp;✅ All triples deleted !';
                } else {
                    $messages[] = '&nbsp;&nbsp;❌ Error : ' . count($riples) . ' triples are not deleted !';
                }
            }
        }
    }

    /**
     * transform video field definition in forms in url field
     * @param array $messages
     * @return void
     * Feature UUID : auj9-video-field
     */
    protected function transformVideoFieldToUrlField(array &$messages)
    {
        $formManager = $this->getService(FormManager::class);
        $forms = $formManager->getAll();
        foreach($forms as $form) {
            if (!empty($form['template']) && is_array($form['template'])) {
                $toSave = false;
                foreach($form['template'] as $key => $fieldTemplate) {
                    if ($fieldTemplate[0] === 'video') {
                        $toSave = true;
                    }
                }
                if ($toSave) {
                    $messages[] = "ℹ️ Converting videofield to urlfield in form {$form['bn_id_nature']}";
                    $separator = preg_quote('***', '/');
                    $form['bn_template'] = preg_replace(
                        "/\nvideo$separator([^*]+)$separator([^|]+)$separator([^*]+)$separator([^*]+)$separator([^|]+)$separator([^*]+)((?:{$separator}[^*]+){4}(?:$separator(?:[^*]+| \\* )){2}(?:{$separator}[^*]*){4,}\r?\n)/",
                        "\nlien_internet***$1***$2***displayvideo*** ***$5***$3|$4|$6$7",
                        $form['bn_template']
                    );
                    $formManager->update($form);
                    $messages[] = "&nbsp;&nbsp; ✅";
                }

            }
        }
    }
}
