<template>
    <v-card class="pa-4">
        <h2 class="border-left ps-2">{{ section.section_json.stype_json.heading }}</h2>
        <h3>{{ section.section_json.stype_json.subheading }}</h3>
        <div v-html="section.section_json.stype_json.subtext"></div>
    </v-card>
</template>

<script>
export default {
    name: "TextSectionPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>
<style scoped>
.border-left{
    border-left: 3px solid red;
}
</style>

