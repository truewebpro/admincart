<template>
    <v-container class="theme-page">
        <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact" color="primary">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Themes
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn variant="tonal" color="primary" density="compact" class="text-none"
                       prepend-icon="mdi-eye" :href="'https://'+this.$store.state.shop.subdomain" target="_blank">View Your Store</v-btn>
            </v-col>
        </v-row>
        <v-row class="mt-0">
            <v-col cols="12" md="12">
                <v-tabs v-model="ttab" align-tabs="center" height="32" density="compact" color="primary" selectedClass="bg-lblue"
                        bgColor="grey-lighten-3" sliderColor="primary"
                        class="my-2">
                    <v-tab class="bg-white">Preferences</v-tab>
                    <v-tab class="bg-white">Social Media</v-tab>
                </v-tabs>
                <v-tabs-window v-model="ttab">
                    <v-tabs-window-item>
                        <v-card class="border-sm">
                            <v-card-title>Social sharing image and SEO</v-card-title>
                            <v-card-text>
                                <v-form v-model="pvalid" @submit.prevent="updatePrefs">
                                    <v-row align="start">
                                        <v-col cols="12" md="4">
                                            <v-card class="border-sm" elevation="0">
                                                <v-img v-if="!ogimage" :src="cdn+existOg" :aspect-ratio="120/63" cover></v-img>
                                                <v-img v-else :src="previewImage" :aspect-ratio="120/63" cover></v-img>
                                                <v-file-input
                                                    v-model="ogimage" clearable
                                                    :rules="ogRules"
                                                    variant="underlined"
                                                    accept="image/png, image/jpeg, image/jpg, image/webp"
                                                    hint="Best Size 1200 x 630" persistent-hint>
                                                </v-file-input>
                                                <v-card-text>
                                                    <div>{{this.$store.state.shop.subdomain}}</div>
                                                    <h2 class="font-weight-bold text-truncate">{{mtitle}}</h2>
                                                    <div class="text-truncate">{{mdesc}}</div>
                                                </v-card-text>
                                            </v-card>
                                        </v-col>
                                        <v-col cols="12" md="8">
                                            <div class="mb-3">
                                                <v-img v-if="!logo" :src="cdn+existLogo" max-width="200" max-height="100" contain/>
                                                <v-img v-else :src="logoPreview" max-width="400" max-height="100" contain/>
                                                <v-file-input v-model="logo"
                                                              label="Update or New Logo" clearable persistent-hint
                                                              :rules="logoRules"
                                                              accept="image/png, image/jpeg, image/jpg, image/webp"
                                                              show-size
                                                              variant="underlined"
                                                              prepend-icon="mdi-camera"
                                                              hint="Best Size 400 x 100"/>
                                            </div>
                                            <div>
                                                <v-text-field v-model="mtitle" :rules="mtitleRule" label="Home Page Title" density="compact" variant="outlined"
                                                              persistent-placeholder persistent-counter counter="60"></v-text-field>
                                            </div>
                                            <div class="mt-2">
                                                <v-textarea v-model="mdesc" :rules="mdescRule" label="Meta Description" density="compact" variant="outlined"
                                                            persistent-placeholder persistent-counter counter="320"></v-textarea>
                                            </div>
                                            <div class="mt-2">
                                                <v-btn type="submit" :loading="isLoading" :disabled="!pvalid || isLoading"
                                                       variant="tonal" density="comfortable" color="success"
                                                       class="w-25">Save</v-btn>
                                            </div>
                                        </v-col>
                                    </v-row>
                                </v-form>
                            </v-card-text>
                        </v-card>
                    </v-tabs-window-item>
                    <v-tabs-window-item>
                        <v-card class="border-sm">
                            <v-card-title>Social Media Links</v-card-title>
                            <v-card-text>
                                <v-row align="center">
                                    <v-col cols="12" md="4">
                                        <v-card class="border-sm" elevation="0">
                                            <v-card-text>
                                                <v-row>
                                                    <v-col v-for="smo in smos" :key="smo.name" cols="auto" class="px-1">
                                                        <v-btn icon variant="elevated" :color="smo.color">
                                                            <v-icon>{{smo.icon}}</v-icon>
                                                        </v-btn>
                                                    </v-col>
                                                </v-row>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                    <v-col cols="12" md="8">
                                        <v-form v-model="svalid" @submit.prevent="updateSocials">
                                            <div v-for="smo in smos" :key="smo.name" class="mb-3">
                                                <v-text-field v-model="smlinks[smo.name]" :label="smo.name" density="compact"
                                                              variant="outlined"
                                                              persistent-placeholder persistent-counter counter="70"
                                                              :prefix="smo.prefix" :prepend-inner-icon="smo.icon"></v-text-field>
                                            </div>
                                            <div class="mt-2">
                                            <v-btn type="submit" :disabled="!svalid" variant="tonal" density="comfortable" color="success" class="w-25">Save</v-btn>
                                            </div>
                                        </v-form>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-tabs-window-item>
                </v-tabs-window>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name:"Preferences",
    data(){
        return{
            ttab:null,
            isLoading: false,
            pvalid:false,
            svalid:false,
            cdn:this.$store.state.cdn,
            ntitle:'Theme Page',
            mtitle:"Home Page Title",
            mdesc:"Home Page Description",
            smos:[
                {name:'Facebook',icon:'mdi-facebook',prefix:"https://www.facebook.com/",color:'blue'},
                {name:'Instagram',icon:'mdi-instagram',prefix:"https://www.instagram.com/",color:'purple'},
                {name:'X-Twitter',icon:'mdi-twitter',prefix:"https://www.X.com/",color:'black'},
                {name:'LinkedIn',icon:'mdi-linkedin',prefix:"https://www.linkedin.com/",color:'info'},
                {name:'Youtube',icon:'mdi-youtube',prefix:"https://www.youtube.com/",color:'red'}
            ],
            pref_id:null,
            prefs:[],
            previewImage: '',
            ogimage:null,
            existOg:'',
            ogRules: [
                v => !v || (v && ["image/png", "image/jpeg", "image/jpg", "image/webp"].includes(v.type)) || "Only PNG, JPG, JPEG, or WebP allowed",
                v => !v || (v && v.size < 2 * 1024 * 512) || "File size must be under 1MB"
            ],
            logoPreview: '',
            logo:null,
            existLogo:'',
            logoRules: [
                v => !v || (v && ["image/png", "image/jpeg", "image/jpg", "image/webp"].includes(v.type)) || "Only PNG, JPG, JPEG, or WebP allowed",
                v => !v || (v && v.size < 2 * 1024 * 512) || "File size must be under 1MB"
            ],
            smlinks: {},
            mtitleRule:[
                (v) => !!v || "Title is required",
                (v) => (v && v.length >= 10) || "Minimum 10 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            mdescRule:[
                (v) => !!v || "Description is required",
                (v) => (v && v.length >= 120) || "Minimum 120 characters required",
                (v) => (v && v.length <= 320) || "Maximum 320 characters allowed"
            ],
            shopname:this.$store.state.shop.shop_name || "Shop_name",
            subdomain:this.$store.state.shop.subdomain || "Shop_name",
        }
    },
    created() {
        this.getPreferences();
    },
    mounted() {
        this.updatePreviewImage(this.ogimage);
        this.updateLogoPreview(this.logo);
    },
    watch: {
        ogimage(newVal) {
            this.updatePreviewImage(newVal);
        },
        logo(newVal) {
            this.updateLogoPreview(newVal);
        }
    },
    methods:{
        updateLogoPreview(file) {
            if (file instanceof File) {
                this.logoPreview = URL.createObjectURL(file);
            } else if (typeof file === 'string') {
                this.logoPreview = this.cdn + file;
            } else {
                this.logoPreview = `https://dummyimage.com/1200x630/000/fff&text=${this.shopname}`;
            }
        },
        updatePreviewImage(file) {
            if (file instanceof File) {
                this.previewImage = URL.createObjectURL(file);
            } else if (typeof file === 'string') {
                this.previewImage = this.cdn + file;
            } else {
                this.previewImage = `https://dummyimage.com/1200x630/000/fff&text=${this.shopname}`;
            }
        },
        getPreferences(){
            axios.get('/sadmin/shop/preferences')
                .then((resp)=>{
                    this.pref_id = resp.data.preferences.preference_id;
                    this.prefs = resp.data.preferences;
                    this.mtitle = resp.data.preferences.home_title;
                    this.mdesc = resp.data.preferences.home_description;
                    this.existOg = resp.data.preferences.home_image;
                    this.existLogo = resp.data.preferences.shop_logo;
                    const dbLinks = resp.data.preferences.social_links;
                    this.smlinks = {}; // reset
                    if (Array.isArray(dbLinks)) {
                        dbLinks.forEach((slink) => {
                            this.smlinks[slink.name] = slink.value || '';
                        });
                    }
                })
        },
        updatePrefs(){
            this.isLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const uprefs = {
                preference_id:this.pref_id,
                home_title:this.mtitle,
                home_description:this.mdesc,
                home_image:this.ogimage,
                shop_logo:this.logo,
            }
            axios.post('/sadmin/shop/preference/update',uprefs,uheaders)
                .then((resp)=>{
                    this.getPreferences();
                    window.Toast.success(resp.data.message);
                })
                .catch((err)=>{
                    window.Toast.error(err)
                })
                .finally(()=>{
                    this.isLoading = false;
                    this.logo = null;
                    this.ogimage = null;
                });
        },
        updateSocials(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const ulinks = this.smos.map((smo) => ({
                name: smo.name,
                icon: smo.icon,
                prefix: smo.prefix,
                value: this.smlinks[smo.name] || '',
            }));
            const slinks = {
                preference_id:this.pref_id,
                slinks:ulinks,
            }
            console.log('ulinks',slinks);
            axios.post('/sadmin/shop/social/update',slinks,uheaders)
                .then((resp)=>{
                    this.getPreferences();
                    window.Toast.success(resp.data.message);
                })
                .catch((err)=>{
                    window.Toast.error(err.response.data)
                })
        }
    }
}

</script>

<style scoped>

</style>
