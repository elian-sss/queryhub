<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <div class="text-center mb-8">
            <Link href="/" class="inline-block">
                <div class="flex items-center justify-center gap-3">
                    <div class="w-12 h-12 bg-indigo-600 dark:bg-indigo-500 rounded-lg flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.566.034-1.08.16-1.539.325m-6.4 1.319m6.4 1.319c.554.307.996.742 1.255 1.265m6.4-1.32a48.453 48.453 0 0 1 3.423 6.024m-6.4 1.319c-.554-.307-.996-.742-1.255-1.265m0 0a48.694 48.694 0 0 0-1.255 1.265" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">QueryHub</h1>
                </div>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-lg overflow-hidden sm:rounded-lg border border-gray-200 dark:border-gray-700"
        >
            <slot />
        </div>
    </div>
</template>

