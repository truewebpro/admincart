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
            users:[],
        }
    },
    created() {
        this.getDashboard();
    },
    methods:{
        getDashboard(){
            axios.get('/superadmin/dashboard')
                .then((resp)=>{
                    this.users = resp.data.users;
                })
        }
    }
}
</script>

<style scoped>

</style>
