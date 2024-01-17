<?php

/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-breadcrumbs-action
 */


namespace YesWiki\Alternativeupdatej9rem;

use Exception;
use Throwable;
use YesWiki\Alternativeupdatej9rem\Service\DomService;
use YesWiki\Core\Service\PageManager;
use YesWiki\Core\YesWikiAction;

/**
 * display a breadcrumb based on page menu
 */
class BreadcrumbsAction extends YesWikiAction
{
    protected $domService;
    protected $pageManager;

    public function formatArguments($arg)
    {
        $separator = (
                !empty($arg['separator'])
                && is_string($arg['separator'])
            )
            ? $arg['separator']
            : 'span.breadcrumbs-item:i.fas.fa-chevron-right::i:span';
        $matches = [];
        $availabletags = '(?:b|span|i)';
        for ($i=0; $i < 10 && preg_match("/($availabletags)((?:\.[A-Aa-z0-9_\-]+)+):(.*):$availabletags/",$separator,$matches); $i++) { 
            $classes = array_filter(explode('.',$matches[2]));
            $classRaw = empty($classes)  ? '' : ' class="'.implode(' ',$classes).'"';
            $separator = str_replace(
                $matches[0],
                <<<HTML
                <{$matches[1]}{$classRaw}>{$matches[3]}</{$matches[1]}>
                HTML,
                $separator
            );
            $matches = [];
        }
        return [
            'page' => (
                    !empty($arg['page'])
                    && is_string($arg['page'])
                )
                ? $arg['page']
                : 'PageMenuHaut',
            'separator' => $separator,
            'displaydropdown' => $this->formatBoolean($arg,true,'displaydropdown'),
            'displaydropdownonlyforlast' => $this->formatBoolean($arg,true,'displaydropdownonlyforlast'),
        ];
    }
    public function run()
    {
        // getServices
        $this->domService = $this->getService(DomService::class);
        $this->pageManager = $this->getService(PageManager::class);

        $page = $this->pageManager->getOne($this->arguments['page']);
        if (empty($page)){
            return '';
        }
        $formattedPage = $this->wiki->format("{{include page=\"{$this->arguments['page']}\"}}");
        $formattedPage = $this->domService->outputConvertEncoding($formattedPage);
        $output = $formattedPage;
        try {
            $domXpath = $this->domService->loadDomXpath($formattedPage);
            $tree = $this->domService->buildTreeFomUlLiContent($domXpath);
            $rootTag = $this->params->get('root_page');
            $tree = [
                'text' => '<i class="fas fa-home"></i>',
                'tag' => $rootTag,
                'link' => '',
                'children' => $tree
            ];
            $currentTag = explode('/',$_GET['wiki'] ?? '',2)[0] ?? $rootTag;
            $path = $this->getCurrentPath($currentTag,$tree);
            $output = $this->render('@alternativeupdatej9rem/breadcrumbs-action.twig',[
                'path' => $path,
                'separator' => $this->arguments['separator'],
                'displaydropdown' => $this->arguments['displaydropdown'],
                'displaydropdownonlyforlast' => $this->arguments['displaydropdownonlyforlast']
            ]);
        } catch (Throwable $th) {
            // do nothing;
            throw new Exception("Error BreadcrumbsAction {$th->getMessage()}", 1, $th);
        }

        return $output;
    }

    /**
     * get path from tree
     * @param string $currentTag
     * @param array $tree
     * @return array [['text'=> string, 'tag' => string, 'link' => string],...] top parent, then next
     */
    protected function getCurrentPath(string $currentTag,array $tree): array
    {
        $path = [];
        if (!$this->getCurrentPathRecursive($currentTag,$tree,$path)){
            // default home
            $path[] = $tree;
            $path[] = [
                'tag' => $currentTag,
                'text' => $currentTag,
                'link' => '',
            ];
        }
        return $path;
    }

    /**
     * get path from tree
     * @param string $currentTag
     * @param array $tree
     * @param array &$path [['text'=> string, 'tag' => string, 'link' => string],...] top parent, then next
     * @return bool $found
     */
    protected function getCurrentPathRecursive(string $currentTag,array $tree, array &$path): bool
    {
        if (strtolower($tree['tag']) === strtolower($currentTag)){
            $path[] = $tree;
            return true;
        }
        if (!empty($tree['children'])){
            foreach ($tree['children'] as $childTree) {
                if ($this->getCurrentPathRecursive($currentTag,$childTree,$path)){
                    array_unshift($path,$tree);
                    return true;
                }
            }
        }
        return false;
    }
}
