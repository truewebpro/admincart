<template>
    <v-container class="blogs-list">
        <v-row class="position-sticky top-0 bg-grey-lighten-5" style="z-index: 99">
            <v-col cols="12" md="6">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact" color="primary">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Blog Posts
                </h2>
            </v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn link :to="{name:'BlogsNew'}"  variant="tonal" color="success" density="comfortable" class="text-none"
                       prepend-icon="mdi-plus">Add Blog Post</v-btn>
            </v-col>
        </v-row>
        <v-row class="mt-0">
            <v-col cols="12" md="12">
                <v-card elevation="0" class="border-sm">
                    <div class="ma-2">
                        <v-text-field v-model="bsearch" variant="outlined" placeholder="Search all pages" density="compact" hide-details
                                      prepend-inner-icon="mdi-magnify" clearable></v-text-field>
                    </div>
                    <v-data-table :items="ablogs" :headers="ablogsHeader" :search="bsearch" items-per-page="20"
                                  :custom-filter="customFilter" hover>
                        <template v-slot:item.blog_image="{item}">
                            <v-img v-if="item.blog_image != null" :src="cdn+item.blog_image" width="100" max-width="100" cover class="my-1 rounded"></v-img>
                            <v-img v-else :src="cdn+'noimage.png'" class="my-1"></v-img>
                        </template>
                        <template v-slot:item.blog_title="{item}">
                            <div class="title d-flex align-center justify-space-between">
                                <router-link :to="'/theme/blogs/'+item.blog_id"
                                             class="font-weight-medium text-grey-darken-4 linkd">
                                    {{item.blog_title}}
                                </router-link>
                                <a :href="'https://'+domain+'/blogs/'+item.blog_slug" target="_blank">
                                    <v-icon class="opacity-0" title="View Blog Online">mdi-eye-outline</v-icon>
                                </a>
                            </div>
                        </template>
                        <template v-slot:item.blog_excerpt="{item}">
                            <div>{{item.blog_excerpt}}</div>
                        </template>
                        <template v-slot:item.blog_status="{item}">
                            <v-btn v-if="item.blog_status === 'active'" variant="tonal" color="success" density="compact" class="rounded-pill text-capitalize">{{item.blog_status}}</v-btn>
                            <v-btn v-else color="red" variant="tonal" density="compact" class="rounded-pill text-capitalize">{{item.blog_status}}</v-btn>
                        </template>
                        <template v-slot:item.btags="{item}">
                            <div v-if="item.btags">
                                <v-chip v-for="(btag,index) in item.btags" :key="index" density="compact"
                                        color="success" variant="outlined" class="me-1 mb-1">{{btag}}</v-chip>
                            </div>
                        </template>
                        <template v-slot:item.updated_at="{item}">
                            <div class="font-weight-medium">{{dayjs(item.updated_at).format('D MMM [at] h:mm a')}}</div>
                        </template>
                        <template v-slot:item.created_at="{item}">
                            <div class="font-weight-medium">{{dayjs(item.created_at).format('D MMM [at] h:mm a')}}</div>
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
    name:"BlogsList",
    data(){
        return{
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
            bsearch:'',
            ablogs:[],
            selectedBlog:null,
            selectedBlogs:[],
            ablogsHeader:[
                {title:'Image',value:'blog_image'},
                {title:'Title',value:'blog_title',width:350},
                {title:'Status',value:'blog_status'},
                {title:'Author',value:'user.name'},
                {title:'Updated',value:'updated_at',width: 135},
                {title:'Created',value:'created_at',width: 135},
            ]
        }
    },
    created() {
        this.getAllBlogs();
    },
    methods:{
        dayjs,
        getAllBlogs(){
            axios.get('/sadmin/blogs/list')
                .then((resp)=>{
                    this.ablogs = resp.data.blogs;
                })
        },
        customFilter(value, search, item) {
            if (!search) return true;

            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');

            return searchTerms.every(term => title.includes(term));
        },
    }
}

</script>

<style scoped>
.linkd {text-decoration: none;}
.linkd:hover {text-decoration: underline;}
.title:hover{
    span { text-decoration: underline !important;}

    .opacity-0 {opacity: 1 !important;}
}
</style>
