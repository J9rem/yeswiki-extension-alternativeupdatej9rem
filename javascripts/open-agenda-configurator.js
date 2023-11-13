/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * Feature UUID : auj9-open-agenda-connect
 */

import asyncHelper from './asyncHelper.js'

let rootsElements = ['.open-agenda-configurator']
let isVueJS3 = (typeof Vue.createApp == "function");

let appParams = {
    components: {},
    data() {
        return {
            activated: false,
            associations: {},
            callingApi: false,
            forms: null,
            keys: {},
            message:'',
            messageClass:'',
            newFormId:'',
            newFormAgendaId:'',
            newFormKey:'',
            newFormPublicKey:'',
            newKeyName:'',
            newKeyValue:'',
            token: ''
        }
    },
    computed:{
        agendaForms(){
            return (this.forms === null) ? {} : Object.fromEntries(
                Object.entries(this.forms)
                    .map(([k,form])=>{
                        if (typeof form === 'object'){
                            form.prepared = Array.isArray(form?.prepared) 
                                ? form.prepared
                                : (
                                    (typeof form?.prepared === 'object' && form?.prepared !== null)
                                    ? Object.values(form.prepared)
                                    : []
                                )
                        }
                        return [k,form]
                    })
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
        async getForms(){
            if (this.forms !== null){
                return this.forms
            }
            this.message = 'getform'
            this.messageClass = 'info'
            this.callingApi = true
            return await asyncHelper.fetch(wiki.url('?api/forms'))
                .then((forms)=>{
                    this.forms = forms
                    this.message = 'youturn'
                    this.messageClass = 'info'
                    return forms
                })
                .finally(()=>{this.callingApi = false})
        },
        manageError(error){
            const output = asyncHelper.manageError(error)
            this.message = 'error'
            this.messageClass = 'danger'
            return output
        },
        async registerNewForm(){
            this.callingApi = true
            this.message = 'registeringassociation'
            this.messageClass = 'info'
            return await asyncHelper.fetch(wiki.url('?api/openagenda/config/setassociation'),'post',{
                    id:String(this.newFormId),
                    name:this.newFormKey,
                    value:this.newFormAgendaId,
                    public:this.newFormPublicKey,
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
            return await asyncHelper.fetch(wiki.url('?api/openagenda/config/setkey'),'post',{
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
            return await asyncHelper.fetch(wiki.url('?api/openagenda/config/removeassociation'),'post',{
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
            return await asyncHelper.fetch(wiki.url('?api/openagenda/config/removekey'),'post',{
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
        async testFormKey(formId){
            this.callingApi = true
            this.message = `testingformkey ${formId}`
            this.messageClass = 'info'
            return await asyncHelper.fetch(wiki.url(`?api/openagenda/config/testpublickey/${formId}`),'post',{
                token:this.token
            })
            .then((data)=>{
                this.message = 'ok'
                this.messageClass = 'success'
                return data
            })
            .catch(this.manageError)
            .finally(()=>{this.callingApi = false})
        },
        async testKey(keyName){
            this.callingApi = true
            this.message = `testingkey ${keyName}`
            this.messageClass = 'info'
            return await asyncHelper.fetch(wiki.url(`?api/openagenda/config/testkey/${keyName}`),'post',{
                token:this.token
            })
            .then((data)=>{
                this.message = 'ok'
                this.messageClass = 'success'
                return data
            })
            .catch(this.manageError)
            .finally(()=>{this.callingApi = false})
        },
        async toggleActivation(value){
            this.callingApi = true
            this.message = `changing state`
            this.messageClass = 'info'
            return await asyncHelper.fetch(wiki.url('?api/openagenda/config/toggleactivation'),'post',{
                token:this.token
            })
            .then((data)=>{
                this.activated = (data?.isActivated === true)
                this.message = 'ok'
                this.messageClass = 'success'
                return data
            })
            .catch(this.manageError)
            .finally(()=>{this.callingApi = false})
        }
    },
    mounted(){
        const data = JSON.parse(this.dataset)
        this.activated = (data?.isActivated === true)
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