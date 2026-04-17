<template>
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-center">
            <div class="w-full md:w-2/3">
                <v-card class="card shadow-2xl!">
                    <v-card-item title="Example Heading" appendIcon="mdi-account"></v-card-item>
                    <v-card-text class="card-body">
                        <div class="text-xl">I'm an example component.</div>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn color="success" variant="elevated">new button</v-btn>
                    </v-card-actions>

                </v-card>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
