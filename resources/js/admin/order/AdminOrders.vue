<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12" md="6">
                <span class="text-h6">Orders</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn class="text-none me-1" size="small" variant="outlined" color="grey-darken-4">Export</v-btn>
                <v-btn class="text-none" size="small" variant="outlined" color="grey-darken-4">More Actions</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <v-toolbar density="default" height="44" color="white">
                        <v-tabs v-model="status" color="primary" grow density="compact" show-arrows class="w-100">
                            <v-tab v-for="(stat, index) in ostatuses" :key="index" :value="stat.value"
                                   class="text-capitalize text-body-1" :color="stat.color" backgroundColor="red">
                                {{ stat.title }}
                            </v-tab>
                        </v-tabs>
                    </v-toolbar>
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
                        <v-data-table :items="filteredOrders" :headers="ordersHeaders" density="comfortable"
                                      hover show-select :search="osearch" :custom-filter="customFilter"
                                      items-per-page="50" :itemsPerPageOptions="[{value:50,title:'50'},{value:100,title:'100'}]"
                                      items-per-page-text="per page"
                                      :loading="isLoading" loading-text="Loading Orders">
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
                                        <div class="font-weight-medium">£{{item.order_total}}</div>
                                    </td>
                                    <td>
                                        <div class="font-weight-regular">{{item.shipping_name}}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex ga-2 align-center">
                                            <v-img v-if="item.payment_method === 'Viva Smart'" max-width="36" max-height="36"
                                                   :src="cdn+'payment/viva_smart.png'"/>
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
                        </v-data-table>
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
export default {
    name:"AdminOrders",
    data(){
        return{
            cdn:this.$store.state.cdn,
            osearch:'',
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
                {title:'Order',value:'order_number',width:150},
                {title:'Date',value:'placed_at',width:150},
                {title:'Customer',value:'shipping_name',maxWidth:375},
                {title:'Total',value:'order_total'},
                {title:'Payment Status',value:'payment_status'},
                {title:'Fulfillment Status',value:'fulfillment_status'},
                {title:'Items',value:'order_items_count'},
                {title:'Delivery Method',value:'shipping_method'},
                {title:'Label Status',value:'label_status'},
            ]
        }
    },
    mounted() {
        this.getAllOrders();
    },
    computed:{
        filteredOrders(){
            let filtered = this.orders.filter((o) => {
                const matchStatus = this.status === "all" || o.order_status === this.status;
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
        getAllOrders(){
            this.isLoading = true;
            axios.get('/sadmin/orders')
                .then((resp)=>{
                    const aorders = resp.data.orders;
                    this.orders = aorders?.map((item) => {
                        if (item.deleted_at !== null) {
                            return {...item, order_status: "archived"};
                        }
                        return item;
                    });
                })
                .finally(()=>{
                    this.isLoading = false;
                })
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
