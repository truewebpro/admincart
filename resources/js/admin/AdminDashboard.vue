<template>
    <v-container>
        <v-row align="stretch">
            <v-col cols="12" md="8">
                <v-card class="position-relative overflow-visible elevation-6 rounded-lg h-100">
                    <v-card-text>
                        <div class="d-flex">
                            <div class="w-75 d-flex flex-column justify-space-between">
                                <div class="text-h5 text-md-h4">Welcome <span class="font-weight-bold">{{suser.name}} 🎉</span></div>
                                <p class="text-body-1 mt-3">
                                    You have done 72% 😎 more sales today. Check your new raising badge in your profile.
                                </p>
                            </div>
                            <div class="position-absolute bottom-0 right-0">
                                <v-img width="225"
                                    src="https://demos.themeselection.com/materio-vuetify-vuejs-admin-template/demo-1/assets/illustration-john-2-DCqPs8R_.png"/>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="h-100 elevation-6 rounded-lg d-flex flex-column">
                    <v-card-item
                        title="Revenue"
                        :subtitle="('Total £'+ paidRev.toFixed(2) + ' Growth this month' )"
                        append-icon="mdi-finance"></v-card-item>
                    <v-card-text class="d-flex align-center">
                        <div class="d-flex align-center ga-3">
                            <v-avatar icon="mdi-progress-check" class="bg-success rounded"></v-avatar>
                            <div class="d-flex flex-column">
                                <div class="text-body-1 text-medium-emphasis">Paid</div>
                                <h5 class="text-h5 font-weight-medium">£{{paidRev.toFixed(2)}}</h5>
                            </div>
                        </div>
                        <v-spacer/>
                        <div class="d-flex align-center ga-3">
                            <v-avatar icon="mdi-progress-clock" class="bg-warning rounded"></v-avatar>
                            <div class="d-flex flex-column">
                                <div class="text-body-1 text-medium-emphasis">Pending</div>
                                <h5 class="text-h5 font-weight-medium">£{{pendingRev.toFixed(2)}}</h5>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="4">
                <v-card class="h-100 elevation-6 rounded-lg d-flex flex-column">
                    <v-card-item
                        title="Orders"
                        :subtitle="('Total £'+ paidRev.toFixed(2) + ' Growth this month' )"
                        append-icon="mdi-truck-fast-outline"></v-card-item>
                    <v-card-text class="d-flex align-center">
                        <div class="d-flex align-center ga-3">
                            <v-avatar icon="mdi-truck-check" class="bg-success rounded"></v-avatar>
                            <div class="d-flex flex-column">
                                <div class="text-body-1 text-medium-emphasis">Shipped</div>
                                <h5 class="text-h5 font-weight-medium">{{shippedOrd}}</h5>
                            </div>
                        </div>
                        <v-spacer/>
                        <div class="d-flex align-center ga-3">
                            <v-avatar icon="mdi-truck-delivery" class="bg-warning rounded"></v-avatar>
                            <div class="d-flex flex-column">
                                <div class="text-body-1 text-medium-emphasis">Pending</div>
                                <h5 class="text-h5 font-weight-medium">{{pendingOrd}}</h5>
                            </div>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn block color="success" variant="tonal" append-icon="mdi-send" density="compact">View Orders</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
            <v-col cols="12" md="8">
                <v-card class="h-100 elevation-6 rounded-lg d-flex flex-column">
                    <v-card-item
                        title="Transactions"
                        subtitle="Total 48.5% Growth 😎 this month "
                        append-icon="mdi-credit-card-wireless-outline"></v-card-item>
                    <v-card-text class="d-flex align-end">
                        <v-row>
                            <v-col cols="12" md="3">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-finance" class="bg-primary rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Sales</div>
                                        <h5 class="text-h5 font-weight-medium">£{{paidRev.toFixed(2)}}</h5>
                                    </div>
                                </div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-account-group" class="bg-success rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Customers</div>
                                        <h5 class="text-h5 font-weight-medium">{{custNew}}</h5>
                                    </div>
                                </div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-cart-arrow-down" class="bg-info rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Products</div>
                                        <h5 class="text-h5 font-weight-medium">{{productsCount}}</h5>
                                    </div>
                                </div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <div class="d-flex align-center ga-3">
                                    <v-avatar icon="mdi-chart-timeline-variant-shimmer" class="bg-green rounded"></v-avatar>
                                    <div class="d-flex flex-column">
                                        <div class="text-body-1 text-medium-emphasis">Revenue</div>
                                        <h5 class="text-h5 font-weight-medium">£{{(pendingRev + paidRev).toFixed(2)}} </h5>
                                    </div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="8">
                <v-card class="border-sm elevation-6 rounded-lg">
                    <v-card-title class="d-flex justify-space-between bg-grey-lighten-4">
                        <span>Recent Orders</span>
                        <span><v-icon>mdi-dots-horizontal</v-icon></span>
                    </v-card-title>
                    <v-data-table :items="rorders" :headers="rordersHeaders" hide-default-footer mobileBreakpoint="sm">
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
                <v-card class="border-sm elevation-6 rounded-lg">
                    <v-card-title class="d-flex justify-space-between bg-grey-lighten-4">
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
        <v-row dense class="d-none">
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
