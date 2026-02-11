<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3">
            <v-col cols="6" lg="4" v-for="(scat,sdx) in section.section_json.stype_json.scats" :key="sdx">
                <v-card class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute top-0">
                        <h2 class="bg-white pa-2 elevation-2 ml-n4">{{scat.cat_name}}</h2>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "BrowseCollectionPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

