<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Rocket, Pencil, Trash, FileText, Eye, X } from 'lucide-vue-next';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { ref, computed } from 'vue'

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

// ====== Funções utilitárias ======

function nowBrazil() {
    const now = new Date()
    const utc = now.getTime() + now.getTimezoneOffset() * 60000
    return new Date(utc - 3 * 60 * 60000)
}

function getStatus(product: Product): string {
    if (!product.pdf_path && new Date(product.dueDate) < nowBrazil()) return "Atrasado"
    if (!product.pdf_path && new Date(product.dueDate) >= nowBrazil()) return "Pendente"
    if (product.pdf_path && !product.confirmEvidency) return "Pendente Aprovação"
    if (product.pdf_path && product.confirmEvidency === 'Aprovado') return "Finalizado"
    if (product.pdf_path && product.confirmEvidency === 'Reprovado') return "Evidência Recusada"
    return ""
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

const canCreate = () => {
    return currentUserType === 'ADM' || currentUserType === 'Usuario Plus'
}

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

// 🔹 Controle de ordenação
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

// 🔹 Filtro e ordenação aplicados
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
        (!filters.value.status || getStatus(p) === filters.value.status)
    )

    // 🔹 Ordenação dinâmica
    if (sortBy.value) {
        result.sort((a, b) => {
            const dateA = new Date(a[sortBy.value]).getTime()
            const dateB = new Date(b[sortBy.value]).getTime()
            return sortOrder.value === 'asc' ? dateA - dateB : dateB - dateA
        })
    }

    return result
})

// ====== Modal ======
const showActionModal = ref(false)
const selectedAction = ref("")

// ====== Mapeamento de CRs e Clientes ======
const crClientes: Record<string, string> = {
    "16749": "BRASMETAL", "17542": "SAMARCO", "17543": "SAMARCO", "24178": "SAMARCO",
    "24238": "BRASKEM", "25458": "LOCALFRIO", "26052": "CHEVRON", "27911": "BRASKEM",
    "32470": "BASF", "35118": "USIMINAS", "35119": "USIMINAS", "35122": "USIMINAS",
    "35132": "USIMINAS", "35180": "USIMINAS", "40703": "FLEURY", "43409": "BASF",
    "44242": "BRASMETAL", "44914": "BRASKEM", "45955": "BASF", "47287": "INDIRETOS",
    "51115": "ISA ENERGIA", "56115": "SAMARCO", "58492": "FLEURY", "62338": "ANGLO",
    "68807": "USIMINAS", "68820": "INDIRETOS", "75999": "INDIRETOS", "76047": "INDIRETOS",
    "76357": "USIMINAS", "77943": "BRASMETAL", "79012": "ATLAS COPCO", "82840": "INDIRETOS",
}

function getClientePorCR(cr: string): string {
    const crNum = cr.slice(0, 5)
    return crClientes[crNum] || "—"
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

            <div class="mb-4" v-if="canCreate()">
                <Link href="/products/create">
                <Button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg shadow">
                    + Nova Ocorrência
                </Button>
                </Link>
            </div>

            <div
                class="rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg bg-white dark:bg-gray-900 w-full overflow-x-auto">
                <Table class="w-full border-collapse">
                    <TableHeader class="bg-gray-100 dark:bg-gray-800">
                        <TableRow
                            class="divide-x divide-gray-300 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-200">
                            <TableHead>Registrado por</TableHead>
                            <TableHead>CR</TableHead>
                            <TableHead>Ocorrência</TableHead>
                            <TableHead>Origem</TableHead>
                            <TableHead>Ação</TableHead>

                            <TableHead class="cursor-pointer select-none" @click="toggleSort('startDate')">
                                Início
                                <span v-if="sortBy === 'startDate'">
                                    <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg"
                                        class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </TableHead>

                            <TableHead class="cursor-pointer select-none" @click="toggleSort('dueDate')">
                                Prazo
                                <span v-if="sortBy === 'dueDate'">
                                    <svg v-if="sortOrder === 'asc'" xmlns="http://www.w3.org/2000/svg"
                                        class="inline w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 15l7-7 7 7" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="inline w-3 h-3 ml-1"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </TableHead>

                            <TableHead>Responsável</TableHead>
                            <TableHead class="text-center">Status</TableHead>
                            <TableHead class="text-center">Ações</TableHead>
                        </TableRow>

                        <!-- 🔹 Linha de Filtros -->
                        <TableRow
                            class="divide-x divide-gray-200 dark:divide-gray-700 bg-gray-50 dark:bg-gray-700 text-xs text-gray-700 dark:text-gray-200">
                            <TableCell><input v-model="filters.creator" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
                            <TableCell><input v-model="filters.cr" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
                            <TableCell><input v-model="filters.ocorrency" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
                            <TableCell><input v-model="filters.origin" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
                            <TableCell><input v-model="filters.action" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
                            <TableCell><input type="date" v-model="filters.startDate" class="input-filter" />
                            </TableCell>
                            <TableCell><input type="date" v-model="filters.dueDate" class="input-filter" /></TableCell>
                            <TableCell><input v-model="filters.email" placeholder="Filtrar" class="input-filter" />
                            </TableCell>
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
                        <TableRow v-for="product in filteredProducts" :key="product.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border-t border-gray-100 dark:border-gray-700">
                            <TableCell>{{ product.nameCreator || '—' }}</TableCell>
                            <TableCell>{{ product.cr.slice(0, 5) }} - {{ getClientePorCR(product.cr) }}</TableCell>
                            <TableCell>{{ product.ocorrency }}</TableCell>
                            <TableCell>{{ product.origin }}</TableCell>
                            <TableCell class="text-center">
                                <Button size="icon" variant="ghost" class="hover:bg-blue-100 dark:hover:bg-blue-900"
                                    @click="showActionModal = true; selectedAction = product.action">
                                    <Eye class="h-4 w-4 text-blue-600" />
                                </Button>
                            </TableCell>
                            <TableCell>{{ formatDateBrazil(product.startDate) }}</TableCell>
                            <TableCell>{{ formatDateBrazil(product.dueDate) }}</TableCell>

                            <TableCell class="whitespace-nowrap overflow-hidden text-ellipsis max-w-[200px]">{{
                                product.email }}</TableCell>
                            <TableCell class="text-center">
                                <span v-if="getStatus(product) === 'Atrasado'"
                                    class="badge bg-red-100 text-red-700">Atrasado</span>
                                <span v-else-if="getStatus(product) === 'Pendente'"
                                    class="badge bg-yellow-100 text-yellow-700">Pendente</span>
                                <span v-else-if="getStatus(product) === 'Pendente Aprovação'"
                                    class="badge bg-blue-100 text-blue-700">Pendente Aprovação</span>
                                <span v-else-if="getStatus(product) === 'Finalizado'"
                                    class="badge bg-green-100 text-green-700">Finalizado</span>
                                <span v-else-if="getStatus(product) === 'Evidência Recusada'"
                                    class="badge bg-orange-100 text-orange-700">Evidência Recusada</span>
                            </TableCell>

                            <TableCell class="text-center">
                                <template v-if="canEdit(product)">
                                    <template v-if="getStatus(product) === 'Finalizado' && currentUserType !== 'ADM'">
                                        <Button variant="ghost" size="icon" disabled
                                            class="opacity-40 cursor-not-allowed">
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
                                        <FileText class="h-4 w-4" :class="product.pdf_path && product.confirmEvidency === 'Aprovado'
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-gray-500 dark:text-gray-400'" />
                                    </Button>
                                    </Link>
                                </template>

                                <template v-if="currentUserType === 'ADM'">
                                    <Button variant="ghost" size="icon" class="hover:bg-red-100"
                                        @click="handleDelete(product.id)">
                                        <Trash class="h-4 w-4 text-red-600" />
                                    </Button>
                                </template>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Modal -->
            <transition name="fade">
                <div v-if="showActionModal"
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
                    <transition name="popup">
                        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl p-6 w-[480px] text-center">
                            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">📄 Detalhes da Ação
                            </h2>
                            <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap mb-6">{{
                                selectedAction }}</p>
                            <Button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"
                                @click="showActionModal = false">
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
.input-filter {
    width: 100%;
    border-radius: 6px;
    padding: 4px 8px;
    border: 1px solid var(--border-color, #ccc);
    background-color: var(--bg-input, white);
    color: inherit;
    outline: none;
}

th {
    user-select: none;
    cursor: pointer;
}

th:hover {
    background-color: rgba(59, 130, 246, 0.1);
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 9999px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.popup-enter-active,
.popup-leave-active {
    transition: all 0.25s ease;
}

.popup-enter-from,
.popup-leave-to {
    transform: scale(0.9);
    opacity: 0;
}
</style>
