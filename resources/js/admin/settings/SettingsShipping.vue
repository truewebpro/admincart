<template>
    <v-container class="settings-shipping pa-2">
        <v-row dense>
            <v-col cols="12" md="6">
                <h2>
                    <v-icon>mdi-truck</v-icon>
                    Shipping and Delivery
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-md-end">

            </v-col>
        </v-row>
        <v-row dense>
            <v-col cols="12" md="6">
                <v-card class="border-sm mb-3">
                    <v-card-title class="bg-grey-lighten-4 font-weight-medium">Fulfillment Location</v-card-title>
                    <v-card-subtitle class="py-2">
                        <div class="text-body-1 font-weight-medium">Shop Location</div>
                        <div class="text-body-2">United Kingdom</div>
                    </v-card-subtitle>
                    <v-card-title class="bg-grey-lighten-4 text-body-2">United Kingdom > United Kingdom</v-card-title>
                    <v-card-text>
                        <v-list nav>
                            <v-list-item :prepend-avatar="cdn+item.courier?.courier_logo" v-for="(item,index) in ship_methods" :key="index" class="border-b">
                                <template #title>
                                    <div>{{item.method}}</div>
                                </template>
                                <template #subtitle>
                                    <div>{{item.courier?.courier_name}}</div>
                                </template>
                                <template #append>
                                    <v-btn v-if="item.price === 0" variant="text">Free</v-btn>
                                    <v-btn v-else variant="text">£{{item.price.toFixed(2)}}</v-btn>
                                    <v-btn color="green" density="comfortable" variant="outlined" icon size="small"
                                           @click.stop="editExistingMethod(item)"><v-icon>mdi-pencil</v-icon></v-btn>
                                    <v-btn v-if="index > 0" icon size="small" variant="outlined" class="ms-1"
                                           density="comfortable" color="red"
                                           @click.stop="delExistingMethod(item)"><v-icon>mdi-delete</v-icon></v-btn>
                                </template>
                            </v-list-item>
                        </v-list>
                        <v-dialog v-model="ushipDialog" max-width="400">
                            <v-card>
                                <v-card-title>Edit Rate</v-card-title>
                                <v-card-text>
                                    <div>
                                        <h5>Courier Name</h5>
                                        <v-select v-model="editedItem.courier_id" item-title="courier_name" item-value="courier_id" :items="couriers" density="compact" variant="underlined"></v-select>
                                    </div>
                                    <div>
                                        <h5>Shipping Name</h5>
                                        <v-combobox v-model="editedItem.method" :items="methods" density="compact" variant="underlined" clearable></v-combobox>
                                    </div>
                                    <div>
                                        <h5>Price</h5>
                                        <v-number-input v-model="editedItem.price" density="compact" variant="outlined"
                                                        control-variant="split" :precision="2" :min="0"></v-number-input>
                                    </div>
                                    <div class="d-none">
                                        <h5>Zone</h5>
                                        <v-text-field v-model="editedItem.zone" density="compact" variant="underlined"></v-text-field>
                                    </div>
                                    <v-card flat class="border-sm">
                                        <v-list nav>
                                            <v-list-item :title="editedItem.method" prepend-icon="mdi-radiobox-marked">
                                                <template #append>
                                                    <v-btn v-if="editedItem.price === 0" variant="text">Free</v-btn>
                                                    <v-btn v-else variant="text">£{{editedItem.price.toFixed(2)}}</v-btn>
                                                </template>
                                            </v-list-item>
                                        </v-list>
                                    </v-card>
                                    <div class="text-end mt-2">
                                        <v-btn @click="ushipDialog = false" color="red" variant="outlined" density="compact">Cancel</v-btn>
                                        <v-btn @click="updateExistingMethod" color="success" density="compact" class="ms-2">Done</v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-dialog>
                        <v-btn @click="ashipDialog = true" prepend-icon="mdi-plus-circle-outline" class="text-none" variant="outlined" density="compact">Add Rate</v-btn>
                        <v-dialog v-model="ashipDialog" max-width="400">
                            <v-card>
                                <v-card-title>Add Rate</v-card-title>
                                <v-card-text>
                                    <div>
                                        <h5>Courier Name</h5>
                                        <v-select v-model="defaultMethod.courier_id" item-title="courier_name" item-value="courier_id" :items="couriers" density="compact" variant="underlined"></v-select>
                                    </div>
                                    <div>
                                        <h5>Shipping Name</h5>
                                        <v-combobox v-model="defaultMethod.method" :items="methods" density="compact" variant="underlined" clearable></v-combobox>
                                    </div>
                                    <div>
                                        <h5>Price</h5>
                                        <v-number-input v-model="defaultMethod.price" density="compact" variant="outlined"
                                                        control-variant="split" :precision="2" :min="0"></v-number-input>
                                    </div>
                                    <div class="d-none">
                                        <h5>Zone</h5>
                                        <v-text-field v-model="defaultMethod.zone" density="compact" variant="underlined"></v-text-field>
                                    </div>
                                    <v-card flat class="border-sm">
                                        <v-list nav>
                                            <v-list-item :title="defaultMethod.method" prepend-icon="mdi-radiobox-marked">
                                                <template #append>
                                                    <v-btn v-if="defaultMethod.price === 0" variant="text">Free</v-btn>
                                                    <v-btn v-else variant="text">£{{defaultMethod.price.toFixed(2)}}</v-btn>
                                                </template>
                                            </v-list-item>
                                        </v-list>
                                    </v-card>
                                    <div class="text-end mt-2">
                                        <v-btn @click="ashipDialog = false" color="red" variant="outlined" density="compact">Cancel</v-btn>
                                        <v-btn @click="addNewMethod" color="success" density="compact" class="ms-2">Done</v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-dialog>
                        <v-dialog v-model="dshipDialog" max-width="400">
                            <v-card>
                                <v-card-item title="Delete method" appendIcon="mdi-close"/>
                                <v-card-text class="text-center">
                                    Are you sure to delete <br/> {{editedItem.method}} £{{editedItem.price.toFixed(2)}}
                                    <div class="text-end mt-3">
                                        <v-btn @click="dshipDialog = false" color="red" variant="outlined" density="compact">Cancel</v-btn>
                                        <v-btn @click="deleteExistingMethod" color="red" density="compact" class="ms-2">Delete</v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-dialog>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="border-sm mb-3">
                    <v-card-title class="bg-grey-lighten-4 border-b">
                        <v-icon>mdi-map-marker</v-icon>
                        Location
                    </v-card-title>
                    <v-table density="comfortable">
                        <tbody>
                        <tr>
                            <th width="100">Name</th>
                            <td>{{location.location_name}}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{location.location_address}}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{location.location_status}}</td>
                        </tr>
                        <tr>
                            <th>Country</th>
                            <td>{{location.country}}</td>
                        </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name:"SettingsShipping",
    data(){
        return{
            cdn:this.$store.state.cdn,
            ashipDialog:false,
            ushipDialog:false,
            dshipDialog:false,
            location: {},
            couriers:[],
            ship_methods:[],
            selectedCourier:null,
            methods:['Standard (2 to 4 business days)','Tracked (1 to 3 business days)','Express (1 to 2 business days)'],
            defaultMethod:{
                courier_id:1,
                method:'Standard (2 to 4 business days)',
                price:0.00,
                zone:'Domestic'
            },
            editedItem:{
                ship_method_id:'',
                courier_id:'',
                method:'',
                price:'',
                zone:''
            }
        }
    },
    created() {
        this.getShipping();
    },
    methods:{
        getShipping(){
            axios.get('/sadmin/settings/shipping')
                .then((resp)=>{
                    this.couriers = resp.data.couriers;
                    this.ship_methods = resp.data.ship_methods;
                    this.location = resp.data.location;
                })
        },
        addNewMethod(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const adata = this.defaultMethod;

            axios.post('/sadmin/settings/shipping/update',adata,uheaders)
                .then((resp)=>{
                    window.Toast.success('Shipping Method Added Successfully')
                    this.ashipDialog = false;
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.getShipping();
                })
        },
        editExistingMethod(item){
            this.ushipDialog = true;
            this.getShipping();
            this.editedItem = item;
        },
        delExistingMethod(item){
            this.dshipDialog = true;
            this.getShipping();
            this.editedItem = item;
        },
        updateExistingMethod(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const udata = this.editedItem;

            axios.post('/sadmin/settings/shipping/update',udata,uheaders)
                .then((resp)=>{
                    window.Toast.success('Shipping Method Updated Successfully')
                    this.ushipDialog = false;
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.getShipping();
                })
        },
        deleteExistingMethod(){
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const ddata = this.editedItem;
            axios.post('/sadmin/settings/shipping/delete',ddata,uheaders)
                .then((resp)=>{
                    window.Toast.success(resp.data.message)
                    this.dshipDialog = false;
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
                .finally(()=>{
                    this.getShipping();
                })
        }
    }
}

</script>

<style scoped>

</style>
