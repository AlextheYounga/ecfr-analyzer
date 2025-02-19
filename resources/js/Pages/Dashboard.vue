<template>

	<Head title="Dashboard" />
	<div>
		<TransitionRoot as="template" :show="sidebarOpen">
			<Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
				<TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
					<div class="fixed inset-0 bg-gray-900/80" />
				</TransitionChild>

				<div class="fixed inset-0 flex">
					<TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0"
									 leave-to="-translate-x-full">
						<DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
							<TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
								<div class="absolute left-full top-0 flex w-16 justify-center pt-5">
									<button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
										<span class="sr-only">Close sidebar</span>
										<XMarkIcon class="size-6 text-white" aria-hidden="true" />
									</button>
								</div>
							</TransitionChild>
							<!-- Sidebar component, swap this element with another sidebar if you like -->
							<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4 ring-1 ring-white/10">
								<div class="flex h-16 shrink-0 items-center text-white">
									<h1 class="p-4 text-xl">CFR Intel</h1>
								</div>
								<nav class="flex flex-1 flex-col">
									<ul role="list" class="flex flex-1 flex-col gap-y-7">
										<li>
											<ul role="list" class="-mx-2 space-y-1">
												<li v-for="item in navigation" :key="item.name">
													<a :href="item.href" :class="[item.current ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
														<component :is="item.icon" class="size-6 shrink-0" aria-hidden="true" />
														{{ item.name }}
													</a>
												</li>
											</ul>
										</li>
										<li>
											<div class="text-xs/6 font-semibold text-gray-400">External Links</div>
											<ul role="list" class="-mx-2 mt-2 space-y-1">
												<li v-for="link in external" :key="link.name">
													<a :href="link.href" :class="[link.current ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
														<component :is="link.icon" class="size-6 shrink-0" aria-hidden="true" />
														<span class="truncate">{{ link.name }}</span>
													</a>
												</li>
											</ul>
										</li>
									</ul>
								</nav>
							</div>
						</DialogPanel>
					</TransitionChild>
				</div>
			</Dialog>
		</TransitionRoot>

		<!-- Static sidebar for desktop -->
		<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
			<!-- Sidebar component, swap this element with another sidebar if you like -->
			<div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
				<div class="flex h-16 shrink-0 items-centert text-white">
					<h1 class="p-4 text-xl">CFR Intel</h1>
				</div>
				<nav class="flex flex-1 flex-col">
					<ul role="list" class="flex flex-1 flex-col gap-y-7">
						<li>
							<ul role="list" class="-mx-2 space-y-1">
								<li v-for="item in navigation" :key="item.name">
									<a :href="item.href" :class="[item.current ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
										<component :is="item.icon" class="size-6 shrink-0" aria-hidden="true" />
										{{ item.name }}
									</a>
								</li>
							</ul>
						</li>
						<li>
							<div class="text-xs/6 font-semibold text-gray-400">External Links</div>
							<ul role="list" class="-mx-2 mt-2 space-y-1">
								<li v-for="link in external" :key="link.name">
									<a :href="link.href" :class="[link.current ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
										<component :is="link.icon" class="size-6 shrink-0" aria-hidden="true" />
										<span class="truncate">{{ link.name }}</span>
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</nav>
			</div>
		</div>

		<div class="lg:pl-72">
			<div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
				<button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
					<span class="sr-only">Open sidebar</span>
					<Bars3Icon class="size-6" aria-hidden="true" />
				</button>

				<!-- Separator -->
				<div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true" />

				<div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
					<form class="grid flex-1 grid-cols-1" action="/search" method="GET">
						<input type="search" name="search" aria-label="Search" class="col-start-1 row-start-1 block size-full bg-white pl-8 text-base text-gray-900 border-none outline-none placeholder:text-gray-400 sm:text-sm/6" placeholder="Search" />
						<MagnifyingGlassIcon class="pointer-events-none col-start-1 row-start-1 size-5 self-center text-gray-400" aria-hidden="true" />
					</form>
				</div>
			</div>

			<main>
				<div>
					<div class="flex h-screen bg-gray-100 text-gray-800">
						<!-- Main Content Area -->
						<div class="flex flex-col flex-1 min-w-0">
							<!-- Dashboard Content -->
							<main class="p-6 overflow-auto">
								<!-- Word Count By Title -->
								<div class="bg-white shadow rounded-lg p-6">
									<h2 class="text-lg font-semibold mb-4">Word Count By Title</h2>
									<h3 class="text-md mb-4">Total Meaningful Words: <b>{{ totalWords.toLocaleString() }}</b></h3>
									<div id="title-pie-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 80vh;">
										<span class="text-gray-400">Chart Placeholder</span>
									</div>
								</div>

								<!-- Charts Grid -->
								<div class="grid lg:grid-cols-2 md:grid-cols-2 gap-6 pt-6">
									<!-- Word Count by Title (with scrollable list) -->
									<div class="bg-white shadow rounded-lg p-6">
										<h2 class="text-lg font-semibold mb-4">Word Count by Title</h2>
										<!-- Scrollable list container -->
										<div class="overflow-y-auto border rounded-lg p-4 bg-gray-50" style="height: 60vh;">
											<ul class="divide-y divide-gray-200">
												<li v-for="(title, index) in titles" :key="index" class="py-2 flex justify-between">
													<span class="text-gray-700">{{ title.name }}</span>
													<span class="font-semibold text-gray-900">{{ title.word_count.toLocaleString() }}</span>
												</li>
											</ul>
										</div>
									</div>

									<!-- Word Count By Agency -->
									<div class="bg-white shadow rounded-lg p-6">
										<h2 class="text-lg font-semibold mb-4">Word Count By Agency</h2>
										<!-- Scrollable list container -->
										<div class="overflow-y-auto border rounded-lg p-4 bg-gray-50" style="height: 60vh;">
											<ul class="divide-y divide-gray-200">
												<li v-for="(agency, index) in agencies" :key="index" class="py-2 flex justify-between">
													<span class="text-gray-700">{{ agency.name }}</span>
													<span class="font-semibold text-gray-900">{{ agency.word_count.toLocaleString() }}</span>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="bg-white shadow rounded-lg p-6 mt-6">
									<h2 class="text-lg font-semibold mb-4">Word Count By Agency</h2>
									<h3 class="text-md mb-4">Total Agencies <i>(that we know of)</i>: <b>{{ agencyCount }}</b></h3>
									<div id="agency-pie-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
										<span class="text-gray-400">Chart Placeholder</span>
									</div>
								</div>
								<!-- Frequency of Amendments -->
								<div class="bg-white shadow rounded-lg p-6 mt-6">
									<h2 class="text-lg font-semibold mb-4">Number of Amendments</h2>
									<h3 class="text-md mb-4">Total Amendments since 2013 by Title</h3>
									<div id="amendments-bar-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
										<span class="text-gray-400">Chart Placeholder</span>
									</div>
								</div>
							</main>
						</div>
					</div>
				</div>
			</main>
		</div>
	</div>
</template>

<script setup>
import { ref } from 'vue'
import {
	Dialog,
	DialogPanel,
	TransitionChild,
	TransitionRoot,
} from '@headlessui/vue'
import {
	Bars3Icon,
	HomeIcon,
	DocumentIcon,
	XMarkIcon,
	CodeBracketIcon
} from '@heroicons/vue/24/outline'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import { titlePieChart, agencyPieChart, amendmentsBarChart } from '@/charts';

const navigation = [
	{ name: 'Dashboard', href: '/', icon: HomeIcon, current: true },
	{ name: 'Titles', href: '/titles', icon: DocumentIcon, current: false },
]
const external = [
	{ id: 1, name: 'Github', href: 'https://github.com/AlextheYounga/ecfr-analyzer', icon: CodeBracketIcon, current: false },
]

const sidebarOpen = ref(false);

const props = defineProps({
	agencyCount: Number,
	titles: Array,
	totalWords: Number,
	agencies: Array
});

// ECharts Pie Chart
onMounted(() => {
	titlePieChart(props);
	agencyPieChart(props);
	amendmentsBarChart(props);
});
</script>