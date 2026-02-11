import { createStore } from "vuex"

const store = createStore({
    state:{
        token: localStorage.getItem('token') || null,
        user: window.APP_USER || JSON.parse(localStorage.getItem('user')) || null,
        shop: window.APP_SHOP || JSON.parse(localStorage.getItem('shop')) || null,
        cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
        isAuthenticated:false,
        brands: [],
        productTypes: [],
        tags: [],
        poptions: [],
        acats: [],
    },
    mutations:{
        SET_SHOP(state, shop){
            state.shop = shop
            localStorage.setItem('shop', JSON.stringify(shop))
        },

        SET_USER(state, user){
            state.user = user
            localStorage.setItem('user', JSON.stringify(user))
        },

        LOGOUT(state){
            state.user = null
            state.shop = null
            state.token = null

            localStorage.removeItem('user')
            localStorage.removeItem('shop')
            localStorage.removeItem('token')
        }
    },
    getters:{
        shop:state => state.shop,
        user:state => state.user,
        isAuthenticated:state => !!state.user
    }
})

export default store;
