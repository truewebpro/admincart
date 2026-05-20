<template>
   <v-container class="pa-2">
       <v-row dense>
           <v-col cols="12" md="6">
               <span class="text-h6"><v-icon>mdi-account-outline</v-icon> Customers</span> </v-col>
           <v-col cols="12" md="6" class="text-end">
               <v-btn variant="outlined" class="text-none" color="grey-darken-4" density="compact" @click="exportToCSV">Export</v-btn>
               <v-btn variant="outlined" class="text-none mx-1" color="grey-darken-4" density="compact">Import</v-btn>
               <AddCustomerDialog v-model="showAddCustomer" @created="handleCustomerCreated"/>
               <v-btn @click="showAddCustomer = true" class="text-none" color="grey-darken-4" density="compact">Add Customer</v-btn>
           </v-col>
           <v-col cols="12" md="12">
               <v-card class="border-sm">
                   <v-card-text>
                       <div class="d-flex">
                           <div class="font-weight-medium">{{totalItems}}</div>
                           <div class="mx-1 font-weight-medium text-grey-darken-2">Customers</div>
                           <div class="mx-2">|</div>
                           <div class="font-weight-medium ">100 % <span class="text-grey-darken-2"> of your customer base</span></div>
                       </div>
                   </v-card-text>
               </v-card>
           </v-col>
           <v-col cols="12">
               <v-card flat class="border">
                   <div class="px-2 py-2 d-flex">
                       <v-text-field v-model="csearch" class="w-50 me-2" variant="outlined" density="compact" clearable hide-details
                                     placeholder="Searching all Products"
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
                               <v-list-item @click="setSort('created_at')" title="Last Update" />
                               <v-list-item @click="setSort('amount_spent')" title="Amount Spent" />
                               <v-list-item @click="setSort('orderscount')" title="Total Orders" />
                               <v-divider/>
                               <v-list-item @click="setDirection('asc')" title="A-Z" />
                               <v-list-item @click="setDirection('desc')" title="Z-A" />
                           </v-list>
                       </v-menu>
                   </div>
                   <div>
                       <v-data-table-server
                           v-model:page="page"
                           v-model:sort-by="sortBy"
                           v-model:items-per-page="itemsPerPage"
                           :items="custs"
                           :headers="custsHeaders"
                           :items-length="totalItems"
                           :items-per-page="itemsPerPage"
                           hover
                           :loading="isLoading"
                           @update:options="loadItems"
                           density="comfortable"
                           mobileBreakpoint="sm">
                           <template v-slot:item.fname="{item}">
                               <div class="title d-flex align-center justify-space-between">
                                   <router-link class="text-decoration-none text-grey-darken-3"
                                                :to="'/customers/'+item.customer_id">
                                       <span class="font-weight-medium">{{item.fname}} {{item?.lname}}</span>
                                   </router-link>
                               </div>
                           </template>
                           <template v-slot:item.email="{item}">
                               <div>
                                   <v-chip v-if="item.cstatus === 'active'" variant="tonal" class="font-weight-medium"
                                           color="success" density="compact">Subscribed</v-chip>
                                   <v-chip v-else-if="item.cstatus === 'inactive'" variant="tonal" class="font-weight-medium"
                                           color="grey" density="compact">Not subscribed</v-chip>
                                   <v-chip v-else-if="item.cstatus === 'unsubscribed'" variant="flat" class="font-weight-medium"
                                           color="yellow" density="compact">unsubscribed</v-chip>
                                   <v-chip v-else variant="flat" class="font-weight-medium"
                                           color="grey" density="compact">Not subscribed</v-chip>
                               </div>
                           </template>
                           <template v-slot:item.defaultaddress="{item}">
                               <div v-if="item?.defaultaddress">
                                   <span v-if="item.defaultaddress?.city">{{item.defaultaddress?.city}}, </span>
                                   <span>{{item.defaultaddress?.country}}</span>
                               </div>
                               <div v-else class="text-grey">
                                    No address
                                </div>
                           </template>
                           <template v-slot:item.ordercount="{item}">
                               <div>{{item.ordercount}} order<span v-if="item.ordercount > 1">s</span></div>
                           </template>
                           <template v-slot:item.amount_spent="{item}">
                               <div>£{{ Number(item.amount_spent || 0).toFixed(2) }}</div>
                           </template>
                           <template v-slot:item.ctags="{item}">
                               <div v-if="item.ctags">
                                   <v-chip variant="tonal" density="compact" v-for="(ctag,index) in item.ctags" :key="index">
                                       {{ctag}}
                                   </v-chip>
                               </div>
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
import AddCustomerDialog from "@/admin/customer/AddCustomerDialog.vue";
import debounce from "lodash/debounce";

export default {
    name:"Customers",
    components: {AddCustomerDialog},
    data(){
        return{
            showAddCustomer: false,
            csearch:'',
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            sortBy: [],
            custs:[],
            isLoading: false,
            status:"All",
            custstatus:[],
            cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
            custsHeaders:[
                {title:'Customer Name',value:'fname',width:150},
                {title:'Email Subscription',value:'email',maxWidth:275},
                {title:'Location',key:'defaultaddress',maxWidth:200},
                {title:'Orders',key:'ordercount'},
                {title:'Amount Spent',key:'amount_spent'},
                {title:'Tags',value:'ctags'},
            ]
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getAllCusts();
        }, 400);
    },
    watch:{
        csearch() {
            this.debouncedSearch();
        },
        status() {
            this.page = 1;
            this.getAllCusts();
        },
    },
    methods:{
        mergeProps,
        loadItems(options) {
            this.page = options.page;
            this.itemsPerPage = options.itemsPerPage;
            if (options.sortBy.length > 0) {
                this.sort_by = options.sortBy[0].key;
                this.sort_order = options.sortBy[0].order;
            } else {
                this.sort_by = 'customer_id';
                this.sort_order = 'desc';
            }
            this.getAllCusts();
        },
        setSort(key) {
            this.sortBy = [{
                key: key,
                order: this.sort_order || 'asc'
            }];
            this.sort_by = key;
            this.page = 1;
            this.getAllCusts();
        },
        setDirection(direction) {
            this.sort_order = direction;
            this.sortBy = [{
                key: this.sort_by || 'customer_id',
                order: direction
            }];
            this.page = 1;
            this.getAllCusts();
        },
        handleCustomerCreated(customer) {
            this.getAllCusts();
        },
        async getAllCusts(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/customers',{
                    params:{
                        page:this.page,
                        search:this.csearch,
                        per_page: this.itemsPerPage,
                        sort_by:this.sort_by,
                        sort_order:this.sort_order,
                        status: this.status,
                    }
                })
                const respData = resp.data;
                const allData = respData.customers;
                this.custs = allData.data || [];
                this.totalItems = allData.total;
                this.page = allData.current_page;
            } catch (e) {
                console.error("Failed to load customers", e);
            } finally {
                this.isLoading = false;
            }

        },
        exportToCSV() {
            const headers = ['FirstName','LastName' ,'Email', 'Location','Country', 'Orders', 'Amount Spent', 'Tags'];
            const rows = this.custs.map((cust) => {
                return [
                    `${cust.fname}`,
                    `${cust.lname}`,
                    cust.email || '',
                    `${cust.defaultaddress?.city || ' '}`,
                    `${cust.defaultaddress?.country || ' '}`,
                    cust.ordercount || 0,
                    `${cust.amount_spent}`,
                    (cust.ctags || []).join(', ')
                ];
            });
            const csvContent = [headers, ...rows].map(e => e.join(",")).join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", "customers.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}

</script>

<style scoped>
.title:hover{
    span { text-decoration: underline !important;}
    .opacity-0 {opacity: 1 !important;}
}

</style>
