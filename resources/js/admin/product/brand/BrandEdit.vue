<template>
    <v-container>
        <v-row class="position-sticky top-0 bg-grey-lighten-3" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link to="/pros/brands" icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    {{brand.brand_name}}
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn :href="domain+'brands/'+brand.brand_slug" target="_blank" variant="tonal" class="text-none me-2"
                       density="compact" title="Preview" color="primary">Preview</v-btn>
                <v-btn variant="tonal" @click="disCard" class="text-none me-2" density="compact" color="red">Discard</v-btn>
                <v-btn @click="editBrand" :loading="upLoading" :disabled="upLoading" variant="tonal" color="success" density="compact" class="text-none" >Save</v-btn>
            </v-col>
        </v-row>
        <v-tabs v-model="btab" color="primary" selectedClass="bg-lblue"
                density="compact" bgColor="grey-lighten-3" sliderColor="primary"
                slider-transition="fade"
                class="mt-4">
            <v-tab value="general">Brand</v-tab>
            <v-tab value="content">Sections</v-tab>
            <v-tab value="faqs">FAQ's</v-tab>
        </v-tabs>
        <v-window v-model="btab">
            <v-window-item value="general">
                <v-row>
                    <v-col cols="12" md="9">
                        <v-card class="border-sm">
                            <v-card-text>
                                <v-text-field v-model="brand.brand_name" label="Brand Name" :rules="btitleRule"
                                              density="compact" class="mb-1" ref="brandNameField"
                                              variant="outlined" placeholder="Brand name"
                                              persistent-placeholder></v-text-field>
                                <h2 class="font-weight-semibold mb-2">Brand Description</h2>
                                <RichTextEditor v-model="brand.brand_desc"/>
                                <!--                        <v-textarea v-model="brand.brand_desc" label="Brand Description" density="compact" variant="outlined" placeholder="Brand Description" persistent-placeholder></v-textarea>-->
                            </v-card-text>
                        </v-card>
                        <v-card class="border-sm mt-4">
                            <v-card-title>Search engine listing</v-card-title>
                            <v-card-text>
                                <div class="font-weight-medium text-h6">{{this.$store.state.shop.shop_name}}</div>
                                <div class="text-body-2 text-grey-darken-4 mb-2">{{ domain }}>{{brand.brand_slug}}</div>
                            </v-card-text>
                            <v-divider></v-divider>
                            <v-card-text>
                                <v-text-field v-model="brand.meta_title" variant="outlined" density="compact" label="Meta Title"
                                              counter="70" :placeholder="brand.brand_name" persistent-counter
                                              persistent-placeholder class="mb-3"></v-text-field>
                                <v-textarea v-model="brand.meta_desc" variant="outlined" density="compact" label="Meta Description"
                                            counter="160" :placeholder="brand.brand_desc"
                                            persistent-counter persistent-placeholder class="mb-3"></v-textarea>
                                <v-text-field v-model="brand.brand_slug" variant="outlined" density="compact" persistent-placeholder
                                              persistent-hint :prefix="domain+'brands/'"></v-text-field>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-card class="border-sm">
                            <v-card-title>Brand Status</v-card-title>
                            <v-card-text>
                                <v-select v-model="brand.brand_status" variant="outlined" density="compact" label="Status"
                                          :items="['Active','Inactive']" persistent-placeholder></v-select>
                            </v-card-text>
                        </v-card>
                        <v-card class="border-sm mt-4">
                            <v-card-title>Brand Image</v-card-title>
                            <v-card-text>
                                <v-img v-if="brand.brand_image != null" :src="cdn+brand.brand_image" max-height="200"></v-img>
                                <v-img v-else :src="cdn+'noimage.png'" max-height="200"></v-img>
                                <v-file-upload v-model="newImage" density="compact" browse-text="Add Image"
                                               icon="mdi-upload" class="mt-3"
                                               title="Upload Image" clearable show-size
                                ></v-file-upload>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-window-item>
            <v-window-item value="content">
                <BrandSections
                    :sections="sections"
                    :stypes="stypes"
                    :brand_id="brand_id"
                    :allProducts="pros"
                    :allCategories="cats"
                    :allBanners="allBanners"
                    :allBrands="brands"
                    :alinks="alinks"
                    :cdn="cdn"
                    @refresh="getBrandById"
                />
            </v-window-item>
            <v-window-item value="faqs">
                <BrandFaqs
                    :brand_id="brand_id"
                    :faqs="faqs"
                    @refresh-faqs="getBrandById"
                />
            </v-window-item>
        </v-window>

    </v-container>
</template>
<script>
import axios from "axios";
import {VFileUpload} from "vuetify/labs/components";
import RichTextEditor from "@/components/RichTextEditor.vue";
import BrandFaqs from "@/admin/product/brand/BrandFaqs.vue";
import BrandSections from "@/admin/product/brand/BrandSections.vue";
export default {
    name:"BrandEdit",
    props:{
        brand_id:[Number,String]
    },
    components:{BrandSections, BrandFaqs, RichTextEditor, VFileUpload},
    data(){
        return{
            btab:'general',
            stypes:[],
            sections:[],
            cats:[],
            pros:[],
            allBanners:[],
            faqs:[],
            cdn:this.$store.state.cdn,
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+'/',
            upLoading:false,
            dataLoaded: false,
            brand:{},
            brands:[],
            newImage:null,
            btitleRule:[
                (v) => !!v || "Brand is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed",
                v => !this.checkDuplicateBrandName(v) || 'Brand name already exists',
            ],
        }
    },
    watch:{
        brand_id: {
            immediate: true,
            handler(newVal) {
                if (newVal) this.getBrandById();
            }
        },
        brands(){
            this.$nextTick(() => {
                if (this.$refs.brandNameField) {
                    this.$refs.brandNameField.validate();
                }
            });
        }
    },
    computed: {
        alinks() {
            return this.$store.state.alinks;
        },
    },
    mounted() {
        this.getBrandById()
        this.$store.dispatch('fetchAlinks');
    },
    methods:{
       async getBrandById(){
            this.dataLoaded = false;
            await axios.get('/sadmin/brand/'+this.brand_id)
                .then((resp)=>{
                    const allData = resp.data;
                    const branData = allData.brand;
                    this.brand = allData.brand;
                    this.sections = branData.sections || [];
                    this.faqs = branData.faqs || [];
                    this.brands = allData.brands;
                    this.stypes = allData.stypes;
                    this.cats = allData.cats;
                    this.pros = allData.pros;
                    this.dataLoaded = true;
                })
        },
        disCard(){
            this.getBrandById();
        },
        checkDuplicateBrandName(name) {
            if (!this.dataLoaded) return false;
            if (!name) return false;
            const nameLower = name.trim().toLowerCase();
            const currentId = String(this.brand_id);
            return this.brands.some((brand, index) => {
                const brandIdStr = String(brand.brand_id);
                if (currentId && brandIdStr === currentId) return false;
                return brand.brand_name.trim().toLowerCase() === nameLower;
            });
        },
        editBrand(){
            this.upLoading = true;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}};
            const ubrand = {
                brand_id:this.brand_id,
                brand_name:this.brand.brand_name,
                brand_desc:this.brand.brand_desc,
                brand_status:this.brand.brand_status,
                brand_image:this.newImage,
                brand_slug:this.brand.brand_slug,
                meta_title:this.brand.meta_title,
                meta_desc:this.brand.meta_desc,
            }
            axios.post('/sadmin/brand/update',ubrand,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getBrandById();
                })
                .finally(()=>{
                    this.upLoading = false;
                    this.newImage = null;
                })
        }
    }
}

</script>

<style scoped>

</style>
