<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="12">
                <v-card>
                    <v-card-item>
                        <template #prepend>
                            <v-icon>mdi-format-list-numbered</v-icon>
                        </template>
                        <template #title>
                            <div class="text-h5 font-weight-bold">Inventory Settings</div>
                        </template>
                        <template #append>
                            <v-btn prependIcon="mdi-plus">Add New</v-btn>
                        </template>
                    </v-card-item>
                </v-card>
            </v-col>
            <v-col cols="12">
                <v-card>
                    <v-data-table></v-data-table>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>
<script>
export default {
    name:"SettingsInventory",
    data(){
        return{

        }
    }
}

</script>

<style scoped>

</style>
