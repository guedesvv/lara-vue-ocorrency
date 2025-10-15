<script setup lang="ts">
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

import { ref, watch } from "vue"
import AppLayout from '@/layouts/AppLayout.vue'
import Label from '@/components/ui/label/Label.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@/types'
import Input from "@/components/ui/input/Input.vue"
import Button from "@/components/ui/button/Button.vue"

import type { DateValue } from "@internationalized/date"
import { DateFormatter, getLocalTimeZone } from "@internationalized/date"
import { cn } from "@/utils"
import { Calendar as CalendarIcon } from "lucide-vue-next"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"

// === Configuração de datas ===
const df = new DateFormatter("pt-BR", { dateStyle: "long" })
const calendarValueStart = ref<DateValue>()
const calendarValueDue = ref<DateValue>()

// === Usuário logado ===
const page = usePage()
const currentUser = page.props.auth?.user

// === Formulário ===
const form = useForm({
  cr: "",
  ocorrency: "",
  origin: "",
  action: "",
  startDate: "",
  dueDate: "",
  email: "",
  pdf: null,
  nameCreator: currentUser?.name || "", // 👈 já inclui o nome do criador
})

// === Sincroniza calendários com form ===
watch(calendarValueStart, (val) => {
  form.startDate = val
    ? val.toDate(getLocalTimeZone()).toISOString().split("T")[0]
    : ""
})

watch(calendarValueDue, (val) => {
  form.dueDate = val
    ? val.toDate(getLocalTimeZone()).toISOString().split("T")[0]
    : ""
})

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Registrar Ocorrência", href: "/products/create" },
]

// === Lista de CRs ===
const crList = [
  { value: "16749 - SP - LOG - WAELZHOLZ BRASMETAL" },
  { value: "17542 - MG - LOG - SAMARCO GERMANO NOVO" },
  { value: "17543 - ES - LOG - SAMARCO UBU NOVO" },
  { value: "24178 - ES - LOG - SAMARCO SPOT ES" },
  { value: "24238 - SP - LOG - BRASKEM - ILHAS DE FATURAMENTO" },
  { value: "25458 - SP - TRM - LOCALFRIO GUARUJA (LGH)" },
  { value: "26052 - SP - LOG - CHEVRON ORONITE" },
  { value: "27911 - SP - LOG - BRASKEM ENTAMBORAMENTO" },
  { value: "32470 - SP - LOG - BASF BATISTINI" },
  { value: "35118 - SP - LOG - USIMINAS - EMBALAGEM (ORM)" },
  { value: "35119 - SP - LOG - USIMINAS - SERVICOS PORTUARIOS (ORM)" },
  { value: "35122 - SP - LOG - USIMINAS - EXPED DE PROD ACABADOS (ORM)" },
  { value: "35132 - SP - LOG - USIMINAS - LAMINACAO A QUENTE (ORM)" },
  { value: "35180 - SP - LOG - USIMINAS - GERENCIA DA UNIDADE (ORM)" },
  { value: "40703 - SP - LOG - FLEURY" },
  { value: "43409 - SP - LOG - BASF LOG EXPEDICAO" },
  { value: "44242 - SP - LOG - WAELZHOLZ BRASMETAL LAMINACAO" },
  { value: "44914 - SP - LOG - PIB BRASKEM" },
  { value: "45955 - SP - LOG - BASF AMERICANA" },
  { value: "47287 - SP - IND - GC LOG - LUCAS RODRIGUES" },
  { value: "51115 - SP - LOG - ISA CTEEP" },
  { value: "56115 - MG - LOG - SAMARCO GERMANO PDER" },
  { value: "58492 - SP - LOG - FLEURY ASSIST ADM" },
  { value: "62338 - MG - LOG - ANGLO AMERICAN - MATO DENTRO" },
  { value: "68807 - SP - LOG - USIMINAS CUBATAO SPOT" },
  { value: "68820 - SP - IND - APOIO ADM GR LUCAS RODRIGUES" },
  { value: "75999 - SP - IND - APRENDIZ LUCAS RODRIGUES - CUBATAO" },
  { value: "76047 - SP - IND - APRENDIZ REGIONAL LUCAS RODRIGUES" },
  { value: "76357 - SP - LOG - USIMINAS MOV. PORTO CUBATAO" },
  { value: "77943 - SP - LOG - WAELZHOLZ BRASMETAL DIADEMA - C25" },
  { value: "79012 - SP - LOG - ATLAS COPCO BARUERI" },
  { value: "82840 - SP - IND - APOIO ESTRATEGICO GR LUCAS RODRIGUES" },
]


// === Envio do formulário ===
const handleSubmit = () => {
  form.post(route("products.store"), { forceFormData: true })
}
</script>

<template>
  <Head title="Nova Ocorrência" />

  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex justify-center py-8 px-4">
      <form
        @submit.prevent="handleSubmit"
        class="bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-8 w-full max-w-2xl space-y-6 border border-gray-200 dark:border-gray-700"
      >
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 text-center mb-4">
          Registrar Nova Ocorrência
        </h2>

        <!-- CR -->
        <div class="space-y-2">
          <Label for="cr">CR</Label>
          <Select v-model="form.cr">
            <SelectTrigger id="cr" class="w-full">
              <SelectValue placeholder="Selecione o CR..." />
            </SelectTrigger>
            <SelectContent class="max-h-[250px] overflow-y-auto">
              <SelectGroup>
                <SelectLabel>Lista de CRs</SelectLabel>
                <SelectItem
                  v-for="cr in crList"
                  :key="cr.value"
                  :value="cr.value"
                >
                  {{ cr.value }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
          <p class="text-sm text-red-600" v-if="form.errors.cr">{{ form.errors.cr }}</p>
        </div>

        <!-- Tipo de Ocorrência -->
        <div class="space-y-2">
          <Label for="tipo">Tipo de Ocorrência</Label>
          <Select v-model="form.ocorrency">
            <SelectTrigger id="tipo" class="w-full">
              <SelectValue placeholder="Selecione..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Interna (GPS)">Interna (GPS)</SelectItem>
              <SelectItem value="Externa (Cliente)">Externa (Cliente)</SelectItem>
            </SelectContent>
          </Select>
          <p class="text-sm text-red-600" v-if="form.errors.ocorrency">{{ form.errors.ocorrency }}</p>
        </div>

        <!-- Origem -->
        <div class="space-y-2">
          <Label for="origin">Origem</Label>
          <Select v-model="form.origin">
            <SelectTrigger id="origin" class="w-full">
              <SelectValue placeholder="Selecione..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Acidente/Incidente">Acidente/Incidente</SelectItem>
              <SelectItem value="RAC">RAC</SelectItem>
              <SelectItem value="Reuniões">Reuniões</SelectItem>
              <SelectItem value="Relatórios">Relatórios</SelectItem>
              <SelectItem value="Inspeções">Inspeções</SelectItem>
              <SelectItem value="Auditorias">Auditorias</SelectItem>
              <SelectItem value="Exigências Legais e Normativas">
                Exigências Legais e Normativas
              </SelectItem>
              <SelectItem value="Mudanças no Processo ou Equipamento">
                Mudanças no Processo ou Equipamento
              </SelectItem>
              <SelectItem value="Programas de SSO">Programas de SSO</SelectItem>
              <SelectItem value="Outros">Outros</SelectItem>
            </SelectContent>
          </Select>
          <p class="text-sm text-red-600" v-if="form.errors.origin">{{ form.errors.origin }}</p>
        </div>

        <!-- Ação -->
        <div class="space-y-2">
          <Label for="acao">Ação</Label>
          <Input
            v-model="form.action"
            id="acao"
            type="text"
            placeholder="Descreva a ação de forma objetiva..."
            maxlength="255"
            class="w-full"
          />
          <p class="text-sm text-red-600" v-if="form.errors.action">{{ form.errors.action }}</p>
        </div>

        <!-- Datas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>Data de Início</Label>
            <Popover>
              <PopoverTrigger as-child>
                <Button variant="outline" class="w-full justify-start text-left font-normal">
                  <CalendarIcon class="mr-2 h-4 w-4" />
                  {{
                    calendarValueStart
                      ? df.format(calendarValueStart.toDate(getLocalTimeZone()))
                      : "Selecione uma data"
                  }}
                </Button>
              </PopoverTrigger>
              <PopoverContent class="p-0">
                <Calendar v-model="calendarValueStart" initial-focus />
              </PopoverContent>
            </Popover>
          </div>

          <div class="space-y-2">
            <Label>Prazo</Label>
            <Popover>
              <PopoverTrigger as-child>
                <Button variant="outline" class="w-full justify-start text-left font-normal">
                  <CalendarIcon class="mr-2 h-4 w-4" />
                  {{
                    calendarValueDue
                      ? df.format(calendarValueDue.toDate(getLocalTimeZone()))
                      : "Selecione uma data"
                  }}
                </Button>
              </PopoverTrigger>
              <PopoverContent class="p-0">
                <Calendar v-model="calendarValueDue" initial-focus />
              </PopoverContent>
            </Popover>
          </div>
        </div>

        <!-- E-mail -->
        <div class="space-y-2">
          <Label for="email">Responsável (e-mail)</Label>
          <Input
            v-model="form.email"
            id="email"
            type="email"
            placeholder="Digite o e-mail do responsável..."
            class="w-full"
          />
          <p class="text-sm text-red-600" v-if="form.errors.email">{{ form.errors.email }}</p>
        </div>

        <!-- Botão -->
        <div class="flex justify-center pt-4">
          <Button
            type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 font-semibold rounded-md shadow-md transition-all"
          >
            Registrar Ocorrência
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>