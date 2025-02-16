<template>
	<div class="flex h-screen bg-gray-100 text-gray-800">
		<!-- Main Content Area -->
		<div class="flex flex-col flex-1 min-w-0">
			<!-- Top Bar / Header -->
			<header class="flex items-center justify-between bg-white shadow px-6 py-4">
				<div>
					<h1 class="text-2xl font-bold">Dashboard</h1>
				</div>
			</header>

			<!-- Dashboard Content -->
			<main class="p-6 overflow-auto">
				<!-- Stats Cards Row -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
					<div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
						<div>
							<p class="text-sm text-gray-500">Total Words</p>
							<p class="text-2xl font-bold">{{ totalWords }}</p>
						</div>
						<div class="bg-blue-100 text-blue-500 p-3 rounded-full">
							<!-- Icon placeholder -->
							<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M9 16h6" />
							</svg>
						</div>
					</div>
				</div>

				<!-- Charts Grid -->
				<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
						<div class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
							<span class="text-gray-400">Chart Placeholder</span>
						</div>
					</div>

					<!-- Frequency of Amendments -->
					<!-- <div class="bg-white shadow rounded-lg p-6">
						<h2 class="text-lg font-semibold mb-4">Frequency of Amendments</h2>
						<div class="bg-gray-100 rounded h-80 flex items-center justify-center">
							<span class="text-gray-400">Chart Placeholder</span>
						</div>
					</div> -->
				</div>
			</main>
		</div>
	</div>
</template>

<script setup>
import { ref } from 'vue'
import { defineProps } from 'vue';

const props = defineProps({
	titles: Array,
	totalWords: Number
});


// A simple ref to toggle the user menu
const showUserMenu = ref(false);

function toggleUserMenu() {
	showUserMenu.value = !showUserMenu.value;
}
</script>
