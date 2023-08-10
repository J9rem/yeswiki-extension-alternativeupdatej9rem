/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function getVideoField({
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper
}){
  return {
      field: {
        label: _t('ALTERNATIVEUPDATE_VIDEO_LABEL'),
        name: "video",
        attrs: { type: "video" },
        icon: '<i class="fas fa-video"></i>',
      },
      attributes: {
        read: readConf,
        write: writeconf,
        semantic: semanticConf
      },
      attributesMapping: defaultMapping,

      advancedAttributes: ['read', 'write', 'semantic', 'hint', 'value'],
      renderInput(field) {
        return {
     
            field: '<input type="text" disabled value="https://framatube.org/w/pAQiVCgv2CsLg79KKXUoMw"/>', 

            onRender() {
              renderHelper.prependHint(field, _t('ALTERNATIVEUPDATE_FIELD_FORM'))
            }
        }
      },
  }
}