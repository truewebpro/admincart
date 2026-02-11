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
]

const router = createRouter({
    history:createWebHistory(),
    routes
})

export default router;
