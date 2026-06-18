<template>
    <v-container>
        <h2>Superadmin Dashboard</h2>
    </v-container>
</template>
<script>
export default {
    name: "SuperAdminDashboard",
    data(){
        return{

        }
    },
    created() {
        this.getDashboard();
    },
    methods:{
        getDashboard(){
            axios.get('/superadmin/dashboard')
                .then((resp)=>{
                    console.log('resp',resp.data);
                })
        }
    }
}
</script>

<style scoped>

</style>
