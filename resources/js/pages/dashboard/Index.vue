<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { BreadcrumbItemInterface } from '@/types/ui';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';

const breadcrumbs: BreadcrumbItemInterface[] = [
	{ transChoiceKey: 'dashboard' }
];
</script>
<template>
	<Head :title="$tChoice('trans.dashboard', 2)" />
	<PageContainer :breadcrumbs="breadcrumbs">
        <HeadingSmall title="Time Line 2" />
        <div class="space-y-8 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent">

            <!-- Item #1 -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-300 group-[.is-active]:bg-emerald-500 text-slate-500 group-[.is-active]:text-emerald-50 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="10">
                        <path fill-rule="nonzero" d="M10.422 1.257 4.655 7.025 2.553 4.923A.916.916 0 0 0 1.257 6.22l2.75 2.75a.916.916 0 0 0 1.296 0l6.415-6.416a.916.916 0 0 0-1.296-1.296Z" />
                    </svg>
                </div>
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded border border-slate-200 shadow">
                    <div class="flex items-center justify-between space-x-2 mb-1">
                        <div class="font-bold text-slate-900">Order Placed</div>
                        <time class="font-caveat font-medium text-indigo-500">08/06/2023</time>
                    </div>
                    <div class="text-slate-500">Pretium lectus quam id leo. Urna et pharetra aliquam vestibulum morbi blandit cursus risus.</div>
                </div>
            </div>

            <!-- Item #2 -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-300 group-[.is-active]:bg-emerald-500 text-slate-500 group-[.is-active]:text-emerald-50 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="10">
                        <path fill-rule="nonzero" d="M10.422 1.257 4.655 7.025 2.553 4.923A.916.916 0 0 0 1.257 6.22l2.75 2.75a.916.916 0 0 0 1.296 0l6.415-6.416a.916.916 0 0 0-1.296-1.296Z" />
                    </svg>
                </div>
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded border border-slate-200 shadow">
                    <div class="flex items-center justify-between space-x-2 mb-1">
                        <div class="font-bold text-slate-900">Order Shipped</div>
                        <time class="font-caveat font-medium text-indigo-500">09/06/2023</time>
                    </div>
                    <div class="text-slate-500">Pretium lectus quam id leo. Urna et pharetra aliquam vestibulum morbi blandit cursus risus.</div>
                </div>
            </div>

            <!-- Item #3 -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-300 group-[.is-active]:bg-emerald-500 text-slate-500 group-[.is-active]:text-emerald-50 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="10">
                        <path fill-rule="nonzero" d="M10.422 1.257 4.655 7.025 2.553 4.923A.916.916 0 0 0 1.257 6.22l2.75 2.75a.916.916 0 0 0 1.296 0l6.415-6.416a.916.916 0 0 0-1.296-1.296Z" />
                    </svg>
                </div>
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded border border-slate-200 shadow">
                    <div class="flex items-center justify-between space-x-2 mb-1">
                        <div class="font-bold text-slate-900">In Transit</div>
                        <time class="font-caveat font-medium text-indigo-500">10/06/2023</time>
                    </div>
                    <div class="text-slate-500">Pretium lectus quam id leo. Urna et pharetra aliquam vestibulum morbi blandit cursus risus.</div>
                </div>
            </div>

            <!-- Item #4 -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-300 group-[.is-active]:bg-emerald-500 text-slate-500 group-[.is-active]:text-emerald-50 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="10">
                        <path fill-rule="nonzero" d="M10.422 1.257 4.655 7.025 2.553 4.923A.916.916 0 0 0 1.257 6.22l2.75 2.75a.916.916 0 0 0 1.296 0l6.415-6.416a.916.916 0 0 0-1.296-1.296Z" />
                    </svg>
                </div>
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded border border-slate-200 shadow">
                    <div class="flex items-center justify-between space-x-2 mb-1">
                        <div class="font-bold text-slate-900">Out of Delivery</div>
                        <time class="font-caveat font-medium text-indigo-500">12/06/2023</time>
                    </div>
                    <div class="text-slate-500">Pretium lectus quam id leo. Urna et pharetra aliquam vestibulum morbi blandit cursus risus.</div>
                </div>
            </div>

            <!-- Item #5 -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group">
                <!-- Icon -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-300 group-[.is-active]:bg-emerald-500 text-slate-500 group-[.is-active]:text-emerald-50 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="12" height="12">
                        <path d="M12 10v2H7V8.496a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V12H0V4.496a.5.5 0 0 1 .206-.4l5.5-4a.5.5 0 0 1 .588 0l5.5 4a.5.5 0 0 1 .206.4V10Z" />
                    </svg>
                </div>
                <!-- Card -->
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded border border-slate-200 shadow">
                    <div class="flex items-center justify-between space-x-2 mb-1">
                        <div class="font-bold text-slate-900">Delivered</div>
                        <time class="font-caveat font-medium text-amber-500">Exp. 12/08/2023</time>
                    </div>
                    <div class="text-slate-500">Pretium lectus quam id leo. Urna et pharetra aliquam vestibulum morbi blandit cursus risus.</div>
                </div>
            </div>

        </div>
        <HeadingSmall title="Time Line 3" />
        <div class="w-full max-w-3xl mx-auto">

            <!-- Vertical Timeline #3 -->
            <div class="space-y-8 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:ml-[8.75rem] md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-300 before:to-transparent">

                <!-- Item #1 -->
                <div class="relative">
                    <div class="md:flex items-center md:space-x-4 mb-3">
                        <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white shadow md:order-1">
                                <svg class="fill-emerald-500" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                                    <path d="M8 0a8 8 0 1 0 8 8 8.009 8.009 0 0 0-8-8Zm0 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
                                </svg>
                            </div>
                            <!-- Date -->
                            <time class="font-caveat font-medium text-xl text-indigo-500 md:w-28">Apr 7, 2024</time>
                        </div>
                        <!-- Title -->
                        <div class="text-slate-500 ml-14"><span class="text-slate-900 font-bold">Mark Mikrol</span> opened the request</div>
                    </div>
                    <!-- Card -->
                    <div class="bg-white p-4 rounded border border-slate-200 text-slate-500 shadow ml-14 md:ml-44">Various versions have evolved over the years, sometimes by accident, sometimes on purpose injected humour and the like.</div>
                </div>

                <!-- Item #2 -->
                <div class="relative">
                    <div class="md:flex items-center md:space-x-4 mb-3">
                        <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white shadow md:order-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                                    <path class="fill-slate-300" d="M14.853 6.861C14.124 10.348 10.66 13 6.5 13c-.102 0-.201-.016-.302-.019C7.233 13.618 8.557 14 10 14c.51 0 1.003-.053 1.476-.143L14.2 15.9a.499.499 0 0 0 .8-.4v-3.515c.631-.712 1-1.566 1-2.485 0-.987-.429-1.897-1.147-2.639Z" />
                                    <path class="fill-slate-500" d="M6.5 0C2.91 0 0 2.462 0 5.5c0 1.075.37 2.074 1 2.922V11.5a.5.5 0 0 0 .8.4l1.915-1.436c.845.34 1.787.536 2.785.536 3.59 0 6.5-2.462 6.5-5.5S10.09 0 6.5 0Z" />
                                </svg>
                            </div>
                            <!-- Date -->
                            <time class="font-caveat font-medium text-xl text-indigo-500 md:w-28">Apr 7, 2024</time>
                        </div>
                        <!-- Title -->
                        <div class="text-slate-500 ml-14"><span class="text-slate-900 font-bold">John Mirkovic</span> commented the request</div>
                    </div>
                    <!-- Card -->
                    <div class="bg-white p-4 rounded border border-slate-200 text-slate-500 shadow ml-14 md:ml-44">If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text.</div>
                </div>

                <!-- Item #3 -->
                <div class="relative">
                    <div class="md:flex items-center md:space-x-4 mb-3">
                        <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white shadow md:order-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                                    <path class="fill-slate-300" d="M14.853 6.861C14.124 10.348 10.66 13 6.5 13c-.102 0-.201-.016-.302-.019C7.233 13.618 8.557 14 10 14c.51 0 1.003-.053 1.476-.143L14.2 15.9a.499.499 0 0 0 .8-.4v-3.515c.631-.712 1-1.566 1-2.485 0-.987-.429-1.897-1.147-2.639Z" />
                                    <path class="fill-slate-500" d="M6.5 0C2.91 0 0 2.462 0 5.5c0 1.075.37 2.074 1 2.922V11.5a.5.5 0 0 0 .8.4l1.915-1.436c.845.34 1.787.536 2.785.536 3.59 0 6.5-2.462 6.5-5.5S10.09 0 6.5 0Z" />
                                </svg>
                            </div>
                            <!-- Date -->
                            <time class="font-caveat font-medium text-xl text-indigo-500 md:w-28">Apr 8, 2024</time>
                        </div>
                        <!-- Title -->
                        <div class="text-slate-500 ml-14"><span class="text-slate-900 font-bold">Vlad Patterson</span> commented the request</div>
                    </div>
                    <!-- Card -->
                    <div class="bg-white p-4 rounded border border-slate-200 text-slate-500 shadow ml-14 md:ml-44">Letraset sheets containing passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Ipsum.</div>
                </div>

                <!-- Item #4 -->
                <div class="relative">
                    <div class="md:flex items-center md:space-x-4 mb-3">
                        <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white shadow md:order-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                                    <path class="fill-slate-300" d="M14.853 6.861C14.124 10.348 10.66 13 6.5 13c-.102 0-.201-.016-.302-.019C7.233 13.618 8.557 14 10 14c.51 0 1.003-.053 1.476-.143L14.2 15.9a.499.499 0 0 0 .8-.4v-3.515c.631-.712 1-1.566 1-2.485 0-.987-.429-1.897-1.147-2.639Z" />
                                    <path class="fill-slate-500" d="M6.5 0C2.91 0 0 2.462 0 5.5c0 1.075.37 2.074 1 2.922V11.5a.5.5 0 0 0 .8.4l1.915-1.436c.845.34 1.787.536 2.785.536 3.59 0 6.5-2.462 6.5-5.5S10.09 0 6.5 0Z" />
                                </svg>
                            </div>
                            <!-- Date -->
                            <time class="font-caveat font-medium text-xl text-indigo-500 md:w-28">Apr 8, 2024</time>
                        </div>
                        <!-- Title -->
                        <div class="text-slate-500 ml-14"><span class="text-slate-900 font-bold">Mila Capentino</span> commented the request</div>
                    </div>
                    <!-- Card -->
                    <div class="bg-white p-4 rounded border border-slate-200 text-slate-500 shadow ml-14 md:ml-44">It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout.</div>
                </div>

                <!-- Item #5 -->
                <div class="relative">
                    <div class="md:flex items-center md:space-x-4 mb-3">
                        <div class="flex items-center space-x-4 md:space-x-2 md:space-x-reverse">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white shadow md:order-1">
                                <svg class="fill-red-500" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                                    <path d="M8 0a8 8 0 1 0 8 8 8.009 8.009 0 0 0-8-8Zm0 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
                                </svg>
                            </div>
                            <!-- Date -->
                            <time class="font-caveat font-medium text-xl text-indigo-500 md:w-28">Apr 9, 2024</time>
                        </div>
                        <!-- Title -->
                        <div class="text-slate-500 ml-14"><span class="text-slate-900 font-bold">Mark Mikrol</span> closed the request</div>
                    </div>
                    <!-- Card -->
                    <div class="bg-white p-4 rounded border border-slate-200 text-slate-500 shadow ml-14 md:ml-44">If you are going to use a passage of Lorem Ipsum!</div>
                </div>

            </div>
            <!-- End: Vertical Timeline #3 -->

        </div>
	</PageContainer>
</template>
