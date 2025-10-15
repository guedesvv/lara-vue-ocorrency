<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Filter, AlertTriangle, Clock, Hourglass, CheckCircle2, XCircle } from 'lucide-vue-next'

/** ================== TIPOS ================== */
interface Item { cr: string; origin: string; status?: string; count?: number; dueDate?: string | Date; pdf_path?: string; confirmEvidency?: string }
interface Product { id: number; cr: string; origin: string; startDate: Date; dueDate: Date; pdf_path?: string; confirmEvidency?: string; email: string; emailCreator?: string; nameCreator?: string }

const props = defineProps<{ items?: Item[]; products?: Product[] }>()
const items = props.items ?? []
const products = props.products ?? []

/** ================== MAP CR → NOME ================== */
const crMap: Record<string, string> = {
  '16749': 'BRASMETAL', '17542': 'SAMARCO', '17543': 'SAMARCO', '24178': 'SAMARCO',
  '24238': 'BRASKEM', '25458': 'LOCALFRIO', '26052': 'CHEVRON', '27911': 'BRASKEM',
  '32470': 'BASF', '35118': 'USIMINAS', '35119': 'USIMINAS', '35122': 'USIMINAS',
  '35132': 'USIMINAS', '35180': 'USIMINAS', '40703': 'FLEURY', '43409': 'BASF',
  '44242': 'BRASMETAL', '44914': 'BRASKEM', '45955': 'BASF', '47287': 'INDIRETOS',
  '51115': 'ISA ENERGIA', '56115': 'SAMARCO', '58492': 'FLEURY', '62338': 'ANGLO',
  '68807': 'USIMINAS', '68820': 'INDIRETOS', '75999': 'INDIRETOS', '76047': 'INDIRETOS',
  '76357': 'USIMINAS', '77943': 'BRASMETAL', '79012': 'ATLAS COPCO', '82840': 'INDIRETOS'
}
const crLabelFrom = (cr: string) => crMap[cr?.substring(0, 5)] ?? cr?.substring(0, 5) ?? cr ?? ''

/** ================== CORES / ORDEM DE STATUS (igual index.vue) ================== */
const statusKeys = ['Atrasado', 'Pendente', 'Pendente Aprovação', 'Finalizado', 'Evidência Recusada'] as const
 type StatusKey = typeof statusKeys[number]
const statusColor = {
  Atrasado: { hex: '#AD000F' },
  Pendente: { hex: '#C48A00' },
  'Pendente Aprovação': { hex: '#004FA3' },
  Finalizado: { hex: '#025C12' },
  'Evidência Recusada': { hex: '#D97706' }
} as const
const pickStatusColor = (s: string) => (statusColor as any)[s]?.hex ?? '#6B7280'

/** ================== FUNÇÕES COMPARTILHADAS (igual index.vue) ================== */
function nowBrazil() {
  const now = new Date()
  const utc = now.getTime() + now.getTimezoneOffset() * 60000
  return new Date(utc - 3 * 60 * 60000)
}

function getStatusesGeneric(obj: Partial<Item & Product>): StatusKey[] {
  const statuses: StatusKey[] = []
  const dueDate = obj.dueDate ? new Date(obj.dueDate) : undefined
  const pdf = obj.pdf_path
  const confirm = obj.confirmEvidency
  const hasMeta = Boolean(dueDate || pdf || confirm)

  // Finalizado é exclusivo (mesma regra da index.vue)
  if (pdf && confirm === 'Aprovado') return ['Finalizado']

  if (hasMeta) {
    // Pode acumular com outros (ex.: Atrasado + Evidência Recusada)
    if (dueDate && dueDate < nowBrazil()) statuses.push('Atrasado')

    if (pdf && confirm === 'Reprovado') {
      statuses.push('Evidência Recusada')
    } else if (pdf && !confirm) {
      statuses.push('Pendente Aprovação')
    } else if (!pdf) {
      statuses.push('Pendente')
    }
    return statuses
  }

  // Sem metadados: aceita múltiplos no campo `status`
  const raw = String(obj.status ?? '')
  const parts = raw.split(/[|,;/]+/).map(s => s.trim()).filter(Boolean)
  const valid = parts.filter((s): s is StatusKey => statusKeys.includes(s as StatusKey))

  // Finalizado continua exclusivo
  if (valid.includes('Finalizado')) return ['Finalizado']

  // remove duplicatas e retorna
  return Array.from(new Set(valid))
}

/** ================== FONTE, FILTROS E LINHAS BASE ================== */
const selectedCr = ref<string>('__ALL__')
const selectedStatus = ref<string>('__ALL__')

// Normaliza fonte em linhas genéricas com peso (count)
const sourceRows = computed(() => {
  if (products.length) {
    return products.map(p => ({
      crLabel: crLabelFrom(p.cr),
      origin: p.origin,
      weight: 1,
      ...p
    }))
  }
  return items.map(i => ({
    crLabel: crLabelFrom(i.cr),
    origin: i.origin,
    weight: i.count ?? 1,
    ...i
  }))
})

const crOptions = computed<string[]>(() => Array.from(new Set(sourceRows.value.map(r => r.crLabel))).filter(Boolean).sort())

const filteredRows = computed(() => sourceRows.value.filter(r => {
  const byCr = selectedCr.value === '__ALL__' || r.crLabel === selectedCr.value
  const byStatus = selectedStatus.value === '__ALL__' || getStatusesGeneric(r).includes(selectedStatus.value as StatusKey)
  return byCr && byStatus
}))

/** ====== CONTAGEM DE STATUS (multi-rotulagem) ====== */
const statusCounts = computed<Record<StatusKey, number>>(() => {
  const counts = {
    Atrasado: 0,
    Pendente: 0,
    'Pendente Aprovação': 0,
    Finalizado: 0,
    'Evidência Recusada': 0
  } as Record<StatusKey, number>
  for (const r of filteredRows.value) {
    const w = (r as any).weight ?? 1
    for (const s of getStatusesGeneric(r)) {
      counts[s] += w
    }
  }
  return counts
})

function toggleStatus(target: StatusKey) {
  selectedStatus.value = selectedStatus.value === target ? '__ALL__' : target
}

/** ================== AGREGAÇÕES (Top Origem / Top CR) ================== */
function topAgg(getKey: (r: any) => string, n = 5) {
  const map = new Map<string, number>()
  for (const r of filteredRows.value) {
    const k = getKey(r)
    if (!k) continue
    map.set(k, (map.get(k) ?? 0) + (r.weight ?? 1))
  }
  const entries = Array.from(map.entries()).sort((a, b) => b[1] - a[1]).slice(0, n)
  const max = Math.max(1, ...entries.map(([, v]) => v))
  return { entries, max }
}

const originRanking = computed(() => topAgg(r => r.origin, 5))
const crRanking = computed(() => topAgg(r => r.crLabel, 5))

/** ====== BARRAS EM PÉ POR STATUS ====== */
const statusPairs = computed(() => {
  const pairs = statusKeys.map(k => [k, statusCounts.value[k]] as [StatusKey, number])
  const max = Math.max(1, ...pairs.map(([, v]) => v))
  return { pairs, max }
})
</script>

<template>
  <Head title="Estatísticas" />
  <AppLayout :breadcrumbs="[{ title: 'Estatísticas', href: '/dashboard' }]">
    <div class="px-6 py-6 space-y-6 w-full">

      <!-- Linha 1: Filtro CR + Cards (visual padrão da index.vue) -->
      <div class="flex items-stretch gap-4 w-full">
        <!-- Filtro CR -->
        <div class="rounded-2xl border border-blue-200 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 p-4 shadow-sm w-[320px] shrink-0">
          <div class="flex items-center gap-2 mb-2 text-[13px] font-semibold text-gray-700 dark:text-gray-300">
            <Filter class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            CR
          </div>
          <select v-model="selectedCr"
                  class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white/90 dark:bg-gray-800
                         text-gray-800 dark:text-gray-100 text-[14px] px-3 py-2.5 focus:ring-2 focus:ring-blue-500 transition">
            <option value="__ALL__">Todos</option>
            <option v-for="cr in crOptions" :key="cr" :value="cr">{{ cr }}</option>
          </select>
        </div>

        <!-- Cards de status (cores e gradiente iguais à index.vue) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 flex-1">
          <!-- Atrasado -->
          <div role="button" tabindex="0"
               @click="toggleStatus('Atrasado')" @keydown.enter.prevent="toggleStatus('Atrasado')"
               class="stat-card from-red-50 to-white dark:from-[#2a0f10] dark:to-[#0b0b0b] cursor-pointer select-none"
               :class="selectedStatus === 'Atrasado' ? 'stat-card--active ring-2 ring-red-300 dark:ring-red-700 bg-red-50 dark:bg-red-900/10' : ''">
            <div class="flex items-center gap-3">
              <div class="icon-wrap bg-red-100 dark:bg-red-900/40"><AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400"/></div>
              <div>
                <div class="stat-label text-red-700 dark:text-red-300">Atrasado</div>
                <div class="stat-value text-red-800 dark:text-red-200">{{ statusCounts['Atrasado'] }}</div>
              </div>
            </div>
          </div>

          <!-- Pendente -->
          <div role="button" tabindex="0"
               @click="toggleStatus('Pendente')" @keydown.enter.prevent="toggleStatus('Pendente')"
               class="stat-card from-yellow-50 to-white dark:from-[#2a220c] dark:to-[#0b0b0b] cursor-pointer select-none"
               :class="selectedStatus === 'Pendente' ? 'stat-card--active ring-2 ring-yellow-300 dark:ring-yellow-700 bg-yellow-50 dark:bg-yellow-900/10' : ''">
            <div class="flex items-center gap-3">
              <div class="icon-wrap bg-yellow-100 dark:bg-yellow-900/40"><Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400"/></div>
              <div>
                <div class="stat-label text-yellow-700 dark:text-yellow-300">Pendente</div>
                <div class="stat-value text-yellow-800 dark:text-yellow-200">{{ statusCounts['Pendente'] }}</div>
              </div>
            </div>
          </div>

          <!-- Pendente Aprovação -->
          <div role="button" tabindex="0"
               @click="toggleStatus('Pendente Aprovação')" @keydown.enter.prevent="toggleStatus('Pendente Aprovação')"
               class="stat-card from-blue-50 to-white dark:from-[#0e1a2b] dark:to-[#0b0b0b] cursor-pointer select-none"
               :class="selectedStatus === 'Pendente Aprovação' ? 'stat-card--active ring-2 ring-blue-300 dark:ring-blue-700 bg-blue-50 dark:bg-blue-900/10' : ''">
            <div class="flex items-center gap-3">
              <div class="icon-wrap bg-blue-100 dark:bg-blue-900/40"><Hourglass class="w-5 h-5 text-blue-600 dark:text-blue-400"/></div>
              <div>
                <div class="stat-label text-blue-700 dark:text-blue-300">Pendente Aprovação</div>
                <div class="stat-value text-blue-800 dark:text-blue-200">{{ statusCounts['Pendente Aprovação'] }}</div>
              </div>
            </div>
          </div>

          <!-- Finalizado -->
          <div role="button" tabindex="0"
               @click="toggleStatus('Finalizado')" @keydown.enter.prevent="toggleStatus('Finalizado')"
               class="stat-card from-green-50 to-white dark:from-[#0f2417] dark:to-[#0b0b0b] cursor-pointer select-none"
               :class="selectedStatus === 'Finalizado' ? 'stat-card--active ring-2 ring-green-300 dark:ring-green-700 bg-green-50 dark:bg-green-900/10' : ''">
            <div class="flex items-center gap-3">
              <div class="icon-wrap bg-green-100 dark:bg-green-900/40"><CheckCircle2 class="w-5 h-5 text-green-600 dark:text-green-400"/></div>
              <div>
                <div class="stat-label text-green-700 dark:text-green-300">Finalizado</div>
                <div class="stat-value text-green-800 dark:text-green-200">{{ statusCounts['Finalizado'] }}</div>
              </div>
            </div>
          </div>

          <!-- Evidência Recusada -->
          <div role="button" tabindex="0"
               @click="toggleStatus('Evidência Recusada')" @keydown.enter.prevent="toggleStatus('Evidência Recusada')"
               class="stat-card from-orange-50 to-white dark:from-[#261b0c] dark:to-[#0b0b0b] cursor-pointer select-none"
               :class="selectedStatus === 'Evidência Recusada' ? 'stat-card--active ring-2 ring-orange-300 dark:ring-orange-700 bg-orange-50 dark:bg-orange-900/10' : ''">
            <div class="flex items-center gap-3">
              <div class="icon-wrap bg-orange-100 dark:bg-orange-900/40"><XCircle class="w-5 h-5 text-orange-600 dark:text-orange-400"/></div>
              <div>
                <div class="stat-label text-orange-700 dark:text-orange-300">Evidência Recusada</div>
                <div class="stat-value text-orange-800 dark:text-orange-200">{{ statusCounts['Evidência Recusada'] }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Linha 2: Dois cards “Top” (barras horizontais alinhadas) -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 w-full">
        <!-- Top Origem -->
        <div class="panel">
          <div class="panel-head">
            <span>Origem</span>
            <span class="muted"></span>
          </div>
          <div class="p-4">
            <table class="wide-table">
              <thead>
                <tr>
                  <th></th>
                  <th class="text-right"> </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="[label, value] in originRanking.entries" :key="label">
                  <td>
                    <div class="rowline">
                      <span class="truncate" :title="label">{{ label }}</span>
                      <div class="track" aria-hidden="true">
                        <div class="fill" :style="{ width: `calc(${(value / originRanking.max) * 100}% )` }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-right strong tabular-nums">{{ value }}</td>
                </tr>
                <tr v-if="originRanking.entries.length===0">
                  <td colspan="2" class="empty">Sem dados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Top CR -->
        <div class="panel">
          <div class="panel-head">
            <span>Contratos</span>
            <span class="muted"></span>
          </div>
          <div class="p-4">
            <table class="wide-table">
              <thead>
                <tr>
                  <th></th>
                  <th class="text-right"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="[label, value] in crRanking.entries" :key="label">
                  <td>
                    <div class="rowline">
                      <span class="truncate" :title="label">{{ label }}</span>
                      <div class="track">
                        <div class="fill navy" :style="{ width: `calc(${(value / crRanking.max) * 100}% )` }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="text-right strong tabular-nums">{{ value }}</td>
                </tr>
                <tr v-if="crRanking.entries.length===0">
                  <td colspan="2" class="empty">Sem dados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Linha 3: Barras em pé por Status -->
      <div class="panel">
        <div class="panel-head">
          <span>Status</span>
          <span class="muted"></span>
        </div>
        <div class="p-6 pt-4">
          <div class="vbar-wrap">
            <div
              v-for="[label, value] in statusPairs.pairs"
              :key="label"
              class="vbar-col"
              @click="toggleStatus(label as StatusKey)"
              :title="`${label}: ${value}`"
            >
              <div class="vbar" :style="{
                height: `${Math.round((value / statusPairs.max) * 180)}px`,
                background: pickStatusColor(label)
              }"></div>
              <div class="vbar-value tabular-nums">{{ value }}</div>
              <div class="vbar-label" :style="{ color: pickStatusColor(label) }">{{ label }}</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<style scoped>
/* ====== CARDS ESTÉTICOS (padrão index.vue) ====== */
.stat-card {
  background-image: linear-gradient(var(--tw-gradient-stops));
  --tw-gradient-from: rgba(255,255,255,0);
  --tw-gradient-to: rgba(255,255,255,0);
  border: 1px solid rgba(17,24,39,0.06);
  border-radius: 16px;
  padding: 14px 16px;
  box-shadow: 0 4px 16px rgba(17,24,39,0.05);
  transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
}
.dark .stat-card { border-color: rgba(255,255,255,0.08); box-shadow: 0 6px 18px rgba(0,0,0,0.25); }
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(17,24,39,0.10); }
.stat-card--active { border-color: transparent !important; }
.icon-wrap { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; box-shadow: inset 0 1px 0 rgba(255,255,255,.6); }
.stat-label { font-size: .8rem; line-height: 1rem; opacity: .9; }
.stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.1; }

/* ====== PANELS ====== */
.panel{ border:1px solid rgba(17,24,39,0.08); border-radius:18px; background:#fff; box-shadow: 0 6px 18px rgba(17,24,39,0.06); }
:host(.dark) .panel, .dark .panel{ background:#111827; border-color: rgba(255,255,255,0.08); }
.panel-head{ display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid rgba(17,24,39,0.08); font-weight: 800; }
.muted{ font-size:12px; color:#6b7280; }
.strong{ font-weight:700; }

/* ====== TABELAS “ALINHADAS” ====== */
.wide-table{ width:100%; font-size:14px; border-collapse:separate; border-spacing:0; }
.wide-table thead th{ text-align:left; padding:6px 0 10px; color:#111827; font-weight:600; }
.wide-table tbody tr{ border-top:1px solid rgba(17,24,39,0.06); }
.wide-table tbody td{ padding:10px 0; vertical-align:middle; }
.wide-table .empty{ text-align:center; color:#6b7280; padding:16px 0; }

/* Linha com barra alinhada: o “track” tem largura fixa para todas linhas */
.rowline{ display:grid; grid-template-columns: minmax(120px, 1fr) 320px; align-items:center; gap:16px; }
@media (max-width: 1280px){ .rowline{ grid-template-columns: minmax(100px,1fr) 260px; } }
@media (max-width: 1024px){ .rowline{ grid-template-columns: minmax(80px,1fr) 220px; } }

.track{ height:8px; background:#eef2f7; border-radius:6px; overflow:hidden; position:relative; }
.fill{ height:100%; background:#1D4ED8; border-radius:6px; transition:width .25s ease; }
.fill.navy{ background:#0B1B6B; }

/* ====== BARRAS EM PÉ (STATUS) ====== */
.vbar-wrap{ width:100%; display:flex; align-items:flex-end; justify-content:space-between; gap:22px; min-height: 220px; }
.vbar-col{ flex:1; display:flex; flex-direction:column; align-items:center; cursor:pointer; user-select:none; }
.vbar{ width: 38px; max-width: 54px; border-radius:10px; box-shadow: inset 0 1px 0 rgba(255,255,255,.5); transition: height .25s ease, transform .15s ease; }
.vbar-col:hover .vbar{ transform: translateY(-2px); }
.vbar-value{ font-weight:800; margin-top:6px; font-variant-numeric: tabular-nums; }
.vbar-label{ margin-top:4px; font-size:12px; text-align:center; line-height:1.1; }

/* dark tweaks */
.dark .wide-table thead th{ color:#e5e7eb; }
.dark .muted{ color:#9ca3af; }
.dark .track{ background:#1f2937; }
</style>
