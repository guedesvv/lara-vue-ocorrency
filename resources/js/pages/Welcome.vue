<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <!-- Container principal -->
    <div
        class="relative flex min-h-screen flex-col items-center p-6 text-[#1b1b18] lg:justify-center lg:p-8 overflow-hidden bg-gradient-to-b from-gray-50 via-blue-100 to-gray-200">

        <!-- Blobs decorativos -->
        <div class="absolute -top-20 -left-32 w-96 h-96 bg-blue-700/30 rounded-full blur-3xl pointer-events-none z-0"></div>
        <div class="absolute top-40 -right-40 w-[28rem] h-[28rem] bg-purple-700/20 rounded-full blur-3xl pointer-events-none z-0"></div>

        <!-- Header -->
        <header class="relative z-50 mb-6 w-full max-w-[335px] text-sm lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4 text-gray-800 font-medium">
                <Link v-if="$page.props.auth.user" :href="dashboard()"
                    class="inline-block rounded-sm border border-gray-400/50 px-5 py-1.5 text-sm hover:bg-gray-200/60 transition backdrop-blur-md bg-white/80 shadow-sm">
                    Início
                </Link>
                <template v-else>
                    <Link :href="login()"
                        class="inline-block rounded-sm border border-gray-400/50 px-5 py-1.5 text-sm hover:bg-gray-200/60 transition backdrop-blur-md bg-white/80 shadow-sm">
                        Entrar
                    </Link>
                    <Link :href="register()"
                        class="inline-block rounded-sm border border-gray-400/50 px-5 py-1.5 text-sm hover:bg-gray-200/60 transition backdrop-blur-md bg-white/80 shadow-sm">
                        Registrar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="relative z-50 flex w-full items-center justify-center lg:grow mt-[-120px] pointer-events-none">
            <main
                class="pointer-events-auto flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-xl lg:max-w-4xl lg:flex-row bg-white/90 shadow-2xl backdrop-blur-md border border-gray-200">
                
                <!-- Texto -->
                <div class="flex-1 p-6 lg:p-20 text-gray-800">
                    <h1 class="mb-2 text-3xl font-extrabold tracking-tight">Projeto SIGO</h1>
                    <p class="mb-4 text-base text-gray-600">
                        <b>Si</b>stema de  <b>G</b>estão de  <b>O</b>corrências. <br />
                        Desenvolvido em Laravel
                        <img src="https://laravel.com/img/logomark.min.svg" alt="Laravel Logo"
                            class="inline-block h-5 ml-1 align-middle" />
                    </p>

                    <ul class="mb-6">
                        <li class="flex items-center gap-0 text-sm text-gray-600">
                            <b>Desenvolvimento</b>
                            <a href="https://www.linkedin.com/in/jo%C3%A3o-guedes-40b765186/" target="_blank"
                                class="ml-1 inline-flex items-center space-x-1 font-medium text-blue-900 underline">
                                <span>João Guedes</span>
                                <svg width="10" height="11" viewBox="0 0 10 11" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5">
                                    <path d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001" stroke="currentColor"
                                        stroke-linecap="square" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Imagem -->
                <div class="relative bg-gray-100 w-full lg:w-[438px] flex items-center justify-center">
                    <img src="/images/logo4_welcome.svg" alt="Qualidade, Segurança, Saúde e Meio Ambiente"
                        class="p-10 max-h-[300px] object-contain" />
                </div>
            </main>
        </div>

        <!-- Ondas no rodapé -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none pointer-events-none z-0">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none"
                class="w-full h-32 pointer-events-none">
                <path d="M0,32 C300,120 900,0 1200,96 L1200,120 L0,120 Z" class="fill-blue-950/95"></path>
            </svg>
        </div>
    </div>
</template>
