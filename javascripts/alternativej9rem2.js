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
            installing: false,
            loading: false,
            loadingPackages: false,
            loadedVersion: 0,
            message: "",
            messageClass: {
                alert: true,
                ['alert-info']: true
            },
            token: null,
            packagesPaths: [],
            postInstallMessage: "",
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
            app.message = _t('ALTERNATIVEUPDATE_PASSWORD_CHECK');
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
                        app.message = _t('ALTERNATIVEUPDATE_LOADING_DATA');
                        app.messageClass = {alert:true,['alert-success']:true};
                        app.getListOfPackages();
                    } else {
                        app.message =  _t('ALTERNATIVEUPDATE_PASSWORD_CHECK_ERROR');
                        app.messageClass = {alert:true,['alert-danger']:true};
                    }
                },
                error: function(xhr,status,error){
                    if (JSON.parse(xhr.responseText).wrongPassword){
                        app.message = _t('ALTERNATIVEUPDATE_WRONG_PASSWORD');
                        app.messageClass = {alert:true,['alert-warning']:true};
                    } else {
                        app.message = _t('ALTERNATIVEUPDATE_PASSWORD_CHECK_ERROR');
                        app.messageClass = {alert:true,['alert-danger']:true};
                    }
                },
                complete: function(){
                    app.loading = false;
                }
            });
        },
        getListOfPackages: function (updateMessage = true){
            let app = this;
            app.packagesPaths = [];
            app.packages = {};
            if (!this.token){
                app.message = _t('ALTERNATIVEUPDATE_TOKEN_ERROR');
                app.messageClass = {alert:true,['alert-danger']:true};
            } else {
                if (updateMessage){
                    app.message = _t('ALTERNATIVEUPDATE_LOADING_DATA');
                    app.messageClass = {alert:true,['alert-info']:true};
                }
                
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
                                app.loadedVersion = app.loadedVersion +1;
                                app.loadPackages(updateMessage);
                            }
                        },
                        error: function(xhr,status,error){
                            app.message = _t('ALTERNATIVEUPDATE_LOADING_DATA_ERROR');
                            app.messageClass = {alert:true,['alert-danger']:true};
                        }
                    });
                })
            }
        },
        loadPackages: function (updateMessage = true){
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
                                app.loadPackages(updateMessage);
                            } else if(app.loadedVersion == app.versions.length){
                                app.updatePackagesInfo(updateMessage);
                            }
                        },
                        error: function(xhr,status,error){
                            app.message = _t('ALTERNATIVEUPDATE_LOADING_DATA_ERROR_PART');
                            app.messageClass = {alert:true,['alert-danger']:true};
                        }
                    });
                }
            });
        },
        updatePackagesInfo: function (updateMessage = true){
            let app = this;
            if (app.loadingPackages || !app.token){
                return;
            }
            app.loadingPackages = true;
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                cache: false,
                data: {
                    action: 'updatePackagesInfos',
                    token: app.token,
                    packages: app.packages,
                    versions: app.versions,
                },
                success: function(data){
                    app.data = data;
                    if (updateMessage){
                        app.message = _t('ALTERNATIVEUPDATE_DATA_LOADED');
                        app.messageClass = {alert:true,['alert-success']:true};
                        setTimeout(()=>{app.message=""},5000);
                    }
                    app.ready = true;
                },
                error: function(xhr,status,error){
                    app.message = _t('ALTERNATIVEUPDATE_LOADING_DATA_ERROR_PART');
                    app.messageClass = {alert:true,['alert-danger']:true};
                },
                complete: function(){
                    app.loadingPackages = false;
                }
            });
        },
        install: function(event){
            let app = this;
            if (!app.ready || !app.token || app.installing){
                return;
            }
            let elem = event.target;
            let version = elem.dataset.version;
            let name = elem.dataset.extName;
            let type = elem.dataset.type;
            app.installing = true;
            app.postInstallMessage = "";
            app.message = _t('ALTERNATIVEUPDATE_INSTALLING',{name,version});
            app.messageClass = {alert:true,['alert-info']:true};
            let fileUrl = "";
            for (const url in app.packages) {
                let anchor = `/${version}/packages.json`;
                let extName = ((type == "themes") ? 'theme' : 'extension')+`-${name}`;
                if (url.slice(-anchor.length) == anchor && app.packages[url].hasOwnProperty(extName)){
                    fileUrl = url.replace(/packages\.json$/,app.packages[url][extName].file);
                }
            }
            app.downloadFile({
                name:name,
                version:version,
                fileUrl:fileUrl
            });
        },
        refresh: function (){
            if (!this.ready || !this.token || this.installing || this.loadingPackages){
                return;
            }
            this.postInstallMessage = "";
            this.message = _t('ALTERNATIVEUPDATE_LOADING_DATA');
            this.messageClass = {alert:true,['alert-success']:true};
            this.ready = false;
            this.updatePackagesInfo(true);
        },
        delete: function(event){
            let app = this;
            if (!app.ready || !app.token || app.installing){
                return;
            }
            let elem = event.target;
            let name = elem.dataset.extName;
            app.installing = true;
            app.postInstallMessage = "";
            app.message = _t('ALTERNATIVEUPDATE_DELETING',{name});
            app.messageClass = {alert:true,['alert-info']:true};
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                cache: false,
                data: {
                    action: 'delete',
                    token: app.token,
                    packages: app.packages,
                    packageName: name,
                },
                success: function(data){
                    app.data = data;
                    app.message = _t('ALTERNATIVEUPDATE_DATA_DELETED',{name});
                    app.messageClass = {alert:true,['alert-success']:true};
                    setTimeout(()=>{app.message=""},15000);
                },
                error: function(xhr,status,error){
                    app.message = _t('ALTERNATIVEUPDATE_DELETE_ERROR');
                    app.messageClass = {alert:true,['alert-danger']:true};
                },
                complete: function(){
                    app.installing = false;
                    app.ready = false;
                    app.updatePackagesInfo(false);
                }
            });
        },
        activate: function(event){
            this.activation(event,true);
        },
        deactivate: function(event){
            this.activation(event,false);
        },
        activation: function (event,mode){
            let app = this;
            if (!app.ready || !app.token || app.installing){
                return;
            }
            let elem = event.target;
            let name = elem.dataset.extName;
            app.installing = true;
            app.postInstallMessage = "";
            app.message = _t(mode ? 'ALTERNATIVEUPDATE_ACTIVATING' : 'ALTERNATIVEUPDATE_DEACTIVATING',{name});
            app.messageClass = {alert:true,['alert-info']:true};
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                cache: false,
                data: {
                    action: 'activation',
                    token: app.token,
                    packages: app.packages,
                    packageName: name,
                    activation: mode ? "true" : "false",
                },
                success: function(data){
                    app.data = data;
                    app.message = _t(mode ? 'ALTERNATIVEUPDATE_ACTIVATED' : 'ALTERNATIVEUPDATE_DEACTIVATED',{name});
                    app.messageClass = {alert:true,['alert-success']:true};
                    setTimeout(()=>{app.message=""},15000);
                },
                error: function(xhr,status,error){
                    app.message = _t('ALTERNATIVEUPDATE_ACTIVATION_ERROR');
                    app.messageClass = {alert:true,['alert-danger']:true};
                },
                complete: function(){
                    app.installing = false;
                    app.ready = false;
                    app.updatePackagesInfo(false);
                }
            });
        },
        downloadFile: function(params){
            let app = this;
            let zip = new JSZip();
            JSZipUtils.getBinaryContent(
                params.fileUrl, 
                function(err, data) {
                    if(err) {
                        app.message = _t('ALTERNATIVEUPDATE_INSTALL_ERROR',{name:params.name,version:params.version});
                        app.messageClass = {alert:true,['alert-danger']:true};
                        app.installing = false;
                        app.ready = false;
                        app.updatePackagesInfo(false);
                        return;
                    }
                
                    zip.loadAsync(data).then(function () {
                        zip.generateAsync({
                            type: "base64",
                            compression: "DEFLATE"
                        }).then(function (content) {
                            params.zipContent = content;
                            app.downloadMD5File(params);
                        });
                    });
                }
            );
        },
        downloadMD5File: function(params){
            let app = this;
            params.MD5file = "not managed";
            app.doInstall(params);
            return;
            $.ajax({
                method: "GET",
                url: params.fileUrl + '.md5',
                success: function(data){
                    params.MD5file = data;
                    app.doInstall(params);
                },
                error: function(){
                    app.message = _t('ALTERNATIVEUPDATE_INSTALL_ERROR',{name:params.name,version:params.version});
                    app.messageClass = {alert:true,['alert-danger']:true};
                    app.installing = false;
                    app.ready = false;
                    app.updatePackagesInfo(false);
                },
            });
        },
        doInstall: function(params){
            let app = this;
            let fd = new FormData();
            fd.append('action', 'install');
            fd.append('token', app.token);
            fd.append('version', params.version);
            fd.append('packageName', params.name);
            fd.append('md5', params.MD5file);
            fd.append('file', params.zipContent);
            fd.append('packages', JSON.stringify(app.packages));
            $.ajax({
                method: "POST",
                url: wiki.url(`api/alternativeupdatej9rem`),
                data: fd,
                cache: false,
                processData: false,
                contentType: false,
                success: function(data){
                    app.postInstallMessage = data.messages.replace('api/update',"").replace("href=",'style="display:none;" href=');
                    app.message = "";
                    app.messageClass = {alert:true,['alert-success']:true};
                },
                error: function(xhr,){
                    let res = JSON.parse(xhr.responseText)
                    if (res.installed === false){
                        let message = _t('ALTERNATIVEUPDATE_INSTALL_WARNING',{name:params.name,version:params.version});
                        app.message = message;
                        app.messageClass = {alert:true,['alert-warning']:true};
                    } else {
                        let message = _t('ALTERNATIVEUPDATE_INSTALL_ERROR',{name:params.name,version:params.version});
                        app.message = message;
                        app.messageClass = {alert:true,['alert-danger']:true};
                    }
                },
                complete: function(){
                    app.installing = false;
                    app.ready = false;
                    app.updatePackagesInfo(false);
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