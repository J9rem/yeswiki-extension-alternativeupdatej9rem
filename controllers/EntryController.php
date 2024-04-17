<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-option-entrycontroller-edit-instead-of-view-if-only-one
 */

namespace YesWiki\Alternativeupdatej9rem\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Controller\EntryController As BazarEntryController;

/**
 * class to extend EntryController and try to edit entry instead of view
 * according to option
 */
class EntryController extends BazarEntryController
{
    /**
     * @var bool $editInsteadOfView
     */
    protected $editInsteadOfView;
    /**
     * @var ParameterBagInterface $params
     */
    protected $params;

    /**
     * lazy load of params
     * @return ParameterBagInterface
     */
    protected function getParams(): ParameterBagInterface
    {
        if (empty($this->params) || !($this->params instanceof ParameterBagInterface)) {
            $this->params = $this->getService(ParameterBagInterface::class);
        }
        return $this->params;
    }

    public function create($formId, $redirectUrl = null)
    {
        $this->editInsteadOfView = false;
        if (in_array($this->getParams()->get('editEntryInsteadOfShowingIfOnlyOne'), [true, 1, 'true'], true)) {
            if (!empty($formId)) {
                /**
                 * @var array|null $form
                 */
                $form = $this->formManager->getOne($formId);
                if (!empty($form)
                    && isset($form['bn_only_one_entry'])
                    && $form['bn_only_one_entry'] === "Y"
                    && !empty($this->authController->getLoggedUser())) {
                    $this->editInsteadOfView = true;
                }
            }
        }
        /**
         * @var string $output
         */
        $output = parent::create($formId, $redirectUrl);
        // reset before leaving
        $this->editInsteadOfView = false;
        return $output;
    }

    
    /**
     * @param string $entryId
     * @param string|null $time choose only the entry's revision corresponding to time, null = latest revision
     * @param bool $showFooter
     * @param string|null $userNameForRendering userName used to render the entry, if empty uses the connected user
     */
    public function view($entryId, $time = '', $showFooter = true, ?string $userNameForRendering = null)
    {
        if ($this->editInsteadOfView === true){
            /**
             * @var string $output
             */
            $output = '';
            if ($this->securityController->isWikiHibernated()) {
                $output = $this->securityController->getMessageWhenHibernated();
            } elseif ($this->aclService->hasAccess('write', $entryId) && $this->aclService->hasAccess('read', $entryId)) {
                $output = $this->update($entryId);
            } else {
                $output = $this->render('@templates/alert-message.twig', [
                    'type' => 'danger',
                    'message' => _t('EDIT_NO_WRITE_ACCESS')
                ]);
            }
            // reset
            $this->editInsteadOfView = false;
            return $output;
        }
        // reset
        $this->editInsteadOfView = false;
        return parent::view($entryId, $time, $showFooter, $userNameForRendering);
    }
}
