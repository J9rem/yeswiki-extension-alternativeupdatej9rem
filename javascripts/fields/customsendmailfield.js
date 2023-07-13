/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function getCustomSendMailField({
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper,
  linkedLabelAUJ9remConf
}){
  return {
      field: {
        label: _t('ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL'),
        name: "customsendmail",
        attrs: { type: "customsendmail" },
        icon: '<i class="fas fa-at"></i>',
      },
      attributes: {
        linkedLabel: linkedLabelAUJ9remConf,
        askForCurrentSave: {
          label: _t('ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_ASK_FOR_CURRENT_SAVE'),
          options: {
            "": _t('NO'),
            yes: _t('YES')
          }
        },
        labelForOption: {
          label: _t('ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL_FOR_OPTIONS'),
          value: _t('ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_LABEL_FOR_OPTIONS_VALUE')
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
          field: `
            <span>Send an e-mail when registering</span>
            <select name="${field.name}-preview" id="${field.name}-preview">
              <option 
                value="option-yes" 
                selected="selected"
                id="${field.name}-preview-yes"
                >
                ${_t('YES')}
              </option>
              <option 
                value="option-no" 
                id="${field.name}-preview-no"
                >
                ${_t('NO')}
              </option>
            </select>
            `,
            onRender() {
              renderHelper.prependHint(field, _t('ALTERNATIVEUPDATE_FIELD_FORM'))
              renderHelper.defineLabelHintForGroup(field, 'linkedLabel', _t('ALTERNATIVEUPDATE_FIELD_LINKEDLABEL_HINT'))
              renderHelper.defineLabelHintForGroup(field, 'askForCurrentSave', _t('ALTERNATIVEUPDATE_CUSTOM_SENDMAIL_ASK_FOR_CURRENT_SAVE_HINT'))
            }
        }
      },
  }
}