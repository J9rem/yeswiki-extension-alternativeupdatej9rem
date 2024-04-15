<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 *   maybe needed for 4.4.4
 */

namespace YesWiki\Alternativeupdatej9rem\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Alternativeupdatej9rem\Controller\CaptchaController as AUJ9CaptchaController;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Security\Controller\CaptchaController;
use YesWiki\Security\Controller\SecurityController as CoreSecurityController;
use YesWiki\Wiki;

class SecurityController extends CoreSecurityController
{
    protected $captchaController;
    protected $useCore;

    public function __construct(
        TemplateEngine $templateEngine,
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->useCore = $wiki->services->has(CaptchaController::class);
        $this->captchaController = $this->useCore
            ? $wiki->services->get(CaptchaController::class)
            : $wiki->services->get(AUJ9CaptchaController::class);
        $this->templateEngine = $templateEngine;
        $this->params = $params;
    }

    /**
     * check captcha before save edit
     * @param string $mode 'page' or 'entry'
     * @return array [bool $state,string $error]
     */
    public function checkCaptchaBeforeSave(string $mode = 'page'): array
    {
        if (!$this->wiki->UserIsAdmin() && $this->params->get('use_captcha')) {
            if (($mode != 'entry' && isset($_POST['submit']) && $_POST['submit'] == self::EDIT_PAGE_SUBMIT_VALUE)
                || ($mode == 'entry' && !empty($_POST['bf_titre']))) {
                /**
                 * @var string $error message if error
                 */
                $error = '';
                if (empty($_POST['captcha'])) {
                    $error = _t('CAPTCHA_ERROR_PAGE_UNSAVED');
                } elseif (!$this->captchaController->check(
                        $_POST['captcha'] ?? '',
                        $_POST['captcha_hash'] ?? ''
                    )) {
                    $error = _t('CAPTCHA_ERROR_WRONG_WORD');
                }
                // clean if error
                if (!empty($error)){
                    $_POST['submit'] = '';
                    if ($mode == 'entry') {
                        unset($_POST['bf_titre']);
                    }
                }
                unset($_POST['captcha']);
                unset($_POST['captcha_hash']);
            }
        }

        return [empty($error), $error ?? null];
    }

    /**
     * render captcha field if needed
     * @return string
     */
    public function renderCaptchaField(): string
    {
        $champsCaptcha = '';
        if (!$this->wiki->UserIsAdmin() && $this->params->get('use_captcha')) {
            // afficher les champs de formulaire et de l'image
            $hash = $this->captchaController->generateHash();
            $champsCaptcha = $this->templateEngine->render(
                $this->useCore ? '@security/captcha-field.twig' : '@alternativeupdatej9rem/captcha-field.twig',
                [
                    'baseUrl' => $this->wiki->getBaseUrl(),
                    'crypt' => $hash,
                    'cryptBase64' => base64_encode($hash)
                ]
            );
        }
        return $champsCaptcha;
    }
}
