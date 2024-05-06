<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-perf-sql
 */


namespace YesWiki\Alternativeupdatej9rem;

use BazarAction;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Core\YesWikiAction;

/**
 * replace core LinkRss__ action to display only needed forms
 */
class LinkRssAction__ extends YesWikiAction
{
    public function run()
    {
        $output = '';
        if ($this->wiki->CheckModuleACL('rss', 'handler')) {
            $output .= '<link rel="alternate" type="application/rss+xml" '
                .'title="'.htmlspecialchars(_t('BAZ_FLUX_RSS_GENERAL')).'" '
                .'href="'.$this->wiki->Href('rss').'">'."\n";
            /**
             * @var FormManager $formManager
             */
            $formManager = $this->getService(FormManager::class);

            /**
             * @var mixed $rawListOfForms
             */
            $rawListOfForms = $this->params->get('displayedFormsInRssHandler');

            /**
             * @var int[] $formsIds
             */
            $formsIds = [];
            if (!empty($rawListOfForms) && is_string($rawListOfForms)) {
                foreach (explode(',',$rawListOfForms) as $rawId) {
                    if (
                        !empty(trim($rawId))
                        && intval($rawId) > 0
                        && strval(intval($rawId)) === $rawId
                        ){
                        $formsIds[] = intval($rawId);
                    }
                }
            }

            /**
             * @var array $forms from FormManager
             */
            $forms = empty($formsIds)
                ? $formManager->getAll()
                : $formManager->getMany($formsIds);

            if (!empty($forms) && is_array($forms)){
                foreach ($forms as $form) {
                    /**
                     * @var string $formName
                     */
                    $formName = htmlspecialchars($form['bn_label_nature'] ?? 'error');
                    /**
                     * @var string $rssLink
                     */
                    $rssLink = $this->wiki->Href('rss', $this->wiki->getPageTag(), 'id='.($form['bn_id_nature'] ?? 0));
                    $output .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"$formName\" href=\"$rssLink\">\n";
                }
            }
        }
        return $output;
    }
}
