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
        ratio:{
          label: _t('ALTERNATIVEUPDATE_VIDEO_RATIO_LABEL'),
          options: {'':'16/9','4par3':'4/3'},
        },
        maxwidth:{
          label: _t('ALTERNATIVEUPDATE_VIDEO_MAXWIDTH_LABEL'),
          value: ''
        },
        maxheight:{
          label: _t('ALTERNATIVEUPDATE_VIDEO_MAXHEIGHT_LABEL'),
          value: ''
        },
        class:{
          label: _t('ALTERNATIVEUPDATE_VIDEO_POSITION_LABEL'),
          options: {
            '': 'standard',
            'pull-left': _t('ALTERNATIVEUPDATE_VIDEO_POSITION_LEFT'),
            'pull-right': _t('ALTERNATIVEUPDATE_VIDEO_POSITION_RIGHT'),
          }
        },
        read: readConf,
        write: writeconf,
        semantic: semanticConf
      },
      attributesMapping: {
        ...defaultMapping,
        ...{
          3:'ratio',
          4:'maxwidth',
          6:'maxheight',
          7:'class'
        }
      },
      advancedAttributes: ['read', 'write', 'semantic', 'hint', 'value','ratio','maxwidth','maxheight','class'],
      renderInput(field) {
        return {
     
            field: '<input type="text" disabled value="https://framatube.org/w/pAQiVCgv2CsLg79KKXUoMw"/>', 

            onRender() {
              renderHelper.prependHint(field, _t('ALTERNATIVEUPDATE_FIELD_FORM'))
              renderHelper.defineLabelHintForGroup(field, 'maxwidth', _t('ALTERNATIVEUPDATE_VIDEO_MAX_HINT'))
              renderHelper.defineLabelHintForGroup(field, 'maxheight', _t('ALTERNATIVEUPDATE_VIDEO_MAX_HINT'))
            }
        }
      },
  }
}