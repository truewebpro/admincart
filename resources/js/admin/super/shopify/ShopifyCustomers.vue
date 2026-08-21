<template>
    <v-row dense>
        <v-col cols="12">
            <v-card>
                <v-card-title>Fetched Customer</v-card-title>
                <v-text-field v-model="customer_search" class="ma-2" clearable density="compact" variant="outlined"
                              hide-details appendInnerIcon="mdi-magnify" placeholder="Search Customer..."></v-text-field>
                <v-row dense v-if="selectedPros.length > 0" class="mb-2 px-2">
                    <v-col cols="6" md="3">
                        <v-btn variant="text" density="compact" class="font-weight-bold text-none">{{selectedPros.length}} Selected</v-btn>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-btn @click="selectedPros = []" variant="outlined" density="compact" class="text-none">Unselect All</v-btn>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-menu>
                            <template v-slot:activator="{ props: menu }">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props: tooltip }">
                                        <v-btn variant="outlined" class="text-none me-5" v-bind="mergeProps(menu, tooltip)"
                                               density="compact" append-icon="mdi-chevron-down">
                                            More Actions
                                        </v-btn>
                                    </template>
                                    <span>More Actions</span>
                                </v-tooltip>
                            </template>
                            <!--                                <v-list nav density="compact">-->
                            <!--                                    <v-list-item @click="archiveCustomer">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-archive-outline</v-icon>Archive Customer</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-list-item base-color="error" @click="deleteCustomer">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Customer</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-divider class="mt-1"/>-->
                            <!--                                    <v-list-item @click="addBulkTagDialog = true">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-tag-plus-outline</v-icon>Add Tags</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-list-item base-color="error" @click="removeBulkTagDialog = true">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-tag-remove-outline</v-icon>Remove Tags</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                    <v-divider class="mt-1"/>-->
                            <!--                                    <v-list-item base-color="success" @click="exportSelectedCustomer">-->
                            <!--                                        <v-list-item-title><v-icon class="me-2">mdi-export</v-icon>Export Customer</v-list-item-title>-->
                            <!--                                    </v-list-item>-->
                            <!--                                </v-list>-->
                        </v-menu>
                    </v-col>
                </v-row>
                <v-data-table-server
                    v-model:page="page"
                    v-model:items-per-page="itemsPerPage"
                    :items="scusts"
                    :headers="scustsHeader"
                    :items-length="totalItems"
                    :items-per-page="itemsPerPage"
                    v-model="selectedPros"
                    hover show-select
                    return-object
                    :loading="isLoading"
                    @update:options="loadItems"
                    density="comfortable"
                    mobileBreakpoint="sm"
                >
                    <template #item.first_name="{item}">
                        <div class="py-1">
                            <div>{{item.first_name}} {{item.last_name}}</div>
                        </div>
                    </template>
                    <template #item.email="{item}">
                        <div class="py-1">
                            <div>{{item.email}}</div>
                        </div>
                    </template>
                    <template #item.addresses="{item}">
                        <div class="py-1">
                            <div>
                                {{item.addresses}}
                            </div>
                        </div>
                    </template>
                    <template #item.actions="{item}">
                        <v-btn v-if="!item.customer_id" @click="createCustomerInSystem(item)" size="small"
                               color="success" variant="outlined" density="comfortable">
                            Create
                        </v-btn>
                        <v-btn v-else size="small"
                               color="success" variant="tonal" density="comfortable">
                            Done
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card>
        </v-col>
        <pre>{{scusts}}</pre>
    </v-row>
</template>
<script>
import {mergeProps} from "vue";
import debounce from "lodash/debounce";
import dayjs from "dayjs";

export default {
    name: "ShopifyCustomers",
    data(){
        return{
            shop_id:this.$store.state.shop_id,
            customer_search:"",
            stotal:0,
            page: 1,
            itemsPerPage: 50,
            totalItems: 0,
            sort_by: '',
            sort_order: 'desc',
            only_not_imported: true,
            sortBy: [],
            selectedPros:[],
            selectedCustomer:{},
            scusts:[],
            isLoading: false,
            scustsHeader:[
                {title:"ID",key:'thirdparty_id'},
                {title:"Name",key:'first_name'},
                {title:"Phone",key:'phone'},
                {title:"Email",key:'email'},
                {title:"Addresses",key:'addresses'},
                {title:"CustomerId",key:'customer_id'},
                {title:"Actions",key:'actions'},
            ],
        }
    },
    computed: {
        dayjs() {
            return dayjs
        }
    },
    created() {
        this.debouncedSearch = debounce(() => {
            this.page = 1;
            this.getSyncedCustomer();
        }, 400);
    },
    watch:{
        customer_search() {
            this.debouncedSearch();
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
                this.sort_by = 'id';
                this.sort_order = 'desc';
            }
            this.getSyncedCustomer();
        },
        async getSyncedCustomer(){
            this.isLoading = true;
            const shopId = this.shop_id;
            try {
                const resp = await axios.get(`/superadmin/shopify/${shopId}/synced-customers`,{
                    params:{
                        page:this.page,
                        search:this.customer_search,
                        per_page: this.itemsPerPage,
                        sort_by:this.sort_by,
                        sort_order:this.sort_order,
                        only_not_imported:this.only_not_imported,
                    }
                })
                const respData = resp.data;
                const allData = respData.items;
                this.scusts = allData.data;
                this.totalItems = allData.total;
                this.page = allData.current_page;
                this.stotal = respData.stotal;
            }
            catch (e) {
                console.error("Failed to load customer", e);
            } finally {
                this.isLoading = false;
            }
        },
        createCustomerInSystem(item){
            this.isLoading = true;
            axios.post('/superadmin/shopify/create-single-customer',{
                id:item.id
            }).then((resp)=>{
                console.log("respIn",resp.data);
                this.getSyncedCustomer();
            })
                .catch((err)=>{
                    console.log(err)
                })
                .finally(()=>{
                    this.isLoading = false
                })
        },

    }
}
</script>
