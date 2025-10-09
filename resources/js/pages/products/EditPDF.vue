<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Label from '@/components/ui/label/Label.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Button from "@/components/ui/button/Button.vue"
import { ref, computed } from "vue"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { FileText, Eye } from 'lucide-vue-next'

interface Product {
  id: number
  cr: string
  pdf_path?: string
  confirmEvidency?: string
  reason?: string
  evidencyApprover?: string
  evidencyUpploader?: string
  lastPdfUpload?: string
  reasonDateTime?: string
}

const props = defineProps<{ product: Product }>()

const page = usePage()
const currentUserType = page.props.auth?.user?.userType
const currentUserName = page.props.auth?.user?.name ?? ""

const form = useForm({
  pdf: null as File | null,
})

const evidencyForm = useForm({
  confirmEvidency: "",
  reason: "",
})

const showRejectModal = ref(false)
const showApproveModal = ref(false)
const showHistoryModal = ref(false)
const showReasonModal = ref(false)
const selectedReason = ref("")

const pdfHistory = ref<any[]>([])
const isLoadingHistory = ref(false)

const loadHistory = async () => {
  isLoadingHistory.value = true
  try {
    const response = await fetch(route('products.pdfHistory', { product: props.product.id }))
    if (response.ok) {
      pdfHistory.value = await response.json()
    }
  } catch (err) {
    console.error(err)
  } finally {
    isLoadingHistory.value = false
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  form.pdf = target.files && target.files.length > 0 ? target.files[0] : null
}

const handleSubmit = () => {
  if (!form.pdf) {
    alert("Selecione um arquivo PDF antes de enviar.")
    return
  }
  form.post(route("products.updatePdf", { product: props.product.id }), { forceFormData: true })
}

const approveEvidency = () => {
  evidencyForm.transform((data) => ({
    ...data,
    confirmEvidency: "Aprovado",
    reason: "",
  }))
  evidencyForm.put(route("products.approveEvidency", { product: props.product.id }), {
    onSuccess: () => {
      showApproveModal.value = false
      // força atualização da página ao concluir a aprovação
      window.location.reload()
    }
  })
}

const rejectEvidency = () => {
  evidencyForm.transform((data) => ({
    ...data,
    confirmEvidency: "Reprovado",
  }))
  evidencyForm.put(route("products.rejectEvidency", { product: props.product.id }), {
    onSuccess: () => {
      showRejectModal.value = false
      window.location.reload()
    }
  })
}

const canUpload = () => {
  if (currentUserType === "ADM") return true
  if (!props.product.pdf_path) return true
  if (props.product.confirmEvidency === "Reprovado") return true
  return false
}

const canManageEvidency = computed(() => {
  if (currentUserType === "ADM") return true
  if (currentUserType === "Usuario Plus") {
    const upploader = props.product.evidencyUpploader ?? ""
    return upploader.trim().toLowerCase() !== currentUserName.trim().toLowerCase()
  }
  return false
})

const openReasonModal = (reason: string) => {
  selectedReason.value = reason
  showReasonModal.value = true
}
</script>

<template>
  <Head title="Atualizar Evidência" />

  <AppLayout :breadcrumbs="[{ title: 'Editar PDF', href: `/products/${props.product.id}/pdf` }]">
    <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-lg w-10/12 mx-auto">

      <!-- Cabeçalho -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">
          Gestão de Evidência
        </h1>
        <Button class="bg-blue-100 hover:bg-blue-200 text-blue-800 dark:bg-blue-800 dark:hover:bg-blue-700 dark:text-blue-100 text-sm px-3 py-1"
          @click="showHistoryModal = true; loadHistory()">
          📜 Histórico
        </Button>
      </div>

      <!-- Upload PDF -->
      <form v-if="canUpload()" @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <Label for="pdf">Selecione Evidência (PDF)</Label>
          <input id="pdf" type="file" accept="application/pdf" @change="handleFileChange"
            class="w-full border rounded p-2 text-sm mt-2 bg-gray-50 dark:bg-gray-800 dark:text-gray-200" />
          <div class="text-sm text-red-600 mt-1" v-if="form.errors.pdf">{{ form.errors.pdf }}</div>
        </div>
        <Button class="bg-blue-600 hover:bg-blue-700 w-full" type="submit">📤 Enviar Evidência</Button>
      </form>

      <div v-else
        class="p-4 mt-4 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
        ⚠️ Já existe uma evidência anexada. Você não pode substituir este arquivo.
      </div>

      <!-- PDF atual -->
      <div v-if="props.product.pdf_path" class="mt-6 space-y-4">
        <a :href="`/storage/${props.product.pdf_path}`" target="_blank"
          class="flex items-center gap-2 px-4 py-3 rounded-md border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium transition">
          📄 Visualizar Evidência Atual
        </a>

        <div class="mt-2 text-sm">
          <span class="px-2 py-1 text-xs rounded-full"
            :class="props.product.confirmEvidency === 'Aprovado'
              ? 'bg-green-100 text-green-700'
              : props.product.confirmEvidency === 'Reprovado'
                ? 'bg-red-100 text-red-700'
                : 'bg-yellow-100 text-yellow-700'">
            {{ props.product.confirmEvidency ?? 'Pendente Aprovação' }}
          </span>
        </div>

        <div
          class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm space-y-2 text-sm border border-gray-200 dark:border-gray-700">
          <p v-if="props.product.confirmEvidency === 'Aprovado' && props.product.evidencyApprover"
            class="text-green-700 dark:text-green-400">
            <b>✅ Aprovado por:</b> {{ props.product.evidencyApprover }}
          </p>

          <div v-if="props.product.confirmEvidency === 'Reprovado' && props.product.evidencyApprover"
            class="text-red-700 dark:text-red-400">
            <p><b>Recusado por:</b> {{ props.product.evidencyApprover }}</p>
            <p v-if="props.product.reason" class="mt-1 text-red-600 dark:text-red-400">
              <b>❌ Motivo:</b> {{ props.product.reason }}
            </p>
          </div>

          <p v-if="props.product.evidencyUpploader" class="text-blue-700 dark:text-blue-400">
            <b>📤 Enviado por:</b> {{ props.product.evidencyUpploader }}
          </p>

          <p v-if="props.product.lastPdfUpload" class="text-gray-600 dark:text-gray-300">
            <b>🕒 Último upload:</b> {{ new Date(props.product.lastPdfUpload).toLocaleString("pt-BR") }}
          </p>

          <p v-if="props.product.reasonDateTime" class="text-gray-600 dark:text-gray-300">
            <b>📅 Última aprovação/recusa:</b> {{ new Date(props.product.reasonDateTime).toLocaleString("pt-BR") }}
          </p>
        </div>
      </div>

      <!-- Aprovação -->
      <div v-if="canManageEvidency && props.product.pdf_path" class="mt-8 flex gap-4">
        <Button class="bg-green-600 hover:bg-green-700 flex-1" v-if="props.product.confirmEvidency !== 'Aprovado'"
          @click="showApproveModal = true">
          ✅ Aprovar Evidência
        </Button>

        <Button v-if="currentUserType === 'ADM' || props.product.confirmEvidency !== 'Aprovado'"
          class="bg-red-600 hover:bg-red-700 flex-1" @click="showRejectModal = true">
          ❌ Recusar Evidência
        </Button>
      </div>

      <div v-else-if="currentUserType === 'Usuario Plus'"
        class="mt-6 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-sm">
        ⚠️ Você não pode aprovar ou reprovar evidências enviadas por você mesmo.
      </div>

      <!-- Modal Aprovação -->
      <transition name="fade">
        <div v-if="showApproveModal"
          class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl w-[420px] text-center">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3">Confirmar Aprovação</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
              Deseja realmente aprovar esta evidência? Essa ação registrará seu nome como aprovador.
            </p>
            <div class="flex justify-center gap-3">
              <Button class="bg-blue-100 hover:bg-blue-200 text-blue-800" @click="showApproveModal = false">Cancelar</Button>
              <Button class="bg-green-600 hover:bg-green-700" @click="approveEvidency">Confirmar</Button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Modal Recusa -->
      <transition name="fade">
        <div v-if="showRejectModal"
          class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl w-[420px]">
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-3 text-center">Motivo da Recusa</h2>
            <textarea v-model="evidencyForm.reason" rows="3"
              class="w-full border border-gray-300 dark:border-gray-700 rounded-lg p-2 text-sm text-gray-700 dark:text-gray-300 dark:bg-gray-800 mb-4 focus:ring-2 focus:ring-red-400 focus:outline-none resize-none"
              placeholder="Descreva o motivo da reprovação..."></textarea>
            <div class="flex justify-end gap-3">
              <Button class="bg-blue-100 hover:bg-blue-200 text-blue-800" @click="showRejectModal = false">Cancelar</Button>
              <Button class="bg-red-600 hover:bg-red-700" @click="rejectEvidency">Confirmar Recusa</Button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Modal Histórico -->
      <transition name="fade">
        <div v-if="showHistoryModal"
          class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50">
          <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-8 w-[1000px] max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-5">
              <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                📜 Histórico de Evidências Reprovadas
              </h2>
              <Button class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs px-3 py-1"
                @click="showHistoryModal = false">Fechar</Button>
            </div>

            <div v-if="isLoadingHistory" class="text-center text-gray-500 dark:text-gray-400 py-6">
              Carregando histórico...
            </div>

            <div v-else-if="pdfHistory.length > 0" class="overflow-x-auto">
              <Table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg">
                <TableHeader class="bg-gray-100 dark:bg-gray-800">
                  <TableRow>
                    <TableHead class="px-3 py-2 text-left">📅 Data Recusa</TableHead>
                    <TableHead class="px-3 py-2 text-left">👤 Enviado por</TableHead>
                    <TableHead class="px-3 py-2 text-left">🧍 Reprovado por</TableHead>
                    <TableHead class="px-3 py-2 text-center">💬 Motivo</TableHead>
                    <TableHead class="px-3 py-2 text-center">📄 PDF</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="item in pdfHistory" :key="item.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <TableCell class="px-3 py-2">{{ new Date(item.reasonDateTime).toLocaleString("pt-BR") }}</TableCell>
                    <TableCell class="px-3 py-2">{{ item.evidencyUploader || '—' }}</TableCell>
                    <TableCell class="px-3 py-2">{{ item.evidencyApprover || '—' }}</TableCell>
                    <TableCell class="px-3 py-2 text-center">
                      <Button class="bg-blue-100 hover:bg-blue-200 text-blue-700 dark:bg-blue-900 dark:hover:bg-blue-800 px-2 py-1"
                        @click="openReasonModal(item.reason)">
                        <Eye class="inline w-4 h-4" />
                      </Button>
                    </TableCell>
                    <TableCell class="px-3 py-2 text-center">
                      <a v-if="item.pdf_path" :href="`/storage/${item.pdf_path}`" target="_blank"
                        class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-xs font-semibold transition">
                        <FileText class="h-4 w-4 mr-1" /> PDF
                      </a>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-6">
              Nenhum histórico de recusa encontrado.
            </div>
          </div>
        </div>
      </transition>

      <!-- Modal Motivo -->
      <transition name="fade">
        <div v-if="showReasonModal"
          class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 backdrop-blur-sm">
          <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl w-[450px] max-w-[90%] text-center max-h-[80vh] overflow-y-auto">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-4">💬 Motivo da Recusa</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line mb-6 break-words">
              {{ selectedReason }}
            </p>
            <Button class="bg-blue-100 hover:bg-blue-200 text-blue-800" @click="showReasonModal = false">Fechar</Button>
          </div>
        </div>
      </transition>
    </div>
  </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
