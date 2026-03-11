<template>
    <v-container class="pa-2">
        <v-row dense>
            <v-col cols="12" md="6">
                <h2>
                    <v-icon>mdi-bank</v-icon>
                    Payment Methods
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-md-end">
            </v-col>
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Shop Payment Methods</v-card-title>
                    <v-list nav v-model:selected="editedItem" base-color="black" active-class="primary">
                        <v-list-item v-for="(item, index) in spmethods" :key="index"
                                     class="border-b"
                                     :prepend-avatar="cdn+item.payment_icon" append-icon="mdi-plus">
                            <template #prepend>
                                <v-icon>mdi-drag-vertical</v-icon>
                                <v-avatar><v-img :src="cdn+item.payment_icon"></v-img></v-avatar>
                            </template>
                            <template #append>
                                <v-btn color="success" density="compact" class="text-none" @click.stop="editExistingMethod(item)">Edit</v-btn>
                            </template>
                            <template #subtitle>
                                <v-btn v-if="item.payment_options.test_mode === 'true'" append-icon="mdi-check-circle" color="red" size="small" density="compact">Test Mode</v-btn>
                                <v-btn v-if="item.payment_options.test_mode === 'false'" append-icon="mdi-close-circle" color="success" size="small" density="compact">Test Mode</v-btn>
                                <v-btn v-if="item.payment_status === 'inactive'" append-icon="mdi-close-circle" color="red" size="small" density="compact" class="ms-1">Status</v-btn>
                                <v-btn v-if="item.payment_status === 'active'" append-icon="mdi-check-circle" color="success" size="small" density="compact" class="ms-1">Status</v-btn>
                            </template>
                            <template #title>
                                <v-btn variant="text" color="black">{{item.payment_name}}</v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-avatar size="48" class="me-2">
                            <v-img :src="cdn+selectedPmethod?.payment_icon"></v-img>
                        </v-avatar>  <v-select v-model="selectedPmethod" :items="availablePmethods" item-value="payment_method" return-object
                                               item-title="payment_name" variant="underlined" hide-details density="compact"></v-select>
                    </v-card-title>
                    <v-card-text>
                        <v-form v-model="favalid">
                            <div v-for="(item,key) in formData" :key="key">
                                <div>
                                    <div class="font-weight-medium">{{key.toUpperCase()}}</div>
                                    <v-radio-group v-if="key === 'environment'" v-model="formData[key]" inline hide-details>
                                        <v-radio value="dev" label="Develoment" density="default" color="green"></v-radio>
                                        <v-radio value="prod" label="Production" density="default" color="red"></v-radio>
                                    </v-radio-group>
                                    <v-text-field v-model="formData[key]" v-if="key === 'company_name'" variant="outlined" density="compact" :rules="compRule" persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'bank_name'" variant="outlined" density="compact" :rules="bankRule" counter="70" counter-value="70" persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'account_no'" variant="outlined" density="compact" :rules="accountRule" counter="50" counter-value="50" persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'sort_code'" variant="outlined" density="compact" :rules="sortcodeRule" counter="30" counter-value="30" persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'client_id'" variant="outlined" density="compact" :rules="cidRule" counter="100" counter-value="100" persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'secret_key'" variant="outlined" density="compact" :rules="secretRule" counter persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'merchant_id'" variant="outlined" density="compact" :rules="merchantRule" counter persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'api_key'" variant="outlined" density="compact" :rules="apiRule" counter persistent-counter></v-text-field>
                                    <v-text-field v-model="formData[key]" v-if="key === 'note'" variant="outlined" density="compact" :rules="noteRule" counter></v-text-field>
                                    <v-radio-group v-if="key === 'test_mode'" v-model="formData[key]" inline>
                                        <v-radio :value="true" label="True" density="default" color="green"></v-radio>
                                        <v-radio :value="false" label="False" density="default" color="red"></v-radio>
                                    </v-radio-group>
                                </div>
                            </div>
                            <v-btn @click="addShopPaymentMethod" :disabled="!favalid" density="compact" prepend-icon="mdi-plus" color="success">Add</v-btn>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-dialog v-model="payDialog" persistent max-width="500">
                <v-card>
                    <v-card-title>Edit {{editedItem.payment_name}} Payment Method</v-card-title>
                    <v-card-text>
                        <v-form v-model="fuvalid">
                            <div v-if="editedItem.payment_method === 'bank_transfer'">
                                <v-text-field label="Company name" v-model="editedItem.payment_options.company_name" variant="outlined"
                                              density="compact" :rules="compRule"></v-text-field>
                                <v-text-field label="Bank name" v-model="editedItem.payment_options.bank_name" variant="outlined"
                                              density="compact" :rules="bankRule"></v-text-field>
                                <v-text-field label="Account number" v-model="editedItem.payment_options.account_no" variant="outlined"
                                              density="compact" :rules="accountRule"></v-text-field>
                                <v-text-field label="Sort code" v-model="editedItem.payment_options.sort_code" variant="outlined"
                                              density="compact" :rules="sortcodeRule"></v-text-field>
                            </div>
                            <div v-if="editedItem.payment_method === 'viva_smart'">
                                <v-text-field label="Client ID" v-model="editedItem.payment_options.client_id" variant="outlined"
                                              density="compact" :rules="cidRule"></v-text-field>
                                <v-text-field label="Secret key" v-model="editedItem.payment_options.secret_key" variant="outlined"
                                              density="compact" :rules="secretRule"></v-text-field>
                            </div>
                            <div v-if="editedItem.payment_method === 'world_pay' || editedItem.payment_method === 'credit_card' || editedItem.payment_method === 'others'">
                                <v-text-field label="Merchant ID" v-model="editedItem.payment_options.merchant_id" variant="outlined"
                                              density="compact" :rules="merchantRule"></v-text-field>
                                <v-text-field label="API key" v-model="editedItem.payment_options.api_key" variant="outlined"
                                              density="compact" :rules="apiRule"></v-text-field>
                            </div>
                            <v-text-field label="Note" v-model="editedItem.payment_options.note" variant="outlined"
                                          density="compact" :rules="noteRule"></v-text-field>
                            <div class="font-weight-medium">Test Mode</div>
                            <v-radio-group v-model="editedItem.payment_options.test_mode" inline hide-details>
                                <v-radio value="true" label="True" color="success"></v-radio>
                                <v-radio value="false" label="False" color="red"></v-radio>
                            </v-radio-group>
                            <div class="font-weight-medium">Payment Status</div>
                            <v-radio-group v-model="editedItem.payment_status" inline hide-details>
                                <v-radio value="active" label="Active" color="green"></v-radio>
                                <v-radio value="inactive" label="inactive" color="red"></v-radio>
                            </v-radio-group>
                            <v-divider/>
                            <div class="d-flex mt-2">
                                <v-spacer/>
                                <v-btn @click="updateShopPaymentMethod" :disabled="!fuvalid" color="info" density="compact">Update</v-btn>
                                <v-spacer/>
                                <v-btn @click="payDialog = false" color="red" density="compact">Cancel</v-btn>
                                <v-spacer/>
                            </div>
                        </v-form>
                    </v-card-text>
                </v-card>
            </v-dialog>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name:"SettingsPayment",
    data(){
        return{
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+"/",
            cdn:this.$store.state.cdn,
            favalid:false,
            fuvalid:false,
            payDialog:false,
            editedItem:{},
            selectedPmethod:{},
            pmethods:[
                {payment_name:'Bank Transfer',payment_method:'bank_transfer',payment_icon:'payment/bank_transfer.png',payment_options:{
                        environment:'dev',
                        company_name:'',
                        bank_name:'',
                        account_no:'',
                        sort_code:'',
                        note:'',
                        test_mode:true,
                    },
                },
                {payment_name:'Viva Smart',payment_method:'viva_smart',payment_icon:'payment/viva_smart.png',payment_options:{
                        environment:'dev',
                        client_id:'',
                        secret_key:'',
                        note:'',
                        test_mode:true,
                    },},
                {payment_name:'World Pay',payment_method:'world_pay',payment_icon:'payment/world_pay.png',payment_options:{
                        environment:'dev',
                        merchant_id:'',
                        api_key:'',
                        note:'',
                        test_mode:true,
                    }},
                {payment_name:'Credit Card',payment_method:'credit_card',payment_icon:'payment/credit_card.png',payment_options:{
                        environment:'dev',
                        merchant_id:'',
                        api_key:'',
                        note:'',
                        test_mode:true,
                    }},
                {payment_name:'Others',payment_method:'others',payment_icon:'payment/others.png',payment_options:{
                        environment:'dev',
                        merchant_id:'',
                        api_key:'',
                        note:'',
                        test_mode:true,
                    }},
            ],
            formData:{},
            spmethods:[],
            compRule:[
                (v) => !!v || "Company Name is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 60) || "Maximum 60 characters allowed"
            ],
            bankRule:[
                (v) => !!v || "Bank Name is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 70) || "Maximum 70 characters allowed"
            ],
            accountRule:[
                (v) => !!v || "Account Number is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 50) || "Maximum 50 characters allowed"
            ],
            sortcodeRule:[
                (v) => !!v || "Sort code is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 30) || "Maximum 30 characters allowed"
            ],
            cidRule:[
                (v) => !!v || "Client ID is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 100) || "Maximum 100 characters allowed"
            ],
            secretRule:[
                (v) => !!v || "Secret Key is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 80) || "Maximum 80 characters allowed"
            ],
            merchantRule:[
                (v) => !!v || "Merchant ID is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 100) || "Maximum 100 characters allowed"
            ],
            apiRule:[
                (v) => !!v || "API key is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 100) || "Maximum 100 characters allowed"
            ],
            noteRule:[
                (v) => !!v || "Note is required",
                (v) => (v && v.length >= 4) || "Minimum 4 characters required",
                (v) => (v && v.length <= 100) || "Maximum 100 characters allowed"
            ]
        }
    },
    computed:{
        availablePmethods(){
            const used = this.spmethods.map(spm => spm.payment_method);
            return this.pmethods.filter(pm => !used.includes(pm.payment_method))
        },
    },
    watch:{
        availablePmethods:{
            handler(newVal){
                if(newVal.length > 1){
                    this.selectedPmethod = newVal[0];
                } else {
                    this.selectedPmethod = null;
                }
            },
            immediate:true
        },
        selectedPmethod(newPal){
            if(newPal){
                this.formData = JSON.parse(JSON.stringify(newPal.payment_options));
            }
        }
    },
    mounted() {
        this.getShopPaymentMethods();
    },
    methods:{
        initFormData(){
            this.formData = {...this.selectedPmethod.payment_options};
        },
        getShopPaymentMethods(){
            axios.get('/sadmin/settings/payment/methods/list')
                .then((resp)=>{
                    this.spmethods = resp.data.spmethods || [];
                })
        },
        addShopPaymentMethod(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const sapdata = {
                payment_name:this.selectedPmethod.payment_name,
                payment_method:this.selectedPmethod.payment_method,
                payment_icon:this.selectedPmethod.payment_icon,
                payment_options:this.formData,
                payment_status:'inactive',
                sort_order:'0',
                shop_id:this.$store.state.shop.shop_id,
            }
            axios.post('/sadmin/settings/payment/method/update',sapdata,uheaders)
                .then((resp)=>{
                    window.Toast.success('Payment Method Added Successfully')
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.getShopPaymentMethods();
                })
        },
        editExistingMethod(item){
            this.payDialog = true;
            this.getShopPaymentMethods();
            this.editedItem = item;
        },
        updateShopPaymentMethod(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const supdata = this.editedItem;
            axios.post('/sadmin/settings/payment/method/update',supdata,uheaders)
                .then((resp)=>{
                    window.Toast.success('Payment Method Updated Successfully')
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.payDialog = false;
                    this.getShopPaymentMethods();
                })
        }
    }
}

</script>

<style scoped>

</style>
