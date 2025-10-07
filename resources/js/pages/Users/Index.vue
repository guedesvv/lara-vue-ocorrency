<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from "vue"

interface User {
  id: number
  name: string
  email: string
  userType?: string
  ApproveUser?: string
}

const props = defineProps<{ users: User[] }>()

// Atualiza usuário via Inertia
const updateUser = (user: User, field: string, value: string) => {
  router.put(route("users.update", { user: user.id }), {
    ...user,
    [field]: value,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

// Excluir usuário (com modal de confirmação)
const userToDelete = ref<User | null>(null)
const showDeleteModal = ref(false)

const confirmDelete = (user: User) => {
  userToDelete.value = user
  showDeleteModal.value = true
}

const deleteUser = () => {
  if (userToDelete.value) {
    router.delete(route("users.destroy", { user: userToDelete.value.id }), {
      onSuccess: () => {
        showDeleteModal.value = false
        userToDelete.value = null
      }
    })
  }
}
</script>

<template>
  <Head title="Controle de Usuários" />

  <AppLayout :breadcrumbs="[{ title: 'Controle de Usuários', href: '/users' }]">
    <div class="p-6">
      <h1 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200">
        👥 Controle de Usuários
      </h1>

      <div class="rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden">
        <table class="w-full border-collapse">
          <thead class="bg-gray-200 dark:bg-gray-800">
            <tr>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Nome</th>
              <th class="px-4 py-2 text-left">Email</th>
              <th class="px-4 py-2 text-left">Tipo de Usuário</th>
              <th class="px-4 py-2 text-left">Aprovar Acesso</th>
              <th class="px-4 py-2 text-center">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in props.users" :key="user.id"
                class="odd:bg-gray-50 even:bg-gray-100 dark:odd:bg-gray-900 dark:even:bg-gray-800">
              
              <td class="px-4 py-2">{{ user.id }}</td>

              <!-- Nome editável -->
              <td class="px-4 py-2">
                <input v-model="user.name" 
                       @blur="updateUser(user, 'name', user.name)"
                       class="border rounded p-1 text-sm w-full 
                              bg-white dark:bg-gray-800 dark:text-gray-200" />
              </td>

              <!-- Email editável -->
              <td class="px-4 py-2">
                <input v-model="user.email" 
                       @blur="updateUser(user, 'email', user.email)"
                       class="border rounded p-1 text-sm w-full 
                              bg-white dark:bg-gray-800 dark:text-gray-200" />
              </td>

              <!-- Tipo -->
              <td class="px-4 py-2">
                <select v-model="user.userType"
                        @change="updateUser(user, 'userType', user.userType!)"
                        class="border rounded p-1 text-sm bg-white dark:bg-gray-800 dark:text-gray-200">
                  <option value="ADM">ADM</option>
                  <option value="Usuario Plus">Usuário Plus</option>
                  <option value="Usuario Padrão">Usuário Padrão</option>
                </select>
              </td>

              <!-- Aprovação -->
              <td class="px-4 py-2">
                <select v-model="user.ApproveUser"
                        @change="updateUser(user, 'ApproveUser', user.ApproveUser!)"
                        class="border rounded p-1 text-sm bg-white dark:bg-gray-800 dark:text-gray-200">
                  <option value="Sim">Sim</option>
                  <option value="Nao">Não</option>
                </select>
              </td>

              <!-- Ações -->
              <td class="px-4 py-2 text-center">
                <button @click="confirmDelete(user)"
                        class="px-3 py-1 rounded bg-red-600 text-white text-sm hover:bg-red-700">
                  Excluir
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal Exclusão -->
      <div v-if="showDeleteModal"
           class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 w-[400px]">
          <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Confirmar Exclusão</h2>
          <p class="text-gray-600 dark:text-gray-300 mb-6">
            Tem certeza que deseja excluir o usuário 
            <b>{{ userToDelete?.name }}</b> ({{ userToDelete?.email }})?
          </p>
          <div class="flex justify-end gap-3">
            <button @click="showDeleteModal = false"
                    class="px-4 py-2 rounded bg-gray-400 text-white hover:bg-gray-500">
              Cancelar
            </button>
            <button @click="deleteUser"
                    class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
              Confirmar
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>