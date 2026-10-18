<template>
  <div class="flex flex-col">
    <h2 class="text-3xl font-bold text-center">{{ $t('ui.modifyFirmware') }}</h2>
    <p class="text-gray-800 mb-3 text-center">{{ $t('ui.modifyFirmwareBasedOnRequirement') }}</p>
      <p class="text-center">
        {{ $t('ui.weCanModifyOurGponFirmwareBasedOnCustomerRequirements') }}
      </p>

    <main>
      <section class="cards text-gray-300 p-2">
        <article class="card h-[280px] bg-gray-900 transform transition duration-500 hover:scale-125 hidden-none z-10">
          <h2>{{ $t('ui.originalFirmware') }}</h2>
          <p>{{ $t('ui.noDedicatedTr069StatusPageOnOntWebGui') }}</p>
          <img :src="firmwareOriImg" alt=""/>
        </article>

        <article class="card h-[280px] bg-gray-900 transform transition duration-500 hover:scale-125 hidden-none z-5">
          <h2>{{ $t('ui.modifiedFirmware') }}</h2>
          <p>{{ $t('ui.addedPageToViewTr069StatusPageOnOntWebGui') }}</p>
          <img :src="firmwareCustomImg" alt=""/>
        </article>
      </section>

      <section class="compare">
        <h3>{{ $t('ui.quickCompare') }}</h3>
        <div class="table-wrap">
          <table class="compare-table">
            <thead>
              <tr>
                <th>{{ $t('labels.features') }}</th>
                <th>{{ $t('ui.original') }}</th>
                <th>{{ $t('ui.modified') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>{{ $t('ui.addedPageToViewTr069Status') }}</td>
                <td><span class="chip danger">{{ $t('ui.notAvailable') }}</span></td>
                <td><span class="chip success">{{ $t('dashboard.inStock') }}</span></td>
              </tr>
              <tr>
                <td>{{ $t('ui.functionToViewConnectionStatusOfTr069') }}</td>
                <td><span class="chip danger">{{ $t('ui.notAvailable') }}</span></td>
                <td><span class="chip success">{{ $t('dashboard.inStock') }}</span></td>
              </tr>
              <tr>
                <td>{{ $t('ui.functionToViewIpAddressOfTr069Interface') }}</td>
                <td><span class="chip danger">{{ $t('ui.notAvailable') }}</span></td>
                <td><span class="chip success">{{ $t('dashboard.inStock') }}</span></td>
              </tr>
              <tr>
                <td>{{ $t('ui.functionToRefreshConnectionStatusOfTr069AndIpAddressOfTr069') }}</td>
                <td><span class="chip danger">{{ $t('ui.notAvailable') }}</span></td>
                <td><span class="chip success">{{ $t('dashboard.inStock') }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
// Import images from public folder
const firmwareOriImg = '/assets/static/solutions/firmware-ori.png'
const firmwareCustomImg = '/assets/static/solutions/firmware-custom.png'
</script>

<style scoped>
.hero {
  text-align: center;
}
.hero h1 {
  font-size: clamp(2rem, 3.5vw + 1rem, 3.5rem);
  letter-spacing: 0.1em;
  background: linear-gradient(90deg, var(--accent), var(--success));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin: 0;
}

main { width: 100%; max-width: 1100px; margin: 0 auto; padding: 1rem; }

.cards {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 56px;
}
@media (min-width: 900px) {
  .cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

.card {
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 1.25rem 1.25rem 1rem;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
  backdrop-filter: blur(6px);
}
.card h2 { margin: 0 0 0.25rem 0; font-size: 1.25rem; }
.card p { margin: 0 0 0.5rem 0; color: var(--muted); }
.card.success { border-color: rgba(52,211,153,0.35); box-shadow: 0 10px 30px rgba(52,211,153,0.15); }

.bullet-list { margin: 0.5rem 0 0; padding-left: 1.1rem; }
.bullet-list li { margin: 0.25rem 0; }

.compare { margin-top: 1.25rem; }
.compare h3 { margin: 0 0 0.75rem 0; }
.table-wrap { overflow-x: auto; background: #fff; border-radius: 12px;}
.compare-table {
  width: 100%;
  border-collapse: collapse;
  background: rgba(8, 8, 8, 0.02);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 12px;
}
.compare-table th, .compare-table td {
  padding: 0.75rem;
  border-bottom: 1px solid rgb(14, 14, 14);
  text-align: left;
}
.compare-table th { color: var(--muted); font-weight: 600; border: #0b1220;}
.compare-table tr:last-child td { border-bottom: none; }

.chip {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  font-size: 0.875rem;
  border: 1px solid currentColor;
}
.chip.success { color: var(--success); background: rgba(52,211,153,0.08); }
.chip.danger { color: var(--danger); background: rgba(251,113,133,0.08); }
</style>
