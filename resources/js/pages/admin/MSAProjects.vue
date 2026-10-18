<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">{{ $t('pageTitles.MSAProjects') }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ $t('ui.maintenanceServiceAgreementTrackProjectItemsWithIssues') }}</p>
      </div>
      <button v-if="activeTab === 'records'" @click="showModal = true; resetForm()" class="px-6 py-3 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg hover:from-orange-700 hover:to-orange-800 transition-all shadow-md font-medium">
        {{ $t('ui.newMsa') }}
      </button>
      <button v-else @click="showContractModal = true; resetContractForm()" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md font-medium">
        {{ $t('ui.newMsaContract') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button @click="activeTab = 'records'"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm',
            activeTab === 'records'
              ? 'border-orange-500 text-orange-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
          {{ $t('ui.msaRecords') }}
        </button>
        <button @click="activeTab = 'contracts'; loadContracts()"
          :class="[
            'py-3 px-1 border-b-2 font-medium text-sm',
            activeTab === 'contracts'
              ? 'border-indigo-500 text-indigo-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
          {{ $t('ui.msaContract') }}
        </button>
      </nav>
    </div>

    <!-- Filters -->
    <div class="card p-4" v-if="activeTab === 'records'">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="filters.search" @input="loadMSAs" type="text" :placeholder="$t('ui.searchByMsaCodeOrProduct')" class="input" />
        <select v-model="filters.status" @change="loadMSAs" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="pending">{{ $t('labels.pending') }}</option>
          <option value="in_repair">{{ $t('labels.in_repair') }}</option>
          <option value="returned">{{ $t('labels.returned') }}</option>
          <option value="replaced">{{ $t('labels.replaced') }}</option>
          <option value="closed">{{ $t('labels.closed') }}</option>
        </select>
      </div>
    </div>

    <div class="card p-4" v-else>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input v-model="contractFilters.search" @input="loadContracts" type="text" :placeholder="$t('ui.searchByMsaCodeOrProject')" class="input" />
        <select v-model="contractFilters.status" @change="loadContracts" class="input">
          <option value="">{{ $t('sales.allStatus') }}</option>
          <option value="draft">{{ $t('labels.draft') }}</option>
          <option value="active">{{ $t('labels.active') }}</option>
          <option value="expired">{{ $t('labels.expired') }}</option>
          <option value="cancelled">{{ $t('labels.cancelled') }}</option>
        </select>
      </div>
    </div>

    <!-- MSA Table -->
    <div class="table-wrapper" v-if="activeTab === 'records'">
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.msaRecords') }}</h3>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('common.loading') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.msaCode') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.product') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('labels.project') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.issue') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('sales.quantity') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="msa in msas.data" :key="msa.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ msa.msa_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ msa.product?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ msa.project?.project_name || '-' }}</td>
            <td class="px-6 py-4">
              <span :class="getIssueBadge(msa.issue_type)">{{ msa.issue_type }}</span>
            </td>
            <td class="px-6 py-4 text-sm font-bold">{{ msa.quantity }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusBadge(msa.status)">{{ $label(msa.status) }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button v-if="msa.status === 'pending'" @click="startRepair(msa)" class="text-blue-600 hover:text-blue-900">{{ $t('ui.sendToRepair') }}</button>
              <button v-if="msa.status === 'in_repair'" @click="openReturnModal(msa, 'return')" class="text-green-600 hover:text-green-900">{{ $t('ui.markReturned') }}</button>
              <button v-if="msa.status === 'in_repair'" @click="openReturnModal(msa, 'replace')" class="text-orange-600 hover:text-orange-900">{{ $t('ui.replace') }}</button>
              <button v-if="['returned', 'replaced'].includes(msa.status)" @click="closeMSA(msa)" class="text-gray-600 hover:text-gray-900">{{ $t('ui.close') }}</button>
              <button @click="deleteMSA(msa)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="msas.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ msas.from }} {{ $t('ui.to') }} {{ msas.to }} {{ $t('sales.of') }} {{ msas.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadMSAs(msas.current_page - 1)" :disabled="!msas.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadMSAs(msas.current_page + 1)" :disabled="!msas.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- MSA Contracts Table -->
    <div class="table-wrapper" v-else>
      <div class="card-header">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('ui.msaContract') }}</h3>
      </div>

      <div v-if="loadingContracts" class="p-8 text-center">
        <p class="text-gray-600">{{ $t('ui.loadingContracts') }}</p>
      </div>

      <table v-else class="min-w-full">
        <thead class="table-header">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.msaCode') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('labels.project') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.period') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('ui.profitRate') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('projectInvestment.materials.status') }}</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('sales.actions') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="contract in msaContracts.data" :key="contract.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ contract.msa_code }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">
              {{ contract.project?.project_name || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ contract.start_date || '-' }} - {{ contract.end_date || '-' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">
              {{ contract.sharing_profit_rate != null ? contract.sharing_profit_rate + '%' : '-' }}
            </td>
            <td class="px-6 py-4">
              <span class="badge">{{ $label(contract.status) }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="openContractPaymentModal(contract)" class="text-emerald-600 hover:text-emerald-900">{{ $t('ui.payments') }}</button>
              <button @click="editContract(contract)" class="text-blue-600 hover:text-blue-900">{{ $t('common.edit') }}</button>
              <button @click="deleteContract(contract)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="msaContracts.data?.length" class="px-6 py-4 bg-gray-50 flex justify-between">
        <p class="text-sm text-gray-700">{{ $t('sales.showing') }} {{ msaContracts.from }} {{ $t('ui.to') }} {{ msaContracts.to }} {{ $t('sales.of') }} {{ msaContracts.total }}</p>
        <div class="flex space-x-2">
          <button @click="loadContracts(msaContracts.current_page - 1)" :disabled="!msaContracts.prev_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.previous') }}</button>
          <button @click="loadContracts(msaContracts.current_page + 1)" :disabled="!msaContracts.next_page_url" class="btn-secondary disabled:opacity-50">{{ $t('sales.next') }}</button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">{{ $t('ui.newMsaRecord') }}</h3>
        
        <form @submit.prevent="saveMSA" class="space-y-4">
          <!-- Project Selection (Primary) -->
          <div class="p-4 bg-blue-50 rounded-lg">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.project') }}</label>
            <select v-model="form.project_investment_id" required class="input">
              <option value="">{{ $t('ui.selectProject') }}</option>
              <option v-for="project in investProjects" :key="project.id" :value="project.id">{{ project.project_name }} ({{ project.project_code }})</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">{{ $t('ui.onlyProjectInvestTypeProjectsAreShown') }}</p>
          </div>
          
          <!-- Product (Optional) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.productOptional') }}</label>
            <select v-model="form.product_id" class="input">
              <option value="">{{ $t('ui.noSpecificProduct') }}</option>
              <option v-for="product in products" :key="product.id" :value="product.id">{{ product.title }}</option>
            </select>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.quantity') }}</label>
              <input v-model.number="form.quantity" type="number" min="1" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.reportedDate') }}</label>
              <input v-model="form.reported_date" type="date" required class="input" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.issueType') }}</label>
            <select v-model="form.issue_type" required class="input">
              <option value="damaged">{{ $t('labels.defective') }}</option>
              <option value="defective">{{ $t('labels.defective') }}</option>
              <option value="malfunction">{{ $t('ui.malfunction') }}</option>
              <option value="other">{{ $t('labels.other') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.issueDescription') }}</label>
            <textarea v-model="form.issue_description" rows="2" class="input"></textarea>
          </div>

          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(error) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? $t('sales.saving') : $t('ui.createMsa') }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MSA Contract Modal -->
    <div v-if="showContractModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg">
        <h3 class="text-2xl font-bold mb-6">{{ editingContractId ? $t('ui.editMsaContract') : $t('ui.newMsaContract2') }}</h3>

        <form @submit.prevent="saveContract" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.projectOptional') }}</label>
            <select v-model="contractForm.project_investment_id" class="input">
              <option value="">{{ $t('ui.noProject') }}</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.project_name }} ({{ project.project_code }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.msaCode') }}</label>
              <input v-model="contractForm.msa_code" class="input" :placeholder="$t('ui.autoGeneratedIfEmpty')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
              <select v-model="contractForm.status" class="input">
                <option value="draft">{{ $t('labels.draft') }}</option>
                <option value="active">{{ $t('labels.active') }}</option>
                <option value="expired">{{ $t('labels.expired') }}</option>
                <option value="cancelled">{{ $t('labels.cancelled') }}</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.timeline.startDate') }}</label>
              <input v-model="contractForm.start_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.endDate') }}</label>
              <input v-model="contractForm.end_date" type="date" class="input" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.sharingProfitRate') }}</label>
            <input v-model.number="contractForm.sharing_profit_rate" type="number" step="0.01" min="0" max="100" class="input" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
            <textarea v-model="contractForm.notes" rows="2" class="input"></textarea>
          </div>

          <div v-if="contractError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(contractError) }}</div>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button type="button" @click="showContractModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button type="submit" :disabled="savingContract" class="btn-primary">
              {{ savingContract ? $t('sales.saving') : $t('ui.saveContract') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MSA Contract Payments Modal -->
    <div v-if="showContractPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-xl font-bold text-gray-900">{{ $t('ui.msaContractPayments') }}</h3>
            <p class="text-sm text-gray-600">{{ $t('ui.msaCode2') }} {{ paymentContract?.msa_code }}</p>
          </div>
          <button @click="closeContractPaymentModal" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>

        <div class="mb-4 p-3 bg-gray-50 rounded text-sm text-gray-600">
          {{ $t('ui.noFixedTotalAmountForMsaContractsPaymentsAreRecordedAsNeeded') }}
        </div>

        <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
          <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold text-gray-800">{{ editingContractPaymentId ? $t('ui.editPayment') : $t('ui.addPayment2') }}</h4>
            <button v-if="editingContractPaymentId" @click="resetContractPaymentForm" type="button" class="text-sm text-gray-500 hover:text-gray-700">
              {{ $t('ui.cancelEdit') }}
            </button>
          </div>
          <form @submit.prevent="submitContractPayment" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.type2') }}</label>
              <select v-model="contractPaymentForm.payment_type" required class="input">
                <option value="dp">DP</option>
                <option value="termin">{{ $t('labels.installment') }}</option>
                <option value="full">{{ $t('labels.full') }}</option>
                <option value="sharing_profit">{{ $t('ui.sharingProfit') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.amount2') }}</label>
              <input v-model.number="contractPaymentForm.amount" type="number" min="0.01" step="0.01" required class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.paymentDate') }}</label>
              <input v-model="contractPaymentForm.payment_date" type="date" class="input" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.method') }}</label>
              <input v-model="contractPaymentForm.method" class="input" :placeholder="$t('ui.transferCash')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.referenceNo') }}</label>
              <input v-model="contractPaymentForm.reference_number" class="input" :placeholder="$t('ui.optional')" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.materials.status') }}</label>
              <select v-model="contractPaymentForm.status" class="input">
                <option value="paid">{{ $t('labels.paid') }}</option>
                <option value="pending">{{ $t('labels.pending') }}</option>
                <option value="cancelled">{{ $t('labels.cancelled') }}</option>
              </select>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('projectInvestment.progress.notes') }}</label>
              <textarea v-model="contractPaymentForm.notes" rows="2" class="input" :placeholder="$t('ui.optionalNotes')"></textarea>
            </div>
            <div v-if="contractPaymentError" class="md:col-span-3 bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ $ui(contractPaymentError) }}</div>
            <div class="md:col-span-3 flex justify-end gap-3">
              <button type="button" @click="closeContractPaymentModal" class="btn-secondary">{{ $t('ui.close') }}</button>
              <button type="submit" :disabled="contractPaymentSaving" class="btn-primary">
                {{ contractPaymentSaving ? $t('sales.saving') : $t('ui.savePayment') }}
              </button>
            </div>
          </form>
        </div>

        <div v-if="contractPaymentsLoading" class="text-center py-4 text-gray-500">{{ $t('ui.loadingPayments') }}</div>
        <table v-else-if="contractPayments.length" class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left">{{ $t('sales.date') }}</th>
              <th class="px-4 py-2 text-left">{{ $t('ui.type') }}</th>
              <th class="px-4 py-2 text-left">{{ $t('ui.method') }}</th>
              <th class="px-4 py-2 text-left">{{ $t('ui.reference') }}</th>
              <th class="px-4 py-2 text-right">{{ $t('ui.amount') }}</th>
              <th class="px-4 py-2 text-right">{{ $t('projectInvestment.materials.status') }}</th>
              <th class="px-4 py-2 text-right">{{ $t('sales.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="payment in contractPayments" :key="payment.id">
              <td class="px-4 py-2">{{ payment.payment_date || '-' }}</td>
              <td class="px-4 py-2 capitalize">{{ $label(payment.payment_type) }}</td>
              <td class="px-4 py-2">{{ payment.method || '-' }}</td>
              <td class="px-4 py-2">{{ payment.reference_number || '-' }}</td>
              <td class="px-4 py-2 text-right font-semibold">Rp {{ formatCurrency(payment.amount || 0) }}</td>
              <td class="px-4 py-2 text-right">
                <span :class="payment.status === 'paid' ? 'text-emerald-600' : payment.status === 'pending' ? 'text-yellow-600' : 'text-gray-400'">
                  {{ $label(payment.status) }}
                </span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <button @click="editContractPayment(payment)" class="text-blue-600 hover:text-blue-900">{{ $t('common.edit') }}</button>
                <button @click="deleteContractPayment(payment)" class="text-red-600 hover:text-red-900">{{ $t('common.delete') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-else class="text-center text-gray-500 py-4">{{ $t('ui.noPaymentsRecorded') }}</p>
      </div>
    </div>

    <!-- Return/Replace Modal -->
    <div v-if="showReturnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">{{ returnType === 'return' ? $t('ui.markReturned') : $t('ui.replaceItem') }}</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('ui.itemCondition') }}</label>
            <select v-model="returnCondition" class="input">
              <option value="">{{ $t('ui.selectCondition') }}</option>
              <option value="repaired">{{ $t('ui.repairedFixed') }}</option>
              <option value="partial">{{ $t('ui.partiallyFixed') }}</option>
              <option value="unrepairable">{{ $t('ui.unrepairable') }}</option>
              <option value="discarded">{{ $t('ui.discarded') }}</option>
            </select>
          </div>
          
          <div v-if="returnType === 'replace'" class="space-y-3">
            <div><label class="block mb-1 text-sm">{{ $t('ui.returnedSerialNumbersOnePerLine') }}</label><textarea v-model="oldReturnSerials" rows="3" class="input"></textarea></div>
            <div><label class="block mb-1 text-sm">{{ $t('ui.replacementSerialNumbersOnePerLine') }}</label><textarea v-model="newReturnSerials" rows="3" class="input"></textarea></div>
          </div>
          <p v-if="returnType === 'replace'" class="text-sm text-orange-600 bg-orange-50 p-3 rounded-lg">
            {{ $t('ui.thisWillDeduct') }} {{ selectedMSA?.quantity }} {{ $t('ui.unitSFromStockForReplacement') }}
          </p>

          <div class="flex justify-end space-x-3 pt-4 border-t">
            <button @click="showReturnModal = false" class="btn-secondary">{{ $t('common.cancel') }}</button>
            <button @click="confirmReturn" :disabled="!returnCondition" class="btn-primary">
              {{ returnType === 'return' ? $t('ui.confirmReturn') : $t('ui.confirmReplacement') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { uiText, uiLocale } from '../../utils/uiText';

import { ref, computed, onMounted } from 'vue';
import api from '../../services/api';

const msas = ref({ data: [] });
const msaContracts = ref({ data: [] });
const products = ref([]);
const projects = ref([]);
const loading = ref(true);
const loadingContracts = ref(false);
const activeTab = ref('records');

// Computed: filter only invest-type projects
const investProjects = computed(() => {
  return projects.value.filter(p => p.type === 'invest' || !p.type);
});
const showModal = ref(false);
const showContractModal = ref(false);
const showContractPaymentModal = ref(false);
const showReturnModal = ref(false);
const selectedMSA = ref(null);
const returnType = ref('');
const returnCondition = ref('');
const saving = ref(false);
const error = ref('');
const savingContract = ref(false);
const contractError = ref('');
const editingContractId = ref(null);
const paymentContract = ref(null);
const contractPayments = ref([]);
const contractPaymentsLoading = ref(false);
const contractPaymentSaving = ref(false);
const contractPaymentError = ref('');
const editingContractPaymentId = ref(null);

const filters = ref({ search: '', status: '' });
const contractFilters = ref({ search: '', status: '' });

const form = ref({
  product_id: '',
  project_investment_id: '',
  quantity: 1,
  issue_type: 'defective',
  issue_description: '',
  reported_date: new Date().toISOString().split('T')[0],
});

const contractForm = ref({
  project_investment_id: '',
  msa_code: '',
  start_date: '',
  end_date: '',
  sharing_profit_rate: null,
  status: 'active',
  notes: ''
});

const contractPaymentForm = ref({
  payment_type: 'dp',
  amount: null,
  payment_date: new Date().toISOString().split('T')[0],
  method: '',
  status: 'paid',
  reference_number: '',
  notes: ''
});

const getStatusBadge = (status) => {
  const badges = {
    pending: 'badge-warning',
    in_repair: 'badge-info',
    returned: 'badge-success',
    replaced: 'badge-primary',
    closed: 'badge-secondary',
  };
  return badges[status] || 'badge';
};

const getIssueBadge = (issue) => {
  const badges = {
    damaged: 'badge-danger',
    defective: 'badge-warning',
    malfunction: 'badge-info',
    other: 'badge-secondary',
  };
  return badges[issue] || 'badge';
};

const loadMSAs = async (page = 1) => {
  loading.value = true;
  try {
    const response = await api.get('/msa-projects', { params: { page, ...filters.value } });
    msas.value = response.data;
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

const loadProjects = async () => {
  try {
    const response = await api.get('/project-investments', { params: { per_page: 1000 } });
    projects.value = response.data.data || [];
  } catch (err) {
    console.error(err);
  }
};

const loadContracts = async (page = 1) => {
  loadingContracts.value = true;
  try {
    const response = await api.get('/msa-contracts', { params: { page, ...contractFilters.value } });
    msaContracts.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loadingContracts.value = false;
  }
};

const resetForm = () => {
  form.value = {
    product_id: '',
    project_investment_id: '',
    quantity: 1,
    issue_type: 'defective',
    issue_description: '',
    reported_date: new Date().toISOString().split('T')[0],
  };
  error.value = '';
};

const resetContractForm = () => {
  contractForm.value = {
    project_investment_id: '',
    msa_code: '',
    start_date: '',
    end_date: '',
    sharing_profit_rate: null,
    status: 'active',
    notes: ''
  };
  editingContractId.value = null;
  contractError.value = '';
};

const saveMSA = async () => {
  saving.value = true;
  error.value = '';
  
  try {
    await api.post('/msa-projects', form.value);
    showModal.value = false;
    loadMSAs();
  } catch (err) {
    error.value = err.response?.data?.message || uiText("Gagal menyimpan MSA");
  } finally {
    saving.value = false;
  }
};

const saveContract = async () => {
  savingContract.value = true;
  contractError.value = '';

  try {
    if (editingContractId.value) {
      await api.put(`/msa-contracts/${editingContractId.value}`, contractForm.value);
    } else {
      await api.post('/msa-contracts', contractForm.value);
    }
    showContractModal.value = false;
    resetContractForm();
    loadContracts();
  } catch (err) {
    contractError.value = err.response?.data?.message || uiText("Gagal menyimpan kontrak");
  } finally {
    savingContract.value = false;
  }
};

const editContract = (contract) => {
  editingContractId.value = contract.id;
  contractForm.value = {
    project_investment_id: contract.project_investment_id || '',
    msa_code: contract.msa_code || '',
    start_date: contract.start_date || '',
    end_date: contract.end_date || '',
    sharing_profit_rate: contract.sharing_profit_rate ?? null,
    status: contract.status || 'active',
    notes: contract.notes || ''
  };
  showContractModal.value = true;
};

const deleteContract = async (contract) => {
  if (!confirm(uiText("Hapus kontrak MSA ini?"))) return;
  try {
    await api.delete(`/msa-contracts/${contract.id}`);
    loadContracts();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal menghapus kontrak"));
  }
};

const resetContractPaymentForm = () => {
  contractPaymentForm.value = {
    payment_type: 'dp',
    amount: null,
    payment_date: new Date().toISOString().split('T')[0],
    method: '',
    status: 'paid',
    reference_number: '',
    notes: ''
  };
  contractPaymentError.value = '';
  editingContractPaymentId.value = null;
};

const loadContractPayments = async (contractId) => {
  if (!contractId) {
    contractPayments.value = [];
    return;
  }
  contractPaymentsLoading.value = true;
  try {
    const response = await api.get('/payments', {
      params: { payable_type: 'msa_contract', payable_id: contractId, per_page: 100 }
    });
    contractPayments.value = response.data.data || response.data || [];
  } catch (err) {
    console.error(err);
    contractPayments.value = [];
  } finally {
    contractPaymentsLoading.value = false;
  }
};

const openContractPaymentModal = async (contract) => {
  paymentContract.value = contract;
  showContractPaymentModal.value = true;
  resetContractPaymentForm();
  await loadContractPayments(contract.id);
};

const closeContractPaymentModal = () => {
  showContractPaymentModal.value = false;
  paymentContract.value = null;
  contractPayments.value = [];
  resetContractPaymentForm();
};

const submitContractPayment = async () => {
  if (!paymentContract.value) return;
  contractPaymentError.value = '';

  const amount = Number(contractPaymentForm.value.amount || 0);
  if (amount <= 0) {
    contractPaymentError.value = uiText("Nominal harus lebih besar dari 0.");
    return;
  }

  contractPaymentSaving.value = true;
  try {
    const payload = {
      payment_type: contractPaymentForm.value.payment_type,
      amount: amount,
      payment_date: contractPaymentForm.value.payment_date,
      method: contractPaymentForm.value.method,
      status: contractPaymentForm.value.status,
      reference_number: contractPaymentForm.value.reference_number,
      notes: contractPaymentForm.value.notes
    };

    if (editingContractPaymentId.value) {
      await api.put(`/payments/${editingContractPaymentId.value}`, payload);
    } else {
      await api.post('/payments', {
        payable_type: 'msa_contract',
        payable_id: paymentContract.value.id,
        ...payload
      });
    }
    await loadContractPayments(paymentContract.value.id);
    resetContractPaymentForm();
  } catch (err) {
    contractPaymentError.value = err.response?.data?.message || uiText("Gagal menyimpan pembayaran");
  } finally {
    contractPaymentSaving.value = false;
  }
};

const editContractPayment = (payment) => {
  editingContractPaymentId.value = payment.id;
  contractPaymentForm.value = {
    payment_type: payment.payment_type || 'dp',
    amount: Number(payment.amount || 0),
    payment_date: payment.payment_date || new Date().toISOString().split('T')[0],
    method: payment.method || '',
    status: payment.status || 'paid',
    reference_number: payment.reference_number || '',
    notes: payment.notes || ''
  };
  contractPaymentError.value = '';
};

const deleteContractPayment = async (payment) => {
  if (!confirm(uiText("Hapus pembayaran ini?"))) return;
  try {
    await api.delete(`/payments/${payment.id}`);
    await loadContractPayments(paymentContract.value?.id);
    if (editingContractPaymentId.value === payment.id) {
      resetContractPaymentForm();
    }
  } catch (err) {
    contractPaymentError.value = err.response?.data?.message || uiText("Gagal menghapus pembayaran");
  }
};

const formatCurrency = (value) => {
  const num = Number(value || 0);
  return new Intl.NumberFormat(uiLocale(), { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
};

const startRepair = async (msa) => {
  try {
    await api.post(`/msa-projects/${msa.id}/start-repair`);
    loadMSAs();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal memulai perbaikan"));
  }
};

const oldReturnSerials = ref('');
const newReturnSerials = ref('');
const openReturnModal = (msa, type) => {
  selectedMSA.value = msa;
  returnType.value = type;
  returnCondition.value = '';
  oldReturnSerials.value = (msa.serial_numbers || []).join('\n');
  newReturnSerials.value = '';
  showReturnModal.value = true;
};

const confirmReturn = async () => {
  try {
    const endpoint = returnType.value === 'return' ? 'mark-returned' : 'replace';
    await api.post(`/msa-projects/${selectedMSA.value.id}/${endpoint}`, {
      condition: returnCondition.value,
      ...(returnType.value === 'replace' ? {
        serial_numbers: oldReturnSerials.value.split(/[\n,]+/).map(sn => sn.trim()).filter(Boolean),
        replacement_serial_numbers: newReturnSerials.value.split(/[\n,]+/).map(sn => sn.trim()).filter(Boolean),
      } : {}),
    });
    showReturnModal.value = false;
    loadMSAs();
  } catch (err) {
    alert(err.response?.data?.message || uiText("Gagal memproses"));
  }
};

const closeMSA = async (msa) => {
  try {
    await api.post(`/msa-projects/${msa.id}/close`);
    loadMSAs();
  } catch (err) {
    alert(uiText("Gagal menutup MSA"));
  }
};

const deleteMSA = async (msa) => {
  if (!confirm(uiText("Hapus catatan MSA ini?"))) return;
  try {
    await api.delete(`/msa-projects/${msa.id}`);
    loadMSAs();
  } catch (err) {
    alert(uiText("Gagal menghapus MSA"));
  }
};

onMounted(() => {
  loadMSAs();
  loadProducts();
  loadProjects();
});
</script>
