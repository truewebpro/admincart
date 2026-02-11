<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-2">
            <v-col v-for="(ques,qdx) in section.section_json.stype_json.faqs" :key="qdx" cols="12" lg="6">
                <v-expansion-panels variant="accordion">
                    <v-expansion-panel
                        :title="ques.question"
                    >
                        <template #text>
                            <div v-html="ques.answer"></div>
                        </template>
                    </v-expansion-panel>
                </v-expansion-panels>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "FaqsPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

