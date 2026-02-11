<template>
    <v-card class="pa-4">
        <v-row align="center">
            <v-col cols="12" md="6">
                <h2 class="text-h3 font-weight-medium mb-3">{{ section.section_json.stype_json.heading }}</h2>
                <div v-html="section.section_json.stype_json.subtext"></div>
                <v-btn color="black" class="mt-3">{{ section.section_json.stype_json.button_text }}</v-btn>
            </v-col>
            <v-col cols="12" md="6" class="text-center">
                <div v-if="section.section_json.stype_json.video_url">
                    <video controls preload="metadata" class="w-100 h-100 v-img__img--cover">
                        <source :src="cdn + section.section_json.stype_json.video_url" type="video/mp4">
                        <source :src="cdn + section.section_json.stype_json.video_url.replace('.mp4', '.webm')" type="video/mp4">
                    </video>
                </div>
                <v-img v-else :src="cdn + 'novideo.jpg'" :aspect-ratio="128/72" cover position="top"/>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
import {VVideo} from "vuetify/labs/components";

export default {
    name: "VideoWithTextPreview",
    components:{VVideo},
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

