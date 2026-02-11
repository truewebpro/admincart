<template>
    <v-container class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <v-row dense class="mt-2">
            <v-col cols="12" lg="12">
                <div class="d-flex flex-wrap ga-3">
                    <v-btn color="gray" :append-icon="section.section_json.stype_json.icon" variant="outlined"
                           class="text-none font-weight-bold rounded-lg border border-sm"
                           :href="'https://'+domain+slink.link"
                           v-for="(slink,sdx) in section.section_json.stype_json.slinks" :key="sdx">
                        {{slink.title}}
                    </v-btn>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    name: "PeopleSearchPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    },
    data(){
        return {
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
        }
    }
}
</script>

