<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3" v-if="section.section_json.stype_json.type === 'grid'">
            <v-col cols="6" lg="2" v-for="n in 12" :key="n">
                <v-card>
                    <v-img :src="cdn + 'noimage.png'" :aspect-ratio="1/1" width="220"></v-img>
                    <v-card-text>
                        <div>Brand</div>
                        <h2>Product Title {{n}}</h2>
                        <div class="d-flex ga-2">
                            <span class="text-red font-weight-medium">£{{n+1.99}}</span>
                            <del>£{{n+2.99}}</del>
                        </div>
                        <div class="d-flex mt-2 align-center">
                            <v-btn class="w-100 ms-1" density="comfortable" color="black">Add to Cart</v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row class="mt-3" v-if="section.section_json.stype_json.type === 'slider'">
            <v-slide-group>
                <v-slide-group-item v-for="n in 12" :key="n">
                    <v-card class="ma-2">
                        <v-img :src="cdn + 'noimage.png'" :aspect-ratio="1/1" width="220"></v-img>
                        <v-card-text>
                            <div>Brand</div>
                            <h2>Product Title {{n}}</h2>
                            <div class="d-flex ga-2">
                                <span class="text-red font-weight-medium">£{{n+1.99}}</span>
                                <del>£{{n+2.99}}</del>
                            </div>
                            <div class="d-flex mt-2 align-center">
                                <v-btn class="w-100 ms-1" density="comfortable" color="black">Add to Cart</v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-slide-group-item>
            </v-slide-group>
        </v-row>
        <v-row dense
            class="mt-3"
            align="stretch"
            v-if="section.section_json.stype_json.type === 'slideimage'">
            <v-col cols="12" lg="2">
                <v-card color="primary" class="bg-primary fill-height"></v-card>
            </v-col>
            <v-col cols="12" lg="10">
                <v-slide-group>
                    <v-slide-group-item v-for="n in 10" :key="n">
                        <v-card class="ma-1">
                            <v-img :src="cdn + 'noimage.png'" :aspect-ratio="1" width="220"></v-img>
                            <v-card-text>
                                <div>Brand</div>
                                <h3>Product Title {{n}}</h3>
                                <div class="d-flex ga-2">
                                    <span class="text-red font-weight-medium">£{{n+1.99}}</span>
                                    <del>£{{n+2.99}}</del>
                                </div>
                                <div class="d-flex mt-2 align-center">
                                    <v-btn class="w-100 ms-1" density="comfortable" color="black">Add to Cart</v-btn>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-slide-group-item>
                </v-slide-group>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "FeaturedProductsPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

