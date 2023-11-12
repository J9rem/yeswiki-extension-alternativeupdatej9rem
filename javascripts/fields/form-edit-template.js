/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Feature UUID : auj9-send-mail-selector-field
registerFieldAUJ9rem(getSendMailSelectorField({
  formAndListIds,
  listAndFormUserValues,
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper: templateHelper,
  linkedLabelAUJ9remConf
}))
// Feature UUID : auj9-custom-sendmail
registerFieldAUJ9rem(getCustomSendMailField({
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper: templateHelper,
  linkedLabelAUJ9remConf
}))
// Feature UUID : auj9-video-field
registerFieldAUJ9rem(getUrlField({
  defaultMapping,
  readConf,
  writeconf,
  semanticConf,
  renderHelper: templateHelper
}))