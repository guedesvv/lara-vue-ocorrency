<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Rocket, Pencil, Trash, FileText } from 'lucide-vue-next';
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
    id: number,
    cr: string,
    ocorrency: string,
    origin: string,
    action: string,
    startDate: Date,
    dueDate: Date,
    email: string,
    emailCreator?: string,
    created_at: Date,
    pdf_path?: string,
    confirmEvidency?: string,
}

interface User {
    id: number,
    name: string,
    email: string,
}

interface Props {
    products: Product[];
    users: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Ocorrências',
        href: '/products',
    },
];

const page = usePage()
const currentUserEmail = page.props.auth?.user?.email
const currentUserType = page.props.auth?.user?.userType

function nowBrazil() {
    const now = new Date();
    const utc = now.getTime() + now.getTimezoneOffset() * 60000;
    return new Date(utc - 3 * 60 * 60000);
}

// Função que retorna o status da ocorrência
function getStatus(product: Product): string {
    if (!product.pdf_path && new Date(product.dueDate) < nowBrazil()) return "Atrasado"
    if (!product.pdf_path && new Date(product.dueDate) >= nowBrazil()) return "Pendente"
    if (product.pdf_path && !product.confirmEvidency) return "Pendente Aprovação"
    if (product.pdf_path && product.confirmEvidency === 'Aprovado') return "Finalizado"
    if (product.pdf_path && product.confirmEvidency === 'Reprovado') return "Evidência Recusada"
    return ""
}

// Permissões
const handleDelete = (id: number) => {
    if (confirm('Você quer realmente remover a ocorrência?')) {
        router.delete(route('products.destroy', { id }));
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

// 🔹 Retorna nome do criador
const getCreatorName = (emailCreator?: string) => {
    if (!emailCreator) return "—"
    const user = props.users.find(u => u.email === emailCreator)
    return user ? user.name : emailCreator
}

// 🔹 Filtros
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
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset()) // corrige para o fuso local
    return d.toISOString().slice(0, 10) // yyyy-mm-dd
}

const filteredProducts = computed(() => {
    return props.products.filter(p =>
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
})
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

            
            <!-- Título -->
            <h1 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200">
                📋 Registro das Ocorrências
            </h1>

                        <!-- Botão Nova Ocorrência -->
            <div class="mt-6" v-if="canCreate()">
                <Link href="/products/create">
                    <Button class="bg-blue-500 hover:bg-blue-600"><b>+ Nova Ocorrência</b></Button>
                </Link>
            </div>
            <br></br>
            

            <!-- Tabela -->
            <div class="rounded-xl border border-gray-300 dark:border-gray-700 shadow-md bg-gray-100 dark:bg-gray-900 w-full overflow-x-auto">
                <Table class="w-full table-fixed border-collapse">
                    <TableHeader class="bg-gray-200 dark:bg-gray-800">
                        <TableRow class="divide-x divide-gray-300 dark:divide-gray-700 text-sm">
                            <TableHead class="min-w-[140px]">Registrado por</TableHead>
                            <TableHead class="min-w-[180px]">CR</TableHead>
                            <TableHead class="min-w-[150px] break-words">Ocorrência</TableHead>
                            <TableHead class="min-w-[120px] break-words">Origem</TableHead>
                            <TableHead class="min-w-[180px] break-words">Ação</TableHead>
                            <TableHead class="min-w-[90px]">Início</TableHead>
                            <TableHead class="min-w-[90px]">Prazo</TableHead>
                            <TableHead class="min-w-[200px] break-words">Responsável</TableHead>
                            <TableHead class="min-w-[120px] text-center">Status</TableHead>
                            <TableHead class="min-w-[60px] text-center">Ações</TableHead>
                        </TableRow>

                        <!-- Linha de Filtros -->
                        <TableRow class="divide-x divide-gray-300 dark:divide-gray-700 bg-gray-50 dark:bg-gray-700 text-sm">
                            <TableCell><input v-model="filters.creator" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input v-model="filters.cr" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input v-model="filters.ocorrency" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input v-model="filters.origin" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input v-model="filters.action" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input type="date" v-model="filters.startDate" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input type="date" v-model="filters.dueDate" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell><input v-model="filters.email" placeholder="Filtrar" class="w-full p-1 text-xs" /></TableCell>
                            <TableCell>
                                <select v-model="filters.status" class="w-full p-1 text-xs">
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

                    <!-- Corpo -->
                    <TableBody>
                        <TableRow v-for="product in filteredProducts" :key="product.id"
                            class="transition-colors divide-x divide-gray-300 dark:divide-gray-700 
                                   odd:bg-gray-100 even:bg-gray-200 hover:bg-gray-300
                                   dark:odd:bg-gray-900 dark:even:bg-gray-800 dark:hover:bg-gray-700">

                            <TableCell class="whitespace-normal break-words">{{ getCreatorName(product.emailCreator) }}</TableCell>
                            <TableCell class="whitespace-normal break-words">{{ product.cr }}</TableCell>
                            <TableCell class="whitespace-normal break-words">{{ product.ocorrency }}</TableCell>
                            <TableCell class="whitespace-normal break-words">{{ product.origin }}</TableCell>
                            <TableCell class="whitespace-normal break-words">{{ product.action }}</TableCell>
                            <TableCell>{{ new Date(product.startDate).toLocaleDateString("pt-BR") }}</TableCell>
                            <TableCell>{{ new Date(product.dueDate).toLocaleDateString("pt-BR") }}</TableCell>
                            <TableCell class="whitespace-normal break-words">{{ product.email }}</TableCell>

                            <!-- Status -->
                            <TableCell class="text-center">
                                <span v-if="getStatus(product) === 'Atrasado'"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Atrasado</span>
                                <span v-else-if="getStatus(product) === 'Pendente'"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Pendente</span>
                                <span v-else-if="getStatus(product) === 'Pendente Aprovação'"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Pendente Aprovação</span>
                                <span v-else-if="getStatus(product) === 'Finalizado'"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Finalizado</span>
                                <span v-else-if="getStatus(product) === 'Evidência Recusada'"
                                    class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">Evidência Recusada</span>
                            </TableCell>

                            <!-- Ações -->
                            <TableCell class="text-center">
                                <template v-if="canEdit(product)">
                                    <Link :href="`/products/${product.id}/edit`">
                                        <Button variant="ghost" size="icon">
                                            <Pencil class="h-4 w-4 text-slate-600" />
                                        </Button>
                                    </Link>
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


        </div>
    </AppLayout>
</template>
