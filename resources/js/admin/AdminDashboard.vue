<template>
    <v-container>
        <v-row dense>
            <v-col cols="12" md="6">
                <span class="text-h6">Welcome {{suser.name}}</span>
            </v-col>
            <v-col cols="12" md="6" class="text-end d-flex ga-3 align-center">
                <v-autocomplete item-title="shop_name" density="compact"
                                variant="underlined" hide-details
                                label="Shop Name"></v-autocomplete>
                Role :  <v-btn density="comfortable" color="success">Role</v-btn>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="indigo" variant="flat" elevation="1" class="grad rounded-lg">
                    <v-card-title class="d-flex justify-space-between">
                        <span>Revenue</span>
                        <span>+16% <v-icon>mdi-arrow-up-thin</v-icon></span>
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex justify-space-between text-center">
                            <div>
                                <div class="text-h6">£{{paidRev.toFixed(2)}}</div>
                                <div class="font-weight-bold">Paid</div>
                            </div>
                            <div>
                                <div class="text-h6">£{{pendingRev.toFixed(2)}}</div>
                                <div class="font-weight-bold">Pending</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="red" variant="flat" elevation="1" class="grad rounded-lg">
                    <v-card-title class="d-flex justify-space-between">
                        <span>Orders</span>
                        <span>+16% <v-icon>mdi-arrow-up-thin</v-icon></span>
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex justify-space-between text-center">
                            <div>
                                <div class="text-h6">{{shippedOrd}}</div>
                                <div class="font-weight-bold">Shipped</div>
                            </div>
                            <div>
                                <div class="text-h6">{{pendingOrd}}</div>
                                <div class="font-weight-bold">Pending</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="green" variant="flat" elevation="1" class="grad rounded-lg">
                    <v-card-title class="d-flex justify-space-between">
                        <span>Products</span>
                        <span>+16% <v-icon>mdi-arrow-up-thin</v-icon></span>
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex justify-space-between text-center">
                            <div>
                                <div class="text-h6">{{productsCount}}</div>
                                <div class="font-weight-bold">All</div>
                            </div>
                            <div>
                                <div class="text-h6">{{variantsCount}}</div>
                                <div class="font-weight-bold">Variants</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card color="orange" variant="flat" elevation="1" class="grad rounded-lg">
                    <v-card-title class="d-flex justify-space-between">
                        <span>Customers</span>
                        <span>+100% <v-icon>mdi-arrow-up-thin</v-icon></span>
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex justify-space-between text-center">
                            <div>
                                <div class="text-h6">{{custReturned}}</div>
                                <div class="font-weight-bold">Returned</div>
                            </div>
                            <div>
                                <div class="text-h6">{{custNew}}</div>
                                <div class="font-weight-bold">New</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="border-sm">
                    <v-card-title class="d-flex justify-space-between grad bg-indigo">
                        <span>Revenue</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="area" :options="roptions" :series="rseries"></apexchart>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="border-sm">
                    <v-card-title class="d-flex justify-space-between grad bg-orange">
                        <span>Visitors</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="bar" :options="visoptions" :series="visseries"></apexchart>
                </v-card>
            </v-col>
            <v-col cols="12" md="8">
                <v-card class="border-sm">
                    <v-card-title class="d-flex justify-space-between grad bg-red">
                        <span>Recent Orders</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <v-data-table :items="rorders" :headers="rordersHeaders" hide-default-footer>
                        <template v-slot:item.order_items_count="{item}">
                            <div class="font-weight-medium">{{item.order_items_count}} item<span v-if="item.order_items_count > 1">s</span></div>
                        </template>
                        <template v-slot:item.order_total="{item}">
                            £ {{item.order_total}}
                        </template>
                        <template v-slot:item.placed_at="{item}">
                            <div class="font-weight-medium">{{dayjs(item.placed_at).format('D MMM [at] h:mm a')}}</div>
                        </template>
                        <template v-slot:item.fulfillment_status="{item}">
                            <v-chip v-if="item.fulfillment_status === 'unfulfilled'" color="red" density="compact" variant="tonal" class="text-capitalize font-weight-medium">{{item.fulfillment_status}}</v-chip>
                            <v-chip v-else color="green" density="compact" variant="tonal" class="text-capitalize font-weight-medium">{{item.fulfillment_status}}</v-chip>
                        </template>
                        <template v-slot:item.actions="{item}">
                            <v-btn icon="mdi-eye" density="compact" variant="text" color="info"></v-btn>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="border-sm">
                    <v-card-title class="d-flex justify-space-between grad bg-green">
                        <span>Top Sold</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <v-data-table :items="tpros" :headers="tprosHeaders" density="comfortable" hide-default-footer>
                        <template v-slot:item.featured_image="{item}">
                            <v-img :src="cdn+item.featured_image" width="44" height="44" class="rounded-lg my-1"></v-img>
                        </template>
                        <template v-slot:item.title="{item}">
                            <div>{{item.title}}</div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
        <v-row dense align="stretch">
            <v-col cols="12" md="3">
                <v-card class="border-sm h-100">
                    <v-card-title class="d-flex justify-space-between grad bg-indigo">
                        <span>Traffic Source</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="donut" :options="toptions" :series="tseries"></apexchart>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm h-100">
                    <v-card-title class="d-flex justify-space-between grad bg-red">
                        <span>Traffic By Device</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="donut" :options="bdoptions" :series="bdseries"></apexchart>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm h-100">
                    <v-card-title class="d-flex justify-space-between grad bg-green">
                        <span>Sales by Country</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="pie" :options="coptions" :series="cseries"></apexchart>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card class="border-sm h-100">
                    <v-card-title class="d-flex justify-space-between grad bg-orange">
                        <span>Sales by Channels</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <apexchart type="radialBar" :options="rboptions" :series="rbseries"></apexchart>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";

export default {
    name:"AdminDashboard",
    data(){
        return{
            cdn:this.$store.state.cdn,
            users:[],
            suser:{},
            ashops:[],
            selectedShop:null,
            paidRev:0,
            pendingRev:0,
            shippedOrd:0,
            pendingOrd:0,
            ordersCount:0,
            productsCount:0,
            variantsCount:0,
            shopUsers:0,
            custReturned:0,
            custNew:0,
            tpros:[],
            tprosHeaders:[
                {title:'Image',value:'featured_image',width:75},
                {title:'Product',value:'title'},
            ],
            rorders:[],
            rordersHeaders:[
                {title:'#ID',value:'order_number'},
                {title:'Customer',value:'shipping_name'},
                {title:'Quantity',value:'order_items_count'},
                {title:'Total',value:'order_total'},
                {title:'Date',value:'placed_at'},
                {title:'Status',value:'fulfillment_status'},
            ],
            radius: 10,
            padding: 8,
            gradient: 3,
            autoLineWidth: false,
            gradients: [
                ['#222'],
                ['#42b3f4'],
                ['red', 'orange', 'yellow'],
                ['purple', 'violet'],
                ['#00c6ff', '#F0F', '#FF0'],
                ['#f72047', '#ffd200', '#1feaea'],
            ],
            roptions: {
                chart: {
                    id: 'revenue',
                    zoom:{enabled:false},
                },
                colors: ['#8e2de2', '#4a00e0'],
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul','Aug','Sep','Oct','Nov','Dec'],
                }
            },
            rseries: [{
                name: 'Revenue 2024',
                data: [38, 40, 45, 50, 49, 60, 62,65,66,64,44,44]
            },
                {
                    name: 'Revenue 2025',
                    data: [40, 38, 45,66,64,44,44,50, 49, 60, 62,65]
                }],
            bdoptions: {
                chart:{
                    id:"Source",
                    type:'donut'
                },
                legend:{position:'bottom'},
                labels: ["Desktop", "Tablet", "Mobile"],
            },
            bdseries: [30, 45, 35],
            toptions: {
                chart:{
                    id:"Source",
                    type:'donut'
                },
                legend:{position:'bottom'},
                labels: ["Social", "Direct", "Search"],
            },
            tseries: [20, 65, 15],
            visoptions: {
                chart: {
                    id: 'visitors1'
                },
                colors: ['#f7971e', '#ffd200'],
                xaxis: {
                    categories: ['Mo', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                }
            },
            visseries: [{
                name: 'Last Week',
                data: [25, 32, 38, 42, 41, 49, 54]
            },{
                name: 'This Week',
                data: [30, 40, 45, 50, 49, 60, 70]
            }],
            options: {
                chart: {
                    id: 'vuechart-example'
                },
                xaxis: {
                    categories: [1991, 1992, 1993, 1994, 1995, 1996, 1997, 1998]
                }
            },
            series: [{
                name: 'series-1',
                data: [30, 40, 45, 50, 49, 60, 70, 91]
            }],
            coptions: {
                chart:{
                    id:"Source",
                    type:'donut'
                },
                legend:{position:'bottom'},
                labels: ["UK", "USA", "Canada",'Europe'],
            },
            cseries: [30,45,35,20],
            rboptions: {
                chart:{
                    id:"Channels",
                    type:'radialBar'
                },
                plotOptions: {
                    radialBar: {
                        dataLabels: {
                            name: {fontSize: '22px',},
                            value: {fontSize: '16px',},
                            total: {show: true, label: 'Total',
                                formatter: function (w) {return 249}
                            }
                        }
                    }
                },
                labels: ["Email", "Facebook", "Instagram",'Twitter'],
            },
            rbseries: [44, 55, 67, 83],
        }
    },
    created() {
        this.getAllUsers();
    },
    methods:{
        dayjs,
        getAllUsers(){
            axios.get(`/sadmin/dashboard`)
                .then((resp)=>{
                    this.users = resp.data.users;
                    this.suser = resp.data.users;
                    this.ashops = resp.data.shops;
                    if(this.ashops.length > 0){
                        this.selectedShop = this.ashops[0];
                    }
                    this.rorders = resp.data.orders;
                    this.pendingRev = resp.data.pendingCount;
                    this.paidRev = resp.data.paidCount;
                    this.shippedOrd = resp.data.shippedOrd;
                    this.pendingOrd = resp.data.pendingOrd;
                    this.ordersCount = resp.data.ordersCount;
                    this.productsCount = resp.data.productsCount;
                    this.variantsCount = resp.data.variantsCount;
                    this.shopUsers = resp.data.shopUsers;
                    this.tpros = resp.data.tpros;
                    this.custNew = resp.data.customersNew;
                })
        }
    }
}

</script>

<style scoped>
.grad.bg-indigo {
    background: linear-gradient(to right, #8e2de2, #4a00e0) !important;
}
.grad.bg-red {
    background: linear-gradient( 45deg , #ee0979, #ff6a00) !important;
}
.grad.bg-green {
    background: linear-gradient( 45deg , #00b09b, #96c93d) !important;
}
.grad.bg-orange {
    background: linear-gradient(to right, #f7971e, #ffd200) !important;
}
</style>
