<template>
    <v-container class="store-view">
        <v-row>
            <v-col cols="12" md="6">
                <v-card class="border-sm">
                    <v-card-title>Theme Settings</v-card-title>
                    <v-card-text>
                        <v-list nav>
                            <v-list-item >
                                <template v-slot:prepend>
                                    <v-icon>mdi-store</v-icon>
                                </template>
                                <v-list-item-title>{{ tsettings.shop_name || this.$store.state.shop.shop_name }}</v-list-item-title>
                                <v-list-item-subtitle>Shop Name</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon>mdi-link-edit</v-icon>
                                </template>
                                <v-list-item-title>{{ this.$store.state.shop.shop_slug || tsettings.shop_slug }}</v-list-item-title>
                                <v-list-item-subtitle>Shop slug</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon>mdi-web-plus</v-icon>
                                </template>
                                <v-list-item-title>{{ this.$store.state.shop.subdomain || tsettings.subdomain }}</v-list-item-title>
                                <v-list-item-subtitle>Subdomain</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon>mdi-web</v-icon>
                                </template>
                                <v-list-item-title>{{ tsettings.maindomain || this.$store.state.shop.subdomain }}</v-list-item-title>
                                <v-list-item-subtitle>Main Domain</v-list-item-subtitle>
                            </v-list-item>
                            <v-list-item>
                                <template v-slot:prepend>
                                    <v-icon color="success">mdi-web-check</v-icon>
                                </template>
                                <v-list-item-title>{{ this.$store.state.shop.shop_status || tsettings.shop_status }}</v-list-item-title>
                                <v-list-item-subtitle>Shop Status</v-list-item-subtitle>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="6">
                <v-card class="border-sm">
                    <v-card-title>Details</v-card-title>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import {VFileUpload} from "vuetify/labs/components";
import axios from "axios";
export default{
    name:"ShopHome",
    components:{VFileUpload},
    data(){
        return{
            htab:'',
            tsettings:[],
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            shopName:this.$store.state.shop.shop_name || 'ShopName?',
            cdn:this.$store.state.cdn,
            bheading:'Main Heading',
            bsubheading:'Sub Heading',
            bbuttontext:'Buy Now',
            blink:'',
            bstatus:'Active',
            bimage:null,
        }
    },
    created() {
        this.getThemeSetting();
    },
    methods:{
        getThemeSetting(){
            axios.get('/sadmin/shops/theme')
                .then((resp)=>{
                    this.tsettings = resp.data.shop;
                })
        },
    }
}

</script>
