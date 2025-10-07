<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Label from '@/components/ui/label/Label.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Button from "@/components/ui/button/Button.vue"
import { ref } from "vue"

interface Product {
  id: number,
  cr: string,
  pdf_path?: string,
  confirmEvidency?: string,
  reason?: string,
  evidencyApprover?: string,
  evidencyUpploader?: string,
  lastPdfUpload?: string,
}

const props = defineProps<{ product: Product }>()
const page = usePage()
const currentUserType = page.props.auth?.user?.userType

// Formulário só para PDF
const form = useForm({
  pdf: null as File | null,
})

// Formulário para aprovação/reprovação
const evidencyForm = useForm({
  confirmEvidency: "",
  reason: "",
})

// Captura o arquivo selecionado
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    form.pdf = target.files[0]
  } else {
    form.pdf = null
  }
}

// Envia PDF
const handleSubmit = () => {
  if (!form.pdf) {
    alert("Selecione um arquivo PDF antes de enviar.")
    return
  }

  form.post(route("products.updatePdf", { product: props.product.id }), {
    forceFormData: true,
  })
}

// Aprovar evidência
const approveEvidency = () => {
  if (!confirm("Tem certeza que deseja aprovar esta evidência?")) {
    return
  }

  evidencyForm.transform((data) => ({
    ...data,
    confirmEvidency: "Aprovado",
    reason: "",
  }))
  evidencyForm.put(route("products.approveEvidency", { product: props.product.id }))
}

// Modal recusar
const showRejectModal = ref(false)

// Recusar evidência
const rejectEvidency = () => {
  evidencyForm.transform((data) => ({
    ...data,
    confirmEvidency: "Reprovado",
  }))
  evidencyForm.put(route("products.rejectEvidency", { product: props.product.id }), {
    onSuccess: () => {
      showRejectModal.value = false
    }
  })
}

// Regras de permissão para upload
const canUpload = () => {
  if (currentUserType === 'ADM') return true // ADM sempre pode
  if (!props.product.pdf_path) return true // Não existe PDF → pode enviar
  if (props.product.confirmEvidency === "Reprovado") return true // Se foi reprovado → pode reenviar
  return false // Caso contrário, não pode
}
</script>

<template>

  <Head title="Atualizar Evidência" />

  <AppLayout :breadcrumbs="[{ title: 'Editar PDF', href: `/products/${props.product.id}/pdf` }]">
    <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-lg w-8/12 mx-auto">
      <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-6">
        Gestão de Evidência
      </h1>

      <!-- Upload PDF -->
      <form v-if="canUpload()" @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <Label for="pdf">Selecione Evidência (PDF)</Label>
          <input id="pdf" type="file" accept="application/pdf" @change="handleFileChange"
            class="w-full border rounded p-2 text-sm mt-2 bg-gray-50 dark:bg-gray-800 dark:text-gray-200" />
          <div class="text-sm text-red-600 mt-1" v-if="form.errors.pdf">{{ form.errors.pdf }}</div>
        </div>

        <Button class="bg-blue-600 hover:bg-blue-700 w-full" type="submit">
          📤 Enviar Evidência
        </Button>
      </form>

      <!-- Caso o usuário não possa reenviar -->
      <div v-else class="p-4 mt-4 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        ⚠️ Já existe uma evidência anexada. Você não pode substituir este arquivo.
      </div>

      <!-- PDF atual e status -->
      <div v-if="props.product.pdf_path" class="mt-6 space-y-4">
        <!-- Link para visualizar -->
        <a :href="`/storage/${props.product.pdf_path}`" target="_blank" class="flex items-center gap-2 px-4 py-3 rounded-md border border-red-300 bg-red-50 hover:bg-red-100 
                 text-red-700 font-medium transition">
          📄 Visualizar Evidência Atual
        </a>

        <!-- Status -->
        <div class="mt-2 text-sm">
          <span class="px-2 py-1 text-xs rounded-full" :class="props.product.confirmEvidency === 'Aprovado'
            ? 'bg-green-100 text-green-700'
            : props.product.confirmEvidency === 'Reprovado'
              ? 'bg-red-100 text-red-700'
              : 'bg-yellow-100 text-yellow-700'">
            {{ props.product.confirmEvidency ?? 'Pendente Aprovação' }}
          </span>
        </div>

        <!-- Informações adicionais -->
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg shadow-sm space-y-2 text-sm">
          <!-- Se for aprovado -->
          <p v-if="props.product.confirmEvidency === 'Aprovado' && props.product.evidencyApprover"
            class="text-green-700 dark:text-green-400">
            <b>✅ Aprovado por:</b> {{ props.product.evidencyApprover }}
          </p>

          <!-- Se for reprovado -->
          <div v-if="props.product.confirmEvidency === 'Reprovado' && props.product.evidencyApprover"
            class="text-red-700 dark:text-red-400">
            <p><b>Recusado por:</b> {{ props.product.evidencyApprover }}</p>
            <p v-if="props.product.reason" class="mt-1 text-red-600 dark:text-red-400">
              <b>❌ Motivo:</b> {{ props.product.reason }}
            </p>
          </div>

          <!-- Quem enviou -->
          <p v-if="props.product.evidencyUpploader" class="text-blue-700 dark:text-blue-400">
            <b>📤 Enviado por:</b> {{ props.product.evidencyUpploader }}
          </p>

          <!-- Quando enviou -->
          <p v-if="props.product.lastPdfUpload" class="text-gray-600 dark:text-gray-300">
            <b>🕒 Último upload:</b> {{ new Date(props.product.lastPdfUpload).toLocaleString("pt-BR") }}
          </p>
        </div>
      </div>

      <!-- Aprovação/Reprovação -->
      <div v-if="currentUserType === 'ADM' || currentUserType === 'Usuario Plus'" class="mt-8 flex gap-4">
        <!-- Botão de Aprovar -->
        <Button class="bg-green-600 hover:bg-green-700 flex-1" @click="approveEvidency"
          v-if="props.product.confirmEvidency !== 'Aprovado'">
          Aprovar Evidência
        </Button>

        <!-- Botão de Recusar -->
        <Button
          v-if="currentUserType === 'ADM' || (currentUserType === 'Usuario Plus' && props.product.confirmEvidency !== 'Aprovado')"
          class="bg-red-600 hover:bg-red-700 flex-1" @click="showRejectModal = true">
          Recusar Evidência
        </Button>
      </div>

      <!-- Modal Recusa -->
      <div v-if="showRejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-[420px] shadow-lg">
          <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Motivo da Recusa</h2>

          <textarea v-model="evidencyForm.reason" rows="3"
            class="w-full border rounded p-2 text-sm text-gray-700 dark:text-gray-300 dark:bg-gray-800 mb-4"
            placeholder="Digite o motivo da recusa..."></textarea>

          <div class="flex justify-end gap-3">
            <Button class="bg-gray-400 hover:bg-gray-500" @click="showRejectModal = false">Cancelar</Button>
            <Button class="bg-red-600 hover:bg-red-700" @click="rejectEvidency">Confirmar Recusa</Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>