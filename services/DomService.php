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

namespace YesWiki\Alternativeupdatej9rem\Service;

use DOMDocument;
use DOMElement;
use DOMXpath;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use YesWiki\Wiki;

class DomService
{
    protected $params;
    protected $wiki;

    public function __construct(
        ParameterBagInterface $params,
        Wiki $wiki
    ) {
        $this->params = $params;
        $this->wiki = $wiki;
    }

    /**
     * convert output encoding to manage right format for DOM analysis
     * @param string $input
     * @return string $output
     */
    public function outputConvertEncoding(string $input): string
    {
        if (YW_CHARSET != 'ISO-8859-1' && YW_CHARSET != 'ISO-8859-15') {
            // tip to replace mb_convert_encoding($plugin_output_new, 'HTML-ENTITIES', 'UTF-8')
            // from https://stackoverflow.com/questions/37215388/what-is-a-replacement-for-mb-convert-encodingstring-utf-8-html-entities
            $output = preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($m) {
                $char = current($m);
                $utf = iconv('UTF-8', 'UCS-4', $char);
                return sprintf("&#x%s;", ltrim(strtoupper(bin2hex($utf)), "0"));
            }, $input);
        } else {
            $output = $input;
        }
        return $output;
    }

    /**
     * load dom and xpath object
     * @param string $input
     * @return array [DOMDocument $dom,DOMXpath $xpath]
     */
    public function loadDomXpath(string $input): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($input);
        $xpath = new DOMXpath($dom);
        return compact(['dom','xpath']);
    }

    /**
     * convert div ul li ul to dropdowns
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     */
    public function convertDivUlLiULToDropdown(array $domXpath)
    {
        $dropdowns = ($domXpath['xpath'])->query('*/div/ul/li/ul');
        if (!is_null($dropdowns)) {
            foreach ($dropdowns as $element) {
                $element->setAttribute('class', 'dropdown-menu');
                $element->parentNode->setAttribute('class', 'dropdown');
            }
        }
    }

    /**
     * extract nodes of dropdown list
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     * @return array [$nodesForFirstDropdown,$nodesForSecondDropdown]...
     */
    public function extractNodesOfDropdownList(array $domXpath):array
    {
        $dropdownslist = ($domXpath['xpath'])->query('*/div/ul//li/ul/..');
        $results = [];
        if (!is_null($dropdownslist)) {
            foreach ($dropdownslist as $element) {
                $results[] = $element;
            }
        }
        return $results;
    }

    /**
     * update dropdown nodes content
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     * @param array $nodesList
     */
    public function updateNodesContent(array $domXpath, array $nodesList)
    {
        foreach($nodesList as $element) {
            $nodes = $element->childNodes;
            foreach ($nodes as $node) {
                // we search for #text child or a link, if we accessed the dropdown menu, we break
                if ($node->nodeName == 'ul') {
                    break;
                }

                // we add trigger for dropdown
                if ($node->nodeName == 'a') {
                    $class = $node->getAttribute('class');
                    $node->setAttribute('class', $class.' dropdown-toggle');
                    $node->setAttribute('data-toggle', 'dropdown');
                    $caret = ($domXpath['dom'])->createElement('b');
                    $caret->setAttribute('class', 'caret');
                    $node->appendChild($caret);
                } elseif ($node->nodeName == '#text' && !trim($node->nodeValue) == '') {
                    // check if <a exists or must be created
                    $a = ($domXpath['dom'])->createElement('a');
                    $a->setAttribute('class', 'dropdown-toggle');
                    $a->setAttribute('data-toggle', 'dropdown');
                    $a->setAttribute('href', '#');
                    $a->nodeValue = trim($node->nodeValue);
                    $node->nodeValue = '';
                    $caret = ($domXpath['dom'])->createElement('b');
                    $caret->setAttribute('class', 'caret');
                    $a->appendChild($caret);
                    $node->parentNode->insertBefore($a, $node);
                }
            }
        }
    }

    
    /**
     * add class to parent of active link
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     */
    public function addClassToParentOfActiveLink(array $domXpath)
    {
        $activelinks = ($domXpath['xpath'])->query("//a[contains(@class, 'active-link')]");
        if (!is_null($activelinks)) {
            foreach ($activelinks as $activelink) {
                $class = $activelink->parentNode->getAttribute('class');
                $activelink->parentNode->setAttribute('class', $class.' active');
            }
        }
    }

    /**
     * get formatted output
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     * @return string
     */
    public function getFormattedOutput(array $domXpath):string
    {
        return preg_replace(
            '/^<!DOCTYPE.+?>/',
            '',
            str_replace(
                array('<html>', '</html>', '<body>', '</body>'),
                '',
                ($domXpath['dom'])->saveHTML()
            )
        )."\n";
    }

    /**
     * build tree from ul/li content
     * @param array $domXpath [DOMDocument $dom,DOMXpath $xpath]
     * @return array [['text'=>string,'tag'=>string,'link'=> string,'children'=>array]]
     */
    public function buildTreeFomUlLiContent(array $domXpath): array
    {
        $tree = [];
        $mainLi = ($domXpath['xpath'])->query('/html/body/div/ul/li');
        foreach ($mainLi as $element) {
            $data = $this->convertElementToTreeRecursive($element, $domXpath);
            if (!empty($data)) {
                $tree[] = $data;
            }
        }
        return $tree;
    }

    /**
     * convert element to tree
     * @param DOMElement $element
     * @param array $domXpath
     * @return array [['text'=>string,'tag'=>string,'link'=> string,'children'=>array]]
     */
    protected function convertElementToTreeRecursive(DOMElement $element, array $domXpath): array
    {
        $link = null;
        $text = '';
        $tag = '';
        $children = [];
        foreach ($element->childNodes as $node) {
            // save link if needed
            if (empty($link) && $node->nodeName === 'a') {
                $data = $this->convertLinkToTextAndTag($node);
                if (!empty($data['text'])) {
                    list('tag'=>$tag, 'text'=>$text, 'link'=>$link) = $data;
                }
            }
            // save txt if needed
            if (empty($text) && empty($link)) {
                $nodeTxt = trim($node->textContent);
                if (!empty($nodeTxt)) {
                    $text = $nodeTxt;
                }
            }
            // save children from ul if needed
            if (empty($children) && !empty($link) && $node->nodeName === 'ul') {
                if ($node->className === 'fake-ul') {
                    $nodePath =$node->getNodePath();
                    if ($nodePath) {
                        $childNodes = ($domXpath['xpath'])->query("$nodePath/li/div/div/ul/li");
                    }
                } else {
                    $childNodes = $node->childNodes;
                }
                foreach ($childNodes as $childNode) {
                    if ($childNode->nodeName === 'li') {
                        $childData = $this->convertElementToTreeRecursive($childNode, $domXpath);
                        if (!empty($childData)) {
                            $children[] = $childData;
                        }
                    }
                }
            }
        }
        return (empty($tag) && empty($children) && empty($link)) ? [] : compact(['text','tag','children','link']);
    }

    /**
     * convert link to title and tag or link
     * @param DOMElement $link
     * @return array ['text'=>string,'tag'=>string, 'link'=>string]
     */
    protected function convertLinkToTextAndTag(DOMElement $linkElement): array
    {
        $text = trim($linkElement->textContent);
        $tag = '';
        $link = '';
        $href = $linkElement->getAttribute('href');
        if (!empty($href)) {
            $link = $href;
            $quotedBaseUrl = preg_quote($this->params->get('base_url'), '/');
            $match = [];
            if (preg_match("/^$quotedBaseUrl(.+)$/", $href, $match)) {
                $extractedLink = $this->wiki->extractLinkParts($match[1]);
                $tag = $extractedLink['tag'] ?? '';
            }
        } else {
            $link = '#';
        }
        return compact(['text','tag','link']);
    }
}
