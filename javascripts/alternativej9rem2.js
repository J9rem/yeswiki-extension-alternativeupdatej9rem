/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import SpinnerLoader from '../../bazar/presentation/javascripts/components/SpinnerLoader.js'
import ExtsPanel from './components/ExtsPanel.js'

let rootsElements = ['.alternativej9rem2-container'];
let isVueJS3 = (typeof Vue.createApp == "function");

let appParams = {
    components: { SpinnerLoader , ExtsPanel},
    data: function() {
        return {
            uid: null,
            data: {},
            token: null,
            loading: false,
            loadingPackages: false,
            message: "",
            messageClass: {
                alert: true,
                ['alert-info']: true
            },
            packagesPaths: [],
            packages: {},
            ready: false,
            versions: []
        };
    },
    methods: {
        getToken: function() {
            let app = this;
            let password = this.$refs.password.value;
            this.$refs.password.value = null;
            app.token = null
            app.loading = true;
            app.message = "Vérification du mot de passe";
            app.messageClass = {alert:true,['alert-info']:true};
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                data: {
                    action: 'getToken',
                    password: password,
                },
                success: function(data){
                    app.token = data.token || null
                    if (app.token){
                        app.message = "Chargement des données";
                        app.messageClass = {alert:true,['alert-success']:true};
                        app.getListOfPackages();
                    } else {
                        app.message = "Impossible de vérifier votre mot de passe";
                        app.messageClass = {alert:true,['alert-danger']:true};
                    }
                },
                error: function(xhr,status,error){
                    if (JSON.parse(xhr.responseText).wrongPassword){
                        app.message = "Mauvais mot de passe";
                        app.messageClass = {alert:true,['alert-warning']:true};
                    } else {
                        app.message = "Impossible de vérifier votre mot de passe";
                        app.messageClass = {alert:true,['alert-danger']:true};
                    }
                },
                complete: function(){
                    app.loading = false;
                }
            });
        },
        getListOfPackages: function (){
            let app = this;
            app.packagesPaths = [];
            app.packages = {};
            if (!this.token){
                app.message = "getListOfPackages ne peut être appelé sans token";
                app.messageClass = {alert:true,['alert-danger']:true};
            } else {
                app.message = "Chargement des données";
                app.messageClass = {alert:true,['alert-info']:true};
                
                this.versions.forEach((version)=>{
                    $.ajax({
                        method: "POST",
                        url: wiki.url(`api/alternativeupdatej9rem`),
                        data: {
                            action: 'getPackagesPaths',
                            token: app.token,
                            version: version
                        },
                        success: function(data){
                            if (Array.isArray(data)){
                                data.forEach((url)=>{
                                    app.packagesPaths.push(url);
                                });
                                app.loadPackages();
                            }
                        },
                        error: function(xhr,status,error){
                            app.message = "Impossible de charger les données";
                            app.messageClass = {alert:true,['alert-danger']:true};
                        }
                    });
                })
            }
        },
        loadPackages: function (){
            let app = this;
            if (app.loadingPackages){
                return;
            }
            app.loadingPackages = true;
            app.packagesPaths.forEach((url) => {
                if (!app.packages.hasOwnProperty(url)){
                    $.ajax({
                        method: "GET",
                        url: url,
                        success: function(data){
                            app.packages[url] = data;
                            app.loadingPackages = false;
                            if (Object.keys(app.packages).length != app.packagesPaths.length){
                                app.loadPackages();
                            } else {
                                app.updatePackagesInfo();
                            }
                        },
                        error: function(xhr,status,error){
                            app.message = "Impossible de charger une partie des données";
                            app.messageClass = {alert:true,['alert-danger']:true};
                        }
                    });
                }
            });
        },
        updatePackagesInfo: function (){
            let app = this;
            if (app.loadingPackages || !app.token){
                return;
            }
            app.loadingPackages = true;
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                data: {
                    action: 'updatePackagesInfos',
                    token: app.token,
                    packages: app.packages,
                    versions: app.versions,
                },
                success: function(data){
                    app.data = data;
                    app.message = "Données chargées";
                    app.messageClass = {alert:true,['alert-success']:true};
                    setTimeout(()=>{app.message=""},1500);
                    app.ready = true;
                },
                error: function(xhr,status,error){
                    app.message = "Impossible de charger une partie des données";
                    app.messageClass = {alert:true,['alert-danger']:true};
                },
                complete: function(){
                    app.loadingPackages = false;
                }
            });
        }
    },
    mounted(){
        $(isVueJS3 ? this.$el.parentNode : this.$el).on('dblclick',function(e) {
          return false;
        });
        this.uid = isVueJS3 ? this.$el.parentNode.dataset.uid : this.$el.dataset.uid
        this.versions = (isVueJS3 ? this.$el.parentNode.dataset.versions : this.$el.dataset.versions).split(',')
    }
};

if (isVueJS3){
    let app = Vue.createApp(appParams);
    app.config.globalProperties.wiki = wiki;
    rootsElements.forEach(elem => {
        app.mount(elem);
    });
} else {
    Vue.prototype.wiki = wiki;
    rootsElements.forEach(elem => {
        new Vue({
            ...{el:elem},
            ...appParams
        });
    });
}