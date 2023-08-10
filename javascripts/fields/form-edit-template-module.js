/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// formAndListIds are defined in forms_form.twig

import {
    listAndFormUserValues,
    defaultMapping,
    readConf,
    writeconf,
    semanticConf
} from '../../../bazar/presentation/javascripts/form-edit-template/fields/commons/attributes.js'
import renderHelper from '../../../bazar/presentation/javascripts/form-edit-template/fields/commons/render-helper.js'

registerFieldAsModuleAUJ9rem(getSendMailSelectorField({
    formAndListIds,
    listAndFormUserValues,
    defaultMapping,
    readConf,
    writeconf,
    semanticConf,
    renderHelper,
    linkedLabelAUJ9remConf
  }))
registerFieldAsModuleAUJ9rem(getCustomSendMailField({
    defaultMapping,
    readConf,
    writeconf,
    semanticConf,
    renderHelper,
    linkedLabelAUJ9remConf
}))
registerFieldAsModuleAUJ9rem(getVideoField({
    defaultMapping,
    readConf,
    writeconf,
    semanticConf,
    renderHelper,
    linkedLabelAUJ9remConf
}))

