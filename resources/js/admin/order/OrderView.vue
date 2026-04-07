<template>
    <div class="orderpage v-container">
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link to="/orders" icon variant="tonal" density="compact" class="me-2">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    <span class="me-2">#{{orderDetail.order_number}}</span>
                    <v-chip v-if="pstatus === 'pending' || pstatus === 'unpaid'" color="yellow" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="pstatus === 'refunded' || pstatus === 'partially_refunded'" color="yellow" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="pstatus === 'paid' || pstatus === 'partially_paid'" color="green" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'unfulfilled'" color="yellow" variant="flat" density="compact" class="ms-2 text-capitalize">{{fstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'picked'" variant="flat" color="green" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'packed'" variant="flat" color="green" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                    <v-chip v-else variant="tonal" color="black" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                </h2>
                <div class="text-body-2">{{dayjs(orderDetail.placed_at).format('D MMMM [at] h:mm a')}}</div>
            </v-col>
            <v-col cols="12" md="6" class="d-flex ga-2 justify-end">
                <v-menu>
                    <template v-slot:activator="{props}">
                        <v-btn v-bind="props" variant="outlined" class="text-none me-2" append-icon="mdi-chevron-down"
                               density="compact">More Actions</v-btn>
                    </template>
                    <v-list nav density="compact">
                        <v-list-item base-color="dark" v-if="orderDetail.deleted_at" @click="markAsRestore">
                            <v-list-item-title><v-icon class="me-2">mdi-archive-cancel-outline</v-icon>Restore Order</v-list-item-title>
                        </v-list-item>
                        <v-list-item base-color="dark" v-else @click="markAsArchived">
                            <v-list-item-title><v-icon class="me-2">mdi-archive-outline</v-icon>Archive Order</v-list-item-title>
                        </v-list-item>
                        <v-list-item base-color="error">
                            <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Order</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
                <v-btn icon variant="tonal" density="compact">
                    <v-icon>mdi-arrow-up</v-icon>
                </v-btn>
                <v-btn icon variant="tonal" density="compact" class="ms-1">
                    <v-icon>mdi-arrow-down</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="9">
                <v-card class="border-sm">
                    <v-card-title class="text-capitalize d-flex ga-2 font-weight-medium">
                        <v-icon>mdi-package-variant</v-icon>
                        <v-chip v-if="fstatus === 'unfulfilled'" variant="flat" color="yellow"
                                density="comfortable">
                            {{fstatus}}
                        </v-chip>
                        <v-chip v-if="fstatus === 'picked'" variant="flat" color="green" prepend-icon="mdi-playlist-check" density="comfortable">{{fstatus}}</v-chip>
                        <v-chip v-if="fstatus === 'packed'" variant="flat" color="green" prepend-icon="mdi-package-variant-closed-plus" density="comfortable">{{fstatus}}</v-chip>
                        <v-chip density="compact" class="font-weight-medium">{{orderDetail.order_items_count}}</v-chip>
                    </v-card-title>
                    <v-card-subtitle class="font-weight-medium">{{dayjs(orderDetail.placed_at).format('D MMMM [at] h:mm a')}}</v-card-subtitle>
                    <v-card-text>
                        <v-row v-for="(oitem,index) in oitems" :key="index" class="border-b-sm">
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
                    <v-card-actions>
                        <v-btn v-if="fstatus === 'picked'" variant="tonal" color="success" density="comfortable" class="text-none font-weight-bold" prepend-icon="mdi-playlist-check">
                            Items Picked
                        </v-btn>
                        <v-btn v-if="fstatus === 'packed'" variant="tonal" color="success" density="comfortable" class="text-none font-weight-bold" prepend-icon="mdi-package-variant-closed-plus">
                            Items Packed
                        </v-btn>
                        <v-spacer />
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'no_label'" variant="outlined" density="comfortable" color="success" @click="sendToSendCloud">Send to Sendcloud</v-btn>
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'pending'" variant="flat" density="comfortable" color="success">Create Label</v-btn>
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'created'" variant="flat" density="comfortable" color="success">Print Label</v-btn>
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'printed'" variant="flat" density="comfortable" color="success">Re-Print Label</v-btn>
                        <v-btn @click="markAsPicked" v-if="fstatus !== 'picked' && fstatus !== 'packed' && pstatus !== 'pending'" variant="elevated" color="yellow" density="comfortable" class="text-none font-weight-bold">
                            Mark as Items Picked
                        </v-btn>
                        <v-btn @click="markAsPacked" v-if="fstatus !== 'packed' && pstatus !== 'pending'" variant="elevated" color="yellow" density="comfortable" class="text-none font-weight-bold">
                            Mark as Items Packed
                        </v-btn>
                        <v-btn v-if="fstatus === 'packed' && pstatus !== 'pending'" variant="elevated" color="black" density="comfortable" class="text-none font-weight-bold" prepend-icon="mdi-plus">
                            Add Tracking
                        </v-btn>
                    </v-card-actions>
                </v-card>
                <v-card class="border-sm mt-3">
                    <v-card-title class="text-capitalize">
                        <v-icon>mdi-cash-sync</v-icon>
                        <v-chip v-if="pstatus === 'pending' || pstatus === 'unpaid'" variant="flat" color="yellow"
                                density="comfortable" class="font-weight-medium ms-2">
                            {{pstatus}}
                        </v-chip>
                        <v-chip v-else-if="pstatus === 'refunded' || pstatus === 'partially_refunded'" variant="flat" color="red"
                                density="comfortable" class="font-weight-medium ms-2">
                            {{pstatus}}
                        </v-chip>
                        <v-chip v-else variant="flat" color="green" density="comfortable" class="font-weight-medium ms-2">
                            {{pstatus}}
                        </v-chip>
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-lg-space-between my-1">
                                <h4>Subtotal</h4>
                                <div>{{ oitems.length }} item<span v-if="oitems.length > 1">s</span></div>
                                <div>£{{ orderDetail.subtotal }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1">
                                <h4>Shipping</h4>
                                <div>{{ orderDetail.shipping_method }}</div>
                                <div>£{{ (orderDetail.shipping_cost) }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1">
                                <h4>Discount</h4>
                                <div></div>
                                <div>- £{{ orderDetail.discount_amount}}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1 border-b-sm">
                                <h4>VAT @ 20%</h4>
                                <div></div>
                                <div>£{{ orderDetail.tax_amount }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1 border-b-sm font-weight-medium">
                                <h3>Total</h3>
                                <div></div>
                                <div>£{{ orderDetail.order_total }}</div>
                            </div>
                            <div v-if="pstatus === 'pending'" class="d-flex justify-lg-space-between py-1 border-b-sm">
                                <h4>Paid</h4>
                                <div></div>
                                <div>£{{ 0.00.toFixed(2) }}</div>
                            </div>
                            <div v-if="pstatus === 'pending'" class="bg-red-accent-1 px-2 d-flex justify-lg-space-between py-1 border-b-sm">
                                <h4>Balance</h4>
                                <div></div>
                                <div>£{{ orderDetail.order_total.toFixed(2) }}</div>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer />
                        <v-btn @click="sendInvoicetoCustomer" variant="outlined" color="black" density="compact" class="text-none font-weight-bold">
                            Send Invoice
                        </v-btn>
                        <v-btn v-if="pstatus === 'pending' || pstatus === 'unpaid'"
                               variant="elevated" color="black" density="compact"
                               class="text-none font-weight-bold" @click="markPaidDialog = true">Mark as Paid</v-btn>
                    </v-card-actions>
                </v-card>
                <v-card class="border-sm mt-3">
                    <v-card-title>Timeline</v-card-title>
                </v-card>
                <div>
                    <v-timeline align="start" side="end" density="compact" dotColor="green" size="x-small">
                        <v-timeline-item id="otimeline" v-for="(log,index) in logs" :key="index" class="w-100">
                                <div class="w-100 d-flex flex-wrap align-center justify-space-between ga-1">
                                    <div class="d-flex ga-1 align-center flex-wrap">
                                        <div v-if="log.source !== 'system'">By {{log.source}}</div>
                                        <div v-if="log.meta">
                                            {{log.event}} from <span>{{log.meta.from}} to {{log.meta.to}}</span>
                                        </div>
                                        <div class="text-body-2">{{log.description}}</div>
                                    </div>
                                    <div class="text-body-2">{{dayjs(log.created_at).format('hh:mm a')}}</div>
                                </div>
                        </v-timeline-item>
                    </v-timeline>
                </div>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm">
                    <v-card-title>Notes</v-card-title>
                    <v-card-text>
                        {{orderDetail.notes}}
                    </v-card-text>
                </v-card>
                <v-card class="border-sm mt-3">
                    <v-card-title>Customer</v-card-title>
                    <v-card-text>
                        <div class="font-weight-medium">{{customer.fname}} {{customer?.lname}}</div>
<!--                        <div class="font-weight-medium">{{customer.email}}</div>-->
                        <h3 class="small my-2">Contact Information</h3>
                        <div class="font-weight-medium">{{customer.email}}</div>
                        <div class="font-weight-medium">+44 {{customer?.phone}}</div>
                        <h3 class="small my-2">Shipping Address</h3>
                        <div class="font-weight-medium">{{orderDetail.shipping_name}}</div>
                        <div class="font-weight-medium">{{orderDetail.shipping_address_line1}}</div>
                        <div class="font-weight-medium">{{orderDetail.shipping_address_line2}}</div>
                        <div class="font-weight-medium">{{orderDetail.shipping_city}}</div>
                        <div class="font-weight-medium">{{orderDetail.shipping_postcode}}</div>
                        <div class="font-weight-medium">{{orderDetail.shipping_country}}</div>
                        <div class="font-weight-medium">+44 {{orderDetail.shipping_phone}}</div>

                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-dialog v-model="markPaidDialog" max-width="350">
            <v-card>
                <v-card-title>Payment Status</v-card-title>
                <v-card-text class="text-center">
                    Mark this order as paid if you received £{{ orderDetail.order_total }} from another payment method.
                </v-card-text>
                <v-card-actions>
                    <v-btn @click="markPaidDialog = false" variant="outlined" density="compact">cancel</v-btn>
                    <v-btn @click="markAsPaid" variant="flat" density="compact" color="success" class="text-none">Mark as Paid</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";
export default {
    name:"OrderView",
    props:{
        order_id:[Number,String]
    },
    data(){
        return{
            cdn:this.$store.state.cdn,
            orderDetail:[],
            customer:[],
            oitems:[],
            ostatuses:['pending', 'processing', 'completed', 'archived'],
            ostatus:'',
            pstatuses:['pending','paid','partially_paid','refunded','partially_refunded','unpaid','expired','voided'],
            pstatus:'',
            fstatuses:['unfulfilled','picking','picked','packing','packed','partially_fulfilled','fulfilled','scheduled','on_hold','request_declined'],
            fstatus:'',
            lstatuses:['no_label','pending','created','printed','cancelled'],
            lstatus:'',
            markPaidDialog:false,
            logs:[],
        }
    },
    mounted() {
        this.getOrderDetail();
        Echo.channel('orders')
            .listen('.order.updated',(e)=>{
                console.log('Order updated', e);
                this.getOrderDetail();
            });

    },
    methods:{
        dayjs,
        getOrderDetail(){
            axios.get('/sadmin/order/'+this.order_id)
                .then((resp)=>{
                    this.orderDetail = resp.data.order;
                    this.customer = resp.data.order.customer;
                    this.oitems = resp.data.order.order_items;
                    this.ostatus = resp.data.order.order_status;
                    this.pstatus = resp.data.order.payment_status;
                    this.fstatus = resp.data.order.fulfillment_status;
                    this.lstatus = resp.data.order.label_status;
                    this.logs = resp.data.logs;
                })
        },
        markAsPaid(){
            const mpaid = {
                order_id:this.order_id,
                mname:'mark_as_paid',
                payment_status:'paid',
            }
            axios.post('/sadmin/order/update',mpaid)
                .then((resp)=>{
                    this.$store.commit('UPDATE_ORDER',{
                        order_id:Number(this.order_id),
                        payment_status:'paid'
                    })
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                    this.markPaidDialog = false;
                })
        },
        markAsPicked(){
            const mpicked = {
                order_id:this.order_id,
                mname:'mark_as_picked',
                fulfillment_status:'picked',
            }
            axios.post('/sadmin/order/update',mpicked)
                .then((resp)=>{
                    this.$store.commit('UPDATE_ORDER',{
                        order_id:Number(this.order_id),
                        fulfillment_status:'picked'
                    })
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                })
        },
        markAsPacked(){
            const mpacked = {
                order_id:this.order_id,
                mname:'mark_as_packed',
                fulfillment_status:'packed',
            }
            axios.post('/sadmin/order/update',mpacked)
                .then((resp)=>{
                    this.$store.commit('UPDATE_ORDER',{
                        order_id:Number(this.order_id),
                        fulfillment_status:'packed'
                    })
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                })
        },
        markAsArchived(){
            const mdeleted = {
                order_id:this.order_id,
                mname:'mark_as_archived',
            }
            axios.post('/sadmin/order/update',mdeleted)
                .then((resp)=>{
                    const now = new Date().toISOString();
                    this.$store.commit('UPDATE_ORDER',{
                        order_id:Number(this.order_id),
                        deleted_at:now
                    })
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                })
        },
        markAsRestore(){
            const mrestore = {
                order_id:this.order_id,
                mname:'mark_as_restore',
            }
            axios.post('/sadmin/order/update',mrestore)
                .then((resp)=>{
                    this.$store.commit('UPDATE_ORDER',{
                        order_id:Number(this.order_id),
                        deleted_at:null
                    })
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                })
        },
        sendInvoicetoCustomer(){
            const sinvoice = {
                order_id:this.order_id,
                mname:'send_invoice',
            }
            axios.post('/sadmin/order/update',sinvoice)
                .then((resp)=>{
                    window.Toast.success(resp.data.message);
                    this.getOrderDetail();
                })
        },
        sendToSendCloud(){
            const parcel_items = this.oitems.map(item => ({
                description: item.title,
                quantity: item.quantity,
                weight: item.variant.weight || 0.5,
                value: item.price,
                sku: item.variant.sku,
                product_id: item.product_id,
                item_id: item.variant_id,
                properties: item.variant.option_values || "default",
            }));
                axios.post('/sadmin/order/sendtosendcloud/single', {
                    "parcel": {
                        "carrier": "royal_mailv2",
                        "shipping_method": 29632,
                        "order_number": this.orderDetail.order_number,
                        "name": this.orderDetail.shipping_name,
                        "company_name": "",
                        "address": this.orderDetail.shipping_address_line1,
                        "address_2": this.orderDetail.shipping_address_line2,
                        "city": this.orderDetail.shipping_city,
                        "postal_code": this.orderDetail.shipping_postcode,
                        "country": 'GB',
                        "email": this.customer.email,
                        "telephone": this.orderDetail.shipping_phone,
                        "total_order_value": this.orderDetail.order_total,
                        "total_order_value_currency": this.orderDetail.currency_code,
                        parcel_items,
                    },
                    "request_label": true
                })
                    .then((resp)=>{
                        this.$store.commit('UPDATE_ORDER',{
                            order_id:Number(this.order_id),
                        })
                    this.getOrderDetail();
                    window.Toast.success('order send to SendCloud')
                })
                    .catch((err)=>{
                        window.Toast.error(err.message);
                    })
        },
    }
}

</script>

<style>
#otimeline > div {
    width: 100% !important;
}
</style>
