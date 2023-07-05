<?php

namespace YesWiki\Alternativeupdatej9rem\Service;

use URLify;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Bazar\Field\TabsField;
use YesWiki\Templates\Service\TabsService as RealTabsService;

trait TabsServiceDef
{
    protected $states;

    public function __construct()
    {
        parent::__construct();
        $this->states = [];
    }

    /**
     * save current state and return associated index
     * useful for LinkedEntryField to prevent interference with other rendering
     * @return int index
     */
    public function saveState(): int
    {
        $this->states[] = [
            'data' => $this->data,
            'stack' => $this->stack,
            'usedSlugs' => $this->usedSlugs,
            'nextPrefix' => $this->nextPrefix
        ];
        return count($this->states) - 1;
    }

    /**
     * reset current state from associated index and return success
     * useful for LinkedEntryField to prevent interference with other rendering
     * @param int $index
     * @return bool
     */
    public function resetState(int $index): bool
    {
        if (array_key_exists($index,$this->states)){
            $this->data = $this->states[$index]['data'];
            $this->stack = $this->states[$index]['stack'];
            $this->usedSlugs = $this->states[$index]['usedSlugs'];
            $this->nextPrefix = $this->states[$index]['nextPrefix'];
            return true;
        } else {
            return false;
        }
    }
}

if (file_exists('tools/templates/services/TabsService.php')){
    class TabsService extends RealTabsService
    {
        use TabsServiceDef;
    }
} else {
    class TabsService
    {
    }   
}
