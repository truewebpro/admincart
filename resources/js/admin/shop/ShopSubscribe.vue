<template>
    <div>
        <v-container>
            <v-row dense>
                <v-col cols="12" md="12">
                    <v-card>
                        <v-card-item>
                            <template #title>
                                Shop Subscribe Section
                            </template>
                        </v-card-item>
                    </v-card>
                </v-col>
                <v-col cols="12" md="12">
                    <v-form v-model="subForm" @submit.prevent="updateSubSection">
                        <v-card>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field v-model="sdata.headline" variant="underlined" density="compact" label="Headline"></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field v-model="sdata.subheadline" variant="underlined" density="compact" label="SubHeadline"></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field v-model="sdata.promo_text" variant="underlined" density="compact" label="Promo Text"></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field v-model="sdata.privacy_text" variant="underlined" density="compact" label="Privacy Text"></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-select v-model="sdata.style" variant="underlined" density="compact" label="Style"
                                                  :items="['style1','style2','style3']"></v-select>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-color-input v-model="sdata.settings.background_color"
                                                       label="Background Color" color-pip pip-location="prepend-inner"
                                                       variant="outlined" density="compact" mode="hexa" hide-canvas></v-color-input>
                                    </v-col>
                                    <v-col cols="12" md="4">
                                        <v-color-input v-model="sdata.settings.text_color" label="Text Color"
                                                       color-pip pip-location="prepend-inner"
                                                       variant="outlined" density="compact" mode="hexa" hide-canvas></v-color-input>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                            <v-divider/>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="12" md="4" v-for="(extra,index) in sdata.extras" :key="index">
                                        <v-text-field v-model="extra.icon" density="compact" label="Icon" variant="underlined"></v-text-field>
                                        <v-text-field v-model="extra.title" density="compact" label="Title" variant="underlined"></v-text-field>
                                        <v-text-field v-model="extra.detail" density="compact" label="Title" variant="underlined"></v-text-field>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                            <v-card-actions>
                                <v-btn variant="tonal" type="submit" color="success" prependIcon="mdi-pencil" density="comfortable">Update</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-form>
                </v-col>
            </v-row>
        </v-container>
        <v-card v-if="sdata" flat :color="sdata.settings.background_color" rounded="0">
            <v-container>
                <v-row v-if="sdata.style === 'style1'" align="center">
                    <v-col cols="12" md="6">
                        <div v-for="(extra,edx) in sdata.extras" :key="edx" class="mb-3 d-flex ga-2 align-start">
                            <span class="iconify text-h4" :data-icon="extra.icon"></span>
                            <div>
                                <div class="text-h5">{{extra.title}}</div>
                                <div>{{extra.detail}}</div>
                            </div>
                        </div>
                    </v-col>
                    <v-col cols="12" md="6">
                        <div class="d-flex flex-column ga-3">
                            <h2 class="text-h4">{{sdata.headline}}</h2>
                            <h3 class="text-h5">{{sdata.subheadline}}</h3>
                            <p>{{sdata.promo_text}}</p>
                            <p>{{sdata.privacy_text}}</p>
                            <div class="d-flex ga-3 align-center">
                                <v-text-field variant="solo" density="compact" hide-details></v-text-field>
                                <v-btn color="red" density="default">Subscribe</v-btn>
                            </div>
                        </div>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </div>
</template>
<script>
import { VColorInput } from 'vuetify/labs/VColorInput';
import axios from "axios";

export default {
    name:"ShopSubscribe",
    components:{VColorInput},
    data(){
        return{
            subForm:false,
            sdata:{
                id:'',
                style:"style1",
                headline:"Subscribe to our newsletter",
                subheadline:"Start and grow your business",
                promo_text:"Be the first to hear about new products, fantastic special offers, and news.",
                privacy_text:"We value your privacy and promise to keep your details safe.",
                settings: {
                    background_color: "#212529",
                    text_color: "#ffffff"
                },
                extras:[],
                shop_id:"",
            },
        }
    },
    created() {
        this.getShopSubscribeSection();
    },
    methods:{
        getShopSubscribeSection(){
            axios.get('/sadmin/subscribe/section')
                .then((resp)=>{
                    this.sdata = resp.data.subscribe_section;
                })
        },
        updateSubSection(){
            const udata = {
                id:this.sdata.id,
                style:this.sdata.style,
                headline:this.sdata.headline,
                subheadline:this.sdata.subheadline,
                promo_text:this.sdata.promo_text,
                privacy_text:this.sdata.privacy_text,
                settings:this.sdata.settings,
                extras:this.sdata.extras,
            }
            axios.post('/sadmin/subscribe/section/update',udata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getShopSubscribeSection();
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
        }
    }
}

</script>

<style scoped>

</style>
