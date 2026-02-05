<template>
  <div class="flex flex-col">
    <h2 class="text-3xl font-bold text-center">Modify Firmware</h2>
    <p class="text-gray-800 mb-3 text-center">Modify Firmware Based on Requirement</p>
      <p class="text-center">
        We can modify our GPON Firmware based on customer requirements
      </p>

    <main>
      <section class="cards text-gray-300 p-2">
        <article class="card h-[280px] bg-gray-900 transform transition duration-500 hover:scale-125 hidden-none z-10">
          <h2>Original Firmware</h2>
          <p>No dedicated TR-069 status page on ONT Web GUI</p>
          <img :src="firmwareOriImg" alt=""/>
        </article>

        <article class="card h-[280px] bg-gray-900 transform transition duration-500 hover:scale-125 hidden-none z-5">
          <h2>Modified Firmware</h2>
          <p>Added page to view TR-069 status page on ONT Web GUI</p>
          <img :src="firmwareCustomImg" alt=""/>
        </article>
      </section>

      <section class="compare">
        <h3>Quick Compare</h3>
        <div class="table-wrap">
          <table class="compare-table">
            <thead>
              <tr>
                <th>Feature</th>
                <th>Original</th>
                <th>Modified</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Added page to view TR-069 Status</td>
                <td><span class="chip danger">Not Available</span></td>
                <td><span class="chip success">Available</span></td>
              </tr>
              <tr>
                <td>Function to view Connection Status of TR-069</td>
                <td><span class="chip danger">Not Available</span></td>
                <td><span class="chip success">Available</span></td>
              </tr>
              <tr>
                <td>Function to view IP Address of TR-069 Interface</td>
                <td><span class="chip danger">Not Available</span></td>
                <td><span class="chip success">Available</span></td>
              </tr>
              <tr>
                <td>Function to Refresh Connection Status of TR-069 and IP Address of TR-069 Interface</td>
                <td><span class="chip danger">Not Available</span></td>
                <td><span class="chip success">Available</span></td>
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
