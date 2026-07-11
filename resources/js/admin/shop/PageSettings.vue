<template>
    <v-container class="pa-1">
        <v-row dense>
            <v-col cols="12" md="9">
                <v-card class="mb-4">
                    <v-card-title>General</v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12" md="3">
                                <v-switch v-model="page.status" color="success" :label="page.status ? 'Enabled' : 'Disabled'"/>
                            </v-col>
                            <v-col cols="12" md="9">
                                <v-list density="compact">
                                    <v-list-item
                                        prepend-icon="mdi-link"
                                        title="Page Slug"
                                        :subtitle="page.slug"
                                    />
                                </v-list>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field v-model="page.title" label="Page Title" variant="outlined" density="compact"
                                              persistent-placeholder/>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="page.description" label="Page Description" variant="outlined" rows="4"
                                            auto-grow/>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
                <v-card>
                    <v-card-title>SEO</v-card-title>
                    <v-card-text>
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field v-model="page.meta_title" label="Meta Title" variant="outlined"
                                              density="compact"/>
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="page.meta_description" label="Meta Description" variant="outlined" rows="3"
                                            auto-grow/>
                            </v-col>
                            <v-col cols="12">
                                <v-text-field v-model="page.meta_keywords" label="Meta Keywords" variant="outlined"
                                              density="compact" hint="Separate keywords with commas" persistent-hint/>
                            </v-col>
                        </v-row>
                    </v-card-text>
                    <v-divider/>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn color="primary" variant="elevated" :loading="saveLoading" @click="updatePageSetting"> Save
                            Settings
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
            <v-col cols="12" md="3">
                <v-card>
                    <v-card-title>OG Image</v-card-title>
                    <v-card-text>
                        <v-text-field v-model="page.og_image" label="OG Image" variant="outlined" density="compact"
                                      prepend-inner-icon="mdi-image"/>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

    </v-container>
</template>
<script>
export default {
    name: "PageSettings",
    props: {
        page_type: {
            type:String,
            required:true
        },
        slug: {
            type: String,
            required: true,
        }
    },
    data(){
        return {
            loading:false,
            saveLoading: false,
            page: {
                id:null,
                page_type: "",
                slug: "/",

                title: "",
                description: "",

                meta_title: "",
                meta_description: "",
                meta_keywords: "",

                og_image: "",
                status: false,
            },
        }
    },
    methods:{
        getPageSetting() {
            this.loading = true;

            axios.get("/sadmin/page-setting", {
                params: {
                    page_type: this.page_type,
                    slug: this.slug,
                }
            })
                .then((resp) => {
                    this.page = resp.data.page;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        updatePageSetting() {
            this.saveLoading = true;

            axios.post("/sadmin/page-setting/update", this.page)
                .then((resp) => {
                    this.page = resp.data.page;
                    window.Toast.success(resp.data.message);
                })
                .finally(() => {
                    this.saveLoading = false;
                });
        }
    },
    mounted() {
        this.getPageSetting();
    }
}
</script>

<style scoped>

</style>
