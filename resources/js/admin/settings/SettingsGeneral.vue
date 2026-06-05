<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="7">
                <h2 class="text-h5 font-weight-medium mb-2">Company Information</h2>
                <v-card>
                    <v-card-text>
                        <v-form v-model="buvalid" @submit.prevent="updateBusiness">
                            <v-row dense>
                                <v-col cols="12" md="12">
                                    <v-text-field v-model="business.business_name" :rules="nameRule" counter persistent-counter
                                                  label="Business Name"
                                                  density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-combobox
                                    label="Search Address"
                                    v-model="selectedPlace"
                                    :items="predictions"
                                    item-title="description"
                                    item-value="place_id"
                                    variant="underlined"
                                    density="comfortable"
                                    hide-no-data clearable
                                    hide-selected
                                    return-object
                                    :loading="loading"
                                    @update:search="onSearchChange"
                                    @update:model-value="onPlaceSelect"
                                ></v-combobox>
                                <v-col cols="12" md="12">
                                    <v-text-field v-model="business.address_line1" :rules="addressRule" item-title="description"
                                                  label="Address Line 1" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="12">
                                    <v-text-field v-model="business.address_line2" :rules="addressRule" item-title="name"
                                                  label="Address Line 2" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.region" :rules="regionRule" item-title="name"
                                                    label="Region" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.postcode" :rules="postcodeRule"
                                                  label="Postcode" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-autocomplete v-model="business.country" :rules="countryRule" :items="availableCountries"
                                                  label="Country" density="comfortable" variant="underlined"
                                                    item-title="name" item-value="isoCode"></v-autocomplete>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.reg_no" label="Reg No" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.vat_no" label="VAT No" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.email" :rules="emailRule"
                                                  label="Email" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.phone" :rules="phoneRule" label="Phone" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="business.whatsapp" :rules="whatsappRule" label="Whatsapp" density="comfortable" variant="underlined"></v-text-field>
                                </v-col>
                                <v-col cols="12" md="12">
                                    <v-btn :disabled="!buvalid" type="submit" color="success">Update</v-btn>
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="5">
                <h2 class="text-h5 font-weight-medium mb-2">Business Shops</h2>
                <v-card v-for="shop in shops" :key="shop.shop_id" class="mb-3">
                    <v-card-title>{{shop.shop_name}}</v-card-title>
                    <v-card-text class="text-left">
                        <v-table>
                            <tr>
                                <th>Shop Slug</th>
                                <td>{{shop.shop_slug}}</td>
                            </tr>
                            <tr>
                                <th>Shop Subdomain</th>
                                <td>{{shop.subdomain}}</td>
                            </tr>
                            <tr>
                                <th>Shop Main Domain</th>
                                <td>{{shop.maindomain}}</td>
                            </tr>
                            <tr>
                                <th>Shop Status</th>
                                <td>{{shop.shop_status}}</td>
                            </tr>
                            <tr>
                                <th>Order Prefix</th>
                                <td>{{shop.order_prefix}}</td>
                            </tr>
                        </v-table>
                    </v-card-text>
                </v-card>
                <v-card>
                    <v-card-title>Shop Settings</v-card-title>
                    <v-card-text>
                        <v-form v-model="settingValid" @submit.prevent="updateSetting">
                            <v-number-input v-model="setting.min_checkout_price"
                                            label="Min Checkout Price" variant="outlined" control-variant="split"
                                            density="compact" :min="0.99" :precision="2" prefix="GBP"></v-number-input>
                            <h4>Shipping Protection enabled</h4>
                            <v-radio-group v-model="setting.shipping_protection_enabled" inline>
                                <v-radio :value="true" label="Yes" color="green"></v-radio>
                                <v-radio :value="false" label="No" color="red"></v-radio>
                            </v-radio-group>
                            <v-number-input v-model="setting.shipping_protection_fee"
                                            label="Shipping Protection Fee" variant="outlined" control-variant="split"
                                            density="compact" :min="0" :precision="2" prefix="GBP">

                            </v-number-input><v-number-input v-model="setting.free_delivery_amount"
                                            label="Free Delivery Amount" variant="outlined" control-variant="split"
                                            density="compact" :min="0" :precision="2" prefix="GBP"></v-number-input>

                            <h4>Vat Included on Checkout</h4>
                            <v-radio-group v-model="setting.vat_included" inline>
                                <v-radio :value="true" label="Yes" color="green"></v-radio>
                                <v-radio :value="false" label="No" color="red"></v-radio>
                            </v-radio-group>
                            <h4>Hide Prices (B2B)</h4>
                            <v-radio-group v-model="setting.hide_price" inline>
                                <v-radio :value="true" label="Yes" color="green"></v-radio>
                                <v-radio :value="false" label="No" color="red"></v-radio>
                            </v-radio-group>
                            <v-btn type="submit" density="comfortable" color="info">Update</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";
// import { Country, State, City } from 'country-state-city';

export default {
    name:"SettingsGeneral",
    mounted() {
        // this.getAllofThem();
    },
    data(){
        return{
            countries:[],
            availableCountries:[
                {name:"United Kingdom",isoCode:"GB"}
            ],
            states:[],
            cities:[],

            buvalid:false,
            business:{
                business_name:'',
                address_line1:'',
                address_line2:'',
                region:'',
                postcode:'',
                country:'',
                reg_no:'',
                vat_no:'',
                email:'',
                phone:'',
                whatsapp:'',
            },
            ebusiness:{
                business_name:'',
                address_line1:'',
                address_line2:'',
                region:'',
                postcode:'',
                country:'',
                reg_no:'',
                vat_no:'',
                email:'',
                phone:'',
                whatsapp:'',
            },
            searchQuery :'',
            selectedPlace:null,
            loading:false,
            predictions:[],
            place_id: '',
            shops:[],
            nameRule:[
                (v) => !!v || "Company Name is required",
                (v) => (v && v.length >= 5) || "Minimum 5 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed",
            ],
            addressRule:[
                (v) => !!v || "Address is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed",
            ],
            regionRule:[
                (v) => !!v || "Region is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed",
            ],
            postcodeRule:[
                (v) => !!v || "Postcode is required",
                (v) => (v && v.length >= 3) || "Minimum 3 characters required",
                (v) => (v && v.length <= 10) || "Maximum 10 characters allowed",
            ],
            countryRule:[
                (v) => !!v || "Country is required",
                (v) => (v && v.length >= 2) || "Minimum 2 characters required",
                (v) => (v && v.length <= 20) || "Maximum 20 characters allowed",
            ],
            emailRule:[
                (v) => !!v || "Email is required",
                (v) => (v && v.length >= 6) || "Minimum 6 characters required",
                (v) => (v && v.length <= 30) || "Maximum 30 characters allowed",
            ],
            phoneRule:[
                (v) => !!v || "Phone is required",
                (v) => (v && v.length >= 10) || "Minimum 10 characters required",
                (v) => (v && v.length <= 10) || "Maximum 10 characters allowed",
            ],
            whatsappRule:[
                (v) => !!v || "Whatsapp is required",
                (v) => (v && v.length >= 10) || "Minimum 10 characters required",
                (v) => (v && v.length <= 10) || "Maximum 10 characters allowed",
            ],
            setting:{
                setting_id:'',
                shop_id:'',
                min_checkout_price:0.99,
                vat_included:true,
                hide_price:false,
                shipping_protection_enabled:false,
                shipping_protection_fee:0,
                free_delivery_amount:40,
            },
            settingValid:false,
        }
    },
    created() {
        this.getShopBusiness();
    },
    methods:{
        getShopBusiness(){
            axios.get('/sadmin/shop/business')
                .then((resp)=>{
                    this.shops = resp.data.shops || null;
                    this.ebusiness = resp.data.business || null;
                    this.setting = resp.data.setting;
                    if(resp.data.business === null){
                        this.ebusiness = this.business;
                    } else {
                        this.business = this.ebusiness;
                    }
                })
        },
        updateBusiness(){
            const udata = this.business;
            axios.post('/sadmin/shop/business/update',udata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getShopBusiness();
                })
                .catch(err =>{
                    window.Toast.error(err.message);
                })
        },
        updateSetting(){
            const sdata = {
                setting_id:this.setting.setting_id,
                shop_id:this.setting.shop_id,
                min_checkout_price:this.setting.min_checkout_price,
                vat_included:this.setting.vat_included,
                hide_price:this.setting.hide_price,
                shipping_protection_enabled:this.setting.shipping_protection_enabled,
                shipping_protection_fee:this.setting.shipping_protection_fee,
                free_delivery_amount:this.setting.free_delivery_amount,
            }
            axios.post('/sadmin/shop/setting/update',sdata)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getShopBusiness();
                })
        },
        async onSearchChange(val) {
            this.searchQuery = val;
            if (val && val.length > 3) {
                await this.getPredictions(val);
            } else {
                this.predictions = [];
            }
        },
        async getPredictions(input) {
            try {
                this.loading = true;
                const resp = await axios.get(`/google/autocomplete?query=${encodeURIComponent(input)}`);
                this.predictions = resp.data.predictions || [];
            } catch (err) {
                console.error("Prediction error:", err);
            } finally {
                this.loading = false;
            }
        },
        async onPlaceSelect(place) {
            if (place && place.place_id) {
                await this.getPlaceDetails(place.place_id);
            }
        },
        async getPlaceDetails(placeId) {
            try {
                const resp = await axios.get(`/google/details?place_id=${placeId}`);
                const details = resp.data.result;

                console.log("Full address details:", details);

                // Auto-fill your fields:
                this.business.address_line1 = this.extractComponent(details, "street_number")+ ' ' + this.extractComponent(details, "route") ;
                this.business.address_line2 = this.extractComponent(details, "sublocality") || this.extractComponent(details, "locality") + ' ' + this.extractComponent(details, "postal_town");
                this.business.region = this.extractComponent(details, "administrative_area_level_2") || this.extractComponent(details, "postal_town");
                this.business.postcode = this.extractComponent(details, "postal_code");
                this.business.country = this.extractComponent(details, "country");

                console.log("Auto-filled business:", this.business);
            } catch (err) {
                console.error("Place details error:", err);
            }
        },
        extractComponent(details, type) {
            const comp = details.address_components?.find((c) => c.types.includes(type));
            return comp ? comp.long_name : "";
        },
    },
}

</script>

<style scoped>

</style>
