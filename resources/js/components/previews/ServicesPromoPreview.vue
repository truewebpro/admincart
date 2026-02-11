<template>
    <v-card class="pa-4">
        <v-row>
            <v-col cols="12" lg="3" v-for="(item,index) in section.section_json.stype_json" :key="index">
                <v-card elevation="4">
                    <v-card-text class="text-center">
                        <span class="iconify s36" :data-icon="item.icon"></span>
                        <h2>{{item.title}}</h2>
                        <div class="text-body-1">{{item.subtext}}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
export default {
    name: "ServicesPromoPreview",
    props: {
        section: { type: Object, required: true },
        cdn: { type: String, default: "" }
    }
}
</script>
<style scoped>
 .s36 {font-size: 36px}
</style>

