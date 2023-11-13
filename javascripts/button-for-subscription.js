/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */

import asyncHelper from './asyncHelper.js'

/* data */

let token = null
let contactingApi = false

/* methods */

const closest = (domObj,className) => {
    let current = domObj
    let parent = current.parentNode
    while (parent !== null && parent.tagName !== 'BODY' && !parent.classList.contains(className)) {
        current = parent
        parent = parent.parentNode
    }
    return parent.tagName === 'BODY' ? null : parent
}

const configureEvent = (event) => {
    event.preventDefault()
    event.stopPropagation()
}

/* async methods */

const getToken = async () => {
    if (token === null){
        contactingApi = true
        token = await asyncHelper.fetch(window.wiki.url('?api/subscriptions/gettoken'),'post')
            .then((data)=>data?.token ?? null)
            .finally(()=>{
                contactingApi = false
            })
            .catch(asyncHelper.manageError)
    }
    return token
}

const toogleRegistrationForUser = async (entryId,propertyName) => {
    const tokenForPost = await getToken()
    if (tokenForPost !== null){
        if (typeof entryId !== 'string'){
            throw new Error('entryId should be a string !')
        }
        if (typeof propertyName !== 'string'){
            throw new Error('propertyName should be a string !')
        }
        contactingApi = true
        return await asyncHelper.fetch(
                window.wiki.url(`?api/subscriptions/${entryId}/toggleregistration/${propertyName}`),
                'post',
                {
                    token:tokenForPost
                }
            )
            .then((data)=>{
                return {
                    registered: data?.newState === true,
                    errorMsg: data?.errorMsg ?? '',
                    isError: data?.isError === true,
                    nb: data?.nb ?? [],
                    thereIsAvailablePlace: data?.thereIsAvailablePlace === true
                }
            })
            .finally(()=>{
                contactingApi = false
            })
    }
}

/* side effect functions */

window.toogleRegistration = (event,entryId = '',propertyName = '') => {
    configureEvent(event)
    if (contactingApi){
        return null
    }
    const btn = event.target.classList.contains('btn') ? event.target : closest(event.target,'btn')
    btn?.setAttribute('disabled','disabled')
    toogleRegistrationForUser(entryId,propertyName)
        .then(({registered,isError,errorMsg,nb,thereIsAvailablePlace})=>{
            if (isError){
                throw new Error(errorMsg)
            }
            const group = closest(event.target,'subscription-group')
            if (!group){
                return null
            }
            if (registered){
                if (group.classList.contains('not-registered')){
                    group.classList.remove('not-registered')
                    group.classList.add('registered')
                }
            } else {
                if (group.classList.contains('registered')){
                    group.classList.remove('registered')
                    group.classList.add('not-registered')
                }
            }
            if (thereIsAvailablePlace){
                if (group.classList.contains('no-place')){
                    group.classList.remove('no-place')
                }
            } else {
                if (!group.classList.contains('no-place')){
                    group.classList.add('no-place')
                }
            }
            if (nb?.length == 2 && nb?.[0]?.length > 0 && nb?.[1]?.length > 0){
                const spanForNB = group.parentNode.querySelector(`[data-id=${nb[0]}] > span.BAZ_texte`)
                if (spanForNB?.innerText){
                    spanForNB.innerText = nb[1]
                }
            }
        })
        .finally(()=>{
            btn?.removeAttribute('disabled')
        })
        .catch(asyncHelper.manageError)
}