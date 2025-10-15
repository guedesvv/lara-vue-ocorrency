<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Rocket, Pencil, Trash, FileText, Eye, X, ChevronLeft, ChevronRight, CheckCircle2, AlertTriangle, Clock, Hourglass, XCircle } from 'lucide-vue-next';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ref, computed, watch } from 'vue'

interface Product {
  id: number
  cr: string
  ocorrency: string
  origin: string
  action: string
  startDate: Date
  dueDate: Date
  email: string
  emailCreator?: string
  nameCreator?: string
  created_at: Date
  pdf_path?: string
  confirmEvidency?: string
}

interface User {
  id: number
  name: string
  email: string
}

function formatDateBrazil(date: string | Date) {
  if (!date) return '—'
  const d = new Date(date)
  d.setHours(d.getHours() + 3) // 🔹 corrige UTC-3
  return d.toLocaleDateString('pt-BR')
}

interface Props {
  products: Product[]
  users: User[]
}

const props = defineProps<Props>()
const page = usePage()
const currentUserEmail = page.props.auth?.user?.email
const currentUserType = page.props.auth?.user?.userType

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Ocorrências', href: '/products' },
]

// ====== Util ======
function nowBrazil() {
  const now = new Date()
  const utc = now.getTime() + now.getTimezoneOffset() * 60000
  return new Date(utc - 3 * 60 * 60000)
}

function isFinalizado(product: Product) {
  return !!(product.pdf_path && product.confirmEvidency === 'Aprovado')
}

function isOverdue(product: Product) {
  // fundo avermelhado suave nas linhas em atraso (se não finalizado)
  return new Date(product.dueDate) < nowBrazil() && !isFinalizado(product)
}

/**
 * Status múltiplos por item:
 * - Se Finalizado: ["Finalizado"] (exclusivo)
 * - Caso contrário, empilha:
 *   - "Atrasado" se dueDate < agora
 *   - "Evidência Recusada" se confirmEvidency === "Reprovado"
 *   - "Pendente Aprovação" se tem PDF e não está aprovado/reprovado
 *   - "Pendente" se não tem PDF (e não caiu nas anteriores)
 */
function getStatuses(product: Product): string[] {
  const statuses: string[] = []
  if (isFinalizado(product)) return ['Finalizado']

  const overdue = new Date(product.dueDate) < nowBrazil()
  if (overdue) statuses.push('Atrasado')

  if (product.pdf_path && product.confirmEvidency === 'Reprovado') {
    statuses.push('Evidência Recusada')
  } else if (product.pdf_path && !product.confirmEvidency) {
    statuses.push('Pendente Aprovação')
  } else if (!product.pdf_path) {
    statuses.push('Pendente')
  }

  return statuses
}

const handleDelete = (id: number) => {
  if (confirm('Você quer realmente remover a ocorrência?')) {
    router.delete(route('products.destroy', { id }))
  }
}

const canEdit = (product: Product) => {
  if (currentUserType === 'ADM') return true
  if (currentUserType === 'Usuario Plus' && product.emailCreator === currentUserEmail) return true
  return false
}

const canUploadPdf = (product: Product) => {
  if (currentUserType === 'ADM') return true
  if (currentUserType === 'Usuario Padrão' && product.email === currentUserEmail) return true
  if (currentUserType === 'Usuario Plus' && (product.emailCreator === currentUserEmail || product.email === currentUserEmail)) return true
  return false
}

const canCreate = () => currentUserType === 'ADM' || currentUserType === 'Usuario Plus'

const getCreatorName = (emailCreator?: string) => {
  if (!emailCreator) return "—"
  const user = props.users.find(u => u.email === emailCreator)
  return user ? user.name : emailCreator
}

// ====== Filtros e Ordenação ======
const filters = ref({
  creator: "",
  cr: "",
  ocorrency: "",
  origin: "",
  action: "",
  startDate: "",
  dueDate: "",
  email: "",
  status: "",
})

function formatDateForFilter(date: string | Date) {
  const d = new Date(date)
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().slice(0, 10)
}

const sortBy = ref<'startDate' | 'dueDate' | ''>('')
const sortOrder = ref<'asc' | 'desc'>('asc')

function toggleSort(column: 'startDate' | 'dueDate') {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = 'asc'
  }
}

// 🔹 Filtro + ordenação
const filteredProducts = computed(() => {
  let result = props.products.filter(p =>
    (!filters.value.creator || getCreatorName(p.emailCreator).toLowerCase().includes(filters.value.creator.toLowerCase())) &&
    (!filters.value.cr || p.cr.toLowerCase().includes(filters.value.cr.toLowerCase())) &&
    (!filters.value.ocorrency || p.ocorrency.toLowerCase().includes(filters.value.ocorrency.toLowerCase())) &&
    (!filters.value.origin || p.origin.toLowerCase().includes(filters.value.origin.toLowerCase())) &&
    (!filters.value.action || p.action.toLowerCase().includes(filters.value.action.toLowerCase())) &&
    (!filters.value.startDate || formatDateForFilter(p.startDate) === filters.value.startDate) &&
    (!filters.value.dueDate || formatDateForFilter(p.dueDate) === filters.value.dueDate) &&
    (!filters.value.email || p.email.toLowerCase().includes(filters.value.email.toLowerCase())) &&
    (!filters.value.status || getStatuses(p).includes(filters.value.status))
  )

  if (sortBy.value) {
    result.sort((a, b) => {
      const dateA = new Date(a[sortBy.value]).getTime()
      const dateB = new Date(b[sortBy.value]).getTime()
      return sortOrder.value === 'asc' ? dateA - dateB : dateB - dateA
    })
  }
  return result
})

// ====== Contagem por status (com múltiplos por item) ======
const statusCounts = computed(() => {
  const counts: Record<string, number> = {
    "Atrasado": 0,
    "Pendente": 0,
    "Pendente Aprovação": 0,
    "Finalizado": 0,
    "Evidência Recusada": 0,
  }
  filteredProducts.value.forEach(p => {
    getStatuses(p).forEach(s => {
      if (counts[s] !== undefined) counts[s]++
    })
  })
  return counts
})

// ====== Paginação ======
const currentPage = ref(1)
const itemsPerPage = 10

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return filteredProducts.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage))

const changePage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    window.scrollTo({ top: 0, behavior: "smooth" })
  }
}

// 🔹 Reset página ao mudar filtros/ordenação
watch([filters, sortBy, sortOrder], () => {
  currentPage.value = 1
}, { deep: true })

// ====== Modal ======
const showActionModal = ref(false)
const selectedAction = ref("")

// ====== Mapeamento de CRs e Clientes ======
const crClientes: Record<string, string> = {
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
function getClientePorCR(cr: string): string {
  const crNum = cr.slice(0, 5)
  return crClientes[crNum] || "—"
}

// ====== Badge helper ======
function badgeClass(s: string) {
  if (s === 'Atrasado') return 'bg-red-100 text-red-700'
  if (s === 'Pendente') return 'bg-yellow-100 text-yellow-700'
  if (s === 'Pendente Aprovação') return 'bg-blue-100 text-blue-700'
  if (s === 'Finalizado') return 'bg-green-100 text-green-700'
  if (s === 'Evidência Recusada') return 'bg-orange-100 text-orange-700'
  return 'bg-gray-100 text-gray-700'
}

// ====== Click nos cards ======
function toggleStatusFilter(target: string) {
  filters.value.status = (filters.value.status === target) ? '' : target
}

</script>

<template>
  <Head title="Ocorrências" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <!-- Alerta -->
      <div v-if="page.props.flash?.message" class="mb-4">
        <Alert class="bg-blue-100 border border-blue-300 dark:bg-blue-950 dark:border-blue-800">
          <Rocket class="h-5 w-5 text-blue-600 dark:text-black" />
          <AlertTitle class="text-blue-800 font-bold dark:text-white">Tudo certo!</AlertTitle>
          <AlertDescription class="text-blue-700 dark:text-gray-200">
            <b>{{ page.props.flash.message }}</b>
          </AlertDescription>
        </Alert>
      </div>

      <!-- ====== CARDS DE STATUS (clicáveis) ====== -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <!-- Atrasado -->
        <div
          role="button"
          tabindex="0"
          @click="toggleStatusFilter('Atrasado')"
          @keydown.enter.prevent="toggleStatusFilter('Atrasado')"
          class="stat-card from-red-50 to-white dark:from-[#2a0f10] dark:to-[#0b0b0b] cursor-pointer select-none"
          :class="filters.status === 'Atrasado'
            ? 'stat-card--active ring-2 ring-red-300 dark:ring-red-700 bg-red-50 dark:bg-red-900/10'
            : ''"
        >
          <div class="flex items-center gap-3">
            <div class="icon-wrap bg-red-100 dark:bg-red-900/40">
              <AlertTriangle class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <div>
              <div class="stat-label text-red-700 dark:text-red-300">Atrasado</div>
              <div class="stat-value text-red-800 dark:text-red-200">{{ statusCounts['Atrasado'] }}</div>
            </div>
          </div>
        </div>

        <!-- Pendente -->
        <div
          role="button"
          tabindex="0"
          @click="toggleStatusFilter('Pendente')"
          @keydown.enter.prevent="toggleStatusFilter('Pendente')"
          class="stat-card from-yellow-50 to-white dark:from-[#2a220c] dark:to-[#0b0b0b] cursor-pointer select-none"
          :class="filters.status === 'Pendente'
            ? 'stat-card--active ring-2 ring-yellow-300 dark:ring-yellow-700 bg-yellow-50 dark:bg-yellow-900/10'
            : ''"
        >
          <div class="flex items-center gap-3">
            <div class="icon-wrap bg-yellow-100 dark:bg-yellow-900/40">
              <Clock class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <div class="stat-label text-yellow-700 dark:text-yellow-300">Pendente</div>
              <div class="stat-value text-yellow-800 dark:text-yellow-200">{{ statusCounts['Pendente'] }}</div>
            </div>
          </div>
        </div>

        <!-- Pendente Aprovação -->
        <div
          role="button"
          tabindex="0"
          @click="toggleStatusFilter('Pendente Aprovação')"
          @keydown.enter.prevent="toggleStatusFilter('Pendente Aprovação')"
          class="stat-card from-blue-50 to-white dark:from-[#0e1a2b] dark:to-[#0b0b0b] cursor-pointer select-none"
          :class="filters.status === 'Pendente Aprovação'
            ? 'stat-card--active ring-2 ring-blue-300 dark:ring-blue-700 bg-blue-50 dark:bg-blue-900/10'
            : ''"
        >
          <div class="flex items-center gap-3">
            <div class="icon-wrap bg-blue-100 dark:bg-blue-900/40">
              <Hourglass class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <div class="stat-label text-blue-700 dark:text-blue-300">Pendente Aprovação</div>
              <div class="stat-value text-blue-800 dark:text-blue-200">{{ statusCounts['Pendente Aprovação'] }}</div>
            </div>
          </div>
        </div>

        <!-- Finalizado -->
        <div
          role="button"
          tabindex="0"
          @click="toggleStatusFilter('Finalizado')"
          @keydown.enter.prevent="toggleStatusFilter('Finalizado')"
          class="stat-card from-green-50 to-white dark:from-[#0f2417] dark:to-[#0b0b0b] cursor-pointer select-none"
          :class="filters.status === 'Finalizado'
            ? 'stat-card--active ring-2 ring-green-300 dark:ring-green-700 bg-green-50 dark:bg-green-900/10'
            : ''"
        >
          <div class="flex items-center gap-3">
            <div class="icon-wrap bg-green-100 dark:bg-green-900/40">
              <CheckCircle2 class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="stat-label text-green-700 dark:text-green-300">Finalizado</div>
              <div class="stat-value text-green-800 dark:text-green-200">{{ statusCounts['Finalizado'] }}</div>
            </div>
          </div>
        </div>

        <!-- Evidência Recusada -->
        <div
          role="button"
          tabindex="0"
          @click="toggleStatusFilter('Evidência Recusada')"
          @keydown.enter.prevent="toggleStatusFilter('Evidência Recusada')"
          class="stat-card from-orange-50 to-white dark:from-[#261b0c] dark:to-[#0b0b0b] cursor-pointer select-none"
          :class="filters.status === 'Evidência Recusada'
            ? 'stat-card--active ring-2 ring-orange-300 dark:ring-orange-700 bg-orange-50 dark:bg-orange-900/10'
            : ''"
        >
          <div class="flex items-center gap-3">
            <div class="icon-wrap bg-orange-100 dark:bg-orange-900/40">
              <XCircle class="w-5 h-5 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
              <div class="stat-label text-orange-700 dark:text-orange-300">Evidência Recusada</div>
              <div class="stat-value text-orange-800 dark:text-orange-200">{{ statusCounts['Evidência Recusada'] }}</div>
            </div>
          </div>
        </div>
      </div>
      <!-- ====== /CARDS ====== -->

      <div class="rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg bg-white dark:bg-gray-900 w-full overflow-x-auto">
        <Table class="w-full border-collapse">
          <TableHeader class="bg-gray-100 dark:bg-gray-800">
            <TableRow class="divide-x divide-gray-300 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-200">
              <TableHead>Registrado por</TableHead>
              <TableHead>CR</TableHead>
              <TableHead>Ocorrência</TableHead>
              <TableHead>Origem</TableHead>
              <TableHead>Ação</TableHead>
              <TableHead class="cursor-pointer select-none" @click="toggleSort('startDate')">
                Início
                <span v-if="sortBy === 'startDate'">
                  <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </TableHead>
              <TableHead class="cursor-pointer select-none" @click="toggleSort('dueDate')">
                Prazo
                <span v-if="sortBy === 'dueDate'">
                  <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </TableHead>
              <TableHead>Responsável</TableHead>
              <TableHead class="text-center">Status</TableHead>
              <TableHead class="text-center">Ações</TableHead>
            </TableRow>

            <!-- 🔹 Linha de Filtros -->
            <TableRow class="divide-x divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200">
              <TableCell><input v-model="filters.creator" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell><input v-model="filters.cr" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell><input v-model="filters.ocorrency" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell><input v-model="filters.origin" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell><input v-model="filters.action" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell><input type="date" v-model="filters.startDate" class="input-filter" /></TableCell>
              <TableCell><input type="date" v-model="filters.dueDate" class="input-filter" /></TableCell>
              <TableCell><input v-model="filters.email" placeholder="Filtrar" class="input-filter" /></TableCell>
              <TableCell>
                <select v-model="filters.status" class="input-filter">
                  <option value="">Todos</option>
                  <option value="Atrasado">Atrasado</option>
                  <option value="Pendente">Pendente</option>
                  <option value="Pendente Aprovação">Pendente Aprovação</option>
                  <option value="Finalizado">Finalizado</option>
                  <option value="Evidência Recusada">Evidência Recusada</option>
                </select>
              </TableCell>
              <TableCell></TableCell>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="product in paginatedProducts"
              :key="product.id"
              class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border-t border-gray-100 dark:border-gray-700"
              :class="isOverdue(product) ? 'bg-red-50/50 dark:bg-[rgba(128,0,0,0.12)]' : ''"
            >
              <TableCell>{{ product.nameCreator || '—' }}</TableCell>
              <TableCell>{{ product.cr.slice(0, 5) }} - {{ getClientePorCR(product.cr) }}</TableCell>
              <TableCell>{{ product.ocorrency }}</TableCell>
              <TableCell>{{ product.origin }}</TableCell>
              <TableCell class="text-center">
                <Button
                  size="icon"
                  variant="ghost"
                  class="hover:bg-blue-100 dark:hover:bg-blue-900"
                  @click="showActionModal = true; selectedAction = product.action"
                >
                  <Eye class="h-4 w-4 text-blue-600" />
                </Button>
              </TableCell>
              <TableCell>{{ formatDateBrazil(product.startDate) }}</TableCell>
              <TableCell>{{ formatDateBrazil(product.dueDate) }}</TableCell>
              <TableCell class="whitespace-nowrap overflow-hidden text-ellipsis max-w-[200px]">
                {{ product.email }}
              </TableCell>

              <!-- ====== Múltiplas badges ====== -->
              <TableCell class="text-center">
                <div class="flex items-center justify-center gap-1 flex-wrap">
                  <span
                    v-for="s in getStatuses(product)"
                    :key="s"
                    class="badge"
                    :class="badgeClass(s)"
                  >{{ s }}</span>
                </div>
              </TableCell>

              <TableCell class="text-center">
                <template v-if="canEdit(product)">
                  <template v-if="getStatuses(product).includes('Finalizado') && currentUserType !== 'ADM'">
                    <Button variant="ghost" size="icon" disabled class="opacity-40 cursor-not-allowed">
                      <Pencil class="h-4 w-4 text-gray-400" />
                    </Button>
                  </template>
                  <template v-else>
                    <Link :href="`/products/${product.id}/edit`">
                      <Button variant="ghost" size="icon">
                        <Pencil class="h-4 w-4 text-slate-600" />
                      </Button>
                    </Link>
                  </template>
                </template>

                <template v-if="canUploadPdf(product)">
                  <Link :href="`/products/${product.id}/edit-pdf`">
                    <Button variant="ghost" size="icon">
                      <FileText
                        class="h-4 w-4"
                        :class="getStatuses(product).includes('Finalizado') ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'"
                      />
                    </Button>
                  </Link>
                </template>

                <template v-if="currentUserType === 'ADM'">
                  <Button variant="ghost" size="icon" class="hover:bg-red-100" @click="handleDelete(product.id)">
                    <Trash class="h-4 w-4 text-red-600" />
                  </Button>
                </template>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Paginação -->
        <div class="w-full flex flex-col items-center gap-3 p-4">
          <div class="flex items-center gap-2">
            <Button class="pg-btn group" :class="currentPage === 1 ? 'pg-btn--disabled' : ''" :disabled="currentPage === 1" @click="changePage(currentPage - 1)">
              <ChevronLeft class="w-4 h-4 transition-transform group-hover:-translate-x-0.5" />
              <span class="hidden sm:inline">Anterior</span>
            </Button>
            <span class="pg-indicator"> Página {{ currentPage }} de {{ totalPages || 1 }} </span>
            <Button
              class="pg-btn group"
              :class="(currentPage === totalPages || totalPages === 0) ? 'pg-btn--disabled' : ''"
              :disabled="currentPage === totalPages || totalPages === 0"
              @click="changePage(currentPage + 1)"
            >
              <span class="hidden sm:inline">Próxima</span>
              <ChevronRight class="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
            </Button>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 sm:hidden">
            {{ currentPage }} / {{ totalPages || 1 }}
          </div>
        </div>
      </div>

      <br />
      <div class="mb-4" v-if="canCreate()">
        <Link href="/products/create">
          <Button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg shadow">
            + Nova Ocorrência
          </Button>
        </Link>
      </div>

      <!-- Modal -->
      <transition name="fade">
        <div v-if="showActionModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
          <transition name="popup">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-[480px] text-center">
              <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">📄 Detalhes da Ação </h2>
              <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap mb-6">{{ selectedAction }}</p>
              <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg" @click="showActionModal = false">
                <X class="h-4 w-4 inline-block mr-1" /> Fechar
              </Button>
            </div>
          </transition>
        </div>
      </transition>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ====== CARDS ESTÉTICOS ====== */
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
.dark .stat-card {
  border-color: rgba(255,255,255,0.08);
  box-shadow: 0 6px 18px rgba(0,0,0,0.25);
}
.stat-card:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(17,24,39,0.10); }
.stat-card--active { border-color: transparent !important; }

.icon-wrap {
  width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
}
.stat-label { font-size: .8rem; line-height: 1rem; opacity: .9; }
.stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.1; }

/* ====== PAGINAÇÃO ESTÉTICA ====== */
.pg-btn {
  border-radius: 9999px; padding: 0.5rem 0.9rem;
  background: linear-gradient(180deg, #ffffff, #f4f6fb);
  border: 1px solid rgba(17, 24, 39, 0.08);
  color: #111827;
  box-shadow: 0 2px 8px rgba(17, 24, 39, 0.06);
  transition: transform .15s ease, box-shadow .2s ease, opacity .2s ease, background .2s ease;
}
.dark .pg-btn {
  background: linear-gradient(180deg, #111827, #0b1220);
  border-color: rgba(255,255,255,0.08);
  color: #e5e7eb;
  box-shadow: 0 2px 10px rgba(0,0,0,0.25);
}
.pg-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(17, 24, 39, 0.10); }
.pg-btn--disabled, .pg-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

/* badge central */
.pg-indicator {
  font-size: .85rem; padding: .4rem .7rem; border-radius: 9999px;
  background: rgba(59,130,246,0.10); color: #1f3d7a; border: 1px solid rgba(59,130,246,.15);
}
.dark .pg-indicator { background: rgba(59,130,246,0.12); color: #c6dcff; border-color: rgba(59,130,246,.25); }

/* ====== INPUT, TABELA, BADGES ====== */
.input-filter { width: 100%; border-radius: 6px; padding: 4px 8px; border: 1px solid var(--border-color, #ccc); background-color: var(--bg-input, white); color: inherit; outline: none; }
th { user-select: none; cursor: pointer; }
th:hover { background-color: rgba(59, 130, 246, 0.1); }

.badge { padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 9999px; line-height: 1; }

/* Transições modal */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.popup-enter-active, .popup-leave-active { transition: all 0.25s ease; }
.popup-enter-from, .popup-leave-to { transform: scale(0.9); opacity: 0; }

/* ====== MOBILE ====== */
@media (max-width: 768px) {
  .stat-card { padding: 12px 14px; }
  .stat-value { font-size: 1.1rem; }
  table { font-size: 0.75rem; }
  th, td { padding: 0.35rem 0.5rem; }
  .input-filter { font-size: 0.7rem; padding: 3px 6px; }
  .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  button { padding: 0.25rem !important; }
  .badge { font-size: 0.7rem; padding: 0.15rem 0.4rem; }
}
</style>
