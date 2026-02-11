<template>
    <div>
        <v-container fluid class="d-none">
            <v-row>
                <v-col cols="12" md="3">
                    <v-color-picker v-model="settings.background" hide-canvas mode="hexa"></v-color-picker>
                    <div class="mt-2">Background Color</div>
                </v-col>
                <v-col cols="12" md="3">
                    <v-color-picker v-model="settings.color" hide-canvas mode="hexa"></v-color-picker>
                </v-col>
                <v-col cols="12" md="3">
                    <v-color-picker v-model="settings.underline" hide-canvas mode="hexa"></v-color-picker>
                </v-col>
                <v-col cols="12" md="3">
                    <v-select type="number" density="compact" variant="underlined"></v-select>
                    <v-color-picker v-model="settings.columns" hide-canvas mode="hex"></v-color-picker>
                </v-col>
            </v-row>
        </v-container>
        <v-card >
            <v-container>
                <v-row>
                    <v-col cols="12" md="6">
                        <h2 class="text-h6">Footer Settings</h2>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-btn :disabled="fvalid" @click="updateFooter" color="success" density="comfortable">Update</v-btn>
                    </v-col>
                    <v-col cols="12" md="3" v-if="business === null">
                        Create Company Information
                        <v-btn color="info" density="comfortable" class="text-none"
                               link :to="{name:'SettingsGeneral'}">Create Company Info</v-btn>
                    </v-col>
                    <v-col v-else cols="12" md="3">
                        <h2 class="h4 mb-2">{{fsections.custom.label}}</h2>
                        <v-list density="compact" v-if="fsections?.custom?.ptext !== null">
                            <v-list-item v-for="(value, key) in fsections?.custom?.ptext" :key="key" >
                                <div class="d-flex">
                                    <div class="font-weight-medium text-capitalize text-body-1">{{key}} :</div>
                                    <div class="ms-1">{{value}}</div>
                                </div>
                            </v-list-item>
                        </v-list>
                    </v-col>
                    <v-col cols="12" md="3" v-if="qlinks === null">
                        Create Menu
                        <v-btn  color="info" density="comfortable" class="text-none"
                                link :to="{name:'MenuAdd'}">Quick Links</v-btn>
                    </v-col>
                    <v-col cols="12" md="3" v-else>
                        <h2 class="h4 mb-2">{{fsections.menus[0]?.menu_name}}</h2>
                        <v-list v-if="fsections.menus[0]?.mitems !== null">
                            <v-list-item v-for="(menu,index) in fsections?.menus[0]?.mitems">
                                {{menu.label}}
                            </v-list-item>
                        </v-list>
                    </v-col>
                    <v-col cols="12" md="3" v-if="ilinks === null">
                        Create Menu
                        <v-btn color="info" density="comfortable" class="text-none"
                               link :to="{name:'MenuAdd'}">Information</v-btn>
                    </v-col>
                    <v-col cols="12" md="3" v-else>
                        <h2 class="h4 mb-2">{{fsections.menus[1]?.menu_name}}</h2>
                        <v-list v-if="fsections.menus[1]?.mitems !== null">
                            <v-list-item v-for="(menu,index) in fsections?.menus[1]?.mitems">
                                {{menu.label}}
                            </v-list-item>
                        </v-list>
                    </v-col>
                    <v-col cols="12" md="3" v-if="plinks === null">
                        Create Menu
                        <v-btn  color="info" density="comfortable" class="text-none"
                                link :to="{name:'MenuAdd'}">Our Policies</v-btn>
                    </v-col>
                    <v-col cols="12" md="3" v-else>
                        <h2 class="h4 mb-2">{{fsections.menus[2]?.menu_name}}</h2>
                        <v-list v-if="fsections.menus[2]?.mitems !== null">
                            <v-list-item v-for="(menu,index) in fsections?.menus[2]?.mitems">
                                {{menu.label}}
                            </v-list-item>
                        </v-list>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </div>
</template>
<script>
import axios from "axios";

export default {
    name:"ShopFooter",
    data(){
        return{
            fvalid:false,
            business:{},
            ptext: {},
            afooter:{footer_id:'',},
            settings: {
                background:'#000000',
                color:'#ffffff',
                underline:'#bb0000',
                columns:{
                    desktop:4,
                    tablet:2,
                    mobile:1
                }
            },
            fsections: {
                custom:{
                    label:'Contact Us',
                    ptext:{}
                },
                menus:[]
            },
            qlinks: {},
            ilinks: {},
            plinks: {},
            menus:[],
        }
    },
    created() {
        this.getFooterSections();
    },
    methods:{
        getFooterSections(){
            axios.get('/sadmin/footer')
                .then((resp)=>{
                    this.afooter = resp.data.footer;
                    this.menus = resp.data.menus;
                    this.business = resp.data.business;
                    this.qlinks = resp.data.quick_links;
                    this.ilinks = resp.data.information;
                    this.plinks = resp.data.our_policies;
                    this.fsections.custom.ptext = resp.data.business;
                    this.fsections.menus[0] = this.qlinks;
                    this.fsections.menus[1] = this.ilinks;
                    this.fsections.menus[2] = this.plinks;
                    // if(resp.data.footer?.fsections === null){
                    //     this.firstFooter();
                    // }
                })
        },
        updateFooter(){
            const udata = {
                'footer_id':this.afooter.footer_id,
                'fsections':{
                    'custom':{
                        'label':'Contact Us',
                        'ptext':this.business
                    },
                  menus:this.fsections.menus,
                },
                'settings':this.settings,
                'style':this.afooter.style,
            }
            axios.post('/sadmin/footer/update',udata)
                .then((resp)=>{
                    this.getFooterSections();
                    window.Toast.success(resp.data.message);
                })
        },
        firstFooter(){
            if(this.afooter.fsections === null){
                const fdata = {
                    'footer_id':this.afooter.footer_id,
                    'fsections':this.fsections,
                    'settings':this.settings,
                    'style':this.afooter.style,
                }
                axios.post('/sadmin/footer/update',fdata)
                    .then((resp)=>{
                        this.getFooterSections();
                        window.Toast.success(resp.data.message);
                    })
            }
        }
    }
}
</script>

<style scoped>
.footer h2.h4 {
    position: relative;
    &::before {
        background: #bb0000;
        content: "";
        position: absolute;
        bottom: -3px;
        width: 75px;
        height: 2px;
        transition: linear 2s 2s;
    }
}

</style>
