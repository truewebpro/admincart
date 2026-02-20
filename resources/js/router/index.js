import { createRouter, createWebHistory } from "vue-router";

import AdminDashboard from "@/admin/AdminDashboard.vue";
import AdminOrders from "@/admin/order/AdminOrders.vue";
import OrderView from "@/admin/order/OrderView.vue";
import AdminCarts from "@/admin/order/AdminCarts.vue";
import CartView from "@/admin/order/CartView.vue";
import ProductsList from "@/admin/product/ProductsList.vue";
import ProductNew from "@/admin/product/ProductNew.vue";
import ProductEdit from "@/admin/product/ProductEdit.vue";
import InventoryList from "@/admin/stock/InventoryList.vue";


const routes = [
    {path:'/dashboard',name:"AdminDashboard",component:AdminDashboard},
    {path:'/orders',name:"AdminOrders",component:AdminOrders},
    {path:'/carts',name:"AdminCarts",component:AdminCarts},
    {path: '/orders/:order_id',name: 'OrderView',component:OrderView,props:true},
    {path: '/carts/:cart_id',name: 'CartView',component:CartView,props:true},
    {path: '/products',name: 'products',component:ProductsList},
    {path: '/products/new',name: 'productsnew',component:ProductNew},
    {path: '/products/:product_id',name: 'ProductEdit',component:ProductEdit,props: true},
    {path: '/inventory',name: 'InventoryList',component:InventoryList},
    {path: '/categories',name: 'cats',component: () => import('@/admin/cat/Cats.vue')},
    {path: '/categories/new',name: 'catnew',component: () => import('@/admin/cat/CatNew.vue')},
    {path: '/categories/:cat_id',name: 'CatView',component: () => import('@/admin/cat/CatView.vue'),props:true},
    {path: '/pros/ptypes',name: 'ptypes',component: () => import('@/admin/product/ptype/Ptypes.vue')},
    {path: '/pros/brands',name: 'brands',component: () => import('@/admin/product/brand/Brands.vue')},
    {path: '/pros/brands/edit/:brand_id',name:'BrandEdit',component:()=>import('@/admin/product/brand/BrandEdit.vue'),props: true},
    {path: '/pros/tags',name: 'tags',component: () => import('@/admin/product/tag/Tags.vue')},
    {path: '/pros/poptions',name: 'poptions',component: () => import('@/admin/product/option/Poptions.vue')},
    {path: '/pros/features',name: 'Features',component: () => import('@/admin/product/feature/Features.vue')},
    {path: '/customers',name: 'customers',component: () => import('@/admin/customer/Customers.vue')},
    {path: '/customers/:customer_id',name: 'CustomerEdit',component: () => import('@/admin/customer/CustomerEdit.vue'),props:true},
    {path: '/shop',component: () => import('@/admin/shop/ShopSettings.vue'),
        children:[
            {path: 'homepage',name: 'MainHomePage',component: () => import('@/admin/shop/MainHomePage.vue')},
            {path: 'cartpage',name: 'MainCartPage',component: () => import('@/admin/shop/MainCartPage.vue')},
            {path: 'settings',name: 'ShopHome',component: () => import('@/admin/shop/ShopHome.vue')},
            {path: 'footer',name: 'ShopFooter',component: () => import('@/admin/shop/ShopFooter.vue')},
            {path: 'search',name: 'ShopSearch',component: () => import('@/admin/shop/ShopSearch.vue')},
            {path: 'announcement',name: 'ShopAnnouncement',component: () => import('@/admin/shop/ShopAnnouncement.vue')},
            {path: 'subscribe',name: 'ShopSubscribe',component: () => import('@/admin/shop/ShopSubscribe.vue')},
        ]},
    {path: '/theme/store',name:'ThemeView',component: () => import('@/admin/theme/ThemeView.vue')},
    {path: '/theme/preferences',name:'Preferences',component: () => import('@/admin/theme/Preferences.vue')},
    {path: '/theme/menuslist',name:'MenusList',component: () => import('@/admin/menus/MenusList.vue')},
    {path: '/theme/menu/add',name:'MenuAdd',component: () => import('@/admin/menus/MenuAdd.vue')},
    {path: '/theme/menu/edit/:menu_id',name:'MenuEdit',component: () => import('@/admin/menus/MenuEdit.vue'),props:true},
    {path: '/theme/pages',name:'PagesList',component: () => import('@/admin/theme/page/PagesList.vue')},
    {path: '/theme/pages/new',name:'PagesNew',component: () => import('@/admin/theme/page/PagesNew.vue')},
    {path: '/theme/pages/:page_id',name:'PagesEdit',component: () => import('@/admin/theme/page/PagesEdit.vue'),props:true},
    {path: '/theme/blogs',name:'BlogsList',component: () => import('@/admin/theme/blog/BlogsList.vue')},
    {path: '/theme/blogs/new',name:'BlogsNew',component: () => import('@/admin/theme/blog/BlogsNew.vue')},
    {path: '/theme/blogs/:blog_id',name:'BlogsEdit',component: () => import('@/admin/theme/blog/BlogsEdit.vue'),props:true},
    {path: '/theme/policies',name:'PoliciesList',component: () => import('@/admin/theme/policy/PoliciesList.vue')},
    {path: '/theme/policies/new',name:'PoliciesNew',component: () => import('@/admin/theme/policy/PoliciesNew.vue')},
    {path: '/theme/policies/:policy_id',name:'PoliciesEdit',component:  () => import('@/admin/theme/policy/PoliciesEdit.vue'),props:true},
    {path: '/settings',name: 'Settings',component: () => import('@/admin/settings/Settings.vue')},
    {path: '/settings/general',name: 'SettingsGeneral',component: () => import('@/admin/settings/SettingsGeneral.vue')},
    {path: '/settings/shipping',name: 'SettingsShipping',component: () => import('@/admin/settings/SettingsShipping.vue')},
    {path: '/settings/payment',name: 'SettingsPayment',component: () => import('@/admin/settings/SettingsPayment.vue')},
    {path: '/settings/printing',name: 'SettingsPrinting',component: () => import('@/admin/settings/SettingsPrinting.vue')},
    {path: '/settings/marketing',name: 'SettingsMarketing',component: () => import('@/admin/settings/SettingsMarketing.vue')},
    {path: '/settings/inventory',name: 'SettingsInventory',component: () => import('@/admin/settings/SettingsInventory.vue')},
    {path: '/settings/review',name: 'SettingsReview',component: () => import('@/admin/settings/SettingsReview.vue')},
    {path: '/settings/account',name: 'SettingsAccount',component: () => import('@/admin/settings/SettingsAccount.vue')},
]

const router = createRouter({
    history:createWebHistory(),
    routes
})

export default router;
