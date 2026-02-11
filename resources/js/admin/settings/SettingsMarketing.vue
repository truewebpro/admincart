<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6"><h2>Marketing</h2></v-col>
            <v-col cols="12" md="6" class="text-md-end">
                <v-btn color="grey-darken-4" density="compact">Add New</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card>
                    <v-data-table></v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default {
    name:"SettingsMarketing",
    data(){
        return{
            allcoupons:[],
        }
    },
    created() {
        this.getAllCoupons();
    },
    methods:{
        getAllCoupons(){
            axios.get('/sadmin/coupons/list')
                .then((resp)=>{
                    this.allcoupons = resp.data.coupons;
                })
        },
    }
}

</script>

<style scoped>

</style>
