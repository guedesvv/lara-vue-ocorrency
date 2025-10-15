<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
  SidebarMenu, SidebarMenuButton, SidebarMenuItem
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Shield, Users, ChevronRight } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage()
const user = page.props.auth?.user

const mainNavItems: NavItem[] = [
  { title: 'Estatísticas', href: '/dashboard', icon: LayoutGrid },
  { title: 'Ocorrências', href: '/products', icon: Shield },
  ...(user?.userType === 'ADM' ? [{ title: 'Controle de Usuários', href: '/users', icon: Users }] : []),
]

function isActive(href: string) {
  return page.url.startsWith(href)
}
</script>

<template>
  <!-- Sidebar com topo translúcido e conteúdo com contraste suave -->
  <Sidebar collapsible="icon" variant="inset">
    <!-- HEADER: vidro/blur + borda sutil -->
    <SidebarHeader class="sticky top-0 z-10 bg-white/80 dark:bg-gray-950/80 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-gray-950/60 border-b border-gray-200/60 dark:border-white/10">
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child class="!px-3">
            <Link :href="dashboard()" class="flex items-center gap-2">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <!-- CONTENT: seção principal -->
    <SidebarContent class="pt-3">
      
      <SidebarMenu>
        <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
          <SidebarMenuButton
            as-child
            :aria-current="isActive(item.href) ? 'page' : undefined"
            :class="[
              'group relative !px-2.5 rounded-xl border transition-all duration-200',
              isActive(item.href)
                ? 'bg-white dark:bg-gray-900 border-blue-200/70 dark:border-blue-800/40 shadow-[0_8px_20px_rgba(59,130,246,0.15)]'
                : 'bg-transparent border-transparent hover:bg-blue-50/60 dark:hover:bg-blue-900/20 hover:border-blue-200/60 dark:hover:border-blue-800/40'
            ]"
          >
            <Link :href="item.href" class="flex items-center gap-2.5 py-1.5 pr-2 pl-3 relative overflow-hidden" :title="item.title">
              <!-- Indicador à esquerda (rail) -->
              <span
                class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1.5 rounded-r"
                :class="isActive(item.href) ? 'bg-blue-600 dark:bg-blue-500' : 'bg-transparent group-hover:bg-blue-400/70 dark:group-hover:bg-blue-400/60'"
                aria-hidden="true"
              />

              <!-- Ícone -->
              <span class="icon-wrap grid place-items-center rounded-lg w-8 h-8 transition-transform group-hover:scale-[1.06]">
                <component :is="item.icon" class="w-5 h-5 icon" />
              </span>

              <!-- Rótulo -->
              <span class="truncate font-medium text-slate-700 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white">
                {{ item.title }}
              </span>

              <!-- Chevon decorativo no hover/ativo -->
              <ChevronRight
                class="ml-auto w-4 h-4 opacity-0 translate-x-[-4px] transition-all group-hover:opacity-80 group-hover:translate-x-0"
              />

              <!-- Glow de destaque sutil (efeito radial) -->
              <span
                class="pointer-events-none absolute -left-6 top-1/2 -translate-y-1/2 h-16 w-16 rounded-full blur-xl"
                :class="isActive(item.href) ? 'bg-blue-500/10 dark:bg-blue-400/10' : 'group-hover:bg-blue-400/10'"
                aria-hidden="true"
              />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarContent>

    <!-- FOOTER: divisória + nav extra + usuário -->
    <SidebarFooter class="mt-auto border-t border-gray-200/60 dark:border-white/10">
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>

<style scoped>
.icon { stroke-width: 1.9; filter: drop-shadow(0 0 0 rgba(0,0,0,0)); }

/* Halo sutil ao passar o mouse */
.group:hover .icon { filter: drop-shadow(0 2px 6px rgba(59,130,246,0.25)); }

/* Ajustes de contraste no ativo */
:deep([aria-current='page']) .icon { filter: drop-shadow(0 2px 8px rgba(59,130,246,0.35)); }

/* Suaviza o foco via teclado */
:deep(a:focus-visible) {
  outline: 2px solid rgba(37,99,235,.55);
  outline-offset: 2px;
  border-radius: 12px;
}
</style>
