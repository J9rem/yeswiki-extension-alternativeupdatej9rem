/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-open-agenda-connect
 */

let rootsElements = ['.open-agenda-configurator']
let isVueJS3 = (typeof Vue.createApp == "function");

let appParams = {
    components: {},
    data() {
        return {
            associations: {},
            callingApi: false,
            forms: null,
            keys: {},
            message:'',
            messageClass:'',
            newFormId:'',
            newFormKey:'',
            newFormAgendaId:'',
            newKeyName:'',
            newKeyValue:'',
            token: ''
        }
    },
    computed:{
        agendaForms(){
            return (this.forms === null) ? {} : Object.fromEntries(
                Object.entries(this.forms)
                    .filter(([,form])=>{
                        return form?.prepared?.some((field)=>{
                            return ['jour','listedatedeb','listedatefin'].includes(field?.type)
                                && field?.propertyname === 'bf_date_debut_evenement'
                        }) ?? false
                    })
                    .filter(([,form])=>{
                        return form?.prepared?.some((field)=>{
                            return ['jour','listedatedeb','listedatefin'].includes(field?.type)
                                && field?.propertyname === 'bf_date_fin_evenement'
                        }) ?? false
                    })
            )
        },
        dataset(){
            return this.element?.dataset?.data
        },
        element(){
            return isVueJS3 ? this.$el.parentNode : this.$el
        }
    },
    methods:{
        async fetch(url,mode = 'get',dataToSend = {}){
            let func = (url,mode,dataToSend)=>{
                return (mode == 'get')
                    ? fetch(url)
                    : this.post(url,dataToSend)
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
        },
        async getForms(){
            if (this.forms !== null){
                return this.forms
            }
            this.message = 'getform'
            this.messageClass = 'info'
            this.callingApi = true
            return await this.fetch(wiki.url('?api/forms'))
                .then((forms)=>{
                    this.forms = forms
                    this.message = 'youturn'
                    this.messageClass = 'info'
                    return forms
                })
                .finally(()=>{this.callingApi = false})
        },
        manageError(error){
            if (wiki.isDebugEnabled){
                console.error(error)
            }
            this.message = 'error'
            this.messageClass = 'danger'
            return null
        },
        async post(url,dataToSend){
            return await fetch(url,
                {
                    method: 'POST',
                    body: new URLSearchParams(this.prepareFormData(dataToSend)),
                    headers: (new Headers()).append('Content-Type','application/x-www-form-urlencoded')
                })
        },
        prepareFormData(thing){
            let formData = new FormData();
            if (typeof thing == "object"){
                let preForm =this.toPreFormData(thing);
                for (const key in preForm) {
                    formData.append(key,preForm[key]);
                }
            }
            return formData;
        },
        async registerNewForm(){
            this.callingApi = true
            this.message = 'registeringassociation'
            this.messageClass = 'info'
            return await this.fetch(wiki.url('?api/openagenda/config/setassociation'),'post',{
                    id:String(this.newFormId),
                    name:this.newFormKey,
                    value:this.newFormAgendaId,
                    token:this.token
                })
                .then((data)=>{
                    if (typeof data?.associations === 'object'){
                        this.associations = data.associations
                    }
                    this.message = 'ok'
                    this.messageClass = 'success'
                    return data
                })
                .catch(this.manageError)
                .finally(()=>{this.callingApi = false})
        },
        async registerNewKey(){
            if (this.newKeyName?.length < 3){
                this.message = 'error'
                this.messageClass = 'warning'
                return
            }
            if (this.newKeyValue?.length < 10){
                this.message = 'error'
                this.messageClass = 'warning'
                return
            }
            this.callingApi = true
            this.message = 'registeringkey'
            this.messageClass = 'info'
            return await this.fetch(wiki.url('?api/openagenda/config/setkey'),'post',{
                    name:this.newKeyName,
                    value:this.newKeyValue,
                    token:this.token
                })
                .then((data)=>{
                    if (typeof data?.privateApiKeys === 'object'){
                        this.keys = data.privateApiKeys
                    }
                    this.message = 'ok'
                    this.messageClass = 'success'
                    return data
                })
                .catch(this.manageError)
                .finally(()=>{this.callingApi = false})
        },
        async removeFormAssoc(formId){
            this.callingApi = true
            this.message = `removingassoc ${formId}`
            this.messageClass = 'info'
            return await this.fetch(wiki.url('?api/openagenda/config/removeassociation'),'post',{
                    id:formId,
                    token:this.token
                })
                .then((data)=>{
                    if (typeof data?.associations === 'object'){
                        this.associations = data.associations
                    }
                    this.message = 'ok'
                    this.messageClass = 'success'
                    return data
                })
                .catch(this.manageError)
                .finally(()=>{this.callingApi = false})
        },
        async removeKey(name){
            this.callingApi = true
            this.message = `removingkey ${name}`
            this.messageClass = 'info'
            return await this.fetch(wiki.url('?api/openagenda/config/removekey'),'post',{
                    name:name,
                    token:this.token
                })
                .then((data)=>{
                    if (typeof data?.privateApiKeys === 'object'){
                        this.keys = data.privateApiKeys
                    }
                    this.message = 'ok'
                    this.messageClass = 'success'
                    return data
                })
                .catch(this.manageError)
                .finally(()=>{this.callingApi = false})
        },
        toPreFormData(thing,key =""){
            let type = typeof thing;
            switch (type) {
                case 'boolean':
                case 'number':
                case 'string':
                    return {
                        [key]:thing
                    };
                case 'object':
                    if (thing === null) {
                        return {
                            [key]:null
                        };
                    } else if (Object.keys(thing).length > 0){
                        let result = {};
                        for (const propkey in thing) {
                            result = {
                                ...result,
                                ...this.toPreFormData(
                                    thing[propkey],
                                    (key.length == 0) ? propkey : `${key}[${propkey}]`
                                )
                            }
                        }
                        return result;
                    } else {
                        return {
                            [key]: []
                        };
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
                            ...this.toPreFormData(
                                val,
                                (key.length == 0) ? propkey : `${key}[${propkey}]`
                            )
                        }
                    });
                    return result;
                default:
                    return {
                        [key]:null
                    };
            }
        }
    },
    mounted(){
        const data = JSON.parse(this.dataset)
        this.keys = (typeof data?.privateApiKeys === 'object' && Object.keys(data?.privateApiKeys).length > 0) 
            ? data?.privateApiKeys
            : {}
        this.associations = (typeof data?.associations === 'object' && Object.keys(data?.associations).length > 0) 
            ? data?.associations
            : {}
        this.token = (typeof data?.token === 'string' && data?.token?.length > 0) 
            ? data.token
            : ''
        this.getForms().catch(this.manageError)
    },
    watch: {
    }
}
if (isVueJS3){
    let app = Vue.createApp(appParams);
    app.config.globalProperties.wiki = wiki;
    app.config.globalProperties._t = _t;
    rootsElements.forEach(elem => {
        app.mount(elem);
    });
} else {
    Vue.prototype.wiki = wiki;
    Vue.prototype._t = _t;
    rootsElements.forEach(elem => {
        new Vue({
            ...{el:elem},
            ...appParams
        });
    });
}