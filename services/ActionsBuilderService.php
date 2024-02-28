<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface; // Feature UUID : auj9-bazar-list-send-mail-dynamic
use YesWiki\Aceditor\Service\ActionsBuilderService as AceditorActionsBuilderService;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Wiki;

trait ActionsBuilderServiceCommon
{
    protected $previousData;
    protected $data;
    protected $parentActionsBuilderService;
    protected $params; // Feature UUID : auj9-bazar-list-send-mail-dynamic
    protected $renderer;
    protected $wiki;

    public function __construct(TemplateEngine $renderer, Wiki $wiki, $parentActionsBuilderService)
    {
        $this->data = null;
        $this->previousData = null;
        $this->parentActionsBuilderService = $parentActionsBuilderService;
        $this->renderer = $renderer;
        $this->wiki = $wiki;
        $this->params = $this->wiki->services->get(ParameterBagInterface::class); // Feature UUID : auj9-bazar-list-send-mail-dynamic
    }

    public function setPreviousData(?array $data)
    {
        if (is_null($this->previousData)) {
            $this->previousData = is_array($data) ? $data : [];
            if ($this->parentActionsBuilderService && method_exists($this->parentActionsBuilderService, 'setPreviousData')) {
                $this->parentActionsBuilderService->setPreviousData($data);
            }
        }
    }

    // ---------------------
    // Data for the template
    // ---------------------
    public function getData()
    {
        if (is_null($this->data)) {
            if (!empty($this->parentActionsBuilderService)) {
                $this->data = $this->parentActionsBuilderService->getData();
            } else {
                $this->data = $this->previousData;
            }
            
            /* === Feature UUID : auj9-video-field === */
            if (isset($this->data['action_groups']['video']['actions']['video']['properties'])) {
                $this->data['action_groups']['video']['actions']['video']['properties'] = 
                    array_merge(
                        [
                            'url' => [
                                'label' => 'Url',
                                'type' => 'url',
                                'hint' => 'Remplace serveur et id'
                            ]
                        ],
                        $this->data['action_groups']['video']['actions']['video']['properties']
                    );
                    $this->data['action_groups']['video']['actions']['video']['properties']['id']['required'] = false;
                    $this->data['action_groups']['video']['actions']['video']['properties']['serveur']['advanced'] = true;
                    $this->data['action_groups']['video']['actions']['video']['properties']['id']['advanced'] = true;
                    unset($this->data['action_groups']['video']['actions']['video']['properties']['id']['value']);
            }
            /* === end of Feature UUID : auj9-video-field === */
            
            /* === Feature UUID : auj9-bazar-list-video-dynamic === */
            if (isset($this->data['action_groups']['bazarliste']['actions'])) {
                if (!isset($this->data['action_groups']['bazarliste']['actions']['bazarvideo'])){
                    $this->data['action_groups']['bazarliste']['actions']['bazarvideo'] = [];
                }
                $props = [
                    'template' => [
                        'value' => 'video'
                    ]
                ];
                foreach(($this->data['action_groups']['bazarliste']['actions']['bazarcard']['properties'] ?? []) as $propName => $propDef){
                    if($propName == 'displayfields'){
                        $props[$propName] = [
                            'type' => 'correspondance',
                            'subproperties' => [
                                'imagefieldname' => $propDef['subproperties']['visual'] ?? [],
                                'urlfieldname' => [
                                    'type' => 'form-field',
                                    'label' => _t('AUJ9_BAZARVIDEO_ACTION_VIDEO_LINK_LABEL'),
                                    'value' => 'bf_url'
                                ],
                                'title' => $propDef['subproperties']['title'] ?? [],
                                'subtitle' => $propDef['subproperties']['subtitle'] ?? [],
                                'text' => $propDef['subproperties']['text'] ?? [],
                                'footer' => $propDef['subproperties']['footer'] ?? [],
                                'floating' => $propDef['subproperties']['floating'] ?? [],
                            ]
                        ];
                    } elseif ($propName !== 'template'){
                        $props[$propName] = $propDef;
                    }
                }
                $this->data['action_groups']['bazarliste']['actions']['bazarvideo'] = 
                    array_merge(
                        $this->data['action_groups']['bazarliste']['actions']['bazarvideo'],
                        [
                            'label' => _t('AUJ9_BAZARVIDEO_ACTION_LABEL'),
                            'width' => '35%',
                            'properties' => $props
                        ]
                    );
            }
            /* === end of Feature UUID : auj9-bazar-list-video-dynamic === */
            
            /* === Feature UUID : auj9-bazar-list-send-mail-dynamic === */
            if ($this->params->has('sendMail')){
                $sendMailParam = $this->params->get('sendMail');
                if (!empty($sendMailParam['activated']) && $sendMailParam['activated'] === true){
                    if (isset($this->data['action_groups']['bazarliste']['actions'])
                        && !isset($this->data['action_groups']['bazarliste']['actions']['bazarsendmail'])) {
                        $this->data['action_groups']['bazarliste']['actions']['bazarsendmail'] = [
                            'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_LABEL'),
                            'description' => _t('AUJ9_SEND_MAIL_TEMPLATE_DESCRIPTION'),
                            'width' => '35%',
                            'properties' => [
                                'template' => [
                                    'value' => 'send-mail',
                                ],
                                'title' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_TITLE_LABEL'),
                                    'hint' => _t('AUJ9_SEND_MAIL_TEMPLATE_TITLE_EMPTY_LABEL', ['emptyVal' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_TITLE')]),
                                    'type' => 'text',
                                    'default' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_TITLE'),
                                ],
                                'defaultsendername' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_SENDERNAME_LABEL'),
                                    'type' => 'text',
                                    'default' => _t('AUJ9_SEND_MAIL_TEMPLATE_SENDERNAME'),
                                ],
                                'defaultsubject' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULT_SUBJECT_LABEL'),
                                    'type' => 'text',
                                    'default' => '',
                                ],
                                'emailfieldname' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_EMAILFIELDNAME_LABEL'),
                                    'type' => 'form-field',
                                    'default' => 'bf_mail',
                                ],
                                'defaultcontent' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULTCONTENT_LABEL'),
                                    'type' => 'text',
                                    'default' => _t('AUJ9_SEND_MAIL_TEMPLATE_DEFAULTCONTENT'),
                                ],
                                'sendtogroupdefault' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_SENDTOGROUPDEFAULT_LABEL'),
                                    'type' => 'checkbox',
                                    'default' => 'false'
                                ],
                                'groupinhiddencopydefault' => [
                                    'label' => _t('AUJ9_SEND_MAIL_TEMPLATE_GROUP_IN_HIDDIN_COPY_LABEL'),
                                    'type' => 'checkbox',
                                    'default' => 'true',
                                    'showif' => [
                                        'sendtogroupdefault' => false
                                    ]
                                ]
                            ],
                        ];
                    }
                    
                    if (isset($this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor'])
                        && !in_array('bazarsendmail', $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor'])) {
                        $this->data['action_groups']['bazarliste']['actions']['commons']['properties']['showexportbuttons']['showExceptFor'][] =  'bazarsendmail';
                    }
                    $this->wiki->AddJavascriptFile('tools/alternativeupdatej9rem/javascripts/actions-builder-post-update.js',true);
                }
            }
            /* === end of Feature UUID : auj9-bazar-list-send-mail-dynamic === */
            
            /* === Feature UUID : auj9-breadcrumbs-action === */
            if (isset($this->data['action_groups']['advanced-actions']['actions'])) {
                $this->data['action_groups']['advanced-actions']['actions']['breadcrumbs'] = [
                    'label' => _t('AUJ9_BREADCRUMBS_LABEL'),
                    'properties' => [
                        'separator' => [
                            'label' => _t('AUJ9_BREADCRUMBS_SEPARATOR_LABEL'),
                            'hint' => _t('AUJ9_BREADCRUMBS_SEPARATOR_HINT'),
                            'type' => 'text',
                            'required' => true,
                            'default' => 'span.breadcrumbs-item:i.fas.fa-chevron-right::i:span'
                        ],
                        'displaydropdown' => [
                            'label' => _t('AUJ9_BREADCRUMBS_DISPLAY_DROPDOWN_LABEL'),
                            'advanced' => true,
                            'type' => 'checkbox',
                            'default' => 'true',
                            'checkedvalue' => 'true',
                            'uncheckedvalue' => 'false'
                        ],
                        'displaydropdownonlyforlast' => [
                            'label' => _t('AUJ9_BREADCRUMBS_DISPLAY_DROPDOWN_ONLY_FOR_LAST_LABEL'),
                            'advanced' => true,
                            'type' => 'checkbox',
                            'default' => 'true',
                            'checkedvalue' => 'true',
                            'uncheckedvalue' => 'false',
                            'showif' => 'displaydropdown'
                        ],
                        'displaydropdownforchildrenoflastlevel' => [
                            'label' => _t('AUJ9_BREADCRUMBS_DISPLAY_DROPDOWN_FOR_CHILDREN_OF_LAST_LEVEL_LABEL'),
                            'advanced' => true,
                            'type' => 'checkbox',
                            'default' => 'false',
                            'checkedvalue' => 'true',
                            'uncheckedvalue' => 'false',
                            'showif' => 'displaydropdown'
                        ],
                        'page' => [
                            'label' => _t('AUJ9_BREADCRUMBS_PAGE_LABEL'),
                            'advanced' => true,
                            'type' => 'page-list',
                            'default' => 'PageMenuHaut'
                        ]
                    ]
                ];
            }
            /* === end of Feature UUID : auj9-breadcrumbs-action === */
        }
        return $this->data;
    }
}

if (class_exists(AceditorActionsBuilderService::class, false)) {
    class ActionsBuilderService extends AceditorActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
} else {
    class ActionsBuilderService
    {
        use ActionsBuilderServiceCommon;
    }
}
