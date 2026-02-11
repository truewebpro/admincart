<template>
    <v-card class="pa-4">
        <h2>{{ section.section_json.stype_json.heading }}</h2>
        <div v-html="section.section_json.stype_json.subtext"></div>
        <v-row class="mt-3">
            <v-col v-for="n in 3" :key="n" cols="12" lg="4">
                <v-card elevation="3">
                    <v-card-text>
                        <v-rating density="compact" length="5" active-color="orange" model-value="5"/>
                        <h3 class="text-h5 font-weight-medium">Review title {{n+1}}</h3>
                        <div class="mt-2">Excellent experience! Fast delivery, well-packaged, and the vape works flawlessly. Smooth flavor with good throat hit. Definitely ordering again from this shop soon.</div>
                       <div class="d-flex align-center ga-2 mt-2">
                           <v-avatar size="32" color="grey">PN</v-avatar>
                           <div>Person Name</div>
                           <div>15/9/2025</div>
                       </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "ReviewSliderPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>

