<template>
    <v-container class="pages-list">
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Pages
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn link :to="{name:'PagesNew'}" variant="tonal" color="success" density="comfortable" class="text-none"
                       prepend-icon="mdi-plus">Add Page</v-btn>
            </v-col>
        </v-row>
        <v-row class="mt-0">
            <v-col cols="12" md="12">
                <v-card elevation="0" class="border-sm">
                    <div class="ma-2">
                        <v-text-field v-model="psearch" variant="outlined" placeholder="Search all pages" density="compact" hide-details
                                      prepend-inner-icon="mdi-magnify" clearable></v-text-field>
                    </div>
                    <v-data-table :items="apages" :headers="apagesHeader" :search="psearch"
                                  v-model="selectedPage" hover
                                  return-object>
                        <template v-slot:item.page_title="{item}">
                            <div class="title d-flex align-center justify-space-between">
                                <router-link :to="'/theme/pages/'+item.page_id"
                                             class="font-weight-medium text-grey-darken-4 linkd">
                                    {{ item.page_title }}
                                </router-link>
                                <a :href="'https://'+domain+'/pages/'+item.page_slug" target="_blank">
                                    <v-icon class="opacity-0" title="View Page Online">mdi-eye-outline</v-icon>
                                </a>
                            </div>
                        </template>
                        <template v-slot:item.page_status="{item}">
                            <v-btn v-if="item.page_status === 'active'" variant="tonal" color="success" density="compact" class="rounded-pill text-capitalize">{{item.page_status}}</v-btn>
                            <v-btn v-else color="red" variant="tonal" density="compact" class="rounded-pill text-capitalize">{{item.page_status}}</v-btn>
                        </template>
                        <template v-slot:item.page_description="{item}">
                            <div class="text-truncate" v-html="item.page_description">
                            </div>
                        </template>
                        <template v-slot:item.updated_at="{item}">
                            <div class="font-weight-medium">{{dayjs(item.updated_at).format('D MMM [at] h:mm a')}}</div>
                        </template>
                    </v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import axios from "axios";
import dayjs from "dayjs";

export default {
    name:"PagesList",
    data(){
        return{
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            psearch:'',
            apages:[],
            selectedPage:null,
            selectedPages:[],
            apagesHeader:[
                {title:'Title',value:'page_title',sortable:true},
                {title:'Visibility',value:'page_status'},
                {title:'Content',value:'page_description'},
                {title:'Updated',value:'updated_at'},
            ]
        }
    },
    created() {
        this.getAllPages();
    },
    methods:{
        dayjs,
        getAllPages(){
            axios.get('/sadmin/pages/list')
                .then((resp)=>{
                    this.apages = resp.data.pages;
                })
        }
    }
}

</script>

<style scoped>
.linkd {text-decoration: none;font-size: 14px}
.linkd:hover {text-decoration: underline;}
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}
.text-truncate {
    -webkit-line-clamp: 1;
    line-clamp: 1;
    -webkit-box-orient: vertical;
    display: -webkit-box;
    overflow: hidden;
}
</style>
