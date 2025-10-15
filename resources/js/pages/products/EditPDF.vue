<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Label from '@/components/ui/label/Label.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Button from "@/components/ui/button/Button.vue"
import { ref, computed } from "vue"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Eye } from 'lucide-vue-next'

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
const showSuccessModal = ref(false)
const successMessage = ref("")
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
  form.post(route("products.updatePdf", { product: props.product.id }), {
    forceFormData: true,
    onSuccess: () => {
      successMessage.value = "PDF enviado com sucesso!"
      showSuccessModal.value = true
    }
  })
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
      successMessage.value = "Evidência aprovada com sucesso!"
      showSuccessModal.value = true
    },
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
      successMessage.value = "Evidência reprovada e histórico salvo!"
      showSuccessModal.value = true
    },
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

  <Head title="Gestão de Evidência" />

  <AppLayout :breadcrumbs="[{ title: 'Gestão de Evidência', href: `/products/${props.product.id}/pdf` }]">
    <br></br>
    <div
      class="p-8 bg-white dark:bg-gray-900 rounded-3xl shadow-lg w-10/12 mx-auto border border-gray-200 dark:border-gray-800 transition-all duration-300">


      <!-- Cabeçalho -->
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Gestão de Evidência</h1>
        <Button class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg shadow"
          @click="showHistoryModal = true; loadHistory()">Histórico</Button>
      </div>

      <!-- Upload -->
      <form v-if="canUpload()" @submit.prevent="handleSubmit" class="space-y-4">
        <Label for="pdf">Selecione Evidência (PDF)</Label>
        <input id="pdf" type="file" accept="application/pdf" @change="handleFileChange"
          class="w-full border border-gray-300 rounded-lg p-3 text-sm mt-2 bg-gray-50 dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400" />
        <Button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg shadow transition-all">
          Enviar Evidência
        </Button>
      </form>

      <div v-else
        class="p-4 mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
        ⚠️ Já existe uma evidência anexada. Você não pode substituir este arquivo.
      </div>

      <!-- PDF atual -->
      <div v-if="props.product.pdf_path" class="mt-8 space-y-5">
        <a :href="`/storage/${props.product.pdf_path}`" target="_blank"
          class="block text-center py-3 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold transition">
          Visualizar Evidência Atual
        </a>

        <div class="text-center">
          <span class="px-3 py-1 text-xs rounded-full font-semibold" :class="props.product.confirmEvidency === 'Aprovado'
            ? 'bg-green-100 text-green-800'
            : props.product.confirmEvidency === 'Reprovado'
              ? 'bg-red-100 text-red-800'
              : 'bg-yellow-100 text-yellow-800'">
            {{ props.product.confirmEvidency ?? 'Pendente Aprovação' }}
          </span>
        </div>

        <div
          class="mt-4 p-5 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-sm leading-relaxed space-y-1">
          <p v-if="props.product.confirmEvidency === 'Aprovado' && props.product.evidencyApprover"
            class="text-green-700 dark:text-green-400">
            <b>Aprovado por:</b> {{ props.product.evidencyApprover }}
          </p>

          <div v-if="props.product.confirmEvidency === 'Reprovado'" class="text-red-700 dark:text-red-400 space-y-1">
            <p><b>Recusado por:</b> {{ props.product.evidencyApprover }}</p>
            <p v-if="props.product.reason"><b>Motivo:</b> {{ props.product.reason }}</p>
          </div>

          <p v-if="props.product.evidencyUpploader" class="text-blue-700 dark:text-blue-400">
            <b>Enviado por:</b> {{ props.product.evidencyUpploader }}
          </p>

          <p v-if="props.product.lastPdfUpload" class="text-gray-600 dark:text-gray-300">
            <b>Último upload:</b> {{ new Date(props.product.lastPdfUpload).toLocaleString("pt-BR") }}
          </p>

          <p v-if="props.product.reasonDateTime" class="text-gray-600 dark:text-gray-300">
            <b>Última atualização:</b> {{ new Date(props.product.reasonDateTime).toLocaleString("pt-BR") }}
          </p>
        </div>
      </div>

      <!-- Ações -->
      <div v-if="canManageEvidency && props.product.pdf_path" class="mt-10 flex gap-4">
        <Button class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg shadow"
          v-if="props.product.confirmEvidency !== 'Aprovado'" @click="showApproveModal = true">
          Aprovar Evidência
        </Button>

        <Button v-if="currentUserType === 'ADM' || props.product.confirmEvidency !== 'Aprovado'"
          class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg shadow" @click="showRejectModal = true">
          Recusar Evidência
        </Button>
      </div>

      <!-- Modal Aprovação -->
      <transition name="slide-up">
        <div v-if="showApproveModal" class="modal">
          <div class="modal-box border-t-4 border-green-500">
            <h2>Confirmar Aprovação</h2>
            <p>Deseja realmente aprovar esta evidência?</p>
            <div class="flex justify-center gap-3 mt-6">
              <Button class="bg-gray-200 hover:bg-gray-300 text-gray-700"
                @click="showApproveModal = false">Cancelar</Button>
              <Button class="bg-green-600 hover:bg-green-700 text-white" @click="approveEvidency">Confirmar</Button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Modal Recusa -->
      <transition name="slide-up">
        <div v-if="showRejectModal" class="modal">
          <div class="modal-box w-[420px] border-t-4 border-red-500">
            <h2>Motivo da Recusa</h2>
            <textarea v-model="evidencyForm.reason" rows="3"
              class="w-full border border-gray-300 rounded-lg p-2 text-sm mt-3 focus:ring-2 focus:ring-red-400 resize-none"></textarea>
            <div class="flex justify-end gap-3 mt-5">
              <Button class="bg-gray-200 hover:bg-gray-300 text-gray-700"
                @click="showRejectModal = false">Cancelar</Button>
              <Button class="bg-red-600 hover:bg-red-700 text-white" @click="rejectEvidency">Confirmar</Button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Modal Histórico -->
      <transition name="slide-up">
        <div v-if="showHistoryModal" class="modal bg-black/60 backdrop-blur-sm">
          <div class="modal-box w-[900px] max-h-[85vh] overflow-y-auto relative">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Histórico de Evidências</h2>
              <Button class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded-lg"
                @click="showHistoryModal = false">Fechar</Button>
            </div>

            <div v-if="isLoadingHistory" class="text-center text-gray-500 dark:text-gray-400 py-6">
              Carregando histórico...
            </div>

            <div v-else-if="pdfHistory.length > 0" class="overflow-x-auto">
              <Table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg">
                <TableHeader class="bg-gray-100 dark:bg-gray-800">
                  <TableRow>
                    <TableHead class="px-3 py-2 text-left">Data Recusa</TableHead>
                    <TableHead class="px-3 py-2 text-left">Enviado por</TableHead>
                    <TableHead class="px-3 py-2 text-left">Reprovado por</TableHead>
                    <TableHead class="px-3 py-2 text-center">Motivo</TableHead>
                    <TableHead class="px-3 py-2 text-center">PDF</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="item in pdfHistory" :key="item.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <TableCell class="px-3 py-2">{{ new Date(item.reasonDateTime).toLocaleString("pt-BR") }}</TableCell>
                    <TableCell class="px-3 py-2">{{ item.evidencyUploader || '—' }}</TableCell>
                    <TableCell class="px-3 py-2">{{ item.evidencyApprover || '—' }}</TableCell>
                    <TableCell class="px-3 py-2 text-center">
                      <button @click="openReasonModal(item.reason)"
                        class="inline-flex items-center justify-center p-1 text-blue-600 hover:text-blue-800 transition">
                        <Eye class="w-5 h-5" />
                      </button>
                    </TableCell>
                    <TableCell class="px-3 py-2 text-center">
                      <a v-if="item.pdf_path" :href="`/storage/${item.pdf_path}`" target="_blank"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-xs font-semibold transition">
                        PDF
                      </a>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>

            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-6">
              Nenhum histórico encontrado.
            </div>
          </div>
        </div>
      </transition>


      <!-- Modal Motivo -->
      <transition name="slide-up">
        <div v-if="showReasonModal" class="modal bg-black/60 backdrop-blur-sm">
          <div class="modal-box w-[450px] max-w-[90vw] text-center">
            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-3">Motivo da Recusa</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line break-words mb-6">
              {{ selectedReason || 'Nenhum motivo informado.' }}
            </p>
            <Button class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded" @click="showReasonModal = false">
              Fechar
            </Button>
          </div>
        </div>
      </transition>



      <!-- Modal Sucesso -->
      <transition name="slide-up">
        <div v-if="showSuccessModal" class="modal">
          <div class="modal-box border-t-4 border-green-500">
            <div class="checkmark-container">
              <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" />
                <path class="checkmark-check" fill="none" d="M14 27l7 7 16-16" />
              </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Sucesso!</h2>
            <p class="mt-2 text-gray-600">{{ successMessage }}</p>
            <div class="mt-6">
              <Button class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded"
                @click="showSuccessModal = false">Fechar</Button>
            </div>
          </div>
        </div>


      </transition>

    </div>
  </AppLayout>
</template>

<style scoped>
.modal.bg-black\/60 {
  background-color: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
}

.checkmark-container {
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}

.checkmark {
  width: 80px;
  height: 80px;
  stroke: #22c55e;
  /* verde do Tailwind */
  stroke-width: 3;
  stroke-miterlimit: 10;
  box-shadow: inset 0px 0px 0px #22c55e;
  border-radius: 50%;
  animation: scale-in 0.4s ease-in-out forwards;
}

.checkmark-circle {
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  stroke-width: 3;
  stroke-miterlimit: 10;
  stroke: #22c55e;
  fill: none;
  animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark-check {
  transform-origin: 50% 50%;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
}

@keyframes stroke {
  100% {
    stroke-dashoffset: 0;
  }
}

@keyframes scale-in {

  0%,
  100% {
    transform: none;
  }

  50% {
    transform: scale3d(1.1, 1.1, 1);
  }
}


.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal-box {
  background-color: var(--tw-bg-opacity, #fff);
  color: var(--tw-text-opacity, #111);
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  text-align: center;
  animation: slide-fade-in 0.3s ease;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

@keyframes slide-fade-in {
  0% {
    opacity: 0;
    transform: translateY(25px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
