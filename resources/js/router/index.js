import { createRouter, createWebHistory } from "vue-router";
import store from "../store/index.js";

import AdminDashboard from "@/admin/AdminDashboard.vue";
import AdminOrders from "@/admin/order/AdminOrders.vue";
import OrderView from "@/admin/order/OrderView.vue";
import AdminCarts from "@/admin/order/AdminCarts.vue";
import CartView from "@/admin/order/CartView.vue";
import ProductsList from "@/admin/product/ProductsList.vue";
import ProductNew from "@/admin/product/ProductNew.vue";
import ProductEdit from "@/admin/product/ProductEdit.vue";
import InventoryList from "@/admin/stock/InventoryList.vue";
import Cats from "@/admin/cat/Cats.vue";
import CatNew from "@/admin/cat/CatNew.vue";
import CatView from "@/admin/cat/CatView.vue";
import Ptypes from "@/admin/product/ptype/Ptypes.vue";
import Brands from "@/admin/product/brand/Brands.vue";
import BrandEdit from "@/admin/product/brand/BrandEdit.vue";
import Tags from "@/admin/product/tag/Tags.vue";
import Poptions from "@/admin/product/option/Poptions.vue";
import Features from "@/admin/product/feature/Features.vue";
import Customers from "@/admin/customer/Customers.vue";
import CustomerEdit from "@/admin/customer/CustomerEdit.vue";
import ShopSettings from "@/admin/shop/ShopSettings.vue";
import MainHomePage from "@/admin/shop/MainHomePage.vue";
import MainCartPage from "@/admin/shop/MainCartPage.vue";
import ShopHome from "@/admin/shop/ShopHome.vue";
import ShopFooter from "@/admin/shop/ShopFooter.vue";
import ShopSearch from "@/admin/shop/ShopSearch.vue";
import ShopAnnouncement from "@/admin/shop/ShopAnnouncement.vue";
import ShopSubscribe from "@/admin/shop/ShopSubscribe.vue";
import ShopBlogs from "@/admin/shop/ShopBlogs.vue";
import ShopBrands from "@/admin/shop/ShopBrands.vue";
import ShopCats from "@/admin/shop/ShopCats.vue";
import ShopPages from "@/admin/shop/ShopPages.vue";
import ShopPolicies from "@/admin/shop/ShopPolicies.vue";
import ShopProducts from "@/admin/shop/ShopProducts.vue";
import PagesList from "@/admin/theme/page/PagesList.vue";
import PagesNew from "@/admin/theme/page/PagesNew.vue";
import PagesEdit from "@/admin/theme/page/PagesEdit.vue";
import BlogsList from "@/admin/theme/blog/BlogsList.vue";
import BlogsNew from "@/admin/theme/blog/BlogsNew.vue";
import BlogsEdit from "@/admin/theme/blog/BlogsEdit.vue";
import PoliciesList from "@/admin/theme/policy/PoliciesList.vue";
import PoliciesNew from "@/admin/theme/policy/PoliciesNew.vue";
import PoliciesEdit from "@/admin/theme/policy/PoliciesEdit.vue";
import Settings from "@/admin/settings/Settings.vue";
import SettingsGeneral from "@/admin/settings/SettingsGeneral.vue";
import SettingsShipping from "@/admin/settings/SettingsShipping.vue";
import SettingsPayment from "@/admin/settings/SettingsPayment.vue";
import SettingsPrinting from "@/admin/settings/SettingsPrinting.vue";
import SettingsMarketing from "@/admin/settings/SettingsMarketing.vue";
import SettingsInventory from "@/admin/settings/SettingsInventory.vue";
import SettingsReview from "@/admin/settings/SettingsReview.vue";
import SettingsAccount from "@/admin/settings/SettingsAccount.vue";
import MenuEdit from "@/admin/menus/MenuEdit.vue";
import MenuAdd from "@/admin/menus/MenuAdd.vue";
import MenusList from "@/admin/menus/MenusList.vue";
import Preferences from "@/admin/theme/Preferences.vue";
import ThemeView from "@/admin/theme/ThemeView.vue";
import IntegrateList from "@/admin/integrate/IntegrateList.vue";
import AbandonedCarts from "@/admin/order/AbandonedCarts.vue";
import PlansList from "@/superadmin/plans/PlansList.vue";
import SuperAdminDashboard from "@/admin/super/SuperAdminDashboard.vue";
import SuperAdminLayout from "@/admin/super/SuperAdminLayout.vue";
import SuperAdminShops from "@/admin/super/SuperAdminShops.vue";
import SuperShopifysetup from "@/admin/super/SuperShopifysetup.vue";
import AnalyticsOverview from "@/admin/analytics/AnalyticsOverview.vue";
import AnalyticsOrders from "@/admin/order/AnalyticsOrders.vue";
import AnalyticsTraffic from "@/admin/analytics/AnalyticsTraffic.vue";
import ShopifyStyleOverview from "@/admin/analytics/ShopifyStyleOverview.vue";
import SalesAnalytics from "@/admin/analytics/SalesAnalytics.vue";
import SessionsAnalytics from "@/admin/analytics/SessionsAnalytics.vue";
import ReferrerProductAnalytics from "@/admin/analytics/ReferrerProductAnalytics.vue";
import LiveViewPage from "@/admin/analytics/LiveViewPage.vue";
import DraftOrdersList from "@/admin/order/DraftOrdersList.vue";
import DraftOrderCreate from "@/admin/order/DraftOrderCreate.vue";
import LoyaltySettings from "@/admin/loyalty/LoyaltySettings.vue";
import LoyaltyProductPointsOverride from "@/admin/loyalty/LoyaltyProductPointsOverride.vue";
import LoyaltyEarnActions from "@/admin/loyalty/LoyaltyEarnActions.vue";
import LoyaltyActionReviewQueue from "@/admin/loyalty/LoyaltyActionReviewQueue.vue";


const routes = [
    {
        path: '/super',
        component: SuperAdminLayout,
        meta: { requiresSuperAdmin: true },
        children: [
            { path: '', redirect: '/super/dashboard' },
            { path: 'dashboard', name: 'SuperAdminDashboard', component: SuperAdminDashboard },
            { path: 'shops', name: 'SuperAdminShops', component: SuperAdminShops },
            { path: 'plans', name: 'PlansList', component: PlansList },
            { path: 'shopify', name: 'SuperShopifysetup', component: SuperShopifysetup },
        ]
    },
    // {path:'/dashboard',name:"AdminDashboard",component:AdminDashboard,meta: { requiresOwner: true }},
    {path:'/dashboard',name:"AdminDashboard",redirect:"/analytics",meta: { requiresOwner: true }},
    {path: '/analytics', name: 'AnalyticsOverview', component: AnalyticsOverview,},
    {path:'/orders',name:"AdminOrders",component:AdminOrders},
    {path:'/draft-orders',name:"DraftOrdersList",component:DraftOrdersList},
    {path:'/draft-orders/create',name:"DraftOrderCreate",component:DraftOrderCreate},
    {path:'/draft-orders/:id/edit',name:"DraftOrderEdit",component:DraftOrderCreate,props:true},
    {path:'/carts',name:"AdminCarts",component:AdminCarts},
    {path: '/analytics/shopify', name: 'ShopifyStyleOverview', component: ShopifyStyleOverview,},
    {path: '/analytics/traffic', name: 'AnalyticsTraffic', component: AnalyticsTraffic,},
    {path:'/analytics/orders',name:"AnalyticsOrders",component:AnalyticsOrders},
    {path:'/analytics/sales',name:"SalesAnalytics",component:SalesAnalytics},
    {path:'/analytics/sessions',name:"SessionsAnalytics",component:SessionsAnalytics},
    {path:'/analytics/products',name:"ReferrerProductAnalytics",component:ReferrerProductAnalytics},
    {path:'/analytics/live-view',name:"LiveViewPage",component:LiveViewPage},
    {path:'/abandoned/carts',name:"AbandonedCarts",component: AbandonedCarts},
    {path: '/orders/:order_id',name: 'OrderView',component:OrderView,props:true},
    {path: '/carts/:cart_id',name: 'CartView',component:CartView,props:true},
    {path: '/products',name: 'products',component:ProductsList},
    {path: '/products/new',name: 'productsnew',component:ProductNew},
    {path: '/products/:product_id',name: 'ProductEdit',component:ProductEdit,props: true},
    {path: '/inventory',name: 'InventoryList',component:InventoryList},
    {path: '/categories',name: 'cats',component: Cats},
    {path: '/categories/new',name: 'catnew',component: CatNew},
    {path: '/categories/:cat_id',name: 'CatView',component: CatView,props:true},
    {path: '/pros/ptypes',name: 'ptypes',component: Ptypes},
    {path: '/pros/brands',name: 'brands',component: Brands},
    {path: '/pros/brands/edit/:brand_id',name:'BrandEdit',component:BrandEdit,props: true},
    {path: '/pros/tags',name: 'tags',component: Tags},
    {path: '/pros/poptions',name: 'poptions',component: Poptions},
    {path: '/pros/features',name: 'Features',component: Features},
    {path: '/loyalty/settings',name: 'LoyaltySettings',component: LoyaltySettings},
    {path: '/loyalty/product-points-override',name: 'LoyaltyProductPointsOverride',component: LoyaltyProductPointsOverride},
    {path: '/loyalty/earn-actions',name: 'LoyaltyEarnActions',component: LoyaltyEarnActions},
    {path: '/loyalty/review-queue',name: 'LoyaltyActionReviewQueue',component: LoyaltyActionReviewQueue},
    {path: '/customers',name: 'customers',component: Customers},
    {path: '/customers/:customer_id',name: 'CustomerEdit',component: CustomerEdit,props:true},
    {path: '/shop',component: ShopSettings,
        children:[
            {path: 'homepage',name: 'MainHomePage',component: MainHomePage},
            {path: 'cartpage',name: 'MainCartPage',component: MainCartPage},
            {path: 'settings',name: 'ShopHome',component: ShopHome},
            {path: 'footer',name: 'ShopFooter',component: ShopFooter},
            {path: 'search',name: 'ShopSearch',component: ShopSearch},
            {path: 'announcement',name: 'ShopAnnouncement',component: ShopAnnouncement},
            {path: 'subscribe',name: 'ShopSubscribe',component: ShopSubscribe},
            {path: 'products-page',name: 'ShopProducts',component: ShopProducts},
            {path: 'cats-page',name: 'ShopCats',component: ShopCats},
            {path: 'brands-page',name: 'ShopBrands',component: ShopBrands},
            {path: 'blogs-page',name: 'ShopBlogs',component: ShopBlogs},
            {path: 'pages-page',name: 'ShopPages',component: ShopPages},
            {path: 'policies-page',name: 'ShopPolicies',component: ShopPolicies},
        ]},
    {path: '/theme/store',name:'ThemeView',component: ThemeView},
    {path: '/theme/preferences',name:'Preferences',component: Preferences},
    {path: '/theme/menuslist',name:'MenusList',component: MenusList},
    {path: '/theme/menu/add',name:'MenuAdd',component: MenuAdd},
    {path: '/theme/menu/edit/:menu_id',name:'MenuEdit',component: MenuEdit,props:true},
    {path: '/theme/pages',name:'PagesList',component: PagesList},
    {path: '/theme/pages/new',name:'PagesNew',component: PagesNew},
    {path: '/theme/pages/:page_id',name:'PagesEdit',component: PagesEdit,props:true},
    {path: '/theme/blogs',name:'BlogsList',component: BlogsList},
    {path: '/theme/blogs/new',name:'BlogsNew',component: BlogsNew},
    {path: '/theme/blogs/:blog_id',name:'BlogsEdit',component: BlogsEdit,props:true},
    {path: '/theme/policies',name:'PoliciesList',component: PoliciesList},
    {path: '/theme/policies/new',name:'PoliciesNew',component: PoliciesNew},
    {path: '/theme/policies/:policy_id',name:'PoliciesEdit',component:  PoliciesEdit,props:true},
    {path: '/settings',name: 'Settings',component: Settings},
    {path: '/settings/general',name: 'SettingsGeneral',component: SettingsGeneral},
    {path: '/settings/shipping',name: 'SettingsShipping',component: SettingsShipping},
    {path: '/settings/payment',name: 'SettingsPayment',component: SettingsPayment},
    {path: '/settings/printing',name: 'SettingsPrinting',component: SettingsPrinting},
    {path: '/settings/marketing',name: 'SettingsMarketing',component: SettingsMarketing},
    {path: '/settings/inventory',name: 'SettingsInventory',component: SettingsInventory},
    {path: '/settings/review',name: 'SettingsReview',component: SettingsReview},
    {path: '/settings/account',name: 'SettingsAccount',component: SettingsAccount},
    {path: '/integrate',name:'IntegrateList',component: IntegrateList,
        children: [
            {path: '/list',name:'IntegrateLists',component: IntegrateList}
        ]}
]

const router = createRouter({
    history:createWebHistory(),
    routes
})

router.beforeEach(async (to, from, next) => {

    if (!store.state.contextLoaded) {
        await store.dispatch('loadContext')
    }

    const role = store.state.role;
    const requiresSuperAdmin = to.matched.some(r => r.meta.requiresSuperAdmin)
    const requiresOwner = to.matched.some(r => r.meta.requiresOwner)

    if (requiresSuperAdmin && role !== 'superadmin') {
        return next('/analytics')
    }

    if (requiresOwner && !['owner', 'superadmin'].includes(role)) {
        return next('/analytics')
    }

    next()
})

export default router;
