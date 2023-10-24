<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-open-agenda-connect
 */
namespace YesWiki\Alternativeupdatej9rem\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\Service\ConfigurationService;
use YesWiki\Core\YesWikiController;
use YesWiki\Security\Controller\SecurityController;

class ConfigOpenAgendaController extends YesWikiController
{
    public const TOKEN_ID = "POST /api/alternativeupdatej9rem/openagenda";

    protected $configurationService;
    protected $csrfTokenController;
    protected $csrfTokenManager;
    protected $params;
    protected $securityController;

    public function __construct(
        ConfigurationService $configurationService,
        CsrfTokenController $csrfTokenController,
        CsrfTokenManager $csrfTokenManager,
        ParameterBagInterface $params,
        SecurityController $securityController
    ) {
        $this->configurationService = $configurationService;
        $this->csrfTokenController = $csrfTokenController;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->params = $params;
        $this->securityController = $securityController;
    }

    /**
     * render form to configure openagenda api
     */
    public function configOpenAgendaHTML(){  
        if (!$this->wiki->UserIsAdmin()){
            return new Response(
                $this->renderInSquelette('@templates/alert-message.twig',[
                    'type' => 'danger',
                    'message' => _t('DENY_READ')
                ]),
                Response::HTTP_UNAUTHORIZED
            );
        }
        // fake page
        $this->wiki->page = [
            'body' => '======'._t('AUJ9_OPEN_AGENDA_CONFIG_TITLE').'======',
            'tag' => 'api'
        ];
        $openAgendaParams = $this->params->get('openAgenda');
        $content = $this->renderInSquelette('@alternativeupdatej9rem/open-agenda-config.twig',[
            'data' => [
                'privateApiKeys' => $openAgendaParams['privateApiKeys'] ?? [],
                'associations' => $openAgendaParams['associations'] ?? [],
                'token' => $this->csrfTokenManager->refreshToken(self::TOKEN_ID)->getValue()
            ]
        ]);
        $this->wiki->page = null;
        return new Response(
            $content,
            Response::HTTP_OK
        );
    }

    /**
     * set a new key
     */
    public function setKey()
    {
        return $this->manageParam(false,false);
    }

    /**
     * remove a key
     */
    public function removeKey()
    {
        return $this->manageParam(false,true);
    }

    /**
     * set a new association
     */
    public function setAssociation()
    {
        return $this->manageParam(true,false);
    }

    /**
     * remove a association
     */
    public function removeAssociation()
    {
        return $this->manageParam(true,true);
    }

    /**
     * manage param
     * @param bool $association
     * @param bool $delete
     */
    protected function manageParam(bool $association, bool $delete)
    {
        $this->csrfTokenController->checkToken(self::TOKEN_ID, 'POST', 'token',false);
        if ($this->securityController->isWikiHibernated()) {
            return new ApiResponse(
                ['error' => _t('WIKI_IN_HIBERNATION'),'hibernate'=> true],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $error = '';
        $params = [];
        if (!$association || !$delete){
            $params['name'] = '/^[A-Za-z][A-Za-z0-9_\-]{2,}$/';
        }
        if ($association){
            $params['id'] = $delete ? '/.+/' : '/^[0-9]+$/';
        }
        if (!$delete){
            $params['value'] = $association
                ? '/^[0-9]{10,}$/'
                : '/^[a-f0-9]{10,}$/';
        }
        foreach($params as $key => $search){
            if (empty($error)){
                if (empty($_POST[$key])){
                    $error = "\$_POST['$key'] should not be empty";
                } elseif (!is_string($_POST[$key])){
                    $error = "\$_POST['$key'] should be a string";
                } elseif (!preg_match($search,$_POST[$key])){
                    $error = "\$_POST['$key'] is badly formatted";
                }
            }
        }
        if (!empty($error)){
            return new ApiResponse(
                ['error' => $error],
                Response::HTTP_BAD_REQUEST
            );
        }

        list('config' => $config,'openAgenda' => $openAgenda) = $this->getOpenAgendaFromConfig();
        $currentParamName = $association ? 'associations' : 'privateApiKeys';
        $keyForParam = $association ? $_POST['id'] : $_POST['name'];
        if (!isset($openAgenda[$currentParamName])){
            $openAgenda[$currentParamName] = [];
        }
        if (!$delete){
            $openAgenda[$currentParamName][$keyForParam] = $association 
                ? [
                    'key' => $_POST['name'],
                    'id' => $_POST['value']
                ]
                : $_POST['value'];
        } elseif (isset($openAgenda[$currentParamName][$keyForParam])){
            unset($openAgenda[$currentParamName][$keyForParam]);
        }
        if (empty($openAgenda[$currentParamName])){
            unset($openAgenda[$currentParamName]);
        }
        $config->openAgenda = $openAgenda;
        $config->write();

        // reload
        list('openAgenda' => $openAgenda) = $this->getOpenAgendaFromConfig();
        
        return new ApiResponse(
            [$currentParamName=>$openAgenda[$currentParamName]],
            (!$delete && empty($openAgenda[$currentParamName])) ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK
        );
    }

    /**
     * get config from wakka.config.php
     * @return array [$openAgenda,$config]
     */
    protected function getOpenAgendaFromConfig(): array
    {
        $config = $this->configurationService->getConfiguration('wakka.config.php');
        $config->load();
        $openAgenda = (isset($config->openAgenda)) ? $config->openAgenda : [];
        return compact(['config','openAgenda']);
    }
}
