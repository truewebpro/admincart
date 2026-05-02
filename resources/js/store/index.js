import { createStore } from "vuex"
import axios from "axios";

function calculateOrderStats(orders) {
    return orders.reduce((acc, o) => {
        acc.total++;

        if (o.fulfillment_status === 'unfulfilled') acc.unfulfilled++;
        if (o.fulfillment_status === 'fulfilled') acc.fulfilled++;

        if (o.payment_status === 'paid') acc.paid++;
        if (o.payment_status === 'pending') acc.pendingPayment++;

        return acc;
    }, {
        total: 0,
        unfulfilled: 0,
        fulfilled: 0,
        paid: 0,
        pendingPayment: 0
    });
}

const store = createStore({
    state:{
        user: window.APP_USER || JSON.parse(localStorage.getItem('user') || 'null'),
        shop: window.APP_SHOP || JSON.parse(localStorage.getItem('shop') || 'null'),
        cdn:"https://truewebcart.s3-accelerate.amazonaws.com/",
        brands: [],
        brandsLoaded: false,
        productTypes: [],
        productTypesLoaded: false,
        tags: [],
        tagsLoaded: false,
        poptions: [],
        poptionsLoaded: false,
        cats: [],
        catsLoaded: false,
        instocks:[],
        instocksLoaded:false,
        products:[],
        productsLoaded:false,
        orders:[],
        ordersLoaded:false,
        orderStats: {
            total: 0,
            unfulfilled: 0,
            fulfilled: 0,
            paid: 0,
            pendingPayment: 0
        },
        alinks:[],
        role: null,
        shops: [],
        contextLoaded: false,
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
        SET_SHOP(state, shop){
            state.shop = shop
            localStorage.setItem('shop', JSON.stringify(shop))
        },
        SET_ALINKS(state,alinks){
            state.alinks = alinks;
        },
        SET_BRANDS(state, brands){
            state.brands = brands
            state.brandsLoaded = true
        },
        UPDATE_BRAND(state, updatedBrand){
            const index = state.brands.findIndex(b => b.brand_id === updatedBrand.brand_id)
            if(index !== -1){
                state.brands[index] = {...state.brands[index], ...updatedBrand}
            }
        },
        RESET_BRANDS(state){
            state.brands = []
            state.brandsLoaded = false
        },
        ADD_BRAND(state, brand){
            state.brands.unshift(brand)
        },
        DELETE_BRAND(state, brand_id){
            state.brands = state.brands.filter(b => b.brand_id !== brand_id)
        },
        SET_TAGS(state, tags) {
            state.tags = tags
            state.tagsLoaded = true
        },
        UPDATE_TAG(state, updatedTag){
            const index = state.tags.findIndex(t => t.tag_id === updatedTag.tag_id)
            if(index !== -1){
                state.tags[index] = {...state.tags[index], ...updatedTag}
            }
        },
        RESET_TAGS(state){
            state.tags = []
            state.tagsLoaded = false
        },
        ADD_TAG(state, tag){
            state.tags.unshift(tag)
        },
        DELETE_TAG(state, tag_id){
            state.tags = state.tags.filter(t => t.tag_id !== tag_id)
        },
        SET_PRODUCT_TYPES(state, productTypes) {
            state.productTypes = productTypes
            state.productTypesLoaded = true
        },
        UPDATE_PRODUCT_TYPE(state, updatedProductType){
            const index = state.productTypes.findIndex(pt => pt.product_type_id === updatedProductType.product_type_id)
            if(index !== -1){
                state.productTypes[index] = {...state.productTypes[index], ...updatedProductType}
            }
        },
        RESET_PRODUCT_TYPES(state){
            state.productTypes = []
            state.productTypesLoaded = false
        },
        ADD_PRODUCT_TYPE(state, product_type){
            state.productTypes.unshift(product_type)
        },
        DELETE_PRODUCT_TYPE(state, product_type_id){
            state.productTypes = state.productTypes.filter(p => p.product_type_id !== product_type_id)
        },
        SET_POPTIONS(state, poptions) {
            state.poptions = poptions
            state.poptionsLoaded = true
        },
        UPDATE_POPTION(state, updatedPoption){
            const index = state.poptions.findIndex(p => p.option_id === updatedPoption.option_id)
            if(index !== -1){
                state.poptions[index] = {...state.poptions[index], ...updatedPoption}
            }
        },
        RESET_POPTIONS(state){
            state.poptions = []
            state.poptionsLoaded = false
        },
        ADD_POPTION(state, poption){
            state.poptions.unshift(poption)
        },
        DELETE_POPTION(state,option_id){
            state.poptions = state.poptions.filter(p => p.option_id !== option_id)
        },
        SET_CATS(state,cats){
            state.cats = cats
            state.catsLoaded = true
        },
        UPDATE_CAT(state, updatedCat){
            const index = state.cats.findIndex(c => c.cat_id === updatedCat.cat_id)
            if(index !== -1){
                state.cats[index] = {...state.cats[index], ...updatedCat}
            }
        },
        RESET_CATS(state){
            state.cats = []
            state.catsLoaded = false
        },
        ADD_CAT(state, cat){
            state.cats.unshift(cat)
        },
        DELETE_CAT(state, cat_id){
            state.cats = state.cats.filter(c => c.cat_id !== cat_id)
        },
        SET_INVENTORY(state,instocks){
            state.instocks = instocks
            state.instocksLoaded = true
        },
        UPDATE_INSTOCK(state,updatedInstock){
            const index = state.instocks.findIndex(st => st.variant_id === updatedInstock.variant_id)
            if(index !== -1){
                state.instocks[index] = {...state.instocks[index], ...updatedInstock}
            }
        },
        SET_PRODUCTS(state,products){
            state.products = products
            state.productsLoaded = true
        },
        UPDATE_PRODUCT(state, updatedProduct) {
            const index = state.products.findIndex(
                p => p.product_id === updatedProduct.product_id
            )
            if (index !== -1) {
                state.products[index] = {
                    ...state.products[index],
                    ...updatedProduct
                }
            }
        },
        UPDATE_PRODUCTS_STATUS(state, { ids }) {
            state.products = state.products.map(p => {
                if (ids.includes(p.product_id)) {
                    return { ...p,deleted_at: new Date().toISOString(), product_status: "Archived" }
                }
                return p
            })
        },
        ADD_PRODUCT(state,product){
            state.products.unshift(product)
        },
        ADD_TAGS_TO_PRODUCTS(state, { ids, tags }) {
            state.products = state.products.map(p => {
                if (ids.includes(p.product_id)) {
                    const existing = Array.isArray(p.tags) ? p.tags : []
                    return {
                        ...p,
                        tags: [...new Set([...existing, ...tags])]
                    }
                }
                return p
            })
        },
        REMOVE_TAGS_FROM_PRODUCTS(state, { ids, tags }) {
            state.products = state.products.map(p => {
                if (ids.includes(p.product_id)) {
                    return {
                        ...p,
                        tags: p.tags.filter(t => !tags.includes(t))
                    }
                }
                return p
            })
        },
        DELETE_PRODUCT(state,product_id){
            state.products = state.products.filter(pro => pro.product_id !== product_id)
        },
        DELETE_PRODUCTS(state, ids) {
            state.products = state.products.filter(p => !ids.includes(p.product_id))
        },
        SET_ORDERS(state,orders){
            state.orders = orders;
            state.ordersLoaded = true;
            state.orderStats = calculateOrderStats(state.orders);
        },
        UPDATE_ORDER(state,updatedOrder){
            const index = state.orders.findIndex(
                o => o.order_id === updatedOrder.order_id
            )
            if(index !== -1) {
                state.orders[index] = {
                    ...state.orders[index],
                    ...updatedOrder
                }
            }
            state.orderStats = calculateOrderStats(state.orders);
        },
        SET_ORDER_STATS(state, stats) {
            state.orderStats = stats;
        },
        ADD_ORDER(state, order) {
            const exists = state.orders.find(o => o.order_id === order.order_id);
            if (!exists) {
                state.orders.unshift(order);
                state.orderStats = calculateOrderStats(state.orders);
            }
        },
        SET_USER(state, user){
            state.user = user
            localStorage.setItem('user', JSON.stringify(user))
        },
        LOGOUT(state){
            state.user = null
            state.shop = null
        },
        RESET_ALL_DATA(state) {
            state.brands = []
            state.brandsLoaded = false

            state.tags = []
            state.tagsLoaded = false

            state.productTypes = []
            state.productTypesLoaded = false

            state.poptions = []
            state.poptionsLoaded = false

            state.cats = []
            state.catsLoaded = false

            state.instocks = []
            state.instocksLoaded = false

            state.products = []
            state.productsLoaded = false

            state.orders = []
            state.ordersLoaded = false
        }

    },
    getters:{
        shop:state => state.shop,
        user:state => state.user,
        isAuthenticated: state => !!state.user,
        brands: state => state.brands,
        tags: state => state.tags,
        product_types: state => state.productTypes,
        poptions: state => state.poptions,
        cats: state => state.cats,
        instocks:state => state.instocks,
        products:state=> state.products,
        orders:state=>state.orders,
        alinks:state=>state.alinks,
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
        async fetchBrands({state,commit},force = false){
            if(state.brandsLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/brands')
                commit('SET_BRANDS', resp.data.brands)
            } catch (e) {
                console.error('Failed to fetch brands', e)
            }
        },
        async fetchTags({state,commit},force = false){
            if(state.tagsLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/tags')
                commit('SET_TAGS', resp.data.tags)
            } catch (e) {
                console.error('Failed to fetch tags', e)
            }
        },
        async fetchPtypes({state,commit},force = false){
            if(state.productTypesLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/ptypes')
                commit('SET_PRODUCT_TYPES', resp.data.ptypes)
            } catch (e) {
                console.error('Failed to fetch ptypes', e)
            }
        },
        async fetchPoptions({state,commit},force = false){
            if(state.poptionsLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/poptions')
                commit('SET_POPTIONS', resp.data.poptions)
            } catch (e) {
                console.error('Failed to fetch tags', e)
            }
        },
        async fetchCats({state,commit},force = false){
            if(state.catsLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/categories')
                commit('SET_CATS', resp.data.cats)
            } catch (e) {
                console.error('Failed to fetch categories', e)
            }
        },
        async fetchInstocks({state,commit},force = false){
            if(state.instocksLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/inventory')
                commit('SET_INVENTORY',resp.data.variants);
            } catch (e) {
                console.error('Failed to fetch Instocks', e)
            }
        },
        async fetchProducts({state,commit},force = false){
            if(state.productsLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/pros')
                commit('SET_PRODUCTS',resp.data.products);
            } catch (e) {
                console.error('Failed to fetch products', e)
            }
        },
        async fetchOrders({state,commit},force = false){
            if(state.ordersLoaded && !force) return
            try {
                const resp = await axios.get('/sadmin/orders')
                const orders = resp.data.orders
                commit('SET_ORDERS',orders)
                const stats = calculateOrderStats(orders);
                commit('SET_ORDER_STATS', stats);
            } catch (e) {
                console.error('Failed to fetch orders', e)
            }
        },
        async fetchShopResources({ dispatch, state }) {
            if (state.brandsLoaded &&
                state.tagsLoaded &&
                state.productTypesLoaded &&
                state.poptionsLoaded &&
                state.catsLoaded &&
                state.instocksLoaded &&
                state.productsLoaded &&
                state.ordersLoaded
            ) return
            try {
                await Promise.all([
                    dispatch('fetchBrands'),
                    dispatch('fetchTags'),
                    dispatch('fetchPtypes'),
                    dispatch('fetchPoptions'),
                    dispatch('fetchCats'),
                    dispatch('fetchInstocks'),
                    dispatch('fetchProducts'),
                    dispatch('fetchOrders')
                ])
            } catch (e) {
                console.error('Failed to fetch shop resources:', e)
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
