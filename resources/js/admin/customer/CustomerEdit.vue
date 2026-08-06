<template>
    <v-container>
        <v-row dense>
            <v-col cols="12" md="6">
                <v-btn link to="/customers" icon variant="tonal" density="compact" class="me-2">
                    <v-icon>mdi-chevron-left</v-icon>
                </v-btn>
                <span class="text-h6"><v-icon>mdi-account-outline</v-icon> {{ customer.fname }} {{ customer.lname}}</span>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-menu>
                    <template v-slot:activator="{props}">
                        <v-btn v-bind="props" variant="outlined" class="text-none me-2" append-icon="mdi-chevron-down"
                               density="compact">More Actions</v-btn>
                    </template>
                    <v-list nav density="compact">
                        <v-list-item base-color="dark">
                            <v-list-item-title><v-icon class="me-2">mdi-credit-card-clock-outline</v-icon>Issue Store Credit</v-list-item-title>
                        </v-list-item>
                        <v-list-item base-color="error">
                            <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Customer</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </v-col>
        </v-row>
        <v-row class="bg-white my-3 section-card" no-gutters>
            <v-col cols="12" md="4">
                <div class="pa-3 border-e-sm text-2xl">
                    <div class="font-weight-semibold">Amount Spent</div>
                    <div class="font-weight-medium">£{{amount_spent.toFixed(2)}}</div>
                </div>
            </v-col>
            <v-col cols="12" md="4 border-e-sm">
                <div class="pa-3">
                    <div class="font-weight-semibold">Orders</div>
                    <div class="font-weight-medium">{{orders.length}}</div>
                </div>
            </v-col>
            <v-col cols="12" md="4">
                <div class="pa-3">
                    <div class="font-weight-semibold">Customer Since</div>
                    <div class="font-weight-medium">{{formatCreatedAt(customer.created_at) }}</div>
                </div>
            </v-col>
        </v-row>
        <v-tabs v-model="ctab" density="compact" color="primary" class="mb-2"
                selectedClass="bg-grey-lighten-3" sliderColor="red">
            <v-tab value="last">Last Order</v-tab>
            <v-tab value="past">Past Order</v-tab>
            <v-tab value="credit">Store Credit</v-tab>
        </v-tabs>
        <v-window v-model="ctab">
            <v-window-item value="last">
                <v-row>
                    <v-col cols="12" lg="8">
                        <v-card class="section-card">
                            <v-card-title>Last Order Placed</v-card-title>
                            <v-card-text class="d-flex">
                                <div>
                                    <div>
                                        <v-icon>mdi-package-variant</v-icon>
                                        <span class="font-weight-semibold"> #{{last_order.order_number}}</span>
                                        <v-chip class="font-weight-semibold mx-2" density="compact"> {{last_order.payment_status}}</v-chip>
                                        <v-chip class="font-weight-semibold" density="compact"> {{last_order.fulfillment_status}}</v-chip>
                                    </div>
                                    <div class="text-body-2 font-weight-medium">
                                        <span>{{dayjs(last_order.created_at).format("DD, MMMM YYYY, hh:mm a")}}</span>
                                    </div>
                                </div>
                                <div class="ms-auto font-weight-bold text-h6">£{{last_order.order_total}}</div>
                            </v-card-text>
                            <v-card-text>
                                <v-row v-for="(oitem,index) in last_order.order_items" :key="index" class="border-b-sm">
                                    <v-col cols="12" md="auto">
                                        <div v-if="oitem.variant.variant_image" class="border rounded">
                                            <v-img :src="cdn+oitem.variant.variant_image" max-width="75" min-width="75" width="75" class="rounded"></v-img>
                                        </div>
                                        <div v-else-if="oitem.product.featured_image">
                                            <v-img :src="cdn+oitem.product.featured_image" max-width="75" min-width="75" width="75" class="rounded"></v-img>
                                        </div>
                                        <div v-else>
                                            <v-img :src="cdn+'noimage.png'"></v-img>
                                        </div>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <h3 class="font-weight-bold">{{oitem.title}}</h3>
                                        <div v-if="oitem.options">
                                            <div v-for="(opt,index) in oitem.options">
                                                <v-chip density="compact">
                                                    <span class="me-1 font-weight-medium">{{opt.name}}</span>
                                                    <span class="ms-1 font-weight-medium">{{opt.value}}</span>
                                                </v-chip>
                                            </div>
                                        </div>
                                        <div><b>SKU: </b>{{oitem?.variant?.sku}}</div>
                                    </v-col>
                                    <v-col cols="12" md="2" class="text-end">£{{oitem.price}} x {{oitem.quantity}}</v-col>
                                    <v-col cols="12" md="2" class="text-end">£{{oitem.total}}</v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                        <v-card class="mt-4 section-card">
                            <v-card-title>Timeline</v-card-title>
                            <v-card-text>
                                <v-avatar color="primary">
                                    <v-icon>mdi-account</v-icon>
                                </v-avatar>
                                <v-textarea density="compact" variant="underlined"></v-textarea>
                                <v-btn color="primary" variant="outlined" density="compact" class="text-none mx-auto">Post</v-btn>
                            </v-card-text>
                        </v-card>
                        <div>
                            <v-timeline align="start" side="end" density="compact" dotColor="green" size="x-small">
                                <v-timeline-item id="ctimeline" v-for="(otime,index) in orders" :key="index" class="w-100">
                                    <div class="w-100 d-flex flex-wrap align-center justify-space-between ga-1">
                                        <div class="d-flex ga-1 align-center flex-wrap">
                                            <div>{{otime.order_number}}</div>
                                            <div class="text-body-1">£{{otime.order_total.toFixed(2)}}</div>
                                        </div>
                                        <div class="text-body-2">{{dayjs(otime.placed_at).format('DD-MM-YYYY hh:mm a')}}</div>
                                    </div>
                                </v-timeline-item>
                            </v-timeline>
                        </div>
                    </v-col>
                    <v-col cols="12" lg="4">
                        <v-card class="section-card">
                            <v-card-title>Customer</v-card-title>
                            <v-card-text class="font-weight-medium">
                                <div class="font-weight-semibold">Contact Information</div>
                                <div>{{customer.email}}</div>
                                <div>{{customer.phone}}</div>
                                <div class="font-weight-semibold my-4">Default Address ({{defaultaddress.address_title}})</div>
                                <div>{{defaultaddress.fname}} {{defaultaddress.lname}}</div>
                                <div>{{defaultaddress.address_line1}}</div>
                                <div>{{defaultaddress.address_line2}}</div>
                                <div>{{defaultaddress.city}}</div>
                                <div>{{defaultaddress.postcode}}</div>
                                <div>{{defaultaddress.country}}</div>
                                <div>{{defaultaddress.phone}}</div>
                                <div class="font-weight-semibold my-4">Marketing</div>
                                <div>Email Subscribed</div>
                                <div>SMS not Subscribed</div>
                            </v-card-text>
                        </v-card>
                        <v-card class="mt-4 section-card">
                            <v-card-title class="font-weight-semibold">Store Credit</v-card-title>
                            <v-card-text class="font-weight-medium">
                                <div>None</div>
                            </v-card-text>
                        </v-card>
                        <v-card class="mt-4 section-card">
                            <v-card-title class="font-weight-semibold">Tags</v-card-title>
                            <v-card-text class="font-weight-medium">
                                <v-chip v-for="(ctag,index) in customer.ctags" :key="ctag" variant="outlined" color="primary"
                                        density="comfortable">{{ctag}}</v-chip>
                                <v-btn color="success" size="small" class="ms-2">Add more</v-btn>
                            </v-card-text>
                        </v-card>
                        <v-card class="mt-4 section-card">
                            <v-card-title class="font-weight-semibold">Notes</v-card-title>
                            <v-card-text class="font-weight-medium">
                                <div>None</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-window-item>
            <v-window-item value="past">
                <v-row>
                    <v-col cols="12">
                        <v-card class="section-card">
                            <v-card-title>All Orders</v-card-title>
                            <v-data-table :items="orders" :headers="ordersHeaders">

                            </v-data-table>
                        </v-card>
                    </v-col>
                </v-row>
            </v-window-item>
            <v-window-item value="credit">
                <v-row>
                    <v-col cols="12" md="12">
                        <StoreCreditAdjustment :cshopId="cshop_id"/>
                    </v-col>
                </v-row>
            </v-window-item>
        </v-window>
    </v-container>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";
import relativeTime from 'dayjs/plugin/relativeTime';
import StoreCreditAdjustment from "@/admin/loyalty/StoreCreditAdjustment.vue";
dayjs.extend(relativeTime);
export default {
    name:"CustomerEdit",
    components: {StoreCreditAdjustment},
    props:{customer_id:[Number,String]},
    data(){
        return{
            ctab:'general',
            cdn:this.$store.state.cdn,
            customer:{},
            defaultaddress:{},
            orders:[],
            ordersHeaders:[
                {title:'Order',value:'order_number',width:150},
                {title:'Date',value:'placed_at',width:150},
                {title:'Customer',value:'shipping_name',maxWidth:375},
                {title:'Total',value:'order_total'},
                {title:'Items',value:'order_items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Payment Status',value:'payment_status'},
                {title:'Fulfillment Status',value:'fulfillment_status'},
                {title:'Label Status',value:'label_status'},
            ],
            amount_spent:0.00,
            last_order: {},
            cshop_id: null,
        }
    },
    // created() {
    //     this.getCustomerDetails();
    // },
    mounted() {
        this.getCustomerDetails()
    },
    methods:{
        dayjs,
        formatCreatedAt(dateStr) {
            return dayjs(dateStr).fromNow();
        },
        getCustomerDetails(){
            axios.get('/sadmin/customer/details/'+this.customer_id)
                .then((resp)=>{
                    const customer = resp.data.customer;
                    this.customer = customer;
                    this.defaultaddress = customer.defaultaddress || {};
                    this.orders = resp.data.orders || [];
                    this.amount_spent = resp.data.amount_spent || 0.00;
                    this.last_order = resp.data.last_order || {};
                    this.cshop_id = customer.cshop_id;
                })
        }
    }
}

</script>

<style scoped>
.section-card { border: 1px solid #e1e3e6; box-shadow: 0 1px 2px rgba(16,24,40,0.04); border-radius: 8px; }
</style>
