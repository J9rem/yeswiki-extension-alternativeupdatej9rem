/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */
import { readConf, writeconf, semanticConf  } from '../../../bazar/presentation/javascripts/form-edit-template/fields/commons/attributes.js'

export default {
  field: {
    label: _t('AUJ9_NB_SUBSCRIPTION'),
    name: 'nbsubscription',
    attrs: { type: 'nbsubscription' },
    icon: '<i class="fas fa-users"></i>'
  },
  attributes: {
    read: readConf,
    write: writeconf,
    semantic: semanticConf,
  },
  advancedAttributes: ['write', 'semantic'],
  disabledAttributes: ['value','required'],
  renderInput() {
    return { field: `<input type="text" value=""/>` }
  }
}