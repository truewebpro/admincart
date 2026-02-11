<template>
    <div>
        <v-row>
            <v-col v-for="(serv, sdx) in localModel.stype_json" :key="sdx" cols="12" lg="6">
                <div class="d-flex ga-3">
                    <span class="iconify text-h2" :data-icon="serv.icon"></span>
                    <v-text-field v-model="serv.icon" label="Icon"
                                  variant="underlined" density="comfortable"
                                  hint="Use link to find icons" persistent-hint/>
                </div>

                <span class="text-body-2">Icons link <a href='https://icon-sets.iconify.design/' target="_blank" title="Icons Link">Iconify</a></span>
                <v-text-field v-model="serv.title" label="Title" class="mt-3" variant="underlined" density="comfortable"/>
                <v-text-field v-model="serv.subtext" label="Subtext" variant="underlined" density="compact"/>
            </v-col>
        </v-row>
    </div>
</template>

<script>
export default {
    name: "ServicesPromoEditor",
    props: {
        modelValue: { type: Object, default: () => ({ product_ids: [] }) },
        availableIcons: { type: Array, default: () => [] } // [{id:1, name:"Product A"}]
    },
    data() {
        return {
            localModel: JSON.parse(JSON.stringify(this.modelValue)),
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


