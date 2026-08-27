<template>
    <v-row>
        <v-col v-if="liveOrders?.length" cols="12" md="12">
            <v-card>
                <v-card-text v-if="selected.length" class="d-flex align-center justify-space-between mb-3">
                    <span class="text-body-2 text-medium-emphasis">
                      {{ selected.length }}/10 selected
                    </span>
                    <v-btn v-if="selected.length"
                        size="small"
                        color="red"
                        @click="selected = []"
                    >
                        Cancel Selection
                    </v-btn>
                    <v-btn v-if="selected.length"
                        size="small"
                        color="primary"
                        :disabled="!selected.length || bulkCreating"
                        :loading="bulkCreating"
                        @click="confirmBulkCreate = true"
                    >
                        Create Selected ({{ selected.length }})
                    </v-btn>
                </v-card-text>

                <v-data-table-server
                    v-model="selected"
                    show-select
                    item-value="id"
                    density="compact"
                    :items="liveOrders"
                    :headers="liveHeaders"
                    :items-length="-1"
                    :loading="loading"
                    hide-default-footer
                    hover
                    striped="even"
                    mobileBreakpoint="sm"
                    loadingText="Loading Shopify Orders List"
                    noDataText="Order Scope is Missing"
                >
                    <template #item.order_number="{item}">
                        <div class="font-weight-medium">{{item.name}}</div>
                    </template>
                    <template #item.created_at="{item}">
                        <div class="text-body-2">{{dayjs(item.created_at).format('D MMM [at] h:mm a')}}</div>
                    </template>
                    <template #item.customer="{item}">
                        <div class="text-body-2 font-weight-semibold opacity-70">{{item.customer?.first_name}} {{item.customer?.last_name}}</div>
                    </template>
                    <template #item.total_price="{item}">
                        <div class="text-body-2 font-weight-medium">£{{item.total_price}}</div>
                    </template>
                    <template #item.financial_status="{item}">
                        <v-chip density="compact" variant="tonal" color="gray-lighten-3" class="text-capitalize font-weight-medium">{{item.financial_status}}</v-chip>
                    </template>
                    <template #item.fulfillment_status="{item}">
                        <v-chip v-if="item.fulfillment_status != null" density="compact" class="text-capitalize font-weight-medium">
                            {{item.fulfillment_status}}
                        </v-chip>
                        <v-chip v-else density="compact" class="text-capitalize font-weight-medium">
                            {{'pending'}}
                        </v-chip>
                    </template>
                    <template #item.line_items="{item}">
                        <div v-if="item.line_items">
                            {{item.line_items.length}} Item{{item.line_items.length > 1 ? 's' : ''}}
                        </div>
                    </template>
                    <template #item.shipping_lines="{item}">
                        <div class="text-body-2" v-if="item.shipping_lines && item.shipping_lines.length">
                            {{item.shipping_lines[0]?.title}}
                        </div>
                    </template>
                    <!-- Per-row create action -->
                    <template #item.actions="{item}">
                        <v-chip
                            v-if="isAlreadySaved(item.id)"
                            size="small"
                            color="success"
                            variant="tonal"
                            prepend-icon="mdi-check"
                        >
                            Saved
                        </v-chip>

                        <v-btn
                            v-else
                            size="small"
                            variant="tonal"
                            :disabled="creatingIds.includes(item.id)"
                            :loading="creatingIds.includes(item.id)"
                            @click="createSingle(item)"
                        >
                            Create
                        </v-btn>
                    </template>

                    <template #bottom>
                        <div class="d-flex align-center justify-space-between pa-3">
                            <span class="text-caption text-medium-emphasis">
                              {{ liveOrders.length }} orders on this page
                            </span>
                            <div class="d-flex ga-2">
                                <v-btn
                                    size="small"
                                    variant="tonal"
                                    :disabled="!previousPageInfo || loading"
                                    @click="goPrevious"
                                >
                                    <v-icon icon="mdi-chevron-left" start />
                                    Previous
                                </v-btn>
                                <v-btn
                                    size="small"
                                    variant="tonal"
                                    :disabled="!nextPageInfo || loading"
                                    @click="goNext"
                                >
                                    Next
                                    <v-icon icon="mdi-chevron-right" end />
                                </v-btn>
                            </div>
                        </div>
                    </template>

                </v-data-table-server>

                <!-- Bulk create confirmation -->
                <v-dialog v-model="confirmBulkCreate" max-width="420">
                    <v-card>
                        <v-card-title>Create {{ selected.length }} orders?</v-card-title>
                        <v-card-text>
                            This will create real orders in your system for the {{ selected.length }} selected Shopify order{{ selected.length > 1 ? 's' : '' }}. This can't be undone from here.
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer />
                            <v-btn variant="text" @click="confirmBulkCreate = false">Cancel</v-btn>
                            <v-btn color="primary" @click="createBulk">Confirm</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>

                <v-snackbar v-model="showResultSnackbar" :timeout="4000">
                    {{ resultMessage }}
                </v-snackbar>

            </v-card>

        </v-col>
    </v-row>
</template>
<script>
import dayjs from "dayjs";

export default {
    name: "ShopifyOrders",
    data(){
        return{
            shop_id:this.$store.state.shop_id,
            liveOrders:[],
            nextPageInfo: null,
            previousPageInfo: null,
            loading: false,
            perPage: 50,

            selected: [],
            creatingIds: [],
            bulkCreating: false,
            confirmBulkCreate: false,
            showResultSnackbar: false,
            resultMessage: '',

            liveHeaders:[
                {title:'Order',key:'order_number'},
                {title:'Date',key:'created_at',width:160},
                {title:'Customer',key:'customer',sortable: false},
                {title:'Total',key:'total_price',align:'end'},
                {title:'Payment Status',key:'financial_status',sortable: false},
                {title:'Fulfillment Status',key:'fulfillment_status',sortable: false,width:160},
                {title:'Items',key:'line_items',sortable: false,width: 90},
                {title:'Delivery method',key:'shipping_lines',sortable: false,width: 240},
                { title: '', key: 'actions', sortable: false, align: 'end' },
            ],

            sorders:[],
            sloading:false,
            stotal:0,
        }
    },
    computed: {
        // Set of Shopify order ids already saved into sorders, for O(1)
        // lookup in isAlreadySaved() instead of scanning the array on
        // every row render. Always compared as strings, since Shopify's
        // numeric id vs. thirdparty_id (stored as a string) can otherwise
        // silently fail to match due to type mismatch.
        savedOrderIds() {
            return new Set(this.sorders.map((s) => String(s.thirdparty_id)));
        },
    },
    created() {
        this.getLiveOrders();
        this.getSordersList();
    },
    watch: {
        selected(newVal) {
            if (newVal.length > 10) {
                this.selected = newVal.slice(0, 10);
                this.resultMessage = 'You can select up to 10 orders at a time.';
                this.showResultSnackbar = true;
            }
        },
    },
    methods:{
        dayjs,
        isAlreadySaved(orderId) {
            return this.savedOrderIds.has(String(orderId));
        },
        getLiveOrders(pageInfo = null){
            this.loading = true;
            const shopId = this.shop_id;
            axios.get(`/superadmin/shopify/${shopId}/live-orders`,{
                params: {
                    limit: this.perPage,
                    ...(pageInfo ? { page_info: pageInfo } : {}),
                },

            })
                .then((resp)=>{
                    const respData = resp.data;
                    this.liveOrders = respData.orders || [];
                    this.nextPageInfo = respData.next_page_info;
                    this.previousPageInfo = respData.previous_page_info;
                })
                .finally(()=>{
                    this.loading = false;
                })
        },
        goNext() {
            if (this.nextPageInfo) this.getLiveOrders(this.nextPageInfo);
        },
        goPrevious() {
            if (this.previousPageInfo) this.getLiveOrders(this.previousPageInfo);
        },
        async createSingle(item) {
            this.creatingIds.push(item.id);
            try {
                const { data } = await axios.post(
                    `/superadmin/shopify/${this.shop_id}/orders/${item.id}/create`
                );
                this.resultMessage = data.message || 'Order created.';
                await this.getSordersList();
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Failed to create order.';
            } finally {
                this.creatingIds = this.creatingIds.filter((id) => id !== item.id);
                this.showResultSnackbar = true;
            }
        },
        async createBulk() {
            this.confirmBulkCreate = false;
            this.bulkCreating = true;
            try {
                const {data} = await axios.post(
                    `/sadmin/shopify/${this.shop_id}/orders/bulk-create`,
                    {order_ids: this.selected}
                );
                this.resultMessage = `${data.created} created, ${data.failed} failed.`;
                this.selected = [];
                await this.getSordersList();
            } catch (e) {
                this.resultMessage = e.response?.data?.message || 'Bulk create failed.';
            } finally {
                this.bulkCreating = false;
                this.showResultSnackbar = true;
            }
        },
        getSordersList(){
            this.sloading = true;
            return axios.get(`/superadmin/shopify/${this.shop_id}/orders-list`)
                .then((resp)=>{
                    const respData = resp.data;
                    const sorderData = respData.items;
                    this.sorders = sorderData.data || [];
                    this.stotal = respData.total || 0;
                })
                .finally(()=>{
                    this.sloading = false;
                })
        }
    }
}
</script>

<style scoped>

</style>
