import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

const routes = [
    {
        path: "/",
        name: "Landing",
        component: () => import("./../views/home.vue"),
        meta: { public: true },
    },
    {
        path: "/product",
        name: "Product",
        component: () => import("./../views/productview.vue"),
        meta: { public: true },
    },
    {
        path: "/product/:slug",
        name: "product-detail",
        component: () => import("./../views/ProductDetail.vue"),
        meta: { public: true },
    },
    {
        path: "/solutions",
        name: "Solutions",
        component: () => import("../pages/web/Solutions.vue"),
        meta: { public: true },
    },
    {
        path: "/contact",
        name: "Contact",
        component: () => import("../views/Contact.vue"),
        meta: { public: true },
    },
    {
        path: "/project",
        name: "Projects",
        component: () => import("./../views/Projectview.vue"),
        meta: { public: true },
    },
    {
        path: "/diagram-fullscreen",
        name: "DiagramFullscreen",
        component: () =>
            import("../views/Product/components/DiagramFullscreen.vue"),
        meta: { public: true },
    },
    {
        path: "/login",
        name: "Login",
        component: () => import("../pages/auth/Login.vue"),
        meta: { guest: true },
    },
    {
        path: "/verify-2fa",
        name: "TwoFactorVerify",
        component: () => import("../pages/auth/TwoFactorVerify.vue"),
        meta: { guest: true },
    },
    {
        path: "/pages/:slug",
        name: "DynamicPage",
        component: () => import("../pages/web/DynamicPage.vue"),
        meta: { public: true },
    },
    {
        path: "/dashboard",
        component: () => import("../layouts/DashboardLayout.vue"),
        meta: { requiresAuth: true },
        children: [
            {
                path: "",
                name: "Dashboard",
                component: () => import("../pages/admin/Dashboard.vue"),
            },
            {
                path: "products",
                name: "Products",
                component: () => import("../pages/admin/Products.vue"),
            },
            {
                path: "categories",
                name: "Categories",
                component: () => import("../pages/admin/Categories.vue"),
            },
            {
                path: "stock",
                name: "Stock",
                component: () => import("../pages/admin/Stock.vue"),
            },
            {
                path: "sales",
                name: "Sales",
                component: () => import("../pages/admin/Sales.vue"),
            },
            {
                path: "purchases",
                name: "Purchases",
                component: () => import("../pages/admin/Purchases.vue"),
            },
            {
                path: "sales-people",
                name: "SalesPeople",
                component: () => import("../pages/admin/SalesPeople.vue"),
            },
            {
                path: "customers",
                name: "Customers",
                component: () => import("../pages/admin/Customers.vue"),
            },
            {
                path: "warranties",
                name: "Warranties",
                component: () => import("../pages/admin/Warranties.vue"),
            },
            {
                path: "warranties/create",
                name: "WarrantyCreate",
                component: () => import("../pages/admin/WarrantyCreate.vue"),
            },
            {
                path: "warranties/:id/edit",
                name: "WarrantyEdit",
                component: () => import("../pages/admin/WarrantyCreate.vue"),
            },
            {
                path: "lendings",
                name: "Lendings",
                component: () => import("../pages/admin/Lendings.vue"),
            },
            {
                path: "rmas",
                name: "RMAs",
                component: () => import("../pages/admin/RMAs.vue"),
            },
            {
                path: "project-investments",
                name: "ProjectInvestments",
                component: () => import("../pages/admin/ProjectInvestments.vue"),
            },
            {
                path: "msa-projects",
                name: "MSAProjects",
                component: () => import("../pages/admin/MSAProjects.vue"),
            },
            {
                path: "assets",
                name: "Assets",
                component: () => import("../pages/admin/Assets.vue"),
            },
            {
                path: "warehouses",
                name: "Warehouses",
                component: () => import("../pages/admin/Warehouses.vue"),
            },
            {
                path: "stock-transfers",
                name: "StockTransfers",
                component: () => import("../pages/admin/StockTransfers.vue"),
            },
            {
                path: "deliveries",
                name: "Deliveries",
                component: () => import("../pages/admin/Deliveries.vue"),
            },
            {
                path: "history",
                name: "History",
                component: () => import("../pages/admin/History.vue"),
            },
            {
                path: "accounting",
                name: "Accounting",
                component: () => import("../pages/admin/Accounting.vue"),
            },
            {
                path: "bank-accounts",
                name: "BankAccounts",
                component: () => import("../pages/admin/BankAccounts.vue"),
            },
            // CMS Content Management
            {
                path: "cms/solutions",
                name: "CmsSolutions",
                component: () => import("../pages/web/CmsSolutions.vue"),
            },
            {
                path: "cms/projects",
                name: "CmsProjects",
                component: () => import("../pages/web/CmsProjects.vue"),
            },
            {
                path: "cms/settings",
                name: "CmsSettings",
                component: () => import("../pages/web/CmsSettings.vue"),
            },
            {
                path: "cms/contact",
                name: "CmsContact",
                component: () => import("../pages/web/CmsContact.vue"),
            },
            {
                path: "cms/carousel",
                name: "CmsCarousel",
                component: () => import("../pages/web/CmsCarousel.vue"),
            },
            {
                path: "cms/public-products",
                name: "PublicProducts",
                component: () => import("../pages/web/PublicProducts.vue"),
            },
            // Page Builder
            {
                path: "pages",
                name: "Pages",
                component: () => import("../pages/web/PagesList.vue"),
            },
            {
                path: "pages/create",
                name: "PageCreate",
                component: () => import("../pages/web/PageForm.vue"),
            },
            {
                path: "pages/:id/edit",
                name: "PageEdit",
                component: () => import("../pages/web/PageForm.vue"),
            },
            // User Management
            {
                path: "users",
                name: "Users",
                component: () => import("../pages/admin/Users.vue"),
                meta: { requiresSuperAdmin: true },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        return { top: 0 };
    },
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Allow public routes without any checks
    if (to.meta.public === true) {
        next();
        return;
    }

    // For routes that require authentication
    if (to.meta.requiresAuth) {
        // Check if user is authenticated with valid token
        const isAuthenticated = await authStore.checkAuth();

        if (!isAuthenticated) {
            // Not authenticated or token invalid, redirect to login
            next({ name: "Login" });
            return;
        }

        // Check super admin requirement
        if (to.meta.requiresSuperAdmin && !authStore.isSuperAdmin) {
            next({ name: "Dashboard" });
            return;
        }
    }

    // For guest-only routes (like login page)
    if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: "Dashboard" });
        return;
    }

    next();
});

export default router;
