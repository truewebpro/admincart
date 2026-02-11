<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row v-if="section.section_json.stype_json.scats">
            <v-col cols="6" lg="3" v-for="(scat,sdx) in section.section_json.stype_json.scats" :key="sdx">
                <v-card color="red">
                    <v-card-text class="text-center">
                        <v-avatar v-if="scat.image_url">
                            <v-img  :src="cdn + scat.image_url" max-width="60"/>
                        </v-avatar>
                        <v-avatar v-else>
                            <v-img :src="cdn + 'noimage.png'" max-width="60"/>
                        </v-avatar>
                        <h2 class="mt-3">{{scat?.cat_name || 'Collection Name'+sdx}}</h2>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "PopularRangePreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

