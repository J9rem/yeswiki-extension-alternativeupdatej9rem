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
          return this.keyName == '__local' ? 'Thèmes locaux' : `Thèmes non officiels (${this.keyName})`;
        case 'tools':
          return this.keyName == '__local' ? 'Extensions locales' : `Tools/extensions non officielles (${this.keyName})`;
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
        <span v-if="nbMaj>0" class="alert-msg" style="float: right;" v-html="(nbMaj > 1) ? nbMaj+' Majs': '1 maj'"></span>
    </h2>
    </div>
    <div :id="'collapse'+ keyId" class="panel-collapse collapse" role="tabpanel" :aria-labelledby="'heading'+ keyId">
      <table class="table table-striped table-condensed table-updates">
        <thead>
          <tr>
              <th>Nom</th>
              <th>Version installée</th>
              <th>Version disponible</th>
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
            <td v-html="(ext.installed) ? ext.localRelease : 'Non installé'">
            </td>
            <td v-html="ext.release">
            </td>
            <td>
              <template v-if="ext.installed && !(ext.isTheme)">
                <template v-if="ext.isActive">
                  <a v-if="!isHibernated"
                    :href="wiki.url(wiki.pageTag,{activate:ext.name})"
                    class="btn btn-xs btn-primary"
                    title="Activer"
                    >
                    Activer
                  </a>
                  <button v-else disabled
                      class="btn btn-xs btn-primary"
                      title="Wiki en hibernation"
                      data-toggle="tooltip"
                      data-placement="bottom"
                  >
                    Activer
                  </button>
                </template>
                <template v-else>
                  <a v-if="!isHibernated"
                    :href="wiki.url(wiki.pageTag,{deactivate:ext.name})"
                    class="btn btn-xs btn-danger"
                    title="Désactiver"
                    >
                    Désactiver
                  </a>
                  <button v-else disabled
                      class="btn btn-xs btn-danger"
                      title="Wiki en hibernation"
                      data-toggle="tooltip"
                      data-placement="bottom"
                  >
                  Désactiver
                  </button>
                </template>
              </template>
            </td>
            <td>
              <template v-if="!ext.installed">
                <a v-if="!isHibernated"
                  :href="wiki.url(wiki.pageTag,{upgrade:ext.updateLink})"
                  class="btn btn-xs btn-primary"
                  title="Installer"
                  >Installer</a>
                <button v-else disabled
                    class="btn btn-xs btn-primary"
                    title="Wiki en hibernation"
                    data-toggle="tooltip"
                    data-placement="bottom"
                  >Installer
                </button>
              </template>
              <template v-if="ext.installed && (ext.name != 'autoupdate')">
                <a v-if="!isHibernated"
                  :href="wiki.url(wiki.pageTag,{delete:ext.deleteLink.split('delete=').slice(1,1)[0]})"
                  class="btn btn-xs btn-danger"
                  title="Supprimer"
                  >Supprimer</a>
                <button v-else disabled
                    class="btn btn-xs btn-danger"
                    title="Wiki en hibernation"
                    data-toggle="tooltip"
                    data-placement="bottom"
                  >Delete
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
  