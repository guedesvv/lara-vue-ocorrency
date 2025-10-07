<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Shield, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage()

const user = page.props.auth?.user

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboards',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Ocorrências',
        href: '/products',
        icon: Shield,
    },
    ...(user?.userType === 'ADM'
        ? [{
            title: 'Controle de Usuários',
            href: '/users',
            icon: Users,
        }]
        : []),
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                    <SidebarMenuButton
                        as-child
                        :class="{
                            'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': page.url.startsWith(item.href),
                            'hover:bg-gray-100 dark:hover:bg-gray-800': !page.url.startsWith(item.href)
                        }"
                    >
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component :is="item.icon" class="w-5 h-5" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
