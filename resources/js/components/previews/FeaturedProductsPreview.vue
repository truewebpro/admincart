<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3" v-if="section.section_json.stype_json.type === 'grid'">
            <v-col cols="6" lg="2" v-for="n in 12" :key="n">
                <v-card>
                    <v-img :src="cdn + 'noimage.png'" :aspect-ratio="1/1"></v-img>
                    <v-card-text>
                        <div>Brand</div>
                        <h2>Product Title {{n}}</h2>
                        <div class="d-flex ga-2">
                            <span class="text-red font-weight-medium">£{{n+1.99}}</span>
                            <del>£{{n+2.99}}</del>
                        </div>
                        <div class="d-flex mt-2 align-center">
                            <v-text-field append-inner-icon="mdi-plus" prepend-inner-icon="mdi-minus" placeholder="1" class="w-50 me-1" variant="outlined" density="compact" hide-details></v-text-field>
                            <v-spacer/>
                            <v-btn class="w-50 ms-1" density="comfortable" color="black">Add</v-btn>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
        <v-row class="mt-3" v-if="section.section_json.stype_json.type === 'slider'">
            <v-slide-group>
                <v-slide-group-item v-for="n in 12" :key="n">
                    <v-card class="ma-2">
                        <v-img :src="cdn + 'noimage.png'" :aspect-ratio="1/1"></v-img>
                        <v-card-text>
                            <div>Brand</div>
                            <h2>Product Title {{n}}</h2>
                            <div class="d-flex ga-2">
                                <span class="text-red font-weight-medium">£{{n+1.99}}</span>
                                <del>£{{n+2.99}}</del>
                            </div>
                            <div class="d-flex mt-2 align-center">
                                <v-text-field append-inner-icon="mdi-plus" prepend-inner-icon="mdi-minus" placeholder="1" class="w-50 me-1" variant="outlined" density="compact" hide-details></v-text-field>
                                <v-spacer/>
                                <v-btn class="w-50 ms-1" density="comfortable" color="black">Add</v-btn>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-slide-group-item>
            </v-slide-group>
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

