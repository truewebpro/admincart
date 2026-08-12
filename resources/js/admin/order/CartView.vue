<template>
    <div class="cartpage v-container">
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn link to="/abandoned/carts" icon variant="tonal" color="primary" density="compact" class="me-2">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    <span class="me-2">#D{{cartDetail.acart_id}}</span>
                    <v-chip v-if="pstatus === 'pending' || pstatus === 'unpaid'" color="yellow" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="pstatus === 'refunded' || pstatus === 'partially_refunded'" color="yellow" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="pstatus === 'paid' || pstatus === 'partially_paid'" color="green" variant="flat" density="compact" class="text-capitalize">{{pstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'unfulfilled'" color="yellow" variant="flat" density="compact" class="ms-2 text-capitalize">{{fstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'picked'" variant="flat" color="green" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                    <v-chip v-if="fstatus === 'packed'" variant="flat" color="green" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                    <v-chip v-else variant="tonal" color="black" prepend-icon="mdi-playlist-check" density="compact" class="ms-2">{{fstatus}}</v-chip>
                </h2>
                <div class="text-body-2">{{dayjs(cartDetail.placed_at).format('D MMMM [at] h:mm a')}}</div>
            </v-col>
            <v-col cols="12" md="6" class="d-flex ga-2 justify-end">
                <v-btn icon variant="tonal" color="primary" density="compact">
                    <v-icon>mdi-arrow-up</v-icon>
                </v-btn>
                <v-btn icon variant="tonal" color="primary" density="compact" class="ms-1">
                    <v-icon>mdi-arrow-down</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-row v-if="order">
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-title class="bg-success">{{ order.order_status }}</v-card-title>
                    <v-card-text class="pt-4 text-body-1">
                        Order created on {{dayjs(order.created_at).format('DD MMM YYYY, h:mm a')}}. You can
                        <router-link :to="'/orders/'+cartDetail.order_id">view the order</router-link>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row v-else>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-title class="bg-error">
                        <v-icon>mdi-help-circle</v-icon> {{ cartDetail.payment_status }}
                    </v-card-title>
                    <v-card-text class="pt-4 text-body-1">
                        Cart created on {{dayjs(cartDetail.created_at).format('DD MMM YYYY, h:mm a')}}.
                    </v-card-text>
                </v-card>
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
                        <v-chip density="compact" class="font-weight-medium">{{cartDetail.items_count}}</v-chip>
                    </v-card-title>
                    <v-card-subtitle class="font-weight-medium">{{dayjs(cartDetail.placed_at).format('D MMMM [at] h:mm a')}}</v-card-subtitle>
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
                            <v-col cols="12" md="2" class="text-end">£{{oitem.line_total}}</v-col>
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
<!--                        <v-btn v-if="pstatus === 'paid' && lstatus === 'no_label'" variant="outlined" density="comfortable" color="success" @click="sendToSendCloud">Send to Sendcloud</v-btn>-->
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'pending'" variant="flat" density="comfortable" color="success">Create Label</v-btn>
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'created'" variant="flat" density="comfortable" color="success">Print Label</v-btn>
                        <v-btn v-if="pstatus === 'paid' && lstatus === 'printed'" variant="flat" density="comfortable" color="success">Re-Print Label</v-btn>
<!--                        <v-btn @click="markAsPicked" v-if="fstatus !== 'picked' && fstatus !== 'packed' && pstatus !== 'pending'" variant="elevated" color="yellow" density="comfortable" class="text-none font-weight-bold">-->
<!--                            Mark as Items Picked-->
<!--                        </v-btn>-->
<!--                        <v-btn @click="markAsPacked" v-if="fstatus !== 'packed' && pstatus !== 'pending'" variant="elevated" color="yellow" density="comfortable" class="text-none font-weight-bold">-->
<!--                            Mark as Items Packed-->
<!--                        </v-btn>-->
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
                                <div>£{{order?.subtotal || cartDetail.subtotal }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1">
                                <h4>Shipping</h4>
                                <div>{{ cartDetail.shipping_method }}</div>
                                <div>£{{ (cartDetail.shipping_cost) }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1">
                                <h4>Discount</h4>
                                <div></div>
                                <div>- £{{ order?.discount_amount || cartDetail.discount_amount}}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1 border-b-sm">
                                <h4>VAT @ 20%</h4>
                                <div></div>
                                <div>£{{ order?.tax_amount || cartDetail.tax_amount }}</div>
                            </div>
                            <div class="d-flex justify-lg-space-between my-1 border-b-sm font-weight-medium">
                                <h3>Total</h3>
                                <div></div>
                                <div>£{{ order?.order_total || cartDetail.cart_total }}</div>
                            </div>
                            <div v-if="pstatus === 'pending'" class="d-flex justify-lg-space-between py-1 border-b-sm">
                                <h4>Paid</h4>
                                <div></div>
                                <div>£{{ 0.00.toFixed(2) }}</div>
                            </div>
                            <div v-if="pstatus === 'pending'" class="bg-red-accent-1 px-2 d-flex justify-lg-space-between py-1 border-b-sm">
                                <h4>Balance</h4>
                                <div></div>
                                <div>£{{ order?.order_total || cartDetail.cart_total }}</div>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions v-if="order == null">
                        <v-spacer />
                        <v-btn variant="outlined" color="black" density="compact" class="text-none font-weight-bold">
                            Send Invoice
                        </v-btn>
                        <v-btn variant="elevated" color="black" density="compact" class="text-none font-weight-bold">
                            Mark as paid
                        </v-btn>
<!--                        <v-btn v-if="pstatus === 'pending' || pstatus === 'unpaid'"-->
<!--                               variant="elevated" color="black" density="compact"-->
<!--                               class="text-none font-weight-bold" @click="markPaidDialog = true">Mark as Paid</v-btn>-->
                    </v-card-actions>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm">
                    <v-card-title>Notes</v-card-title>
                    <v-card-text>
                        {{cartDetail.notes}}
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
                        <div class="font-weight-medium">{{order?.shipping_name || address?.fname + " " + address?.lname}}</div>
                        <div class="font-weight-medium">{{order?.shipping_address_line1 || address?.address_line1}}</div>
                        <div class="font-weight-medium">{{order?.shipping_address_line2 || address?.address_line2}}</div>
                        <div class="font-weight-medium">{{order?.shipping_city || address?.city}}</div>
                        <div class="font-weight-medium">{{order?.shipping_postcode || address?.postcode}}</div>
                        <div class="font-weight-medium">{{order?.shipping_country || address?.country}}</div>
                        <div class="font-weight-medium">+44 {{order?.shipping_phone || address?.phone}}</div>
                    </v-card-text>
                </v-card>
                <v-card class="border-sm mt-3">
                    <v-card-title>Cart Events</v-card-title>
                    <v-card-text>
                        <div>Ip Address : {{cartDetail.ip_address}}</div>
                        <div>Device Type : {{cartDetail.device_type}}</div>
                        <div>Browser : {{cartDetail.browser}}</div>
                        <div>Platform : {{cartDetail.platform}}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-data-table :items="aevents" :headers="aeventHeaders">
                        <template v-slot:item.created_at="{item}">
                            <span>{{dayjs(item.created_at)}}</span>
                        </template>
                        <template v-slot:item.event_type="{item}">
                            <div>{{(item.event_type)}}</div>
                            <div v-if="item.event_type === 'start_viva_payment' && item.vpayment != null">
                                <v-btn color="success" variant="elevated" density="compact" v-if="order != null">Order Created</v-btn>
                                <v-btn @click="createVivaOrder(item)" color="red" variant="elevated" density="compact" v-else>Create Order</v-btn>
                            </div>
                        </template>
                        <template v-slot:item.event_data="{item}">
                            <div v-if="item.event_data['process']">{{item.event_data['process']}}</div>
                            <div v-if="item.event_data['orderCode']">
                                {{item.event_data['orderCode']}}
                            </div>
                            <span>{{item.event_data}}</span>
                        </template>
                    </v-data-table>
                </v-card>

            </v-col>
        </v-row>
<!--        <v-dialog v-model="markPaidDialog" max-width="350">-->
<!--            <v-card>-->
<!--                <v-card-title>Payment Status</v-card-title>-->
<!--                <v-card-text class="text-center">-->
<!--                    Mark this order as paid if you received £{{ cartDetail.cart_total }} from another payment method.-->
<!--                </v-card-text>-->
<!--                <v-card-actions>-->
<!--                    <v-btn @click="markPaidDialog = false" variant="outlined" density="compact">cancel</v-btn>-->
<!--                    <v-btn @click="markAsPaid" variant="flat" density="compact" color="success" class="text-none">Mark as Paid</v-btn>-->
<!--                </v-card-actions>-->
<!--            </v-card>-->
<!--        </v-dialog>-->
    </div>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";
export default {
    name:"CartView",
    props:{
        cart_id:[Number,String]
    },
    data(){
        return{
            cdn:this.$store.state.cdn,
            cartDetail:[],
            aevents:[],
            aeventHeaders:[
                {title:'Date / Time',key:'created_at'},
                {title:'Event Type',key:'event_type'},
                {title:'Event Data',key:'event_data'}
            ],
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
            order:0,
            address:null,
        }
    },
    created() {
        this.getCartDetail();
    },
    methods:{
        dayjs,
        getCartDetail(){
            axios.get('/sadmin/cart/'+this.cart_id)
                .then((resp)=>{
                    const carDetail = resp.data.cart;
                    this.aevents = carDetail.aevents;
                    this.order = carDetail.order;
                    this.cartDetail = carDetail || [];
                    this.customer = carDetail.customer || {};
                    this.oitems = carDetail.items || [];
                    this.ostatus = carDetail.order?.order_status || 'pending';
                    this.pstatus = carDetail.order?.payment_status || 'pending';
                    this.fstatus = carDetail.order?.fulfillment_status || 'unfulfilled';
                    this.lstatus = carDetail.order?.label_status || 'no_label';
                    this.address = carDetail.address || {};
                })
        },
        createVivaOrder(item){
            console.log('event',JSON.parse(JSON.stringify(item)));
            axios.post('/sadmin/create-viva-missing-order',item)
                .then((resp)=>{
                    if(resp.data.success){
                        const order = resp.data.order;
                        this.$store.commit('ADD_ORDER',order);
                        this.$store.commit('UPDATE_ORDER',{
                            order_id:Number(order.order_id),
                            payment_status:'paid'
                        })
                        this.getCartDetail();
                    }
                })
                .catch((err)=>{
                    window.Toast.error(err.message);
                })
        }
        // markAsPaid(){
        //     const mpaid = {
        //         cart_id:this.cart_id,
        //         mname:'mark_as_paid',
        //         payment_status:'paid',
        //     }
        //     axios.post('/sadmin/cart/update',mpaid)
        //         .then((resp)=>{
        //             window.Toast.success(resp.data.message);
        //             this.getCartDetail();
        //             this.markPaidDialog = false;
        //         })
        // },
        // markAsPicked(){
        //     const mpicked = {
        //         cart_id:this.cart_id,
        //         mname:'mark_as_picked',
        //         fulfillment_status:'picked',
        //     }
        //     axios.post('/sadmin/cart/update',mpicked)
        //         .then((resp)=>{
        //             window.Toast.success(resp.data.message);
        //             this.getCartDetail();
        //         })
        // },
        // markAsPacked(){
        //     const mpacked = {
        //         cart_id:this.cart_id,
        //         mname:'mark_as_packed',
        //         fulfillment_status:'packed',
        //     }
        //     axios.post('/sadmin/cart/update',mpacked)
        //         .then((resp)=>{
        //             window.Toast.success(resp.data.message);
        //             this.getCartDetail();
        //         })
        // },
        // sendToSendCloud(){
        //     const parcel_items = this.oitems.map(item => ({
        //         description: item.title,
        //         quantity: item.quantity,
        //         weight: item.variant.weight || 0.5,
        //         value: item.price,
        //         sku: item.variant.sku,
        //         product_id: item.product_id,
        //         item_id: item.variant_id,
        //         properties: item.variant.option_values || "default",
        //     }));
        //         axios.post('/sadmin/cart/sendtosendcloud/single', {
        //             "parcel": {
        //                 "carrier": "royal_mailv2",
        //                 "shipping_method": 29632,
        //                 "order_number": this.cartDetail.order_number,
        //                 "name": this.cartDetail.shipping_name,
        //                 "company_name": "",
        //                 "address": this.cartDetail.shipping_address_line1,
        //                 "address_2": this.cartDetail.shipping_address_line2,
        //                 "city": this.cartDetail.shipping_city,
        //                 "postal_code": this.cartDetail.shipping_postcode,
        //                 "country": 'GB',
        //                 "email": this.customer.email,
        //                 "telephone": this.cartDetail.shipping_phone,
        //                 "total_order_value": this.cartDetail.cart_total,
        //                 "total_order_value_currency": this.cartDetail.currency_code,
        //                 parcel_items,
        //             },
        //             "request_label": true
        //         })
        //             .then((resp)=>{
        //             this.getCartDetail();
        //             window.Toast.success('order send to SendCloud')
        //         })
        //             .catch((err)=>{
        //                 window.Toast.error(err.message);
        //             })
        // },
    }
}

</script>

<style scoped>

</style>
