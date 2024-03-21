<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-local-cache
 */

namespace YesWiki\Alternativeupdatej9rem;

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use YesWiki\Alternativeupdatej9rem\Service\CacheService;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\YesWikiAction;
use YesWiki\Security\Controller\SecurityController;

class ClearLocalCacheAction extends YesWikiAction
{

    protected const ANTI_CSRF_TOKEN = 'clearlocalcache\\action';
    protected const ANTI_CSRF_TOKEN_KEY = 'token';
    
    protected $csrfTokenController;
    protected $csrfTokenManager;
    protected $securityController;

    public function run()
    {
        // get services
        $this->csrfTokenController = $this->getService(CsrfTokenController::class);
        $this->csrfTokenManager = $this->getService(CsrfTokenManager::class);
        $this->securityController = $this->getService(SecurityController::class);

        if (!$this->wiki->UserIsAdmin()) {
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('AUJ9_CLEAR_LOCAL_CACHE_RESERVED_TO_ADMIN')
            ]);
        } elseif ($this->securityController->isWikiHibernated()) {
            return $this->render("@templates/alert-message.twig", [
                'type' => 'danger',
                'message' => _t('WIKI_IN_HIBERNATION')
            ]);
        }

        // clear local cache
        if (!empty($_GET[self::ANTI_CSRF_TOKEN_KEY]) &&
            is_string($_GET[self::ANTI_CSRF_TOKEN_KEY])) {
            return $this->render("@templates/alert-message.twig", $this->clearLocalCache());
        }

        $text = _t('AUJ9_CLEAR_LOCAL_CACHE_TEXT');
        $token = $this->csrfTokenManager->getToken(self::ANTI_CSRF_TOKEN)->getValue();
        return $this->callAction('button', [
            'link' => $this->wiki->Href('render', null, [
                'content' => "{{clearlocalcache}}",
                self::ANTI_CSRF_TOKEN_KEY => $token
            ], false),
            'text' => $text,
            'title' => $text,
            'class' => 'btn-secondary-2 new-window'
        ]);
    }

    protected function clearLocalCache(): array
    {
        $output = '<b>'._t('AUJ9_CLEARING_LOCAL_CACHE_TEXT').'</b></br>';
        try {
            $this->csrfTokenController->checkToken(self::ANTI_CSRF_TOKEN, 'GET', self::ANTI_CSRF_TOKEN_KEY);
        } catch (TokenNotFoundException $th) {
            return [
                'type' => 'danger',
                'message' => "$output&#10060; not possible to clear cache : '{$th->getMessage()}' !"
            ];
        }
        // TODO clear all used folders
        $this->getService(CacheService::class)->clear('bazarlist');

        $output .= '✅ Done !<br />';

        return ['type' => 'success','message' => $output];
    }
}
