<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3">
            <v-col v-for="n in 3" :key="n" cols="12" lg="4">
                <v-card>
                    <v-img :src="cdn+'noimage.png'" :aspect-ratio="16/9" cover position="top"/>
                    <v-card-text>
                        <div>15 Sep 2025</div>
                        <h3>Blog title {{n+1}}</h3>
                        <div>Vaping has become the go-to tool for smokers looking to put down cigarettes. If you're looking to switch but still have some lingering questions, why not have a read of our blog to learn more about how vaping truly helps when it comes to harm reduction and learn more about all the benefits that quitting comes with.</div>
                        <v-btn color="black" block class="mt-2">Read Blog</v-btn>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "BlogSliderPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

