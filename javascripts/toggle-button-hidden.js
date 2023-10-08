/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-choice-display-hidden-field
 */

const toggleButtonToHideField = (event) => {
    const target = event.target
    event.preventDefault()
    event.stopPropagation()
    const form = $(target).closest('form')
    const page = $(target).closest('.BAZ_cadre_fiche')
    let base = null
    if (page && page.length > 0){
        base = page
    } else if (form && form.length > 0){
        base = form
    }
    if (base !== null){
        if ($(base).hasClass('force-show')){
            $(base).removeClass('force-show')
        } else {
            $(base).addClass('force-show')
        }
    }
}