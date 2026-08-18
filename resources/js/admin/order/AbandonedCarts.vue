<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12" md="6">
                <span class="text-h6">Abandoned Carts</span>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
            </v-col>
            <v-col cols="12">
                <v-tabs v-model="status" color="primary" selectedClass="bg-lblue"
                        density="compact" bgColor="grey-lighten-3" sliderColor="primary"
                        slider-transition="fade" show-arrows class="w-100">
                    <v-tab v-for="(stat, index) in cartstatus" :key="index" :value="stat" class="text-capitalize">
                        {{ stat }}
                        <v-chip size="small" class="ms-2 font-weight-medium" density="comfortable">
                            {{ stat === 'All' ? Object.values(statusCounts).reduce((a, b) => a + b, 0) : statusCounts[stat] }}
                        </v-chip>
                    </v-tab>
                </v-tabs>
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="asearch" class="w-50 me-2" variant="outlined" density="compact"
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
                        <v-data-table-server density="comfortable" mobileBreakpoint="sm"
                            v-model:page="page" loading-text="Loading All carts"
                            :headers="acartsHeaders"
                            :items="acarts"
                            :items-length="totalItems"
                            :items-per-page="itemsPerPage"
                            :loading="isLoading" hover
                            @update:page="getAllCarts"
                        >
                            <template v-slot:item.acart_id="{item}">
                                <div class="title d-flex align-center justify-space-between font-weight-medium">
                                    <router-link :to="'/carts/'+item.acart_id" class="text-decoration-none text-grey-darken-3">
                                        <span>#D{{item.acart_id}}</span>
                                    </router-link>
                                </div>
                                <div class="font-weight-medium">{{item.checkout_id}}</div>
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
                                    <v-img v-if="item.payment_method === 'Stripe'" max-width="36" max-height="36"
                                           :src="cdn+'payment/stripe.png'"/>
                                    <v-img v-if="item.payment_method === 'Bank Transfer' || item.payment_method === 'Bank Deposit'" max-width="36" max-height="36"
                                           :src="cdn+'payment/bank_transfer.png'"/>
                                    <v-chip v-if="item.cart_status === 'converted'"
                                            color="green" density="compact" variant="outlined" class="text-capitalize font-weight-medium">Paid</v-chip>
                                    <v-chip v-else color="red" density="compact" variant="outlined" class="text-capitalize font-weight-medium">pending</v-chip>
                                </div>
                                <v-btn class="mt-1" v-if="item.order_id === null && item.vpayment_id !== null" density="compact" size="small" color="red">
                                    Order Error
                                </v-btn>
                            </template>
                            <template v-slot:item.cart_status="{item}">
                                <v-chip class="text-capitalize font-weight-medium" density="compact"
                                        variant="outlined">{{item.cart_status}}</v-chip>
                            </template>
                        </v-data-table-server>
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
            asearch:'',
            acarts:[],
            isLoading: false,
            status:"All",
            cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
            acartsHeaders:[
                {title:'Cart ID',value:'acart_id',width:150},
                {title:'Date',value:'created_at',width:150},
                {title:'Customer',value:'customer',maxWidth:375},
                {title:'Total',value:'cart_total'},
                {title:'Items',value:'items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Payment Method',value:'payment_method'},
                {title:'Cart Status',value:'cart_status'}
            ],
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            options:{},
            statusCounts:{},
        }
    },
    watch: {
        asearch() {
            this.page = 1;
            this.getAllCarts();
        },

        status() {
            this.page = 1;
            this.getAllCarts();
        }
    },
    mounted() {
        this.getAllCarts()
    },
    computed: {
        cartstatus() {
            const statuses = Object.keys(this.statusCounts);
            return ['All', ...statuses];
        }
    },
    methods:{
        dayjs,
        mergeProps,
        getAllCarts(){
            this.isLoading = true;
            axios.get('/sadmin/carts',{
                params: {
                    page: this.page,
                    search: this.asearch,
                    status: this.status,
                    per_page: this.itemsPerPage
                }
            })
                .then((resp)=>{
                    const aldata = resp.data.acarts;
                    this.statusCounts = resp.data.statusCounts || {};
                    this.acarts = aldata.data;
                    this.totalItems = aldata.total;
                    this.page = aldata.current_page;
                    let statuses = [...new Set(this.acarts.map(item => item.cart_status))];
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
