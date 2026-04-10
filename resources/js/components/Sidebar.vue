<template>
  <aside :class="[
    'sidebar',
    collapsed ? 'sidebar-collapsed' : 'sidebar-expanded'
  ]">
    <!-- Logo Header -->
    <div class="sidebar-header bg-white shadow-md h-16 flex items-center justify-between px-6 border-b border-gray-200">
      <div class="logo-container">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true" class="w-24 h-20" viewBox="0 0.2 2 2">
          <path fill="#3636e0"
            d="M.892 1.548H.678v-.296q0-.034-.008-.047t-.027-.013q-.036 0-.036.061v.296H.394v-.296q0-.034-.008-.047t-.027-.014q-.036 0-.036.061v.296H.11V1.21q0-.092.064-.157T.331.988q.095 0 .17.077Q.585.988.669.988q.107 0 .172.075.051.058.051.17zM1.374.8h.214v.458q0 .126-.069.205-.04.046-.1.073t-.125.027q-.128 0-.215-.083t-.088-.202q0-.116.087-.201t.206-.085l.057.003v.227q-.026-.02-.053-.02-.033 0-.057.023t-.024.056q0 .032.024.055t.059.023q.082 0 .082-.11zm.578.199v.549h-.214V.999zM1.926.786q-.032-.03-.075-.03t-.075.03-.032.07q0 .014.003.026l.006.016q.007.016.021.028.03.028.077.028c.047 0 .057-.009.077-.028q.014-.013.021-.029l.002-.005q.006-.017.006-.037 0-.04-.032-.07m.065.016Q1.973.754 1.928.729T1.834.717q-.046.011-.068.048L1.763.77l-.001.002q.02-.036.066-.045.048-.01.092.014t.064.069q.017.042.001.077.021-.04.004-.087m.043-.02Q2.011.722 1.955.692c-.056-.03-.076-.025-.116-.015q-.056.014-.083.058l-.004.006-.001.002Q1.775.7 1.832.689q.058-.012.114.018t.078.085q.021.051.002.094.025-.049.004-.106" />
        </svg>
        <span v-show="collapsed" class="logo-icon-collapsed">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true" class="w-24 h-20" viewBox="0 0.2 2 2">
            <path fill="#3636e0"
              d="M.892 1.548H.678v-.296q0-.034-.008-.047t-.027-.013q-.036 0-.036.061v.296H.394v-.296q0-.034-.008-.047t-.027-.014q-.036 0-.036.061v.296H.11V1.21q0-.092.064-.157T.331.988q.095 0 .17.077Q.585.988.669.988q.107 0 .172.075.051.058.051.17zM1.374.8h.214v.458q0 .126-.069.205-.04.046-.1.073t-.125.027q-.128 0-.215-.083t-.088-.202q0-.116.087-.201t.206-.085l.057.003v.227q-.026-.02-.053-.02-.033 0-.057.023t-.024.056q0 .032.024.055t.059.023q.082 0 .082-.11zm.578.199v.549h-.214V.999zM1.926.786q-.032-.03-.075-.03t-.075.03-.032.07q0 .014.003.026l.006.016q.007.016.021.028.03.028.077.028c.047 0 .057-.009.077-.028q.014-.013.021-.029l.002-.005q.006-.017.006-.037 0-.04-.032-.07m.065.016Q1.973.754 1.928.729T1.834.717q-.046.011-.068.048L1.763.77l-.001.002q.02-.036.066-.045.048-.01.092.014t.064.069q.017.042.001.077.021-.04.004-.087m.043-.02Q2.011.722 1.955.692c-.056-.03-.076-.025-.116-.015q-.056.014-.083.058l-.004.006-.001.002Q1.775.7 1.832.689q.058-.012.114.018t.078.085q.021.051.002.094.025-.049.004-.106" />
          </svg>
        </span>
      </div>

      <!-- Toggle Button -->
      <button class="toggle-btn" @click="$emit('toggle')">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
      <template v-for="section in filteredMenuSections" :key="section.title">
        <SidebarSection :title="section.title" :collapsed="collapsed">
          <SidebarMenuItem v-for="item in section.items" :key="item.name" :item="item" :collapsed="collapsed" />
        </SidebarSection>
      </template>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import SidebarSection from './SidebarSection.vue'
import SidebarMenuItem from './SidebarMenuItem.vue'

const { t } = useI18n()

const props = defineProps({
  collapsed: Boolean,
  isSuperAdmin: Boolean,
  userRole: {
    type: String,
    default: 'admin'
  }
})

defineEmits(['toggle'])

const menuSections = computed(() => [
  {
    title: t('nav.mainNavigation'),
    items: [
      { name: t('nav.home'), icon: 'home', to: '/', roles: ['admin', 'staff', 'viewer'] },
      { name: t('nav.dashboard'), icon: 'dashboard', to: '/dashboard', roles: ['admin', 'staff', 'viewer'] },
    ]
  },
  {
    title: t('nav.transactions'),
    items: [
      { name: t('nav.sales'), icon: 'sales', to: '/dashboard/sales', roles: ['admin', 'staff'] },
      { name: t('nav.purchases'), icon: 'purchases', to: '/dashboard/purchases', roles: ['admin', 'staff'] },
      { name: t('nav.deliveries'), icon: 'deliveries', to: '/dashboard/deliveries', roles: ['admin', 'staff'] },
      { name: t('nav.salesPeople'), icon: 'users', to: '/dashboard/sales-people', roles: ['admin', 'staff'] },
    ]
  },
  {
    title: t('nav.accounting'),
    items: [
      { name: t('nav.accounts'), icon: 'accounts', to: '/dashboard/accounting', roles: ['admin', 'staff'] },
      { name: t('nav.history'), icon: 'history', to: '/dashboard/history', roles: ['admin', 'staff'] },
    ]
  },
  {
    title: t('nav.products'),
    items: [
      { name: t('nav.productList'), icon: 'products', to: '/dashboard/products', roles: ['admin', 'staff'] },
      { name: t('nav.categories'), icon: 'categories', to: '/dashboard/categories', roles: ['admin', 'staff'] },
    ]
  },
  {
    title: t('nav.inventory'),
    items: [
      { name: t('nav.stocksOverview'), icon: 'stock', to: '/dashboard/stock', roles: ['admin', 'staff'] },
      { name: t('nav.assetManagement'), icon: 'table', to: '/dashboard/assets', roles: ['admin', 'staff'] },
      { name: t('nav.warehouses'), icon: 'warehouse', to: '/dashboard/warehouses', roles: ['admin', 'staff'] },
      { name: t('nav.stockTransfers'), icon: 'transfer', to: '/dashboard/stock-transfers', roles: ['admin', 'staff'] },
    ]
  },
  {
    title: t('nav.services'),
    items: [
      { name: t('nav.warranties'), icon: 'forms', to: '/dashboard/warranties', roles: ['admin', 'staff'] },
      { name: t('nav.lendings'), icon: 'components', to: '/dashboard/lendings', roles: ['admin', 'staff'] },
      { name: t('nav.rmaManagement'), icon: 'elements', to: '/dashboard/rmas', roles: ['admin', 'staff'] }
    ]
  },
  {
    title: t('nav.projects'),
    items: [
      // { name: t('nav.project'), icon: 'settings', to: '/dashboard/projects', roles: ['admin', 'staff'] },
      { name: t('nav.projectInvestment'), icon: 'chart', to: '/dashboard/project-investments', roles: ['admin', 'staff'] },
      { name: t('nav.msaProject'), icon: 'settings', to: '/dashboard/msa-projects', roles: ['admin', 'staff'] },
    ]
  },
  {
    title: t('nav.cms'),
    items: [
      {
        name: t('nav.websiteContent'),
        icon: 'pages',
        roles: ['admin'],
        children: [
          { name: t('nav.solutions'), to: '/dashboard/cms/solutions', roles: ['admin'] },
          { name: t('nav.projects'), to: '/dashboard/cms/projects', roles: ['admin'] },
          { name: t('nav.siteSettings'), to: '/dashboard/cms/settings', roles: ['admin'] },
          { name: t('nav.contactInfo'), to: '/dashboard/cms/contact', roles: ['admin'] },
          { name: t('nav.carousel'), to: '/dashboard/cms/carousel', roles: ['admin'] },
          { name: t('nav.publicProducts'), to: '/dashboard/cms/public-products', roles: ['admin'] },
          { name: t('nav.pages'), to: '/dashboard/pages', roles: ['admin'] }
        ]
      }
    ]
  },
  {
    title: t('nav.administration'),
    items: [
      { name: t('nav.userManagement'), icon: 'users', to: '/dashboard/users', roles: ['admin'], superAdminOnly: true }
    ]
  }
])

const filteredMenuSections = computed(() => {
  return menuSections.value
    .map(section => ({
      ...section,
      items: section.items.filter(item => {
        if (item.superAdminOnly && !props.isSuperAdmin) return false
        if (props.isSuperAdmin) return true
        return item.roles?.includes(props.userRole)
      })
    }))
    .filter(section => section.items.length > 0)
})
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease;
  z-index: 50;
  overflow: hidden;
}

.sidebar-expanded {
  width: 260px;
}

.sidebar-collapsed {
  width: 70px;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  min-height: 64px;
}

.logo-container {
  display: flex;
  align-items: center;
  overflow: hidden;
}

.logo-text {
  display: flex;
  align-items: center;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  white-space: nowrap;
}

.logo-icon {
  font-size: 1.5rem;
  margin-right: 0.5rem;
}

.logo-icon-collapsed {
  font-size: 1.75rem;
}

.logo-name {
  background: linear-gradient(135deg, #254692 0%, #03185c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #f3f4f6;
  border: none;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.toggle-btn:hover {
  background: #e5e7eb;
  color: #2f62a0;
}

.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0.5rem 0;
  margin-left: 12px;
}

/* Custom scrollbar */
.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}
</style>
