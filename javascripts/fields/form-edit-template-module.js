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

registerFieldAsModuleAUJ9rem(getSendMailSelectorField({
    formAndListIds,
    listAndFormUserValues,
    defaultMapping,
    readConf,
    writeconf,
    semanticConf
  }))
