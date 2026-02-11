<template>
    <div>
        <v-text-field variant="underlined" density="compact"
            v-model="localModel.stype_json.heading"
            label="Heading"
        />
        <v-text-field v-model="localModel.stype_json.icon" density="compact" variant="underlined" label="Icon" persistent-hint
                      hint="mdi-magnify, mdi-tick, mdi-tick-circle" class="mb-2"></v-text-field>
        <v-row class="mt-4">
            <v-col cols="12" lg="3" v-for="(slink, sdx) in localModel.stype_json.slinks" :key="sdx">
                <v-text-field v-model="slink.title"  density="compact" variant="underlined" label="Title"></v-text-field>
                <v-text-field v-model="slink.link"  density="compact" variant="underlined" label="Title"
                              :hint="'https://'+domain+slink.link" persistent-hint></v-text-field>
                <v-btn icon density="compact" variant="outlined" color="red" @click="removeItem(sdx)">
                    <v-icon>mdi-delete</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-btn class="mt-2" color="primary" density="compact" @click="addItem">+ Add More</v-btn>
    </div>
</template>

<script>
export default {
    name: "PeopleSearchEditor",
    props: {
        modelValue: { type: Object, default: () => ({ slinks: [] }) },
        alinks: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
            cdn:this.$store.state.cdn,
            domain:this.$store.state.shop.maindomain || this.$store.state.shop.subdomain,
        }
    },
    methods: {
        addItem() {
            this.localModel.stype_json.slinks.push({
                title: "",
                link: "/",
            })
        },
        removeItem(sdx) {
            this.localModel.stype_json.slinks.splice(sdx, 1)
        }
    },
    watch: {
        localModel: {
            handler(val) {
                this.$emit("update:modelValue", val)
            },
            deep: true
        }
    }
}
</script>


