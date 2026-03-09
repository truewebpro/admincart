<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3">
            <v-col cols="6" lg="4" v-for="(scat,sdx) in section.section_json.stype_json.scats" :key="sdx">
                <v-card v-if="section.section_json.stype_json?.style === undefined" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute top-0">
                        <h2 class="bg-white pa-2 elevation-2 ml-n4 rounded-e-xl">{{scat.cat_name}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style1'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute top-0">
                        <h2 class="bg-white pa-2 elevation-2 ml-n4 rounded-e-xl">{{scat.cat_name}} no {{typeof section.section_json.stype_json?.style}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style1b'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute top-0">
                        <h2 class="pa-2">{{scat.cat_name}}</h2>
                        <v-btn size="small" color="black">Shop Now</v-btn>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style2'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style3'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style4'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-relative text-center top-0">
                        <h2 class="pa-2">{{scat.cat_name}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style5'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute top-0 w-100">
                        <h2 class="bg-white pa-2 elevation-2 rounded-lg text-center">{{scat.cat_name}} no {{typeof section.section_json.stype_json?.style}}</h2>
                    </v-card-text>
                </v-card>
                <v-card v-if="section.section_json.stype_json?.style === 'style5b'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top"></v-img>
                    <v-card-text class="position-absolute bottom-0 w-100">
                        <h2 class="bg-white pa-2 elevation-2 rounded-lg text-center">{{scat.cat_name}} no {{typeof section.section_json.stype_json?.style}}</h2>
                    </v-card-text>
                </v-card>
                <v-card variant="text" v-if="section.section_json.stype_json?.style === 'style6'" class="position-relative">
                    <v-img v-if="scat.image_url" :src="cdn + scat.image_url" :aspect-ratio="5/2" cover position="top" class="rounded-xl"></v-img>
                    <v-img v-else :src="cdn + 'noimage.png'" :aspect-ratio="5/2" cover position="top" class="rounded-shaped"></v-img>
                    <v-card-text class="position-relative text-center">
                        <h2 class="pa-2">{{scat.cat_name}}</h2>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "BrowseCollectionPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

