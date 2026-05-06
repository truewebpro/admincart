<template>
    <div>
        <router-view />
    </div>
</template>
<script>
import axios from "axios";

export default {
    name: "SuperAdminLayout",
    computed:{
        shops() {
            return this.$store.state.shops
        }
    },
    methods:{

    }
}
</script>

<style scoped>

</style>
