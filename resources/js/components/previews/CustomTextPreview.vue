<template>
    <v-card class="pa-4">
        <div v-html="section.section_json.stype_json.subtext"></div>
    </v-card>
</template>
<script>
export default {
    name: "CustomTextPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

