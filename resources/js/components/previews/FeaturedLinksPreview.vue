<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3" align="stretch" justify="center">
            <v-col cols="6" md="3"
                   :lg="section.section_json.stype_json.style === 'style7a' || section.section_json.stype_json.style === 'style7b' ? 2 : 3"
                   v-for="(alink,sdx) in section.section_json.stype_json.alinks" :key="sdx">
                <v-card v-if="section.section_json.stype_json.style === 'style1'" class="position-relative style1" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-card-text  class="position-absolute top-0">
                        <h2 class="bg-white pa-2 elevation-2 ml-n4">{{alink.ctitle || alink.title}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json.style === 'style1b'" class="position-relative style1b" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-card-text  class="position-absolute top-0">
                        <h2 class="pa-2 ml-n4 text-white">{{alink.ctitle || alink.title}}</h2>
                        <v-btn size="small" color="black">Shop Now</v-btn>
                    </v-card-text>
                </v-card>
                <v-card class="position-relative style2" v-if="section.section_json.stype_json.style === 'style2'">
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" cover position="top"></v-img>
                </v-card>
                <v-card class="position-relative style3" v-if="section.section_json.stype_json.style === 'style3'">
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" cover position="top"></v-img>
                </v-card>
                <v-card v-if="section.section_json.stype_json.style === 'style4'" class="position-relative style4" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-card-text  class="position-relative text-center">
                        <h2 class="bg-white pa-2">{{alink.ctitle || alink.title}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json.style === 'style5'" class="position-relative style5" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-card-text  class="position-absolute top-0 w-100">
                        <h2 class="bg-white pa-2 elevation-2 rounded-lg text-center">{{alink.ctitle || alink.title}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json.style === 'style5b'" class="position-relative style5b" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="4/5" cover position="top"></v-img>
                    <v-card-text  class="position-absolute bottom-0 w-100">
                        <h2 class="bg-white pa-2 elevation-2 rounded-lg text-center">{{alink.ctitle || alink.title}}</h2>
                    </v-card-text>
                </v-card>
                <v-card variant="text" v-if="section.section_json.stype_json.style === 'style6'" class="position-relative style6" >
                    <v-img v-if="alink.image_url" :src="cdn + alink.image_url" :aspect-ratio="1" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="1" cover position="top" class="rounded-circle"></v-img>
                    <v-card-text  class="position-relative text-center">
                        <h2 class="bg-white pa-2">{{alink.ctitle || alink.title}}</h2>
                    </v-card-text>
                </v-card>
                <v-card
                    variant="elevated"
                    color="primary"
                    v-if="section.section_json.stype_json.style === 'style7a'"
                    class="position-relative style7a fill-height" >
                    <v-card-text  class="position-relative text-center">
                        <h3>{{alink.ctitle || alink.title}}</h3>
                    </v-card-text>
                </v-card>
                <v-card
                    variant="outlined"
                    v-if="section.section_json.stype_json.style === 'style7b'"
                    class="position-relative style7b fill-height border-md border-primary" >
                    <v-card-text  class="position-relative text-center">
                        <h3>{{alink.ctitle || alink.title}}</h3>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "FeaturedLinksPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

