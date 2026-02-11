<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-2">
            <v-col v-for="(fopt,fdx) in section.section_json.stype_json.foptions" :key="fdx" cols="12" md="6" lg="4">
                <v-expansion-panels v-if="section.section_json.stype_json.style !== 'style3'" variant="accordion">
                    <v-expansion-panel :title="fopt.title">
                        <template #text>
                            <div v-html="fopt.ftext"></div>
                        </template>
                    </v-expansion-panel>
                </v-expansion-panels>
                <v-card v-else>
                    <v-card-text>
                        <h3>{{ fopt.title }}</h3>
                        <p v-html="fopt.ftext"></p>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "FeaturedOptionsPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

