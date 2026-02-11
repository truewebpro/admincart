<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <p>{{ section.section_json.stype_json.subheading }}</p>
        <v-img
            v-if="section.section_json.stype_json.image_url"
            :src="cdn + section.section_json.stype_json.image_url"
            max-width="400"
        />
        {{section}}
    </v-card>
</template>

<script>
export default {
    name: "DefaultSectionPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

