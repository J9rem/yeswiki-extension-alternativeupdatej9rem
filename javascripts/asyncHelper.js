/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-open-agenda-connect
 * Feature UUID : auj9-bazar-list-send-mail-dynamic
 * Feature UUID : auj9-subscribe-to-entry
 */

/* sync methods */

const manageError = (error) => {
    if (window.wiki.isDebugEnabled === true || window.wiki.isDebugEnabled === 'true'){
        console.error(error)
    }
    return null
}

const toPreFormData = (thing,key ="") => {
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
                let result = {};
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
                }
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

const prepareFormData = (thing) => {
    let formData = new FormData()
    if (typeof thing == "object"){
        let preForm = toPreFormData(thing)
        for (const key in preForm) {
            formData.append(key,preForm[key])
        }
    }
    return formData
}

/* async methods */

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

export default {
    fetch: localFetch,
    manageError,
    post
}