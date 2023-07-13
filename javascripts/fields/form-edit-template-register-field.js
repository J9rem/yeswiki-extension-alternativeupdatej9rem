/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

var registerFieldAUJ9rem = function(field){
  window.typeUserAttrs = {
    ...window.typeUserAttrs,
    ...{
      [field.field.name]: field.attributes
    }
  }
  window.templates = {
    ...window.templates,
    ...{
      [field.field.name]: field.renderInput
    }
  }
  window.yesWikiMapping = {
    ...window.yesWikiMapping,
    ...{
      [field.field.name]: field.attributesMapping
    }
  }
  if ('disabledAttributes' in field){
    window.typeUserDisabledAttrs[field.field.name] = field.disabledAttributes
  }
  window.fields.push(field.field)
}

var registerFieldAsModuleAUJ9rem = function(field){
  window.formBuilderFields[field.field.name] = field
}

var linkedLabelAUJ9remConf = {
  label: _t('ALTERNATIVEUPDATE_FIELD_LINKEDLABEL'),
  value: "bf_mail",
  placeholder: "bf_mail",
}

