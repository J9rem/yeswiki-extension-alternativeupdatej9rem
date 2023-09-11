/*
 * This file is part of the YesWiki Extension alternativeupdatej9rem.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

let rootsElements = ['.selector_is_recurrent'];
let isVueJS3 = (typeof Vue.createApp == "function");

let appParams = {
    components: {},
    data() {
        return {
            days:[],
            isRecurrent: false,
            months:[],
            nth:'',
            repetition: '',
            step:1,
            whenInMonth:''
        }
    },
    computed:{
        element(){
            return isVueJS3 ? this.$el.parentNode : this.$el
        }
    },
    methods:{
        toggleDay(key){
            if (this.days.includes(key)){
                this.days = this.days.filter((elem)=>elem != key)
            } else {
                this.days.push(key)
            }
        },
        toggleMonth(key){
            if (this.months.includes(key)){
                this.months = this.months.filter((elem)=>elem != key)
            } else {
                this.months.push(key)
            }
        }
    },
    mounted(){
        const data = JSON.parse(this.element?.dataset?.data)
        this.isRecurrent =  data?.isRecurrent === '1'
        this.repetition =  data?.repetition ?? ''
        this.step =  data?.step ?? 1
        this.whenInMonth =  data?.whenInMonth ?? ''
        this.month =  data?.month ?? ''
        this.nth =  data?.nth ?? ''
        this.days = Object.entries(data?.days ?? {})
            .filter(([,val])=>val === '1')
            .map(([idx,])=>idx)
        this.months = Object.entries(data?.months ?? {})
            .filter(([,val])=>val === '1')
            .map(([idx,])=>idx)
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