<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6">
                <span class="text-h6">Categories</span></v-col>
            <v-col cols="12" md="6" class="text-end">
                <v-btn link :to="{name:'catnew'}" class="text-none" variant="tonal" size="small" color="primary">Add Category</v-btn>
            </v-col>
            <v-col cols="12">
                <v-card flat class="border">
                    <div class="px-2 py-2 d-flex">
                        <v-text-field v-model="csearch" class="w-50 me-2" variant="outlined" density="compact" clearable hide-details
                                      placeholder="Searching all Products"
                                      prepend-inner-icon="mdi-magnify"
                        ></v-text-field>
                        <v-menu>
                            <template v-slot:activator="{ props: menu }">
                                <v-tooltip location="top">
                                    <template v-slot:activator="{ props: tooltip }">
                                        <v-btn v-bind="mergeProps(menu, tooltip)" icon density="comfortable">
                                            <v-icon size="small">mdi-sort</v-icon>
                                        </v-btn>
                                    </template>
                                    <span>Sort</span>
                                </v-tooltip>
                            </template>
                            <v-list nav density="compact" base-color="dark">
                                <v-list-item @click="setSort('cat_name')" title="Title" />
                                <v-list-item @click="setSort('updated_at')" title="Updated" />
                                <v-divider/>
                                <v-list-item @click="setDirection('asc')" title="A-Z" />
                                <v-list-item @click="setDirection('desc')" title="Z-A" />
                            </v-list>
                        </v-menu>
                    </div>
                    <v-toolbar v-if="selectedCats.length > 0" density="compact" height="44" color="white">
                            <v-btn variant="text" density="compact" class="font-weight-bold text-none">{{selectedCats.length}} Selected</v-btn>
                            <v-btn @click="selectedCats = []" variant="tonal" color="red" density="compact" class="text-none">Unselect All</v-btn>
                            <v-spacer/>
                            <v-menu>
                                <template v-slot:activator="{ props: menu }">
                                    <v-tooltip location="top">
                                        <template v-slot:activator="{ props: tooltip }">
                                            <v-btn variant="tonal" color="primary" class="text-none me-5" v-bind="mergeProps(menu, tooltip)"
                                                   density="compact" append-icon="mdi-chevron-down">
                                                More Actions
                                            </v-btn>
                                        </template>
                                        <span>More Actions</span>
                                    </v-tooltip>
                                </template>
                                <v-list nav density="compact">
                                    <v-list-item base-color="error" @click="deleteCats">
                                        <v-list-item-title><v-icon class="me-2">mdi-trash-can-outline</v-icon>Delete Collections</v-list-item-title>
                                    </v-list-item>
                                </v-list>
                            </v-menu>
                        </v-toolbar>
                    <div class="pt-2">
                        <v-data-table :items="filteredCats" :headers="catsHeaders" density="comfortable" items-per-page="50"
                                      hover show-select :search="csearch" :custom-filter="customFilter" :loading="isLoading"
                                      v-model="selectedCats" return-object :hideDefaultFooter="filteredCats?.length < 50">
                            <template v-slot:item.cat_image="{item}">
                                <v-img v-if="item.cat_image != null" :src="cdn+item.cat_image"
                                       max-width="45" max-height="45" contain></v-img>
                                <v-img v-else src="https://dummyimage.com/150x150/efe6f2/01010a.png&text=No+Image"
                                       max-width="45" max-height="45" contain class="rounded-lg"></v-img>
                            </template>
                            <template v-slot:item.rules="{item}">
                                <div v-if="item.rules != null">
                                    <div v-for="(rule,index) in item.rules" :key="index">
                                        Product {{rule.column}} is {{rule.relation}} to
                                        {{rule.condition}}
                                        <span v-if="item.rules.length > 1 && index < 1">{{item.cat_rule}}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-slot:item.actions="{item}">
                                <v-btn link :to="'/categories/'+item.cat_id" density="compact" variant="tonal" color="primary">Edit</v-btn>
                            </template>
                        </v-data-table>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
import {mergeProps} from "vue";

export default {
    name:"Cats",
    data(){
        return{
            csearch:'',
            cats:[],
            selectedCats:[],
            isLoading: false,
            status:"All",
            catstatus:[],
            cdn:this.$store.state.cdn,
            catsHeaders:[
                {title:'Image',value:'cat_image',width:60},
                {title:'Title',key:'cat_name',maxWidth:375,sortable:true},
                {title:'Type',key:'cat_type'},
                {title:'Products',key:'catpros_count'},
                {title:'Product Condition',value:'rules'},
                {title:'Status',value:'cat_status'},
                {title:'Actions',value:'actions'},
            ],
            sortKey: 'updated_at', // default sort
            sortDirection: 'desc', // or 'desc'
        }
    },
    mounted() {
        this.getAllCats();
    },
    created() {
    },
    computed: {
        filteredCats() {
            if (this.status === "Archived") return this.cats.filter(p => p.cat_status === "Archived");
            let filtered = this.cats.filter((p)=>{
                const matchStatus = this.status === "All" || p.cat_status === this.status;
                return matchStatus;
            })
            if (this.sortKey) {
                filtered = filtered.sort((a, b) => {
                    const aVal = this.getSortValue(a, this.sortKey).toString();
                    const bVal = this.getSortValue(b, this.sortKey).toString();
                    if (typeof aVal === 'number' && typeof bVal === 'number') {
                        return this.sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
                    }
                    return this.sortDirection === 'asc'
                        ? aVal.toString().localeCompare(bVal.toString(), undefined, { sensitivity: 'base' })
                        : bVal.toString().localeCompare(aVal.toString(), undefined, { sensitivity: 'base' });
                });
            }
            return filtered;
        }
    },
    methods:{
        mergeProps,
        setSort(key) {
            if (this.sortKey === key) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortDirection = 'asc';
            }
        },
        setDirection(dir) {
            this.sortDirection = dir;
        },
        getSortValue(item, key) {
            return key.split('.').reduce((obj, prop) => obj?.[prop], item) ?? '';
        },
        customFilter(value, search, item) {
            if (!search) return true;
            const title = value?.toString().toLowerCase() || '';
            const searchTerms = search.toLowerCase().split(' ');
            return searchTerms.every(term => title.includes(term));
        },
        async getAllCats(){
            this.isLoading = true;
            try {
                const resp = await axios.get('/sadmin/categories');
                const allData = resp.data;
                this.cats = allData.cats;
                let statuses = [...new Set(this.cats.map(item => item.cat_status))];
                statuses = statuses.filter(status => status !== "Archived");
                this.catstatus = ["All", ...statuses, "Archived"];
            } catch (e) {
                console.error("Failed to load categories", e);
            } finally {
                this.isLoading = false;
            }
        },
        deleteCats(){
            const confirmed = window.confirm("Are you sure you want to permanently delete the selected Collections?");
            if (!confirmed) return;
            const uheaders = {headers: {'Content-Type': 'multipart/form-data'}}
            const catIds = this.selectedCats.map(c => c.cat_id);
            const perdeletes = {
                cat_ids: catIds,
                mtype:"perdelete"
            }
            console.log('perdeletes',perdeletes)
        }
    }
}

</script>

<style scoped>
.title:hover{
    span { text-decoration: underline !important;}
    .opacity-0 {opacity: 1 !important;}
}

</style>
