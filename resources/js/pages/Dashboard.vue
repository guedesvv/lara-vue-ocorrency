<script setup lang="ts">
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from "chart.js"
import ChartDataLabels from "chartjs-plugin-datalabels"
import { Bar } from "vue-chartjs"
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Funnel, Filter } from 'lucide-vue-next' // ícones bonitos (opcional se usa shadcn ou lucide)

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ChartDataLabels)

// ===== PROPS =====
const props = defineProps<{
  statusCounts?: Record<string, number>,
  crCounts?: Record<string, number>,
  originCounts?: Record<string, number>,
  items?: Array<{ cr: string; origin: string; status: string; count?: number }>
}>()

const statusCounts = props.statusCounts ?? {}
const crCounts = props.crCounts ?? {}
const originCounts = props.originCounts ?? {}
const items = props.items ?? []

// ===== MAP CR → NOME =====
const crMap: Record<string, string> = {
  "16749": "BRASMETAL", "17542": "SAMARCO", "17543": "SAMARCO", "24178": "SAMARCO",
  "24238": "BRASKEM", "25458": "LOCALFRIO", "26052": "CHEVRON", "27911": "BRASKEM",
  "32470": "BASF", "35118": "USIMINAS", "35119": "USIMINAS", "35122": "USIMINAS",
  "35132": "USIMINAS", "35180": "USIMINAS", "40703": "FLEURY", "43409": "BASF",
  "44242": "BRASMETAL", "44914": "BRASKEM", "45955": "BASF", "47287": "INDIRETOS",
  "51115": "ISA ENERGIA", "56115": "SAMARCO", "58492": "FLEURY", "62338": "ANGLO",
  "68807": "USIMINAS", "68820": "INDIRETOS", "75999": "INDIRETOS", "76047": "INDIRETOS",
  "76357": "USIMINAS", "77943": "BRASMETAL", "79012": "ATLAS COPCO", "82840": "INDIRETOS",
}

// ===== FILTROS =====
const selectedCr = ref<string>('__ALL__')
const selectedOrigin = ref<string>('__ALL__')
const selectedStatus = ref<string>('__ALL__')

// ===== FUNÇÕES AUX =====
function passesFilter(it: { cr: string; origin: string; status: string }) {
  const crNumber = it.cr?.substring(0, 5) ?? it.cr
  const crName = crMap[crNumber] ?? crNumber ?? ''
  const crOk = selectedCr.value === '__ALL__' || crName === selectedCr.value
  const originOk = selectedOrigin.value === '__ALL__' || it.origin === selectedOrigin.value
  const statusOk = selectedStatus.value === '__ALL__' || it.status === selectedStatus.value
  return crOk && originOk && statusOk
}

function aggregate<T extends string>(arr: Array<Record<string, any>>, key: T, mapKey?: (v: string) => string) {
  const out: Record<string, number> = {}
  for (const it of arr) {
    const raw = String(it[key] ?? '')
    const label = mapKey ? mapKey(raw) : raw
    if (!label) continue
    const qty = it.count ?? 1
    out[label] = (out[label] || 0) + qty
  }
  return out
}

function makeOptions(title: string) {
  return {
    indexAxis: "y" as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      datalabels: {
        anchor: "center",
        align: "center",
        color: "#fff",
        font: { weight: "bold", size: 12 },
        formatter: (value: number) => value,
      },
      title: {
        display: true,
        text: title,
        font: { size: 16, weight: "bold" },
        color: "#111827",
      },
    },
    scales: {
      x: { display: false, grid: { display: false } },
      y: { ticks: { color: "#111827" }, grid: { display: false } },
    },
  }
}
function toDataset(values: number[], color = "#004FA3") {
  return [{ data: values, backgroundColor: color, borderRadius: 6 }]
}

// ===== REATIVIDADE GERAL =====
const filteredItems = computed(() => items.filter(passesFilter))

// opções dos selects se ajustam dinamicamente com base nos itens filtrados
const crOptions = computed(() => {
  const base = selectedOrigin.value !== '__ALL__' || selectedStatus.value !== '__ALL__' ? filteredItems.value : items
  const set = new Set<string>()
  for (const it of base) {
    const crNum = it.cr?.substring(0, 5) ?? it.cr
    set.add(crMap[crNum] ?? crNum ?? '')
  }
  return Array.from(set).filter(Boolean).sort()
})
const originOptions = computed(() => {
  const base = selectedCr.value !== '__ALL__' || selectedStatus.value !== '__ALL__' ? filteredItems.value : items
  return Array.from(new Set(base.map(i => i.origin))).filter(Boolean).sort()
})
const statusOptions = computed(() => {
  const base = selectedCr.value !== '__ALL__' || selectedOrigin.value !== '__ALL__' ? filteredItems.value : items
  return Array.from(new Set(base.map(i => i.status))).filter(Boolean).sort()
})

// ===== GRÁFICOS =====
const crAgg = computed(() => aggregate(filteredItems.value, 'cr', cr => crMap[cr.substring(0, 5)] ?? cr))
const crData = computed(() => ({ labels: Object.keys(crAgg.value), datasets: toDataset(Object.values(crAgg.value), "#020D45") }))
const crChartOptions = makeOptions("Ocorrências por CR")

const originAgg = computed(() => aggregate(filteredItems.value, 'origin'))
const originData = computed(() => ({ labels: Object.keys(originAgg.value), datasets: toDataset(Object.values(originAgg.value), "#004FA3") }))
const originChartOptions = makeOptions("Ocorrências por Origem")

const statusAgg = computed(() => aggregate(filteredItems.value, 'status'))
const statusData = computed(() => {
  const labels = Object.keys(statusAgg.value)
  const values = Object.values(statusAgg.value)
  return {
    labels,
    datasets: [{
      data: values,
      backgroundColor: labels.map(lbl => lbl.toLowerCase().includes('final') ? "#025C12" : "#AD000F"),
      borderRadius: 6,
    }]
  }
})
const statusChartOptions = makeOptions("Status das ocorrências")
</script>

<template>

  <Head title="Dashboard" />
  <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
    <div class="p-4 space-y-6">

      <!-- 🔹 FILTROS SEPARADOS E ESTILIZADOS -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Filtro CR -->
        <div class="flex flex-col bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 
           border border-blue-200 dark:border-gray-700 rounded-xl p-4 shadow-md transition-all hover:shadow-lg">
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <Filter class="w-4 h-4 text-blue-600 dark:text-blue-400" /> CR
          </label>
          <select v-model="selectedCr" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 
             text-gray-800 dark:text-gray-100 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 transition-all">
            <option value="__ALL__">Todos</option>
            <option v-for="cr in crOptions" :key="cr" :value="cr">{{ cr }}</option>
          </select>
        </div>

        <!-- Filtro Origem -->
        <div class="flex flex-col bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 
           border border-blue-200 dark:border-gray-700 rounded-xl p-4 shadow-md transition-all hover:shadow-lg">
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <Filter class="w-4 h-4 text-blue-600 dark:text-blue-400" /> Origem
          </label>
          <select v-model="selectedOrigin" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 
             text-gray-800 dark:text-gray-100 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 transition-all">
            <option value="__ALL__">Todas</option>
            <option v-for="o in originOptions" :key="o" :value="o">{{ o }}</option>
          </select>
        </div>

        <!-- Filtro Status -->
        <div class="flex flex-col bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 
           border border-blue-200 dark:border-gray-700 rounded-xl p-4 shadow-md transition-all hover:shadow-lg">
          <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
            <Filter class="w-4 h-4 text-blue-600 dark:text-blue-400" /> Status
          </label>
          <select v-model="selectedStatus" class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 
             text-gray-800 dark:text-gray-100 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 transition-all">
            <option value="__ALL__">Todos</option>
            <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

      </div>

      <!-- 🔹 GRÁFICOS AJUSTADOS À TELA -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-[calc(100vh-260px)]">
        <div class="border rounded-xl bg-white dark:bg-gray-900 p-4 shadow-lg">
          <Bar :data="crData" :options="crChartOptions" />
        </div>
        <div class="border rounded-xl bg-white dark:bg-gray-900 p-4 shadow-lg">
          <Bar :data="originData" :options="originChartOptions" />
        </div>
        <div class="border rounded-xl bg-white dark:bg-gray-900 p-4 shadow-lg">
          <Bar :data="statusData" :options="statusChartOptions" />
        </div>
      </div>

    </div>
  </AppLayout>
</template>
