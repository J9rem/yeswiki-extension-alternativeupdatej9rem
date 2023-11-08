/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */
import {
  listAndFormUserValues,
  readConf,
  writeconf,
  semanticConf,
  listsMapping
} from '../../../bazar/presentation/javascripts/form-edit-template/fields/commons/attributes.js'

export default {
  field: {
    label: _t('AUJ9_SUBSCRIBE'),
    name: 'subscribe',
    attrs: { type: "subscribe" },
    icon: '<i class="fas fa-user-plus"></i>'
  },
  // Define an entire group of fields to be added to the stage at a time.
  set: {
    label: _t('AUJ9_SUBSCRIBE'),
    name: 'subscribe',
    icon: '<i class="fas fa-user-plus"></i>',
    fields: [
      {
        type: 'nbsubscription',
        label: _t('AUJ9_NB_SUBSCRIPTION')
      },
      {
        type: 'subscribe',
        label: _t('AUJ9_SUBSCRIBE')
      }
    ]
  },
  attributes: {
    typesubscription:{
      label: _t('AUJ9_SUBSCRIBE_TYPE'),
      options: {
        ' ': _t('AUJ9_SUBSCRIBE_TYPE_USER'),
        'entry': _t('AUJ9_SUBSCRIBE_TYPE_ENTRY')
      }
    },
    form:{
      label: _t('BAZ_FORM_EDIT_LISTEFICHES_FORMID_LABEL'),
      options: {
        ...{ '': '' },
        ...formAndListIds.forms,
        ...listAndFormUserValues
      }
    },
    showlist: {
      label: _t('AUJ9_SUBSCRIBE_SHOWLIST'),
      options: {
        ' ': _t('YES'),
        'no': _t('NO')
      }
    },
    defaultValue: {
      label: _t('BAZ_FORM_EDIT_SELECT_DEFAULT'),
      value: ''
    },
    hint: { label: _t('BAZ_FORM_EDIT_HELP'), value: '' },
    read: readConf,
    write: writeconf,
    semantic: semanticConf
  },
  advancedAttributes: ['read', 'write', 'semantic', 'hint', 'defaultValue'],
  disabledAttributes: ['value'],
  attributesMapping: { ...listsMapping, ...{ 1: 'form', 4: 'showlist', 7: 'typesubscription' } },
  renderInput() {
    return {
      field: `<span>Liste des inscrits <i class="fas fa-angle-down"></i></span> <button class="btn btn-xs btn-success" disabled><i class="fas fa-user-plus"></i> ${_t('AUJ9_SUBSCRIBE')}</button>`,
    }
  }
}
