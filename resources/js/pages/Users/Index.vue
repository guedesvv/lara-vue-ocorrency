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

// === Controle de edição ===
const editedUsers = ref<{ [key: number]: Partial<User> }>({})

const handleEdit = (user: User, field: keyof User, value: string) => {
  if (!editedUsers.value[user.id]) editedUsers.value[user.id] = {}
  editedUsers.value[user.id][field] = value
}

const saveChanges = (user: User) => {
  const changes = editedUsers.value[user.id]
  if (!changes) return

  router.put(route("users.update", { user: user.id }), {
    ...user,
    ...changes,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      delete editedUsers.value[user.id]
    },
  })
}

// === Controle de exclusão ===
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

      <div
        class="rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg bg-white dark:bg-gray-900 overflow-x-auto">
        <table class="w-full border-collapse">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold">ID</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Nome</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Tipo de Usuário</th>
              <th class="px-4 py-3 text-left text-sm font-semibold">Aprovar Acesso</th>
              <th class="px-4 py-3 text-center text-sm font-semibold">Ações</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="user in props.users" :key="user.id"
              class="divide-y divide-gray-200 dark:divide-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">

              <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ user.id }}</td>

              <!-- Nome editável -->
              <td class="px-4 py-3">
                <input
                  :value="editedUsers[user.id]?.name ?? user.name"
                  @input="handleEdit(user, 'name', $event.target.value)"
                  class="border rounded-md p-1 px-2 w-full text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
              </td>

              <!-- Email editável -->
              <td class="px-4 py-3">
                <input
                  :value="editedUsers[user.id]?.email ?? user.email"
                  @input="handleEdit(user, 'email', $event.target.value)"
                  class="border rounded-md p-1 px-2 w-full text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
              </td>

              <!-- Tipo -->
              <td class="px-4 py-3">
                <select
                  :value="editedUsers[user.id]?.userType ?? user.userType"
                  @change="handleEdit(user, 'userType', $event.target.value)"
                  class="border rounded-md p-1 px-2 w-full text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none">
                  <option value="ADM">ADM</option>
                  <option value="Usuario Plus">Usuário Plus</option>
                  <option value="Usuario Padrão">Usuário Padrão</option>
                </select>
              </td>

              <!-- Aprovação -->
              <td class="px-4 py-3">
                <select
                  :value="editedUsers[user.id]?.ApproveUser ?? user.ApproveUser"
                  @change="handleEdit(user, 'ApproveUser', $event.target.value)"
                  class="border rounded-md p-1 px-2 w-full text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none">
                  <option value="Sim">Sim</option>
                  <option value="Nao">Não</option>
                </select>
              </td>

              <!-- Ações -->
              <td class="px-4 py-3 text-center space-x-2">
                <button
                  v-if="editedUsers[user.id]"
                  @click="saveChanges(user)"
                  class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition">
                  💾 Salvar
                </button>
                <button
                  @click="confirmDelete(user)"
                  class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-md transition">
                  🗑️ Excluir
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal de Exclusão -->
      <transition name="fade">
        <div
          v-if="showDeleteModal"
          class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
        >
          <transition name="popup">
            <div
              class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 w-[420px] text-center"
            >
              <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                ⚠️ Confirmar Exclusão
              </h2>
              <p class="text-gray-600 dark:text-gray-300 mb-6">
                Tem certeza que deseja excluir o usuário
                <b>{{ userToDelete?.name }}</b> ({{ userToDelete?.email }})?
              </p>
              <div class="flex justify-center gap-3">
                <button
                  @click="showDeleteModal = false"
                  class="px-4 py-2 rounded-md bg-gray-400 hover:bg-gray-500 text-white transition"
                >
                  Cancelar
                </button>
                <button
                  @click="deleteUser"
                  class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white transition"
                >
                  Confirmar
                </button>
              </div>
            </div>
          </transition>
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
