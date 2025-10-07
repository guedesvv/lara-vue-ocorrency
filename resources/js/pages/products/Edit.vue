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
import { Head, useForm } from '@inertiajs/vue3'
import Input from "@/components/ui/input/Input.vue"
import Button from "@/components/ui/button/Button.vue"

import type { DateValue } from "@internationalized/date"
import { DateFormatter, getLocalTimeZone } from "@internationalized/date"
import { cn } from "@/utils"
import { Calendar as CalendarIcon } from "lucide-vue-next"
import { Calendar } from "@/components/ui/calendar"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import { parseDate } from "@internationalized/date"

const df = new DateFormatter("pt-BR", { dateStyle: "long" })

// valor dos calendários
const calendarValueStart = ref<DateValue>()
const calendarValueDue = ref<DateValue>()

interface Product {
    id: number,
    cr: string,
    ocorrency: string,
    origin: string,
    action: string,
    startDate: string,
    dueDate: string,
    email: string,
}

const props = defineProps<{ product: Product }>()

// Formulário com valores default
const form = useForm({
    cr: props.product.cr ?? "",
    ocorrency: props.product.ocorrency ?? "",
    origin: props.product.origin ?? "",
    action: props.product.action ?? "",
    startDate: props.product.startDate ?? "",
    dueDate: props.product.dueDate ?? "",
    email: props.product.email ?? "",
})

// sincroniza valores com o form
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

// submit ajustado
const handleSubmit = () => {
    form.put(route("products.update", { product: props.product.id }))
}

if (props.product.startDate) {
    calendarValueStart.value = parseDate(props.product.startDate)
}
if (props.product.dueDate) {
    calendarValueDue.value = parseDate(props.product.dueDate)
}
</script>

<template>

    <Head title="Editando Ocorrência" />

    <AppLayout :breadcrumbs="[{ title: 'Editando Ocorrência', href: `/products/${props.product.id}/EditPDF` }]">
        <div class="p-4">
            <form @submit.prevent="handleSubmit" class="w-8/12 space-y-4">
                <div class="space-y-3">
                    <Label for="cr">CR</Label>
                    <Select v-model="form.cr">
                        <SelectTrigger id="cr" class="w-[350px]">
                            <SelectValue placeholder="Selecione o CR..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Lista de CRs</SelectLabel>

                                <SelectItem value="16749 - SP - LOG - WAELZHOLZ BRASMETAL">16749 - SP - LOG - WAELZHOLZ
                                    BRASMETAL
                                </SelectItem>
                                <SelectItem value="17542 - MG - LOG - SAMARCO GERMANO NOVO">17542 - MG - LOG - SAMARCO
                                    GERMANO NOVO
                                </SelectItem>
                                <SelectItem value="17543 - ES - LOG - SAMARCO UBU NOVO">17543 - ES - LOG - SAMARCO UBU
                                    NOVO</SelectItem>
                                <SelectItem value="24178 - ES - LOG - SAMARCO SPOT ES">24178 - ES - LOG - SAMARCO SPOT
                                    ES</SelectItem>
                                <SelectItem value="24238 - SP - LOG - BRASKEM - ILHAS DE FATURAMENTO">24238 - SP - LOG -
                                    BRASKEM - ILHAS
                                    DE FATURAMENTO</SelectItem>
                                <SelectItem value="25458 - SP - TRM - LOCALFRIO GUARUJA (LGH)">25458 - SP - TRM -
                                    LOCALFRIO GUARUJA
                                    (LGH)</SelectItem>
                                <SelectItem value="26052 - SP - LOG - CHEVRON ORONITE">26052 - SP - LOG - CHEVRON
                                    ORONITE</SelectItem>
                                <SelectItem value="27911 - SP - LOG - BRASKEM ENTAMBORAMENTO">27911 - SP - LOG - BRASKEM
                                    ENTAMBORAMENTO
                                </SelectItem>
                                <SelectItem value="32470 - SP - LOG - BASF BATISTINI">32470 - SP - LOG - BASF BATISTINI
                                </SelectItem>
                                <SelectItem value="35118 - SP - LOG - USIMINAS - EMBALAGEM (ORM)">35118 - SP - LOG -
                                    USIMINAS -
                                    EMBALAGEM (ORM)</SelectItem>
                                <SelectItem value="35119 - SP - LOG - USIMINAS - SERVICOS PORTUARIOS (ORM)">35119 - SP -
                                    LOG - USIMINAS
                                    - SERVICOS PORTUARIOS (ORM)</SelectItem>
                                <SelectItem value="35122 - SP - LOG - USIMINAS - EXPED DE PROD ACABADOS (ORM)">35122 -
                                    SP - LOG -
                                    USIMINAS - EXPED DE PROD ACABADOS (ORM)</SelectItem>
                                <SelectItem value="35132 - SP - LOG - USIMINAS - LAMINACAO A QUENTE (ORM)">35132 - SP -
                                    LOG - USIMINAS -
                                    LAMINACAO A QUENTE (ORM)</SelectItem>
                                <SelectItem value="35180 - SP - LOG - USIMINAS - GERENCIA DA UNIDADE (ORM)">35180 - SP -
                                    LOG - USIMINAS
                                    - GERENCIA DA UNIDADE (ORM)</SelectItem>
                                <SelectItem value="40703 - SP - LOG - FLEURY">40703 - SP - LOG - FLEURY</SelectItem>
                                <SelectItem value="43409 - SP - LOG - BASF LOG EXPEDICAO">43409 - SP - LOG - BASF LOG
                                    EXPEDICAO
                                </SelectItem>
                                <SelectItem value="44242 - SP - LOG - WAELZHOLZ BRASMETAL LAMINACAO">44242 - SP - LOG -
                                    WAELZHOLZ
                                    BRASMETAL LAMINACAO</SelectItem>
                                <SelectItem value="44914 - SP - LOG - PIB BRASKEM">44914 - SP - LOG - PIB BRASKEM
                                </SelectItem>
                                <SelectItem value="45955 - SP - LOG - BASF AMERICANA">45955 - SP - LOG - BASF AMERICANA
                                </SelectItem>
                                <SelectItem value="47287 - SP - IND - GC LOG - LUCAS RODRIGUES">47287 - SP - IND - GC
                                    LOG - LUCAS
                                    RODRIGUES</SelectItem>
                                <SelectItem value="51115 - SP - LOG - ISA CTEEP">51115 - SP - LOG - ISA CTEEP
                                </SelectItem>
                                <SelectItem value="56115 - MG - LOG - SAMARCO GERMANO PDER">56115 - MG - LOG - SAMARCO
                                    GERMANO PDER
                                </SelectItem>
                                <SelectItem value="58492 - SP - LOG - FLEURY ASSIST ADM">58492 - SP - LOG - FLEURY
                                    ASSIST ADM
                                </SelectItem>
                                <SelectItem value="62338 - MG - LOG - ANGLO AMERICAN - MATO DENTRO">62338 - MG - LOG -
                                    ANGLO AMERICAN -
                                    MATO DENTRO</SelectItem>
                                <SelectItem value="68807 - SP - LOG - USIMINAS CUBATAO SPOT">68807 - SP - LOG - USIMINAS
                                    CUBATAO SPOT
                                </SelectItem>
                                <SelectItem value="68820 - SP - IND - APOIO ADM GR LUCAS RODRIGUES">68820 - SP - IND -
                                    APOIO ADM GR
                                    LUCAS RODRIGUES</SelectItem>
                                <SelectItem value="75999 - SP - IND - APRENDIZ LUCAS RODRIGUES - CUBATAO">75999 - SP -
                                    IND - APRENDIZ
                                    LUCAS RODRIGUES - CUBATAO</SelectItem>
                                <SelectItem value="76047 - SP - IND - APRENDIZ REGIONAL LUCAS RODRIGUES">76047 - SP -
                                    IND - APRENDIZ
                                    REGIONAL LUCAS RODRIGUES</SelectItem>
                                <SelectItem value="76357 - SP - LOG - USIMINAS MOV. PORTO CUBATAO">76357 - SP - LOG -
                                    USIMINAS MOV.
                                    PORTO CUBATAO</SelectItem>
                                <SelectItem value="77943 - SP - LOG - WAELZHOLZ BRASMETAL DIADEMA - C25">77943 - SP -
                                    LOG - WAELZHOLZ
                                    BRASMETAL DIADEMA - C25</SelectItem>
                                <SelectItem value="79012 - SP - LOG - ATLAS COPCO BARUERI">79012 - SP - LOG - ATLAS
                                    COPCO BARUERI
                                </SelectItem>
                                <SelectItem value="82840 - SP - IND - APOIO ESTRATEGICO GR LUCAS RODRIGUES">82840 - SP -
                                    IND - APOIO
                                    ESTRATEGICO GR LUCAS RODRIGUES</SelectItem>

                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <div class="text-sm text-red-600" v-if="form.errors.cr">{{ form.errors.cr }}</div>

                    <Label for="tipo">Tipo de ocorrência</Label>
                    <Select v-model="form.ocorrency">
                        <SelectTrigger id="tipo" class="w-[350px]">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Tipos</SelectLabel>
                                <SelectItem value="Interna (GPS)">Interna (GPS)</SelectItem>
                                <SelectItem value="Externa (Cliente)">Externa (Cliente)</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <div class="text-sm text-red-600" v-if="form.errors.ocorrency">{{ form.errors.ocorrency }}</div>

                    <Label for="origem">Origem</Label>
                    <Select v-model="form.origin">
                        <SelectTrigger id="origem" class="w-[350px]">
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectLabel>Origens</SelectLabel>
                                <SelectItem value="Acidente/Incidente">Acidente/Incidente</SelectItem>
                                <SelectItem value="Exigências Legais e Normativas">Exigências Legais e Normativas
                                </SelectItem>
                                <SelectItem value="Auditorias">Auditorias</SelectItem>
                                <SelectItem value="Outros">Outros</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <div class="text-sm text-red-600" v-if="form.errors.origin">{{ form.errors.origin }}</div>

                    <Label for="acao">Ação</Label>
                    <Input v-model="form.action" id="acao" class="w-[350px]" type="text" placeholder="Descreva a ação"
                        maxlength="255" />
                    <div class="text-sm text-red-600" v-if="form.errors.action">{{ form.errors.action }}</div>

                    <Label for="dateStart">Data de Início</Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline"
                                :class="cn('w-[350px] justify-start text-left font-normal', !calendarValueStart && 'text-muted-foreground')">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ calendarValueStart ? df.format(calendarValueStart.toDate(getLocalTimeZone())) :
                                "Selecione uma data" }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0 border rounded-md shadow-md z-50">
                            <Calendar v-model="calendarValueStart" initial-focus />
                        </PopoverContent>
                    </Popover>
                    <div class="text-sm text-red-600" v-if="form.errors.startDate">{{ form.errors.startDate }}</div>

                    <Label for="dateDue">Prazo</Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline"
                                :class="cn('w-[350px] justify-start text-left font-normal', !calendarValueDue && 'text-muted-foreground')">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{ calendarValueDue ? df.format(calendarValueDue.toDate(getLocalTimeZone())) :
                                "Selecione uma data" }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0 border rounded-md shadow-md z-50">
                            <Calendar v-model="calendarValueDue" initial-focus />
                        </PopoverContent>
                    </Popover>
                    <div class="text-sm text-red-600" v-if="form.errors.dueDate">{{ form.errors.dueDate }}</div>

                    <Label for="email">E-mail</Label>
                    <Input v-model="form.email" id="email" class="w-[350px]" type="email"
                        placeholder="Digite o e-mail" />
                    <div class="text-sm text-red-600" v-if="form.errors.email">{{ form.errors.email }}</div>

                    <br />
                    <Button class="bg-blue-500 hover:bg-blue-600" type="submit">
                        Editar Ocorrência
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
