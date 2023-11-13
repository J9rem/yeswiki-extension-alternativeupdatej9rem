/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-subscribe-to-entry
 */

/* data */

let token = null

/* methods */

const closest = (domObj,className) => {
    let current = domObj
    let parent = current.parentNode
    while (parent !== null && parent.tagName !== 'BODY' && !parent.classList.contains(className)) {
        current = parent
        parent = parent.parentNode
    }
    return parent
}

const configureEvent = (event) => {
    event.preventDefault()
    event.stopPropagation()
}

const prepareFormData = (thing) =>{
    let formData = new FormData()
    if (typeof thing == "object"){
        let preForm = toPreFormData(thing)
        for (const key in preForm) {
            formData.append(key,preForm[key])
        }
    }
    return formData
}

const toPreFormData = (thing,key ='') => {
    let type = typeof thing
    switch (type) {
        case 'boolean':
        case 'number':
        case 'string':
            return {
                [key]:thing
            }
        case 'object':
            if (thing === null) {
                return {
                    [key]:null
                }
            } else if (Object.keys(thing).length > 0){
                let result = {}
                for (const propkey in thing) {
                    result = {
                        ...result,
                        ...toPreFormData(
                            thing[propkey],
                            (key.length == 0) ? propkey : `${key}[${propkey}]`
                        )
                    }
                }
                return result
            } else {
                return {
                    [key]: []
                }
            }
        
        case 'array':
            if (thing.length == 0){
                return {
                    [key]: []
                };
            }
            let result = {};
            thing.forEach((val,propkey)=>{
                result = {
                    ...result,
                    ...toPreFormData(
                        val,
                        (key.length == 0) ? propkey : `${key}[${propkey}]`
                    )
                }
            });
            return result
        default:
            return {
                [key]:null
            }
    }
}

/* async methods */


const manageError = (error) => {
    if (window.wiki.isDebugEnabled){
        console.error(error)
    }
    return null
}

const post = async (url,dataToSend) =>{
    return await fetch(url,
        {
            method: 'POST',
            body: new URLSearchParams(prepareFormData(dataToSend)),
            headers: (new Headers()).append('Content-Type','application/x-www-form-urlencoded')
        })
}

const localFetch = async (url,mode = 'get',dataToSend = {}) => {
    let func = (url,mode,dataToSend)=>{
        return (mode == 'get')
            ? fetch(url)
            : post(url,dataToSend)
    }
    return await func(url,mode,dataToSend)
        .then(async (response)=>{
            let json = null
            try {
                json = await response.json()
            } catch (error) {
                throw {
                    'errorMsg': error+''
                }
            }
            if (response.ok && typeof json == 'object'){
                return json
            } else {
                throw {
                    'errorMsg': (typeof json == "object" && 'error' in json) ? json.error : ''
                }
            }
        })
}

const getToken = async () => {
    if (token === null){
        token = await localFetch(window.wiki.url('?api/subscriptions/gettoken'),'post')
            .then((data)=>data?.token ?? null)
            .catch(manageError)
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
        await localFetch(
                window.wiki.url(`?api/subscriptions/${entryId}/toggleregistration/${propertyName}`),
                'post',
                {
                    token:tokenForPost
                }
            )
            .then((data)=>data?.newSate ?? false)
    }
}

/* side effect functions */

window.toogleRegistration = (event,entryId = '',propertyName = '') => {
    configureEvent(event)
    toogleRegistrationForUser(entryId,propertyName)
        .then((registered)=>{
            const group = closest(event.target,'subscription-group')
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
        })
        .catch(manageError)
}