<template>
    <v-container>
        <v-row>
            <v-col cols="12" md="6"><h2>Account</h2></v-col>
            <v-col cols="12" md="6" class="text-md-end">
                <v-btn color="grey-darken-4" density="compact">Add New</v-btn>
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
    name:"SettingsAccount",
    data(){
        return{

        }
    }
}

</script>

<style scoped>

</style>
