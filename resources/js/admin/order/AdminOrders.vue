<template>
    <v-container fluid class="pa-2">
        <v-row dense>
            <v-col cols="12" md="6">
                <span class="text-h6">Orders</span></v-col>
            <v-col cols="12" md="6" class="text-end">
<!--                <v-btn class="text-none me-1" size="small" variant="outlined" color="grey-darken-4">Export</v-btn>-->
<!--                <v-btn class="text-none" size="small" variant="outlined" color="grey-darken-4">More Actions</v-btn>-->
            </v-col>
            <v-col cols="12">
                <v-tabs v-model="status" color="primary" selectedClass="bg-lblue"
                        density="compact" bgColor="grey-lighten-3" sliderColor="primary"
                        slider-transition="fade" show-arrows class="w-100 my-2">
                    <v-tab v-for="(stat, index) in ostatuses" :key="index" :value="stat.value"
                           class="text-capitalize text-body-1 bg-white" :color="stat.color" backgroundColor="red">
                        {{ stat.title }}
                    </v-tab>
                </v-tabs>
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="osearch" class="w-50 me-2" variant="outlined" density="compact"
                                      clearable hide-details
                                      placeholder="Searching Orders"
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
                                <v-list-item title="Order Number" />
                                <v-list-item title="Date" />
                                <v-list-item title="Items" />
                                <v-list-item title="Customer name" />
                                <v-list-item title="Payment Status" />
                                <v-list-item title="Fullfillment Status" />
                                <v-list-item title="Total" />
                            </v-list>
                        </v-menu>
                    </div>
                    <div>
                        <v-data-table-server
                            v-model:page="page"
                            v-model:items-per-page="itemsPerPage"
                            v-model:sort-by="sortBy"
                            :items="orders"
                            :headers="ordersHeaders"
                            :items-length="totalItems"
                            density="comfortable"
                            hover
                            show-select
                            :itemsPerPageOptions="[{value:50,title:'50'},{value:100,title:'100'}]"
                            items-per-page-text="per page"
                            :loading="isLoading"
                            @update:options="loadItems"
                            loading-text="Loading Orders">
                            <template v-slot:item="{item}">
                                <tr class="text-no-wrap" :title="item.deleted_at !== null ? 'archived' :item.order_status"
                                    :class="item.deleted_at !== null ? 'bg-red-lighten-5 archived' : item.order_status"
                                >
                                    <td>
                                        <v-checkbox density="compact" hide-details/>
                                    </td>
                                    <td>
                                        <div class="title d-flex align-center justify-space-between font-weight-medium">
                                            <router-link :to="'/orders/'+item.order_id" class="text-decoration-none text-grey-darken-3">
                                                <span>{{item.order_number}}</span>
                                            </router-link>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-medium">{{dayjs(item.placed_at).format('D MMM [at] h:mm a')}}</div>
                                    </td>
                                    <td>
                                        <div class="font-weight-regular">{{item.shipping_name}}</div>
                                    </td>
                                    <td>
                                        <div class="font-weight-medium">£{{item.order_total}}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex ga-2 align-center">
                                            <v-img v-if="item.payment_method === 'Viva Smart'" max-width="36" max-height="36"
                                                   :src="cdn+'payment/viva_smart.png'"/>
                                            <v-img v-if="item.payment_method === 'Stripe'" max-width="36" max-height="36"
                                                   :src="cdn+'payment/stripe.png'"/>
                                            <v-img v-if="item.payment_method === 'Bank Transfer' || item.payment_method === 'Bank Deposit'" max-width="36" max-height="36"
                                                   :src="cdn+'payment/bank_transfer.png'"/>
                                            <v-chip v-if="item.payment_status === 'pending' || item.payment_status === 'unpaid'
                                                        || item.payment_status === 'refunded' || item.payment_status === 'partially_refunded'
                                                        || item.payment_status === 'expired' || item.payment_status === 'voided'"
                                                    color="red" density="compact" variant="outlined" class="text-capitalize font-weight-medium">
                                                {{item.payment_status}}
                                            </v-chip>
                                            <v-chip v-else-if="item.payment_status === 'paid' || item.payment_status === 'partially_paid'"
                                                    color="green" density="compact" variant="outlined" class="text-capitalize font-weight-medium">
                                                {{item.payment_status}}
                                            </v-chip>
                                        </div>
                                    </td>
                                    <td>
                                        <v-chip
                                            density="compact" variant="outlined" color="red"
                                            class="text-capitalize font-weight-medium"
                                            v-if="item.fulfillment_status === 'unfulfilled' || item.fulfillment_status === 'on_hold' || item.fulfillment_status === 'request_declined'">
                                            {{item.fulfillment_status}}
                                        </v-chip>
                                        <v-chip
                                            density="compact" variant="outlined" color="green"
                                            class="text-capitalize font-weight-medium"
                                            v-else-if="item.fulfillment_status === 'fulfilled' || item.fulfillment_status === 'partially_fulfilled' || item.fulfillment_status === 'scheduled'">
                                            {{item.fulfillment_status}}
                                        </v-chip>
                                        <v-chip
                                            density="compact" variant="outlined" color="success"
                                            class="text-capitalize font-weight-medium"
                                            v-else>
                                            {{item.fulfillment_status}}
                                        </v-chip>
                                    </td>
                                    <td>
                                        <div class="font-weight-medium">{{item.order_items_count}} item<span v-if="item.order_items_count > 1">s</span></div>
                                    </td>
                                    <td>
                                        <div class="font-weight-medium">{{item.shipment_name || item.shipping_method}}</div>
                                    </td>
                                    <td>
                                        <v-chip
                                            density="compact" variant="flat" color="yellow"
                                            class="text-capitalize font-weight-medium"
                                            v-if="item.label_status === 'no_label' || item.label_status === 'cancelled'">
                                            {{item.label_status}}
                                        </v-chip>
                                        <v-chip
                                            density="compact" variant="tonal" color="red"
                                            class="text-capitalize font-weight-medium"
                                            v-if="item.label_status === 'pending' || item.label_status === 'cancelled'">
                                            {{item.label_status}}
                                        </v-chip>
                                        <v-chip
                                            density="compact" variant="outlined" color="green"
                                            class="text-capitalize font-weight-medium"
                                            v-if="item.label_status === 'created' || item.label_status === 'printed'">
                                            {{item.label_status}}
                                        </v-chip>
                                    </td>
                                </tr>
                            </template>
                        </v-data-table-server>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";
import {mergeProps} from "vue";
import dayjs from "dayjs";
import debounce from "lodash/debounce";
export default {
    name:"AdminOrders",
    data(){
        return{
            cdn:this.$store.state.cdn,
            osearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sortBy: [],
            sort_by: '',
            sort_order: 'desc',
            orders:[],
            isLoading: false,
            status:"all",
            selectedType: null,
            selectedBrand: null,
            selectedTag: null,
            orderstatus:[],
            ostatuses:[
                {title:'All',value:"all",color:"primary"},
                {title:'Pending',value:"pending",color:"warning"},
                {title:'Processing',value:"processing",color:"cyan"},
                {title:'Completed',value:"completed",color:"green"},
                {title:'Archived',value:"archived",color:"red"},
            ],
            protypes:[],
            pbrands:[],
            atags:[],
            ordersHeaders:[
                {title:'Order',key:'order_number',width:150},
                {title:'Date',value:'placed_at',width:150},
                {title:'Customer',key:'shipping_name',maxWidth:375},
                {title:'Total',key:'order_total'},
                {title:'Payment Status',key:'payment_status'},
                {title:'Fulfillment Status',key:'fulfillment_status'},
                {title:'Items',key:'order_items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Label Status',value:'label_status'},
            ]
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllOrders();
        }, 400);
    },
    methods:{
        dayjs,
        mergeProps,
        loadItems(options) {
            this.page = options.page;
            this.itemsPerPage = options.itemsPerPage;
            if (options.sortBy.length > 0) {
                this.sort_by = options.sortBy[0].key;
                this.sort_order = options.sortBy[0].order;
            } else {
                this.sort_by = 'placed_at';
                this.sort_order = 'desc';
            }
            this.getAllOrders();
        },
         async getAllOrders(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/orders', {
                    params: {
                        page: this.page,
                        per_page: this.itemsPerPage,
                        search: this.osearch,
                        status: this.status,
                        sort_by: this.sort_by,
                        sort_order: this.sort_order,
                    }
                });
                const allOrders = resp.data.orders;
                this.orders = allOrders.data;
                this.totalItems = allOrders.total;
                this.page = allOrders.current_page;
            } catch (e) {
                console.error("Failed to load orders", e);
            } finally {
                this.isLoading = false;
            }
        }
    },
    watch: {
        osearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllOrders();
        }
    }
}
</script>

<style scoped>
.title:hover{
    span { text-decoration: underline !important;}
    .opacity-0 {opacity: 1 !important;}
}
.v-data-table__td.v-data-table-column--align-start {
    font-weight: 500;
}
tr.processing{
    background-color: rgba(0,255,0,0.05);
}
tr.pending{
    background-color: rgba(255,255,0,0.05);
}
tr.archived{
    text-decoration: line-through;
}
.v-data-table__td.v-data-table-column--align-start.v-data-table__th {
    background: #f1f1f1;
    color: #000;
    font-weight: 600;
    text-transform: capitalize;
    height: 32px;
}
</style>
