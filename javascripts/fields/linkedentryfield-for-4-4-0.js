/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function (){
  if ('yesWikiMapping' in window
    && 'listefichesliees' in window.yesWikiMapping
    && 'defaultMapping' in window){
    window.yesWikiMapping.listefichesliees = {
      ...window.defaultMapping,
      ...{
        0: 'type',
        1: 'id',
        2: 'query',
        3: 'param',
        4: 'number',
        5: 'template',
        6: 'type_link',
        7: 'customLabel'
      }
    }
  }
  if ('typeUserDisabledAttrs' in window){
    window.typeUserDisabledAttrs.listefichesliees = ['required', 'value', 'name', 'label']
  }
  if ('typeUserAttrs' in window
    && 'listefichesliees' in window.typeUserAttrs){
    window.typeUserAttrs.listefichesliees = {
      ...{customLabel:{label:_t('BAZ_FORM_EDIT_NAME'),value:""}},
      ...window.typeUserAttrs.listefichesliees
    }
  }
})()