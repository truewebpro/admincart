<template>
   <v-container class="pa-2">
       <v-row dense>
           <v-col cols="12" md="6">
               <span class="text-h6"><v-icon>mdi-account-outline</v-icon> Customers</span> </v-col>
           <v-col cols="12" md="6" class="text-end">
               <v-btn variant="outlined" class="text-none" color="grey-darken-4" density="compact" @click="exportToCSV">Export</v-btn>
               <v-btn variant="outlined" class="text-none mx-1" color="grey-darken-4" density="compact">Import</v-btn>
               <v-btn class="text-none" color="grey-darken-4" density="compact">Add Customer</v-btn>
           </v-col>
           <v-col cols="12" md="12">
               <v-card class="border-sm">
                   <v-card-text>
                       <div class="d-flex">
                           <div class="font-weight-medium">{{custs.length}}</div>
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
                               <v-list-item title="Last Update" />
                               <v-list-item title="Amount Spent" />
                               <v-list-item title="Total Orders" />
                               <v-divider/>
                               <v-list-item title="A-Z" />
                               <v-list-item title="Z-A" />
                           </v-list>
                       </v-menu>
                   </div>
                   <div>
                       <v-data-table :items="filteredCusts" :headers="custsHeaders" density="comfortable"
                                     hover :search="csearch" :custom-filter="customFilter" :loading="isLoading"
                                     :hide-default-footer="filteredCusts.length < 50" items-per-page="50" mobileBreakpoint="sm">
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
                               <div>
                                   <span v-if="item.defaultaddress?.city">{{item.defaultaddress?.city}}, </span>
                                   <span>{{item.defaultaddress?.country}}</span>
                               </div>
                           </template>
                           <template v-slot:item.ordercount="{item}">
                               <div>{{item.ordercount}} order<span v-if="item.ordercount > 1">s</span></div>
                           </template>
                           <template v-slot:item.amount_spent="{item}">
                               <div>£{{(item.amount_spent).toFixed(2)}}</div>
                           </template>
                           <template v-slot:item.ctags="{item}">
                               <div v-if="item.ctags">
                                   <v-chip variant="tonal" density="compact" v-for="(ctag,index) in item.ctags" :key="index">
                                       {{ctag}}
                                   </v-chip>
                               </div>
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

export default {
    name:"Customers",
    data(){
        return{
            csearch:'',
            custs:[],
            isLoading: false,
            status:"All",
            custstatus:[],
            cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
            custsHeaders:[
                {title:'Customer Name',value:'fname',width:150},
                {title:'Email Subscription',value:'email',maxWidth:275},
                {title:'Location',value:'defaultaddress',maxWidth:200},
                {title:'Orders',key:'ordercount'},
                {title:'Amount Spent',key:'amount_spent'},
                {title:'Tags',value:'ctags'},
            ]
        }
    },
    mounted() {
        this.getAllCusts();
    },
    created() {
        // this.getAllCusts();
    },
    computed: {
        filteredCusts() {
            // if (this.status === "All") return this.custs;
            if (this.status === "Archived") return this.custs.filter(p => p.status === "Archived");
            // return this.custs.filter(p => p.product_status === this.status);
            return this.custs.filter((p) => {
                const matchStatus = this.status === "All" || p.status === this.status;
                return matchStatus;
            });
        }
    },
    methods:{
        mergeProps,
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        getAllCusts(){
            this.isLoading = true;
            axios.get('/sadmin/customers')
                .then((resp)=>{
                    this.custs = resp.data.customers;
                })
                .finally(()=>{
                    this.isLoading = false;
                })
        },
        exportToCSV() {
            const headers = ['Name', 'Email', 'Location', 'Orders', 'Amount Spent', 'Tags'];
            const rows = this.filteredCusts.map((cust) => {
                return [
                    `${cust.fname} ${cust.lname}`,
                    cust.email || '',
                    `${cust.defaultaddress?.city || ''}, ${cust.defaultaddress?.country || ''}`,
                    cust.ordercount || 0,
                    `£${cust.amount_spent}`,
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
