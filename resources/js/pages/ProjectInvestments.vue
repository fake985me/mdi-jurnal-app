<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">{{ t('projectInvestment.title') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ t('projectInvestment.subtitle') }}</p>
      </div>
      <button
        @click="showModal = true; resetForm()"
        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-md hover:shadow-lg font-medium">
        {{ t('projectInvestment.newProject') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadProjects" type="text" placeholder="Search by project name, code, or client..." class="input" />
        <select v-model="filters.status" @change="loadProjects" class="input">
          <option value="">All Status</option>
          <option value="pending">Pending Approval</option>
          <option value="approved">Approved</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="table-wrapper">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">Project Investments</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">Loading...</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Code</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="project in projects.data" :key="project.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ project.project_code }}</td>
            <td class="px-6 py-4">
              <span :class="project.type === 'invest' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'" class="px-2 py-1 text-xs rounded-full">
                {{ project.type === 'invest' ? 'Invest' : 'Design & Build' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ project.project_name }}</td>
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900">{{ project.client_name }}</div>
              <div class="text-xs text-gray-500">{{ project.client_contact }}</div>
            </td>
            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ formatCurrency(project.total_value) }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(project.status)">{{ project.status }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="project.status === 'pending'" @click="approveProject(project)" class="text-green-600 hover:text-green-900">Approve</button>
              <button v-if="project.status === 'approved'" @click="startProject(project)" class="text-blue-600 hover:text-blue-900">Start</button>
              <button v-if="['approved', 'active'].includes(project.status)" @click="completeProject(project)" class="text-green-600 hover:text-green-900">Complete</button>
              <button v-if="project.status !== 'completed'" @click="cancelProject(project)" class="text-red-600 hover:text-red-900">Cancel</button>
              <button @click="viewProject(project)" class="text-indigo-600 hover:text-indigo-900">View</button>
              <button @click="exportProject(project)" class="text-purple-600 hover:text-purple-900">Export</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="projects.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">Showing {{ projects.from }} to {{ projects.to }} of {{ projects.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadProjects(projects.current_page - 1)" :disabled="!projects.prev_page_url" class="btn-secondary disabled:opacity-50">Previous</button>
          <button @click="loadProjects(projects.current_page + 1)" :disabled="!projects.next_page_url" class="btn-secondary disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">New Project Investment</h3>
        
        <form @submit.prevent="saveProject" class="space-y-4">
          <!-- Project Type Selector -->
          <div class="p-4 bg-gray-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-2">Project Type *</label>
            <div class="flex gap-4">
              <label class="flex items-center p-3 border rounded-lg cursor-pointer" :class="form.type === 'invest' ? 'bg-green-50 border-green-500' : 'hover:bg-gray-100'">
                <input type="radio" v-model="form.type" value="invest" class="mr-2" />
                <div>
                  <span class="font-medium">Project Invest</span>
                  <p class="text-xs text-gray-500">Auto-create dedicated warehouse & MSA</p>
                </div>
              </label>
              <label class="flex items-center p-3 border rounded-lg cursor-pointer" :class="form.type === 'design_build' ? 'bg-blue-50 border-blue-500' : 'hover:bg-gray-100'">
                <input type="radio" v-model="form.type" value="design_build" class="mr-2" />
                <div>
                  <span class="font-medium">Design & Build</span>
                  <p class="text-xs text-gray-500">Requires MSA contract</p>
                </div>
              </label>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
              <input v-model="form.project_name" required class="input" placeholder="Enter project name" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Client Name *</label>
              <input v-model="form.client_name" required class="input" placeholder="Enter client name" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Client Contact</label>
              <input v-model="form.client_contact" class="input" placeholder="Phone or email" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
              <input v-model="form.start_date" type="date" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Expected End Date</label>
              <input v-model="form.expected_end_date" type="date" class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="2" class="input"></textarea>
          </div>

          <!-- Items Section -->
          <div class="border-t pt-4 mt-4">
            <div class="flex justify-between items-center mb-3">
              <h4 class="font-semibold text-gray-800">Project Items (Stock Allocation)</h4>
              <button type="button" @click="addItem" class="text-sm text-purple-600 hover:text-purple-800">+ Add Item</button>
            </div>
            
            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-5 gap-2 mb-2">
              <select v-model="item.product_id" @change="setItemPrice(index)" class="input col-span-2">
                <option value="">Select Product</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.title }}
                </option>
              </select>
              <input v-model.number="item.quantity" type="number" min="1" class="input" placeholder="Qty" />
              <input v-model.number="item.unit_price" type="number" step="0.01" class="input" placeholder="Unit Price" />
              <button type="button" @click="form.items.splice(index, 1)" class="text-red-500 hover:text-red-700">✕</button>
            </div>
            
            <div v-if="form.items.length" class="text-right text-sm font-semibold text-gray-700 mt-2">
              Total: {{ formatCurrency(calculateTotal()) }}
            </div>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ error }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Create Project' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Modal with Tabs -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-2xl font-bold">{{ selectedProject?.project_name }}</h3>
            <p class="text-sm text-gray-500">{{ selectedProject?.project_code }}</p>
          </div>
          <span :class="getStatusBadge(selectedProject?.status)">{{ selectedProject?.status }}</span>
        </div>
        
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-4">
          <nav class="flex -mb-px overflow-x-auto">
            <button @click="activeTab = 'overview'" :class="['px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap', activeTab === 'overview' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
              {{ t('projectInvestment.tabs.overview') }}
            </button>
            <button @click="activeTab = 'items'" :class="['px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap', activeTab === 'items' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
              {{ t('projectInvestment.tabs.items') }} ({{ selectedProject?.items?.length || 0 }})
            </button>
            <button @click="activeTab = 'materials'; loadMaterials()" :class="['px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap', activeTab === 'materials' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
              {{ t('projectInvestment.tabs.materials') }} ({{ selectedProject?.materials?.length || 0 }})
            </button>
            <button @click="activeTab = 'progress'" :class="['px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap', activeTab === 'progress' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
              {{ t('projectInvestment.tabs.progress') }}
            </button>
            <button @click="activeTab = 'tasks'; loadTasks()" :class="['px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap', activeTab === 'tasks' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
              {{ t('projectInvestment.tabs.tasks') }} ({{ taskStats.total || 0 }})
            </button>
          </nav>
        </div>

        <!-- Overview Tab -->
        <div v-show="activeTab === 'overview'" class="space-y-4">
          <!-- PO Info -->
          <div v-if="selectedProject?.po_number" class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold text-blue-800 mb-2">📋 {{ t('projectInvestment.po.title') }}</h4>
            <div class="grid grid-cols-3 gap-4 text-sm">
              <div><span class="text-gray-500">{{ t('projectInvestment.po.number') }}:</span> <strong>{{ selectedProject?.po_number }}</strong></div>
              <div><span class="text-gray-500">{{ t('projectInvestment.po.value') }}:</span> <strong class="text-blue-600">{{ formatCurrency(selectedProject?.po_value) }}</strong></div>
              <div><span class="text-gray-500">{{ t('projectInvestment.po.date') }}:</span> {{ formatDate(selectedProject?.po_date) }}</div>
            </div>
          </div>

          <!-- Progress Bars -->
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-green-50 rounded-lg">
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">{{ t('projectInvestment.progress.delivery') }}</span>
                <span class="text-lg font-bold text-green-600">{{ selectedProject?.delivery_progress || 0 }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full transition-all" :style="{width: (selectedProject?.delivery_progress || 0) + '%'}"></div>
              </div>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg">
              <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-600">{{ t('projectInvestment.progress.installation') }}</span>
                <span class="text-lg font-bold text-purple-600">{{ selectedProject?.installation_progress || 0 }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-purple-500 h-3 rounded-full transition-all" :style="{width: (selectedProject?.installation_progress || 0) + '%'}"></div>
              </div>
            </div>
          </div>

          <!-- Project Details -->
          <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg text-sm">
            <div><span class="text-gray-500">{{ t('projectInvestment.clientName') }}:</span> {{ selectedProject?.client_name }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.clientContact') }}:</span> {{ selectedProject?.client_contact || '-' }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.projectLocation') }}:</span> {{ selectedProject?.project_location || '-' }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.picName') }}:</span> {{ selectedProject?.pic_name || '-' }} {{ selectedProject?.pic_phone ? `(${selectedProject.pic_phone})` : '' }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.timeline.startDate') }}:</span> {{ formatDate(selectedProject?.start_date) }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.timeline.expectedEnd') }}:</span> {{ formatDate(selectedProject?.expected_end_date) || '-' }}</div>
            <div><span class="text-gray-500">{{ t('projectInvestment.timeline.duration') }}:</span> {{ selectedProject?.duration_days ? selectedProject.duration_days + ' ' + t('projectInvestment.timeline.days') : '-' }}</div>
            <div>
              <span class="text-gray-500">{{ t('projectInvestment.timeline.daysRemaining') }}:</span> 
              <span :class="selectedProject?.days_remaining < 0 ? 'text-red-600 font-bold' : 'text-green-600'">
                {{ selectedProject?.days_remaining ?? '-' }}
              </span>
            </div>
            <div><span class="text-gray-500">{{ t('projectInvestment.totalValue') }}:</span> <strong class="text-lg text-green-600">{{ formatCurrency(selectedProject?.total_value) }}</strong></div>
            <div v-if="selectedProject?.approved_at"><span class="text-gray-500">{{ t('projectInvestment.approvedAt') }}:</span> {{ formatDate(selectedProject?.approved_at) }}</div>
            <div class="col-span-2" v-if="selectedProject?.scope_of_work">
              <span class="text-gray-500">{{ t('projectInvestment.scopeOfWork') }}:</span>
              <p class="text-gray-700 mt-1 whitespace-pre-wrap">{{ selectedProject?.scope_of_work }}</p>
            </div>
            <div class="col-span-2" v-if="selectedProject?.description">
              <span class="text-gray-500">{{ t('projectInvestment.description') }}:</span>
              <p class="text-gray-700 mt-1">{{ selectedProject?.description }}</p>
            </div>
          </div>
        </div>

        <!-- Items Tab -->
        <div v-show="activeTab === 'items'">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left">Product</th>
                <th class="px-4 py-2 text-right">Qty</th>
                <th class="px-4 py-2 text-right">Unit Price</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-center">Stock Deducted</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in selectedProject?.items" :key="item.id">
                <td class="px-4 py-2">{{ item.product?.title }}</td>
                <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                <td class="px-4 py-2 text-right">{{ formatCurrency(item.unit_price) }}</td>
                <td class="px-4 py-2 text-right font-medium">{{ formatCurrency(item.total_price) }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="item.stock_deducted ? 'text-green-600' : 'text-gray-400'">
                    {{ item.stock_deducted ? '✓ Yes' : '○ No' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Materials Tab -->
        <div v-show="activeTab === 'materials'" class="space-y-4">
          <!-- Materials Stats -->
          <div class="grid grid-cols-5 gap-2 text-center text-xs">
            <div class="p-2 bg-gray-50 rounded"><span class="font-bold">{{ materialStats.total_count || 0 }}</span> Total</div>
            <div class="p-2 bg-yellow-50 rounded"><span class="font-bold text-yellow-600">{{ materialStats.pending || 0 }}</span> Pending</div>
            <div class="p-2 bg-blue-50 rounded"><span class="font-bold text-blue-600">{{ materialStats.ordered || 0 }}</span> Ordered</div>
            <div class="p-2 bg-green-50 rounded"><span class="font-bold text-green-600">{{ materialStats.received || 0 }}</span> Received</div>
            <div class="p-2 bg-purple-50 rounded"><span class="font-bold text-purple-600">{{ materialStats.installed || 0 }}</span> Installed</div>
          </div>

          <!-- Add Material -->
          <div class="flex gap-2">
            <input v-model="newMaterialName" type="text" placeholder="Material name..." class="input flex-1" />
            <input v-model.number="newMaterialQty" type="number" min="1" placeholder="Qty" class="input w-20" />
            <button @click="addMaterial" :disabled="!newMaterialName.trim() || newMaterialQty < 1" class="btn-primary px-4">+ Add</button>
          </div>

          <!-- Materials Table -->
          <div v-if="loadingMaterials" class="text-center py-4 text-gray-500">Loading materials...</div>
          <table v-else-if="materials.length" class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left">Material</th>
                <th class="px-3 py-2 text-center">Planned</th>
                <th class="px-3 py-2 text-center">Received</th>
                <th class="px-3 py-2 text-center">Installed</th>
                <th class="px-3 py-2 text-center">Status</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="mat in materials" :key="mat.id">
                <td class="px-3 py-2">
                  <div class="font-medium">{{ mat.name }}</div>
                  <div class="text-xs text-gray-500">{{ mat.specification || mat.unit }}</div>
                </td>
                <td class="px-3 py-2 text-center">{{ mat.quantity_planned }}</td>
                <td class="px-3 py-2 text-center">
                  <input v-model.number="mat.quantity_received" @change="updateMaterialQty(mat)" type="number" min="0" :max="mat.quantity_planned" class="w-16 text-center input py-1 text-sm" />
                </td>
                <td class="px-3 py-2 text-center">
                  <input v-model.number="mat.quantity_installed" @change="updateMaterialQty(mat)" type="number" min="0" :max="mat.quantity_received" class="w-16 text-center input py-1 text-sm" />
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="getMaterialStatusBadge(mat.status)" class="px-2 py-0.5 text-xs rounded">{{ mat.status }}</span>
                </td>
                <td class="px-3 py-2 text-right">
                  <button @click="deleteMaterial(mat)" class="text-red-600 hover:text-red-800">×</button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-center py-8 text-gray-500">No materials added yet.</p>
        </div>

        <!-- Progress Tab -->
        <div v-show="activeTab === 'progress'" class="space-y-6">
          <!-- Update Progress -->
          <div class="p-4 bg-gray-50 rounded-lg space-y-4">
            <h4 class="font-semibold text-gray-800">📊 Update Progress</h4>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Progress: {{ progressForm.delivery }}%</label>
                <input v-model.number="progressForm.delivery" type="range" min="0" max="100" step="5" class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Installation Progress: {{ progressForm.installation }}%</label>
                <input v-model.number="progressForm.installation" type="range" min="0" max="100" step="5" class="w-full" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="progressForm.notes" rows="2" class="input" placeholder="Add notes about this progress update..."></textarea>
            </div>

            <button @click="saveProgress" :disabled="savingProgress" class="btn-primary">
              {{ savingProgress ? 'Saving...' : 'Save Progress Update' }}
            </button>
          </div>

          <!-- Progress History -->
          <div>
            <h4 class="font-semibold text-gray-800 mb-3">📜 Progress History</h4>
            <div v-if="selectedProject?.progress_logs?.length" class="space-y-2 max-h-64 overflow-y-auto">
              <div v-for="log in selectedProject?.progress_logs" :key="log.id" class="p-3 bg-white rounded border text-sm">
                <div class="flex justify-between items-start">
                  <div>
                    <span :class="getLogTypeBadge(log.type)" class="px-2 py-0.5 text-xs rounded mr-2">{{ log.type }}</span>
                    <span class="font-medium">{{ log.field_name }}</span>
                    <span class="text-gray-500">: {{ log.old_value || '-' }} → {{ log.new_value }}</span>
                  </div>
                  <span class="text-xs text-gray-400">{{ formatDateTime(log.created_at) }}</span>
                </div>
                <div v-if="log.notes" class="text-gray-600 mt-1 text-xs">{{ log.notes }}</div>
                <div class="text-xs text-gray-400 mt-1">by {{ log.updated_by?.name }}</div>
              </div>
            </div>
            <p v-else class="text-center py-4 text-gray-500">No progress updates recorded yet.</p>
          </div>
        </div>

        <!-- Tasks Tab -->
        <div v-show="activeTab === 'tasks'" class="space-y-4">
          <!-- Task Stats -->
          <div class="grid grid-cols-4 gap-2 text-center text-sm">
            <div class="p-2 bg-gray-50 rounded"><span class="font-bold text-gray-600">{{ taskStats.todo || 0 }}</span> To Do</div>
            <div class="p-2 bg-blue-50 rounded"><span class="font-bold text-blue-600">{{ taskStats.in_progress || 0 }}</span> In Progress</div>
            <div class="p-2 bg-green-50 rounded"><span class="font-bold text-green-600">{{ taskStats.completed || 0 }}</span> Completed</div>
            <div class="p-2 bg-red-50 rounded"><span class="font-bold text-red-600">{{ taskStats.overdue || 0 }}</span> Overdue</div>
          </div>

          <!-- Add Task -->
          <div class="flex gap-2">
            <input v-model="newTaskTitle" @keyup.enter="addTask" type="text" placeholder="Add new task..." class="input flex-1" />
            <select v-model="newTaskPriority" class="input w-32">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <button @click="addTask" :disabled="!newTaskTitle.trim()" class="btn-primary px-4">+ Add</button>
          </div>

          <!-- Tasks List -->
          <div v-if="loadingTasks" class="text-center py-4 text-gray-500">Loading tasks...</div>
          <div v-else-if="!tasks.length" class="text-center py-8 text-gray-500">No tasks yet. Add your first task above.</div>
          <div v-else class="space-y-2">
            <div v-for="task in tasks" :key="task.id" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
              <button @click="toggleTaskStatus(task)" :class="['w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs', 
                task.status === 'completed' ? 'bg-green-500 border-green-500 text-white' : 
                task.status === 'in_progress' ? 'bg-blue-500 border-blue-500 text-white' : 'border-gray-300']">
                {{ task.status === 'completed' ? '✓' : task.status === 'in_progress' ? '►' : '' }}
              </button>
              <div class="flex-1">
                <div :class="['font-medium', task.status === 'completed' ? 'line-through text-gray-400' : '']">{{ task.title }}</div>
                <div class="text-xs text-gray-500 flex gap-2">
                  <span :class="getPriorityBadge(task.priority)">{{ task.priority }}</span>
                  <span v-if="task.due_date" :class="isOverdue(task) ? 'text-red-600' : ''">Due: {{ formatDate(task.due_date) }}</span>
                  <span v-if="task.assignee">→ {{ task.assignee.name }}</span>
                </div>
              </div>
              <button @click="editTask(task)" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
              <button @click="deleteTask(task)" class="text-red-600 hover:text-red-800 text-sm">×</button>
            </div>
          </div>
        </div>

        <div class="flex justify-end mt-6">
          <button @click="showViewModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- Edit Task Modal -->
    <div v-if="showTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ editingTask ? 'Edit Task' : 'New Task' }}</h3>
        <form @submit.prevent="saveTask" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
            <input v-model="taskForm.title" required class="input" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="taskForm.description" rows="2" class="input"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select v-model="taskForm.status" class="input">
                <option value="todo">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
              <select v-model="taskForm.priority" class="input">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
            <input v-model="taskForm.due_date" type="date" class="input" />
          </div>
          <div v-if="taskError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ taskError }}</div>
          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showTaskModal = false" class="btn-secondary">Cancel</button>
            <button type="submit" :disabled="savingTask" class="btn-primary">{{ savingTask ? 'Saving...' : 'Save' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../services/api';

const { t } = useI18n();

const projects = ref({ data: [] });
const products = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showViewModal = ref(false);
const selectedProject = ref(null);
const saving = ref(false);
const error = ref('');

const filters = ref({ search: '', status: '' });

const form = ref({
  type: 'invest',
  project_name: '',
  client_name: '',
  client_contact: '',
  description: '',
  start_date: new Date().toISOString().split('T')[0],
  expected_end_date: '',
  items: [],
});

const formatCurrency = (value) => {
  return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
};

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    approved: 'badge-info',
    active: 'badge-primary',
    completed: 'badge-success',
    cancelled: 'badge-danger',
  };
  return badges[status] || 'badge';
};

const loadProjects = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/project-investments', { params: { page, ...filters.value } });
    projects.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await api.get('/products', { params: { per_page: 1000 } });
    products.value = response.data.data || [];
  } catch (err) {
    console.error(err);
  }
};

const resetForm = () => {
  form.value = {
    type: 'invest',
    project_name: '',
    client_name: '',
    client_contact: '',
    description: '',
    start_date: new Date().toISOString().split('T')[0],
    expected_end_date: '',
    items: [],
  };
  error.value = '';
};

const addItem = () => {
  form.value.items.push({ product_id: '', quantity: 1, unit_price: 0 });
};

const setItemPrice = (index) => {
  const item = form.value.items[index];
  const product = products.value.find(p => p.id === parseInt(item.product_id));
  if (product) {
    item.unit_price = product.sell_price || 0;
  }
};

const calculateTotal = () => {
  return form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const saveProject = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/project-investments', form.value);
    showModal.value = false;
    loadProjects();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save project';
  } finally {
    saving.value = false;
  }
};

const viewProject = async (project) => {
  try {
    const response = await api.get(`/project-investments/${project.id}`);
    selectedProject.value = response.data;
    activeTab.value = 'overview';
    progressForm.value = { 
      delivery: response.data.delivery_progress || 0, 
      installation: response.data.installation_progress || 0, 
      notes: '' 
    };
    showViewModal.value = true;
  } catch (err) {
    alert('Failed to load project details');
  }
};

const approveProject = async (project) => {
  if (!confirm('Approve this project? Stock will be deducted for all items.')) return;
  try {
    await api.post(`/project-investments/${project.id}/approve`);
    loadProjects();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to approve project');
  }
};

const startProject = async (project) => {
  try {
    await api.post(`/project-investments/${project.id}/start`);
    loadProjects();
  } catch (err) {
    alert('Failed to start project');
  }
};

const completeProject = async (project) => {
  if (!confirm('Mark this project as completed?')) return;
  try {
    await api.post(`/project-investments/${project.id}/complete`);
    loadProjects();
  } catch (err) {
    alert('Failed to complete project');
  }
};

const cancelProject = async (project) => {
  if (!confirm('Cancel this project? Stock will be returned.')) return;
  try {
    await api.post(`/project-investments/${project.id}/cancel`);
    loadProjects();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to cancel project');
  }
};

const exportProject = (project) => {
  window.open(`/api/project-investments/${project.id}/export`, '_blank');
};

// Task Management
const activeTab = ref('overview');
const tasks = ref([]);
const taskStats = ref({});
const loadingTasks = ref(false);
const showTaskModal = ref(false);
const editingTask = ref(null);
const savingTask = ref(false);
const taskError = ref('');
const newTaskTitle = ref('');
const newTaskPriority = ref('medium');

const taskForm = ref({
  title: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  due_date: '',
});

const loadTasks = async () => {
  if (!selectedProject.value) return;
  loadingTasks.value = true;
  try {
    const response = await api.get(`/project-investments/${selectedProject.value.id}/tasks`);
    tasks.value = response.data.tasks || [];
    taskStats.value = response.data.stats || {};
  } catch (err) {
    console.error('Failed to load tasks:', err);
  } finally {
    loadingTasks.value = false;
  }
};

const addTask = async () => {
  if (!newTaskTitle.value.trim()) return;
  try {
    await api.post(`/project-investments/${selectedProject.value.id}/tasks`, {
      title: newTaskTitle.value,
      priority: newTaskPriority.value,
    });
    newTaskTitle.value = '';
    newTaskPriority.value = 'medium';
    loadTasks();
  } catch (err) {
    alert('Failed to add task');
  }
};

const toggleTaskStatus = async (task) => {
  const nextStatus = {
    todo: 'in_progress',
    in_progress: 'completed',
    completed: 'todo',
  };
  try {
    await api.post(`/project-tasks/${task.id}/status`, { status: nextStatus[task.status] || 'todo' });
    loadTasks();
  } catch (err) {
    alert('Failed to update task status');
  }
};

const editTask = (task) => {
  editingTask.value = task;
  taskForm.value = {
    title: task.title,
    description: task.description || '',
    status: task.status,
    priority: task.priority,
    due_date: task.due_date || '',
  };
  showTaskModal.value = true;
  taskError.value = '';
};

const saveTask = async () => {
  savingTask.value = true;
  taskError.value = '';
  try {
    if (editingTask.value) {
      await api.put(`/project-tasks/${editingTask.value.id}`, taskForm.value);
    } else {
      await api.post(`/project-investments/${selectedProject.value.id}/tasks`, taskForm.value);
    }
    showTaskModal.value = false;
    editingTask.value = null;
    loadTasks();
  } catch (err) {
    taskError.value = err.response?.data?.message || 'Failed to save task';
  } finally {
    savingTask.value = false;
  }
};

const deleteTask = async (task) => {
  if (!confirm('Delete this task?')) return;
  try {
    await api.delete(`/project-tasks/${task.id}`);
    loadTasks();
  } catch (err) {
    alert('Failed to delete task');
  }
};

const getPriorityBadge = (priority) => {
  const badges = {
    low: 'text-gray-500',
    medium: 'text-blue-600',
    high: 'text-orange-600',
    urgent: 'text-red-600 font-bold',
  };
  return badges[priority] || '';
};

const isOverdue = (task) => {
  if (!task.due_date || task.status === 'completed') return false;
  return new Date(task.due_date) < new Date();
};

// Materials Management
const materials = ref([]);
const materialStats = ref({});
const loadingMaterials = ref(false);
const newMaterialName = ref('');
const newMaterialQty = ref(1);

// Progress Management
const progressForm = ref({ delivery: 0, installation: 0, notes: '' });
const savingProgress = ref(false);

const loadMaterials = async () => {
  if (!selectedProject.value) return;
  loadingMaterials.value = true;
  try {
    const response = await api.get(`/project-investments/${selectedProject.value.id}/materials`);
    materials.value = response.data.materials || [];
    materialStats.value = response.data.stats || {};
  } catch (err) {
    console.error('Failed to load materials:', err);
  } finally {
    loadingMaterials.value = false;
  }
};

const addMaterial = async () => {
  if (!newMaterialName.value.trim() || newMaterialQty.value < 1) return;
  try {
    await api.post(`/project-investments/${selectedProject.value.id}/materials`, {
      name: newMaterialName.value,
      quantity_planned: newMaterialQty.value,
    });
    newMaterialName.value = '';
    newMaterialQty.value = 1;
    loadMaterials();
  } catch (err) {
    alert('Failed to add material');
  }
};

const updateMaterialQty = async (material) => {
  try {
    await api.post(`/project-materials/${material.id}/quantities`, {
      quantity_received: material.quantity_received,
      quantity_installed: material.quantity_installed,
    });
    loadMaterials();
  } catch (err) {
    alert('Failed to update material');
  }
};

const deleteMaterial = async (material) => {
  if (!confirm('Delete this material?')) return;
  try {
    await api.delete(`/project-materials/${material.id}`);
    loadMaterials();
  } catch (err) {
    alert('Failed to delete material');
  }
};

const getMaterialStatusBadge = (status) => {
  const badges = {
    pending: 'bg-gray-100 text-gray-600',
    ordered: 'bg-yellow-100 text-yellow-700',
    partial: 'bg-blue-100 text-blue-700',
    received: 'bg-green-100 text-green-700',
    installed: 'bg-purple-100 text-purple-700',
  };
  return badges[status] || 'bg-gray-100 text-gray-600';
};

const saveProgress = async () => {
  if (!selectedProject.value) return;
  savingProgress.value = true;
  try {
    await api.post(`/project-investments/${selectedProject.value.id}/update-progress`, {
      delivery_progress: progressForm.value.delivery,
      installation_progress: progressForm.value.installation,
      notes: progressForm.value.notes,
    });
    progressForm.value.notes = '';
    // Refresh project data
    const response = await api.get(`/project-investments/${selectedProject.value.id}`);
    selectedProject.value = response.data;
    loadProjects();
  } catch (err) {
    alert('Failed to save progress');
  } finally {
    savingProgress.value = false;
  }
};

const getLogTypeBadge = (type) => {
  const badges = {
    delivery: 'bg-green-100 text-green-700',
    installation: 'bg-purple-100 text-purple-700',
    status: 'bg-blue-100 text-blue-700',
    material: 'bg-yellow-100 text-yellow-700',
    other: 'bg-gray-100 text-gray-600',
  };
  return badges[type] || 'bg-gray-100 text-gray-600';
};

const formatDateTime = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleString('id-ID', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  });
};



onMounted(() => {
  loadProjects();
  loadProducts();
});
</script>


