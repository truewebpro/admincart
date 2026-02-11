<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3">
            <v-slide-group>
                <v-slide-group-item  v-for="(brand, bdx) in section.section_json.stype_json.brands" :key="bdx">
                    <v-card elevation="3" class="ma-3">
                        <v-card-text class="text-center">
                            <v-avatar color="black" size="110">
                                <v-img v-if="brand.brand_image" :src="cdn+brand.brand_image" :alt="brand.brand_name" contain/>
                                <v-img v-else :src="cdn+'noimage.png'" :alt="brand.brand_name" contain/>
                            </v-avatar>
                        </v-card-text>
                    </v-card>
                </v-slide-group-item>
            </v-slide-group>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "PopularBrandsPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

