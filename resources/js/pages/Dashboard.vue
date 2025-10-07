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

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ChartDataLabels)

// Props vindas do Laravel
const props = defineProps<{
    statusCounts?: Record<string, number>,
    crCounts?: Record<string, number>,
    originCounts?: Record<string, number>,
}>()

// Fallbacks
const statusCounts = props.statusCounts ?? {}
const crCounts = props.crCounts ?? {}
const originCounts = props.originCounts ?? {}

// Mapeamento CR → Nome
const crMap: Record<string, string> = {
  "16749": "BRASMETAL",
  "17542": "SAMARCO",
  "17543": "SAMARCO",
  "24178": "SAMARCO",
  "24238": "BRASKEM",
  "25458": "LOCALFRIO",
  "26052": "CHEVRON",
  "27911": "BRASKEM",
  "32470": "BASF",
  "35118": "USIMINAS",
  "35119": "USIMINAS",
  "35122": "USIMINAS",
  "35132": "USIMINAS",
  "35180": "USIMINAS",
  "40703": "FLEURY",
  "43409": "BASF",
  "44242": "BRASMETAL",
  "44914": "BRASKEM",
  "45955": "BASF",
  "47287": "INDIRETOS",
  "51115": "ISA ENERGIA",
  "56115": "SAMARCO",
  "58492": "FLEURY",
  "62338": "ANGLO",
  "68807": "USIMINAS",
  "68820": "INDIRETOS",
  "75999": "INDIRETOS",
  "76047": "INDIRETOS",
  "76357": "USIMINAS",
  "77943": "BRASMETAL",
  "79012": "ATLAS COPCO",
  "82840": "INDIRETOS",
}

// === Gráfico 1: Ocorrências por CR (agrupado) ===
const groupedCrCounts: Record<string, number> = {}
Object.entries(crCounts).forEach(([cr, value]) => {
  const crNumber = cr.substring(0, 5)
  const name = crMap[crNumber] ?? crNumber
  groupedCrCounts[name] = (groupedCrCounts[name] || 0) + value
})

const crData = {
  labels: Object.keys(groupedCrCounts),
  datasets: [
    {
      data: Object.values(groupedCrCounts),
      backgroundColor: "#020D45",
      borderRadius: 6,
    },
  ],
}
const crOptions = {
  indexAxis: "y",
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
      text: "Ocorrências por CR",
      font: { size: 16, weight: "bold" },
      color: "#111827",
    },
  },
  scales: {
    x: { display: false, grid: { display: false } }, // remove números e linhas
    y: { ticks: { color: "#111827" }, grid: { display: false } }, // mantém nomes, tira linhas
  },
}

// === Gráfico 2: Por Origem ===
const originData = {
  labels: Object.keys(originCounts),
  datasets: [
    {
      data: Object.values(originCounts),
      backgroundColor: "#004FA3",
      borderRadius: 6,
    },
  ],
}
const originOptions = {
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
      text: "Ocorrências por Origem",
      font: { size: 16, weight: "bold" },
      color: "#111827",
    },
  },
  scales: {
    x: { grid: { display: false } }, // remove linhas, mantém nomes
    y: { grid: { display: false } }, // remove linhas, mantém nomes
  },
}

// === Gráfico 3: Status ===
const statusData = {
  labels: Object.keys(statusCounts),
  datasets: [
    {
      data: Object.values(statusCounts),
      backgroundColor: [
        "#AD000F", // Atrasado
        "#AD000F", // Pendente
        "#AD000F", // Pendente Aprovação
        "#025C12", // Finalizado
        "#AD000F", // Recusado
      ],
      borderRadius: 6,
    },
  ],
}
const statusOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    datalabels: {
      anchor: "center",
      align: "center",
      color: "#fff",
      font: { weight: "bold", size: 14 },
      formatter: (value: number) => value,
    },
    title: {
      display: true,
      text: "Status das ocorrências",
      font: { size: 16, weight: "bold" },
      color: "#111827",
    },
  },
  scales: {
    x: { grid: { display: false } }, // remove linhas, mantém nomes
    y: { grid: { display: false } }, // remove linhas, mantém nomes
  },
}
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
    <div class="grid grid-cols-3 gap-4 p-4">
      
      <!-- Bloco 1 - Ocorrências por CR -->
      <div class="col-span-1 h-[800px] border rounded-xl bg-white dark:bg-gray-900 p-4 shadow-lg">
        <Bar v-if="Object.keys(crCounts).length" :data="crData" :options="crOptions" />
        <p v-else class="text-gray-500">Sem dados de CR</p>
      </div>

      <!-- Bloco 2 e 3 -->
      <div class="col-span-2 flex flex-col gap-4">
        <!-- Origem -->
        <div class="h-[300px] border rounded-xl bg-white dark:bg-gray-900 p-4 shadow-lg">
          <Bar v-if="Object.keys(originCounts).length" :data="originData" :options="originOptions" />
          <p v-else class="text-gray-500">Sem dados de Origem</p>
        </div>

        <!-- Status -->
        <div class="h-[500px] border rounded-xl bg-white dark:bg-gray-900 p-6 shadow-lg">
          <Bar v-if="Object.keys(statusCounts).length" :data="statusData" :options="statusOptions" />
          <p v-else class="text-gray-500">Sem dados de Status</p>
        </div>
      </div>

    </div>
  </AppLayout>
</template>
