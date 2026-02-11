<template>
    <v-carousel height="400" show-arrows="hover" hide-delimiter-background hide-delimiters>
        <v-carousel-item v-for="(scat,sdx) in section.section_json.stype_json" :key="sdx"
                         :src="cdn + (scat?.image_url || 'noimage.png')" cover>
            <v-container class="position-relative h-100">
                <v-row dense align="center" class="h-100" justify="center" :class="'text-'+scat.setting.content_position">
                    <v-col cols="12" lg="12" >
                        <h2 class="pa2">{{scat.heading}}</h2>
                        <h3 class="pa2">{{scat.subheading}}</h3>
                        <p class="pa2">{{scat.promo_text}}</p>
                        <v-btn v-if="scat?.button_text">{{scat?.button_text}}</v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-carousel-item>
    </v-carousel>
</template>

<script>
export default {
    name: "SlideShowPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>
<style scoped>

</style>

