import { defineStore } from 'pinia';
import api from '../services/api';

export const useNavigationStore = defineStore('navigation', {
    state: () => ({
        // Default navigation items (static)
        defaultItems: [
            { name: 'Home', href: '/', order: 0 },
            {
                name: 'Product',
                href: '/product',
                order: 10,
                children: [
                    {
                        name: 'XGSPON', href: '/product?category=XGSPON', children: [
                            { name: 'OLT', href: '/product?category=XGSPON&sub=OLT' },
                            { name: 'ONT', href: '/product?category=XGSPON&sub=ONT' },
                            { name: 'ONU', href: '/product?category=XGSPON&sub=ONU' },
                            { name: 'ONU PoE', href: '/product?category=XGSPON&sub=ONU+PoE' },
                            { name: 'XGSPON Stick', href: '/product?category=XGSPON&sub=STICK' },
                        ]
                    },
                    {
                        name: 'GPON', href: '/product?category=GPON',
                        children: [
                            { name: 'OLT', href: '/product?category=GPON&sub=GPON+OLT' },
                            { name: 'ONT', href: '/product?category=GPON&sub=ONT' },
                            { name: 'ONU', href: '/product?category=GPON&sub=ONU' },
                            { name: 'ONU PoE', href: '/product?category=GPON&sub=ONU+PoE' },
                            { name: 'GPON Stick', href: '/product?category=GPON&sub=GPON+STICK' },
                        ],
                    },
                    {
                        name: 'SWITCH', href: '/product?category=Switch',
                        children: [
                            { name: 'Core Switch', href: '/product?category=SWITCH&sub=BACKBONE+SWITCH' },
                            { name: 'L3 Switch', href: '/product?category=SWITCH&sub=L3+SWITCH' },
                            { name: 'L2 Switch', href: '/product?category=SWITCH&sub=L2+SWITCH' },
                            { name: 'PoE Switch', href: '/product?category=SWITCH&sub=PoE+SWITCH' },
                        ],
                    },
                    {
                        name: 'WIFI', href: '/product?category=WIRELESS',
                        children: [
                            { name: 'Access Point Indoor', href: '/product?category=WIRELESS&sub=Access+Point+Indoor' },
                            { name: 'Access Point Outdoor', href: '/product?category=WIRELESS&sub=Access+Point+Outdoor' },
                            { name: 'Controller', href: '/product?category=WIRELESS&sub=AP+Controller' },
                        ],
                    },
                ]
            },
            { name: 'Service & Solutions', href: '/solutions', order: 20 },
            {
                name: 'Projects', href: '/project', order: 30,
                children: [
                    { name: 'ISP Customer', href: '/project?id=ispcustomer' },
                    { name: 'Managed Services', href: '/project?id=managedservices' },
                    { name: 'FTTX Project', href: '/project?id=fttxproject' },
                ]
            },
            { name: 'Contact', href: '/contact', order: 40 },
        ],
        dynamicItems: [],
        loading: false,
        lastFetch: null,
    }),

    getters: {
        // Combine default and dynamic items, sorted by order
        // Only includes dynamic items with show_in_nav = true for navbar
        navigationItems: (state) => {
            const allItems = [...state.defaultItems];

            // Add dynamic items that have show_in_nav enabled and don't have a parent
            state.dynamicItems
                .filter(item => item.show_in_nav)
                .forEach(item => {
                    if (!item.parent) {
                        allItems.push({
                            ...item,
                            order: item.order ?? 100,
                        });
                    } else {
                        // Find parent and add as child
                        const parent = allItems.find(p => p.name === item.parent);
                        if (parent) {
                            if (!parent.children) parent.children = [];
                            parent.children.push(item);
                        } else {
                            // If parent not found, add as top-level
                            allItems.push({
                                ...item,
                                order: item.order ?? 100,
                            });
                        }
                    }
                });

            // Sort by order
            return allItems.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
        },

        // Get all published pages (for management/display purposes)
        allPublishedPages: (state) => {
            return state.dynamicItems;
        },
    },

    actions: {
        async fetchDynamicNavigation() {
            // Prevent too frequent fetches (cache for 5 minutes)
            const now = Date.now();
            if (this.lastFetch && now - this.lastFetch < 5 * 60 * 1000) {
                return;
            }

            this.loading = true;
            try {
                const response = await api.get('/guest/navigation');
                this.dynamicItems = response.data || [];
                this.lastFetch = now;
            } catch (error) {
                console.error('Failed to fetch navigation:', error);
            } finally {
                this.loading = false;
            }
        },

        // Force refresh navigation (e.g., after page create/update)
        async refreshNavigation() {
            this.lastFetch = null;
            await this.fetchDynamicNavigation();
        },
    },
});
