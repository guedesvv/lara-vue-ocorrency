<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { appearance } from '@/routes';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Configurações de Aparência',
        href: appearance().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Configurações de Aparência" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Configurações de Aparência" description="Atualize suas configurações de aparência" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
