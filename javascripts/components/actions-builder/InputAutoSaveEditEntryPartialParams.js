/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

export default {
  props: ['selectedForms','values'],
  data(){
    return {
      previous:'',
      registering:'',
      controller:null
    }
  },
  methods:{
    appendForm(formData,parentKeyTxt,key,value){
      const currentKey = parentKeyTxt.length > 0 ? `${parentKeyTxt}[${key}]` : key
      if (['number','string','boolean'].includes(typeof value)){
        formData.append(currentKey,String(value))
      } else if (typeof value === 'object'){
        Object.entries(value).forEach(([key2,value2])=>{
          this.appendForm(formData,currentKey,key2,value2)
        })
      }
    },
    extractId(){
      const forms = Object.values(this.selectedForms)
      return (forms.length === 0)
        ? ''
        : (
          forms?.[0]?.bn_id_nature ?? ''
        )
    },
    extractFields(){
      return this.values?.fields ?? ''
    },
    async fetchSecured(url,options={}){
        return await fetch(url,options)
            .then((response)=>{
                if(!response.ok){
                    throw new Error(`Response badly formatted (${response.status} - ${response.statusText})`)
                }
                return response.json()
            })
    },
    getController(){
      if (this.controller === null){
        this.controller = new AbortController()
      }
      return this.controller
    },
    async post(url,data,signal){
      let formData = new FormData()
      Object.entries(data).forEach(([key,value])=>{
        this.appendForm(formData,'',key,value)
      })
      return await this.fetchSecured(
        url,
        {
          method:'POST',
          body: new URLSearchParams(formData),
          headers: (new Headers()).append('Content-Type','application/x-www-form-urlencoded'),
          signal
        }
      )
    },
    async registerTriple(){
      const id = this.extractId()
      const fields = this.extractFields()
      const key = `${id}-${fields}`
      const pageTag = wiki.pageTag
      if (pageTag?.length > 0 && this.previous !== key){
        if (this.registering !== key){
          if (this.registering?.length > 0){
            this.getController().abort()
            this.controller = null
          }
          this.registering = key
          return await this.post(
              wiki.url(`?api/alternativeupdatej9rem/getToken`),
              {},
              this.getController().signal
            )
            .then(async (data)=>{
              const token = data?.token
              return await this.post(
                wiki.url(`?api/alternativeupdatej9rem/set-edit-entry-partial-params/${pageTag}/${id}/${fields}`),
                {
                  ['anti-csrf-token']:token
                },
                this.getController().signal
              )
            })
            .then(()=>{
              this.previous = key
            })
            .catch(()=>{/* do nothing */})
            .finally(()=>{
              this.registering = ''
              this.controller = null
            })
        }
      }
    }
  },
  watch:{
    selectedForms:{
      deep:true,
      handler(){
        this.registerTriple()
      }
    },
    values:{
      deep:true,
      handler(){
        this.registerTriple()
      }
    }
  }
}
  