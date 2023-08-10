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
  renderHelper,
  linkedLabelAUJ9remConf
}){
  return {
      field: {
        label: _t('ALTERNATIVEUPDATE_VIDEO_LABEL'),
        name: "video",
        attrs: { type: "video" },
        icon: '<i class="fas fa-video"></i>',
      },
      attributes: {
        linkedLabel: linkedLabelAUJ9remConf,
        askForCurrentSave: {
          label: _t('ALTERNATIVEUPDATE_VIDEO_ASK_FOR_CURRENT_SAVE'),
          options: {
            "": _t('NO'),
            yes: _t('YES')
          }
        },
        labelForOption: {
          label: _t('ALTERNATIVEUPDATE_VIDEO_LABEL_FOR_OPTIONS'),
          value: _t('ALTERNATIVEUPDATE_VIDEO_LABEL_FOR_OPTIONS_VALUE')
        },
        read: readConf,
        write: writeconf,
        semantic: semanticConf
      },
      attributesMapping: {
        ...defaultMapping,
        ...{
          3:"linkedLabel",
          4:"askForCurrentSave",
          7: "labelForOption"
        }
      },
      advancedAttributes: ['read', 'write', 'semantic', 'hint', 'name', 'value'],
      renderInput(field) {
        return {
     
            onRender() {
              renderHelper.prependHint(field, _t('ALTERNATIVEUPDATE_FIELD_FORM')
                +'<br/>'+_t('ALTERNATIVEUPDATE_VIDEO_HINT'))
              renderHelper.defineLabelHintForGroup(field, 'linkedLabel', _t('ALTERNATIVEUPDATE_FIELD_LINKEDLABEL_HINT'))
              renderHelper.defineLabelHintForGroup(field, 'askForCurrentSave', _t('ALTERNATIVEUPDATE_VIDEO_ASK_FOR_CURRENT_SAVE_HINT'))
            }
        }
      },
  }
}