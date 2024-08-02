<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-fix-4-4-3
 * Feature UUID : auj9-fix-4-4-5
 */

namespace YesWiki\Alternativeupdatej9rem\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\BazarField;
use YesWiki\Bazar\Field\EnumField;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FieldFactory;
use YesWiki\Bazar\Service\FormManager as BazarFormManager;
use YesWiki\Core\Service\DbService;
use YesWiki\Security\Controller\SecurityController;
use YesWiki\Wiki;

class FormManager extends BazarFormManager
{
    /**
     * @var bool $cacheValidatedForAll - to check if cache is up to date for getAll
     */
    protected $cacheValidatedForAll;

    public function __construct(
        Wiki $wiki,
        DbService $dbService,
        EntryManager $entryManager,
        FieldFactory $fieldFactory,
        ParameterBagInterface $params,
        SecurityController $securityController
    ) {
        parent::__construct($wiki, $dbService, $entryManager, $fieldFactory, $params, $securityController);
        $this->cacheValidatedForAll = false;
    }

    public function getAll(): array
    {
        if (!$this->cacheValidatedForAll) {
            /**
             * @var array $forms - forms extracted from database
             */
            $forms = $this->dbService->loadAll("SELECT * FROM {$this->dbService->prefixTable('nature')} ORDER BY bn_label_nature ASC");
            foreach ($forms as $form) {
                if (!empty($form['bn_id_nature'])) {
                    // save only not empty formId
                    $formId = $form['bn_id_nature'];
                    $this->cachedForms[$formId] = $this->getFromRawData($form);
                }
            }
            $this->cacheValidatedForAll = true;
        }
        return $this->cachedForms;
    }

    public function create($data)
    {
        // reset cache
        $this->cacheValidatedForAll = false;
        return parent::create($data);
    }

    public function update($data)
    {
        // reset cache
        $this->cacheValidatedForAll = false;
        return parent::update($data);
    }

    public function delete($id)
    {
        // reset cache
        $this->cacheValidatedForAll = false;
        return parent::delete($id);
    }

    /**
     * return field from field name or property name.
     * Feature UUID : auj9-fix-4-4-5
     */
    public function findFieldFromNameOrPropertyName(?string $name, ?string $formId): ?BazarField
    {
        // check params
        if (empty($name) || empty($formId) || strval(intval($formId)) != strval($formId)) {
            return null;
        }

        $form = $this->getOne($formId);
        if (empty($form) || !is_array($form['prepared'])) {
            return null;
        }

        foreach ($form['prepared'] as $field) {
            if (
                in_array($name, [$field->getName(), $field->getPropertyName()])
                || (
                    // ensure backward compatibility for old custom templates and forms
                    $field instanceof EnumField
                        &&
                            strtolower($field->getPropertyName())
                            === strtolower($field->getType() . $field->getLinkedObjectName() . $name)
                )
            ) {
                return $field;
            }
        }

        return null;
    }
}
