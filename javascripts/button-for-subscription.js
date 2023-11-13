/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */

const configureEvent = (event) => {
    event.preventDefault()
    event.stopPropagation()
}

window.subsriptionRegister = (event,entryId = '') => {
    configureEvent(event)
    console.log({entryId})
}
window.subsriptionUnregister = (event,entryId = '') => {
    configureEvent(event)
    console.log({entryId})
}