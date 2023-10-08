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

use YesWiki\Aceditor\Service\ActionsBuilderService as AceditorActionsBuilderService;
use YesWiki\Core\Service\TemplateEngine;
use YesWiki\Wiki;

trait ActionsBuilderServiceCommon
{
    protected $previousData;
    protected $data;
    protected $parentActionsBuilderService;
    protected $renderer;
    protected $wiki;

    public function __construct(TemplateEngine $renderer, Wiki $wiki, $parentActionsBuilderService)
    {
        $this->data = null;
        $this->previousData = null;
        $this->parentActionsBuilderService = $parentActionsBuilderService;
        $this->renderer = $renderer;
        $this->wiki = $wiki;
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
                                'videofieldname' => [
                                    'type' => 'form-field',
                                    'label' => _t('AUJ9_BAZARVIDEO_ACTION_VIDEO_FIELDNAME_LABEL'),
                                    'value' => 'bf_video'
                                ],
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
