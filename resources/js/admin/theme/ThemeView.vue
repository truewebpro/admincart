<template>
    <v-container fluid class="theme-page pa-0">
        <v-row dense class="position-sticky top-0 bg-grey-lighten-5" align="center" style="z-index: 99">
            <v-col cols="12" md="4">
                <h2 class="text-h6">
                    <v-btn icon variant="tonal" density="compact">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    Themes
                </h2>
            </v-col>
            <v-col ols="12" md="4">
                <v-tabs v-model="ttab" align-tabs="center" stacked density="compact">
                    <v-tab><v-icon icon="mdi-laptop"></v-icon>Desktop</v-tab>
                    <v-tab><v-icon icon="mdi-tablet"></v-icon>Tablet</v-tab>
                    <v-tab><v-icon icon="mdi-cellphone"></v-icon>Mobile</v-tab>
                </v-tabs>
            </v-col>
            <v-col cols="12" md="4" class="text-end">
                <v-btn :href="domain" target="_blank" variant="elevated" color="grey-lighten-2" density="compact" class="text-none"
                       prepend-icon="mdi-eye">View Your Store</v-btn>
            </v-col>
        </v-row>
        <v-row dense class="mt-0">
            <v-col cols="12" md="12">
                <v-tabs-window v-model="ttab">
                    <v-tabs-window-item>
                        <v-card width="100%" class="border-sm rounded-lg">
                            <iframe :src="domain" width="100%" height="600" frameborder="0"
                                    style="border: none;"
                            ></iframe>
                        </v-card>
                    </v-tabs-window-item>
                    <v-tabs-window-item>
                        <v-card max-width="800" class="border-sm rounded-lg mx-auto">
                            <iframe :src="domain" width="100%" height="600" frameborder="0"
                                    style="border: none;"
                            ></iframe>
                        </v-card>
                    </v-tabs-window-item>
                    <v-tabs-window-item>
                        <v-card max-width="425" class="border-sm rounded-lg mx-auto">
                            <iframe :src="domain" width="100%" height="600" frameborder="0"
                                    style="border: none;max-width: 425px"
                            ></iframe>
                        </v-card>
                    </v-tabs-window-item>
                </v-tabs-window>
            </v-col>
        </v-row>
        <v-row dense class="position-sticky bottom-0">
            <v-col cols="12" md="4" class="d">
                <v-card elevation="0" class="border-sm">
                    <v-card-text>
                        <div class="text-body-1">Sessions by device type</div>
                        <div class="d-flex align-center justify-sm-space-evenly">
                            <span class="text-h6">78%</span>
                            <span class="me-1 text-decoration-underline">Mobile</span>
                            <span class="text-h6">18%</span>
                            <span class="me-1 text-decoration-underline">Desktop</span>
                            <span class="text-h6">1%</span>
                            <span class="me-1 text-decoration-underline">Tablet</span>
                            <span class="text-h6">3%</span>
                            <span class="ms-1 text-decoration-underline">Other</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="8" class="d-flex justify-space-between">
                <v-card elevation="0" class="border-sm w-33">
                    <v-card-text>
                        <div class="text-body-1">Loading Speed</div>
                        <div class="d-flex align-center">
                            <span class="text-h6 me-2">1.76 s</span> <v-chip color="success" density="compact" prepend-icon="mdi-circle">Good</v-chip>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card elevation="0" class="border-sm w-33">
                    <v-card-text>
                        <div class="text-body-1">Interactivity</div>
                        <div class="d-flex align-center">
                            <span class="text-h6 me-2">96 ms</span> <v-chip color="success" density="compact" prepend-icon="mdi-circle">Good</v-chip>
                        </div>
                    </v-card-text>
                </v-card>
                <v-card elevation="0" class="border-sm w-33">
                    <v-card-text>
                        <div class="text-body-1">Visual Stability</div>
                        <div class="d-flex align-center">
                            <span class="text-h6 me-2">0.01</span> <v-chip color="success" density="compact" prepend-icon="mdi-circle">Good</v-chip>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name:"ThemeView",
    data(){
        return{
            ttab:null,
            ntitle:'Theme Page',
            cdn:this.$store.state.cdn,
            domain:"https://"+(this.$store.state.shop.maindomain || this.$store.state.shop.subdomain)+'/',
        }
    }
}

</script>

<style scoped>

</style>
