/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function getSendMailSelectorField({
  formAndListIds,
  listAndFormUserValues,
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper,
  linkedLabelAUJ9remConf
}){

    return {
        field: {
          label: _t('ALTERNATIVEUPDATE_SENDMAIL_SELECTOR_LABEL'),
          name: "sendmailselector",
          attrs: { type: "sendmailselector" },
          icon: '<i class="fas fa-at"></i>',
        },
        attributes: {
          subtype2: {
              label: "Origine des données",
              options: {
                list: "Une liste",
                form: "Un Formulaire Bazar",
              },
          },
          listeOrFormId: {
              label: "Choix de la liste/du formulaire",
              options: {
              ...{ "": "" },
              ...formAndListIds.lists,
              ...formAndListIds.forms,
              ...listAndFormUserValues,
              },
          },
          linkedLabel: linkedLabelAUJ9remConf,
          linkedLabelInForm:{
              label: "Champ e-mail dans la fiche du formulaire lié (si formulaire)",
              value: "bf_mail",
          },
          replace_email_by_button: {
            label: "Remplacer l'email par un bouton contact",
            options: { "": "Non", form: "Oui" },
          },
          defaultValue: {
              label: "Valeur par défaut",
              value: ""
          },
          hint: { label: "Texte d'aide", value:"" },
          read: readConf,
          write: writeconf,
          semantic: semanticConf,
        },
        advancedAttributes: ['read', 'write', 'semantic', 'hint','defaultValue','name'],
        disabledAttributes: ['value'],
        attributesMapping: {
          ...defaultMapping,
          ...{
            1: "listeOrFormId",
            3:"linkedLabel",
            4:"linkedLabelInForm",
            5: "defaultValue",
            6: "name",
            7:"subtype2",
            9:"replace_email_by_button"
          }
        },
        renderInput(field) {
          let options = '';
          for (let index = 1; index < 4; index++) {
            options += `
            <option 
              value="option-${index}" 
              ${index == 1 ? ' selected="true" ': ''}
              id="${field.name}-preview-${index}"
              >
              Option ${index}
            </option>`
          }
          return {
            field: `
              <select name="${field.name}-preview" id="${field.name}-preview">
                ${options}
              </select>
              `,
            onRender() {
              renderHelper.prependHint(field, _t('ALTERNATIVEUPDATE_FIELD_FORM'))
              renderHelper.defineLabelHintForGroup(field, 'linkedLabel', _t('ALTERNATIVEUPDATE_FIELD_LINKEDLABEL_HINT'))
            }
          }
        },
    }
}