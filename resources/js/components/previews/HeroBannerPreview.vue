<template>
    <v-card class="pa-4 bg-grey-lighten-3">
        <v-row align="center">
            <v-col cols="12" lg="5">
                <div>
                    <h2 class="text-h3 font-weight-medium text-uppercase">{{ section.section_json.stype_json.heading }}</h2>
                    <div class="promo_text">{{ section.section_json.stype_json.promo_text }}</div>
                    <p class="my-4">{{ section.section_json.stype_json.subheading }}</p>
                    <v-btn color="black">{{ section.section_json.stype_json.button_text }}</v-btn>
                </div>
            </v-col>
            <v-col cols="12" lg="4" offset-lg="3">
                <v-img v-if="section.section_json.stype_json.image_url" :src="cdn + section.section_json.stype_json.image_url" max-width="330"/>
                <v-img v-else :src="cdn + 'noimage.png'" max-width="330"/>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "HeroBannerPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>
<style scoped>
.promo_text {
    position: relative;
    padding: 12px 46px 12px 40px;
    margin-left: -40px;
    width: fit-content;
    color: #263042;
    line-height: 1.2em;
    border-left: none;
    filter: drop-shadow(4px 4px 6px rgba(0, 0, 0, .1));
    font-size: 1.4rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 1.25rem;

    &::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        left: 0;
        top: 0;
        background-color: #fff;
        clip-path: polygon(0 0, 100% 0, calc(100% - 20px) 100%, 0% 100%);
        z-index: -1;
    }
}
</style>

