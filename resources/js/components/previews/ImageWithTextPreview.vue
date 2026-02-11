<template>
    <v-card class="pa-4">
        <v-row align="center">
            <v-col cols="12" md="6">
                <h2 class="text-h3 font-weight-medium mb-3">{{ section.section_json.stype_json.heading }}</h2>
                <div v-html="section.section_json.stype_json.subtext"></div>
                <v-btn color="black" class="mt-3">{{ section.section_json.stype_json.button_text }}</v-btn>
            </v-col>
            <v-col cols="12" md="6" class="text-center">
                <v-img v-if="section.section_json.stype_json.image_url"
                       :src="cdn + section.section_json.stype_json.image_url" :aspect-ratio="6/4"/>
                <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="6/4" cover position="top"/>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "ImageWithTextPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

