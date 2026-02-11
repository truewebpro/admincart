<template>
    <v-container>
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact">
                        <v-icon>mdi-menu</v-icon>
                    </v-btn>
                    Menus
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn variant="outlined" color="grey-darken-2 me-2" density="compact" class="text-none">
                    Url Redirects
                </v-btn>
                <v-btn :to="{name:'MenuAdd'}" variant="elevated" color="grey-darken-2" density="compact" class="text-none">
                    Create Menu
                </v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <v-card>
                    <v-data-table v-if="allmenus" :items="allmenus" :headers="allmenusHeaders" density="comfortable" items-per-page="20"
                                  :hide-default-footer="allmenus.length < 21">
                        <template v-slot:item.menu_name="{item}">
                            <router-link :to="{name:'MenuEdit',params:{menu_id:item.menu_id}}" class="font-weight-medium">{{item.menu_name}}</router-link>
                        </template>
                        <template v-slot:item.mitems="{item}">
                            <span v-for="(mitem, idx) in item.mitems" :key="idx" class="font-weight-medium">
                                <span>{{mitem.label}}, </span>
                            </span>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";

export default{
    name:"MenusList",
    data(){
        return {
            allmenus:[],
            allmenusHeaders:[
                {title:'Menu',key:'menu_name'},
                {title:'Menu Items',key:'mitems'},
            ]
        }
    },
    created() {
        this.getAllMenusList();
    },
    methods:{
        getAllMenusList(){
            axios.get('/sadmin/menus/list')
                .then((resp)=>{
                    this.allmenus = resp.data.menus || [];
                })
        }
    }
}

</script>

<style scoped>

</style>
