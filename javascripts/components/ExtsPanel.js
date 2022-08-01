/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default {
  props: [ 'keyName', 'type','repo' ,'isHibernated','version'],
  data() {
    return {
    }
  },
  computed: {
    exts(){
      switch (this.type) {
        case 'themes':
          return this.repo.themes || {};
        case 'tools':
          return this.repo.tools || {};
        default:
          return {};
      }
    },
    title(){
      switch (this.type) {
        case 'themes':
          return this.keyName == '__local' ?  _t('ALTERNATIVEUPDATE_LOCAL_THEMES'): _t('ALTERNATIVEUPDATE_NO_OFFICIAL_THEMES',{keyName:this.keyName});
        case 'tools':
          return this.keyName == '__local' ? _t('ALTERNATIVEUPDATE_LOCAL_TOOLS') : _t('ALTERNATIVEUPDATE_NO_OFFICIAL_TOOLS',{keyName:this.keyName});
        default:
          return 'Packages';
      }
    },
    keyId(){
      let keyId = (this.keyName+this.version).toLowerCase().replace(/\s/,"");
      switch (this.type) {
        case 'themes':
          return `Themes${keyId}`;
        case 'tools':
          return `Tools${keyId}`;
        default:
          return keyId;
      }
    },
    nbMaj() {
      return Object.keys(this.exts).filter((extKey)=>(this.exts[extKey] && this.exts[extKey].updateAvailable && this.exts[extKey].installed)).length;
    },
    specificClasses() {
      return {
        ['panel-warning']:this.nbMaj>0,
        ['panel-default']:this.nbMaj==0,
      };
    } 
  },
  template: `
  <div class="panel" :class="specificClasses">
    <div class="panel-heading collapsed" 
        role="tab" :id="'heading'+ keyId" 
        data-toggle="collapse" 
        data-parent="#accordion_update_j9rem" 
        :href="'#collapse'+ keyId" 
        aria-expanded="false" 
        :aria-controls="'collapse'+ keyId">
      <h2 class="panel-title"><span v-html="title"></span>
        <span v-if="nbMaj>0" class="alert-msg" style="float: right;" v-html="(nbMaj > 1) ? _t('ALTERNATIVEUPDATE_SEVERAL_EXTS_UPDATE',{nbMaj:nbMaj}): _t('ALTERNATIVEUPDATE_ONE_TEXT_UPDATE')"></span>
    </h2>
    </div>
    <div :id="'collapse'+ keyId" class="panel-collapse collapse" role="tabpanel" :aria-labelledby="'heading'+ keyId">
      <table class="table table-striped table-condensed table-updates">
        <thead>
          <tr>
              <th v-html="_t('ALTERNATIVEUPDATE_NAME')">/th>
              <th v-html="_t('ALTERNATIVEUPDATE_INSTALLED_REVISION')">/th>
              <th v-html="_t('ALTERNATIVEUPDATE_AVAILABLE_REVISION')">/th>
              <th></th>
              <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ext in exts">
            <td>
                <strong v-html="ext.name"></strong>
                <br />
                <small v-html="ext.description"></small>
            </td>
            <td v-html="(ext.installed) ? ext.localRelease : _t('ALTERNATIVEUPDATE_ABSENT')">
            </td>
            <td v-html="ext.release">
            </td>
            <td>
              <template v-if="ext.installed && !(ext.isTheme)">
                <template v-if="keyName != '__local'">
                  <button v-if="!isHibernated"
                    class="btn btn-xs btn-info"
                    :title="_t('ALTERNATIVEUPDATE_REINSTALL')"
                    v-html="_t('ALTERNATIVEUPDATE_REINSTALL')"
                    :data-ext-name="ext.name"
                    :data-version="version"
                    :data-type="type"
                    @click.stop.prevent="$root.install"
                    :disabled="$root.installing"
                    >
                  </button>
                  <button v-else disabled
                      class="btn btn-xs btn-info"
                      :title="_t('ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION')"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      v-html="_t('ALTERNATIVEUPDATE_REINSTALL')"
                  >
                  </button>
                </template>
                <template v-else-if="!ext.isActive">
                  <button v-if="!isHibernated"
                    class="btn btn-xs btn-primary"
                    :title="_t('ALTERNATIVEUPDATE_ACTIVATE')"
                    v-html="_t('ALTERNATIVEUPDATE_ACTIVATE')"
                    :data-ext-name="ext.name"
                    @click.stop.prevent="$root.activate"
                    :disabled="$root.installing"
                    >
                  </button>
                  <button v-else disabled
                      class="btn btn-xs btn-primary"
                      :title="_t('ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION')"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      v-html="_t('ALTERNATIVEUPDATE_ACTIVATE')"
                  >
                  </button>
                </template>
                <template v-else>
                  <button v-if="!isHibernated"
                    class="btn btn-xs btn-danger"
                    :title="_t('ALTERNATIVEUPDATE_DEACTIVATE')"
                    v-html="_t('ALTERNATIVEUPDATE_DEACTIVATE')"
                    :data-ext-name="ext.name"
                    @click.stop.prevent="$root.deactivate"
                    :disabled="$root.installing"
                    >
                  </button>
                  <button v-else disabled
                      class="btn btn-xs btn-danger"
                      :title="_t('ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION')"
                      data-toggle="tooltip"
                      data-placement="bottom"
                      v-html="_t('ALTERNATIVEUPDATE_DEACTIVATE')"
                  >
                  </button>
                </template>
              </template>
            </td>
            <td>
              <template v-if="!ext.installed">
                <button v-if="!isHibernated"
                  class="btn btn-xs btn-primary"
                  :title="_t('ALTERNATIVEUPDATE_INSTALL')"
                  v-html="_t('ALTERNATIVEUPDATE_INSTALL')"
                  :data-ext-name="ext.name"
                  :data-version="version"
                  :data-type="type"
                  @click.stop.prevent="$root.install"
                  :disabled="$root.installing"
                  ></button>
                <button v-else disabled
                    class="btn btn-xs btn-primary"
                    :title="_t('ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION')"
                    data-toggle="tooltip"
                    data-placement="bottom"
                    v-html="_t('ALTERNATIVEUPDATE_INSTALL')"
                  >
                </button>
              </template>
              <template v-if="ext.installed && (ext.name != 'autoupdate')">
                <button v-if="!isHibernated"
                  class="btn btn-xs btn-danger"
                  :title="_t('ALTERNATIVEUPDATE_DELETE')"
                  v-html="_t('ALTERNATIVEUPDATE_DELETE')"
                  :data-ext-name="ext.name"
                  @click.stop.prevent="$root.delete"
                  :disabled="$root.installing"
                  ></button>
                <button v-else disabled
                    class="btn btn-xs btn-danger"
                    :title="_t('ALTERNATIVEUPDATE_WIKI_IN_HIBERNATION')"
                    data-toggle="tooltip"
                    data-placement="bottom"
                    v-html="_t('ALTERNATIVEUPDATE_DELETE')"
                  >
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  `
}
  