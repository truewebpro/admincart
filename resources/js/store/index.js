import { createStore } from "vuex"
import axios from "axios";

const store = createStore({
    state:{
        user: window.APP_USER || JSON.parse(localStorage.getItem('user') || 'null'),
        shop: window.APP_SHOP || JSON.parse(localStorage.getItem('shop') || 'null'),
        shop_id: window.APP_SHOP?.shop_id || JSON.parse(localStorage.getItem('shop_id') || 'null'),
        cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
        orderStats: {
            total: 0,
            fulfilled: 0,
            paid: 0,
            pendingPayment: 0,
            unfulfilled: 0,
            pending: 0,
            processing: 0,
        },
        alinks:[],
        mlists:[],
        role: null,
        shops: [],
        contextLoaded: false,
        allshops: [],
        switchingShop: false,
        planFeatures: [
            { key: 'online_store', label: 'Online Store', type: 'boolean' },
            { key: 'checkout', label: 'Checkout', type: 'boolean' },
            { key: 'products', label: 'Products', type: 'number' },
            { key: 'staff_accounts', label: 'Staff Accounts', type: 'number' },
            { key: 'analytics', label: 'Analytics', type: 'boolean' },
            { key: 'support_24_7', label: '24/7 Support', type: 'boolean' },
            { key: 'custom_domain', label: 'Custom Domain', type: 'boolean' },
            { key: 'ssl', label: 'Free SSL Certificate', type: 'boolean' },
            { key: 'discount_codes', label: 'Discount Codes', type: 'boolean' },
            { key: 'seo_tools', label: 'SEO Tools', type: 'boolean' },
            { key: 'inventory_control', label: 'Inventory Control', type: 'boolean' },
            { key: 'custom_labels', label: 'Custom Labels', type: 'boolean' },
            { key: 'shipping_settings', label: 'Shipping Setting', type: 'boolean' },
            { key: 'loyalty_points', label: 'Loyalty Points', type: 'boolean' },
            { key: 'custom_apps', label: 'Custom Apps', type: 'boolean' },
        ],
        planPresets: {
            basic: {
                online_store: true,
                checkout: true,
                products: 10,
                staff_accounts: 1,
                analytics: false,
                custom_domain: false,
                support_24_7: false,
                shipping_settings:false,
                loyalty_points:false,
                custom_labels:false,
                inventory_control:false,
                custom_apps:false,
                discount_codes:false,
                seo_tools:false,
                ssl:true,
            },
            ecommerce: {
                online_store: true,
                checkout: true,
                products: 100,
                staff_accounts: 5,
                analytics: true,
                custom_domain: true,
                support_24_7: false,
                shipping_settings:false,
                loyalty_points:false,
                custom_labels:false,
                inventory_control:false,
                custom_apps:false,
                discount_codes:false,
                seo_tools:false,
                ssl:true,
            },
            elite: {
                online_store: true,
                checkout: true,
                products: 250,
                staff_accounts: 10,
                analytics: true,
                custom_domain: true,
                support_24_7: true,
                shipping_settings:true,
                loyalty_points:false,
                custom_labels:false,
                inventory_control:true,
                custom_apps:true,
                discount_codes:true,
                seo_tools:true,
                ssl:true,
            },
            advanced: {
                online_store: true,
                checkout: true,
                products: 500,
                staff_accounts: 25,
                analytics: true,
                custom_domain: true,
                support_24_7: true,
                shipping_settings:true,
                loyalty_points:true,
                custom_labels:true,
                inventory_control:true,
                custom_apps:true,
                discount_codes:true,
                seo_tools:true,
                ssl:true,
            },
            business: {
                online_store: true,
                checkout: true,
                products: 'unlimited',
                staff_accounts: 'unlimited',
                analytics: true,
                custom_domain: true,
                support_24_7: true,
                shipping_settings:true,
                loyalty_points:true,
                custom_labels:true,
                inventory_control:true,
                custom_apps:true,
                discount_codes:true,
                seo_tools:true,
                ssl:true,
            },

        }
    },
    mutations:{
        SET_SHOP(state, shopId){
            state.shop_id = shopId
            localStorage.setItem('shop_id', JSON.stringify(shopId))
            // state.shop = state.shops.find(s => s.shop_id === shopId) || null
            localStorage.setItem('shop', JSON.stringify(state.shop))
        },
        SET_ALL_SHOPS(state, shops) {
            state.allshops = shops
        },
        SET_MAILTRAP_LISTS(state, lists) {
            state.mlists = lists
        },
        SET_CONTEXT(state, data) {
            state.shop_id = data.shop_id
            state.role = data.user_role
            state.shops = data.user_shops
            state.contextLoaded = true
            state.shop = data.user_shops.find(s => s.shop_id === data.shop_id)
            localStorage.setItem('shop_id', JSON.stringify(data.shop_id))
            localStorage.setItem('shop', JSON.stringify(state.shop))
        },
        SET_SWITCHING_SHOP(state, val) {
            state.switchingShop = val
        },
        SET_ALINKS(state,alinks){
            state.alinks = alinks;
        },
        SET_ORDER_STATS(state, stats) {
            state.orderStats = stats;
        },
        SET_USER(state, user){
            state.user = user
            localStorage.setItem('user', JSON.stringify(user))
        },
        LOGOUT(state){
            state.user = null
            state.shop = null
            localStorage.removeItem('shop')
            localStorage.removeItem('shop_id')
        },
    },
    getters:{
        shop:state => state.shop,
        user:state => state.user,
        isAuthenticated: state => !!state.user,
        alinks:state=>state.alinks,
        currentShop(state) {
            return state.shops.find(s => s.shop_id === state.shop_id) || null
        },
        currentShopName(state, getters) {
            return getters.currentShop?.shop_name || ''
        },
        planFeatures: (state) => state.planFeatures,
        getPlanPreset: (state) => (type) => {
            return JSON.parse(JSON.stringify(state.planPresets[type] || {}))
        }
    },
    actions:{
        async fetchAlinks({commit}){
            try {
                const resp = await axios.get('/sadmin/search/alinks')
                commit('SET_ALINKS',resp.data.alinks)
            } catch (e) {
                console.error('Failed to fetch alinks', e)
            }
        },
        async fetchOrderStats({ commit }) {
            try {
                const resp = await axios.get('/sadmin/order-stats');
                commit('SET_ORDER_STATS', resp.data);
            } catch (e) {
                console.error(e);
            }
        },
        async fetchAllShops({ commit }) {
            const res = await axios.get('/superadmin/shops')
            commit('SET_ALL_SHOPS', res.data.shops);
        },
        async fetchMailtrapList({ commit }) {
            const res = await axios.get('/superadmin/mailtrap/contacts/list')
            commit('SET_MAILTRAP_LISTS', res.data.lists);
        },
        async loadContext({ commit }, force = false) {
            if (!force && this.state.contextLoaded) return
            try {
                const res = await axios.get('/superadmin/shop-users')
                commit('SET_CONTEXT', res.data)
            } catch (e) {
                console.error('Failed to load context', e)
            }
        },
        async switchShop({ dispatch,commit, state }, shopId) {
            if (state.shop_id === shopId) return
            try {
                commit('SET_SWITCHING_SHOP', true)
                const respo = await axios.post('/superadmin/switch-shop', {
                    shop_id: shopId
                })
                if (!respo.data.success) {
                    throw new Error('Switch failed')
                }
                commit('SET_SHOP', shopId)
                await dispatch('loadContext', true)
                await dispatch('fetchOrderStats')
            } catch (e) {
                console.error('Switch shop failed', e)
            } finally {
                commit('SET_SWITCHING_SHOP', false)
            }
        },
        async logout({commit}){
            await axios.post('/logout')
            commit('LOGOUT');
            window.location.href = '/login';
        },
    }
})

export default store;
