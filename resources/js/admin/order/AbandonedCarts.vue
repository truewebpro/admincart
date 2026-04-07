<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12" md="6">
                <span class="text-h6">Abandoned Carts</span>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none me-1" size="small" variant="outlined" color="grey-darken-4">Export</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <v-toolbar density="default" height="44" color="white">
                        <v-tabs v-model="status" color="primary" density="compact" show-arrows class="w-100">
                            <v-tab v-for="(stat, index) in cartstatus" :key="index" :value="stat" class="text-capitalize">
                                {{ stat }}
                            </v-tab>
                        </v-tabs>
                        <v-slide-group density="compact" class="w-100" show-arrows>
                            <v-spacer></v-spacer>
                            <v-btn variant="text" class="text-none ma-1" density="compact">
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                            <v-btn variant="outlined" class="text-none ma-1" density="compact">
                                <v-icon>mdi-magnify</v-icon>
                                <v-icon>mdi-sort-variant</v-icon>
                            </v-btn>
                            <v-btn variant="outlined" class="text-none ma-1" density="compact" min-width="28" max-width="28">
                                <v-icon>mdi-sort</v-icon>
                            </v-btn>
                        </v-slide-group>
                    </v-toolbar>
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="csearch" class="w-50 me-2" variant="outlined" density="compact"
                                      clearable hide-details
                                      placeholder="Searching Carts"
                                      prepend-inner-icon="mdi-magnify"
                        ></v-text-field>
                        <v-menu>
                            <template v-slot:activator="{ props: menu }">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props: tooltip }">
                                        <v-btn v-bind="mergeProps(menu, tooltip)" icon density="comfortable">
                                            <v-icon size="small">mdi-sort</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Sort</span>
                                </v-tooltip>
                            </template>
                            <v-list density="compact">
                                <v-list-item title="Status Title" />
                                <v-list-item title="Created" />
                                <v-list-item title="Updated" />
                                <v-list-item title="Inventory" />
                                <v-list-item title="Product Type" />
                                <v-list-item title="Brand" />
                            </v-list>
                        </v-menu>
                    </div>
                    <div>
                        <v-data-table :items="acarts" :headers="acartsHeaders" density="comfortable" mobileBreakpoint="sm"
                                      hover :search="asearch" :loading="isLoading" loading-text="Loading All Carts">
                            <template v-slot:item.acart_id="{item}">
                                <div class="title d-flex align-center justify-space-between font-weight-medium">
                                    <router-link :to="'/carts/'+item.acart_id" class="text-decoration-none text-grey-darken-3">
                                        <span>#D{{item.acart_id}}</span>
                                    </router-link>
                                </div>
                            </template>
                            <template v-slot:item.created_at="{item}">
                                <div class="font-weight-medium">{{dayjs(item.created_at).format('D MMM [at] h:mm a')}}</div>
                            </template>
                            <template v-slot:item.customer="{item}">
                                <div class="font-weight-thin">{{item.customer?.fname}} {{item.customer?.lname}}</div>
                            </template>
                            <template v-slot:item.cart_total="{item}">
                                <div class="font-weight-medium">£{{item.cart_total}}</div>
                            </template>
                            <template v-slot:item.items_count="{item}">
                                <div class="font-weight-medium">{{item.items_count}} item<span v-if="item.items_count > 1">s</span></div>
                            </template>
                            <template v-slot:item.shipping_method="{item}">
                                <div class="font-weight-medium">{{item.shipment_name || item.shipping_method}}</div>
                            </template>
                            <template v-slot:item.payment_method="{item}">
                                <div class="d-flex ga-2 align-center">
                                    <v-img v-if="item.payment_method === 'Viva Smart'" max-width="36" max-height="36"
                                           :src="cdn+'payment/viva_smart.png'"/>
                                    <v-img v-if="item.payment_method === 'Bank Transfer' || item.payment_method === 'Bank Deposit'" max-width="36" max-height="36"
                                           :src="cdn+'payment/bank_transfer.png'"/>
                                    <v-chip v-if="item.cart_status === 'converted'"
                                            color="green" density="compact" variant="outlined" class="text-capitalize font-weight-medium">Paid</v-chip>
                                    <v-chip v-else color="red" density="compact" variant="outlined" class="text-capitalize font-weight-medium">pending</v-chip>
                                </div>
                            </template>
                            <template v-slot:item.cart_status="{item}">
                                <v-chip class="text-capitalize font-weight-medium" density="compact"
                                        variant="outlined">{{item.cart_status}}</v-chip>
                            </template>
                        </v-data-table>
                        <!--                        <v-data-table :items="filteredCarts" :headers="cartsHeaders" density="comfortable"-->
                        <!--                                      hover show-select :search="csearch" :custom-filter="customFilter"-->
                        <!--                                      :loading="isLoading" loading-text="Loading Carts">-->
                        <!--                            <template v-slot:item.cart_id="{item}">-->
                        <!--                                <div class="title d-flex align-center justify-space-between font-weight-medium">-->
                        <!--                                    <router-link :to="'/carts/'+item.cart_id" class="text-decoration-none text-grey-darken-3">-->
                        <!--                                        <span>#D{{item.cart_id}}</span>-->
                        <!--                                    </router-link>-->
                        <!--                                </div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.placed_at="{item}">-->
                        <!--                                <div class="font-weight-medium">{{dayjs(item.placed_at).format('D MMM [at] h:mm a')}}</div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.order_total="{item}">-->
                        <!--                                <div class="font-weight-medium">£{{item.order_total}}</div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.payment_status="{item}">-->
                        <!--                                <div class="d-flex ga-2 align-center">-->
                        <!--                                    <v-img v-if="item.payment_method === 'Viva Smart'" max-width="36" max-height="36"-->
                        <!--                                           :src="cdn+'payment/viva_smart.png'"/>-->
                        <!--                                    <v-img v-if="item.payment_method === 'Bank Transfer' || item.payment_method === 'Bank Deposit'" max-width="36" max-height="36"-->
                        <!--                                           :src="cdn+'payment/bank_transfer.png'"/>-->
                        <!--                                    <v-chip v-if="item.payment_status === 'pending' || item.payment_status === 'unpaid'-->
                        <!--                                || item.payment_status === 'refunded' || item.payment_status === 'partially_refunded'-->
                        <!--                                || item.payment_status === 'expired' || item.payment_status === 'voided'"-->
                        <!--                                            color="red" density="compact" variant="outlined" class="text-capitalize font-weight-medium">-->
                        <!--                                        {{item.payment_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                    <v-chip v-else-if="item.payment_status === 'paid' || item.payment_status === 'partially_paid'"-->
                        <!--                                            color="green" density="compact" variant="outlined" class="text-capitalize font-weight-medium">-->
                        <!--                                        {{item.payment_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                </div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.fulfillment_status="{item}">-->
                        <!--                                <div v-if="item.order_id != null">-->
                        <!--                                    <v-chip density="compact" variant="outlined" color="green"-->
                        <!--                                            class="text-capitalize font-weight-medium">-->
                        <!--                                        {{item?.order?.order_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                </div>-->
                        <!--                                <div v-else>-->
                        <!--                                    <v-chip-->
                        <!--                                        density="compact" variant="outlined" color="red"-->
                        <!--                                        class="text-capitalize font-weight-medium"-->
                        <!--                                        v-if="item.fulfillment_status === 'unfulfilled' || item.fulfillment_status === 'on_hold' || item.fulfillment_status === 'request_declined'">-->
                        <!--                                        {{item.fulfillment_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                    <v-chip-->
                        <!--                                        density="compact" variant="outlined" color="green"-->
                        <!--                                        class="text-capitalize font-weight-medium"-->
                        <!--                                        v-else-if="item.fulfillment_status === 'fulfilled' || item.fulfillment_status === 'partially_fulfilled' || item.fulfillment_status === 'scheduled'">-->
                        <!--                                        {{item.fulfillment_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                    <v-chip-->
                        <!--                                        density="compact" variant="outlined" color="success"-->
                        <!--                                        class="text-capitalize font-weight-medium"-->
                        <!--                                        v-else>-->
                        <!--                                        {{item.fulfillment_status}}-->
                        <!--                                    </v-chip>-->
                        <!--                                </div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.label_status="{item}">-->
                        <!--                                <v-chip-->
                        <!--                                    density="compact" variant="flat" color="yellow"-->
                        <!--                                    class="text-capitalize font-weight-medium"-->
                        <!--                                    v-if="item.label_status === 'no_label' || item.label_status === 'cancelled'">-->
                        <!--                                    {{item.label_status}}-->
                        <!--                                </v-chip>-->
                        <!--                                <v-chip-->
                        <!--                                    density="compact" variant="tonal" color="red"-->
                        <!--                                    class="text-capitalize font-weight-medium"-->
                        <!--                                    v-if="item.label_status === 'pending' || item.label_status === 'cancelled'">-->
                        <!--                                    {{item.label_status}}-->
                        <!--                                </v-chip>-->
                        <!--                                <v-chip-->
                        <!--                                    density="compact" variant="outlined" color="green"-->
                        <!--                                    class="text-capitalize font-weight-medium"-->
                        <!--                                    v-if="item.label_status === 'created' || item.label_status === 'printed'">-->
                        <!--                                    {{item.label_status}}-->
                        <!--                                </v-chip>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.cart_items_count="{item}">-->
                        <!--                                <div class="font-weight-medium">{{item.cart_items_count}} item<span v-if="item.cart_items_count > 1">s</span></div>-->
                        <!--                            </template>-->
                        <!--                            <template v-slot:item.shipping_method="{item}">-->
                        <!--                                <div class="font-weight-medium">{{item.shipment_name || item.shipping_method}}</div>-->
                        <!--                            </template>-->
                        <!--                        </v-data-table>-->
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import dayjs from "dayjs";
import {mergeProps} from "vue";
import axios from "axios";

export default {
    name: "AbandonedCarts",
    data(){
        return{
            csearch:'',
            asearch:'',
            carts:[],
            acarts:[],
            isLoading: false,
            status:"All",
            cartstatus:[],
            cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
            cartsHeaders:[
                {title:'Cart ID',value:'cart_id',width:150},
                {title:'Date',value:'placed_at',width:150},
                {title:'Customer',value:'shipping_name',maxWidth:375},
                {title:'Total',value:'order_total'},
                {title:'Items',value:'cart_items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Payment Status',value:'payment_status'},
                {title:'Fulfillment Status',value:'fulfillment_status'},
            ],
            acartsHeaders:[
                {title:'Cart ID',value:'acart_id',width:150},
                {title:'Date',value:'created_at',width:150},
                {title:'Customer',value:'customer',maxWidth:375},
                {title:'Total',value:'cart_total'},
                {title:'Items',value:'items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Payment Method',value:'payment_method'},
                {title:'Cart Status',value:'cart_status'}
            ]
        }
    },
    mounted() {
        this.getAllCarts()
    },
    computed: {
        filteredCarts() {
            let filtered = this.carts.filter((o) => {
                const matchStatus = this.status === "All" || o.order_status === this.status;
                return matchStatus;
            });
            return filtered;
        }
    },
    methods:{
        dayjs,
        mergeProps,
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        getAllCarts(){
            this.isLoading = true;
            axios.get('/sadmin/carts')
                .then((resp)=>{
                    this.carts = resp.data.carts;
                    this.acarts = resp.data.acarts;
                    this.carts = this.carts.map((item) => {
                        if (item.deleted_at !== null) {
                            return {...item, order_status: "Archived"};
                        }
                        return item;
                    });
                    let statuses = [...new Set(this.carts.map(item => item.order_status))];
                    statuses = statuses.filter(status => status !== "Archived");
                    this.cartstatus = ["All", ...statuses, "Archived"];
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        }
    }
}
</script>

<style scoped>

</style>
