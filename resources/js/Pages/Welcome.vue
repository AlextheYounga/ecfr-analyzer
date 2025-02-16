<template>
	<div class="flex h-screen bg-gray-100 text-gray-800">
		<!-- Main Content Area -->
		<div class="flex flex-col flex-1 min-w-0">
			<!-- Top Bar / Header -->
			<header class="flex items-center justify-between bg-white shadow px-6 py-4">
				<div>
					<h1 class="text-2xl font-bold">eCFR Analysis Dashboard</h1>
				</div>
			</header>

			<!-- Dashboard Content -->
			<main class="p-6 overflow-auto">
				<!-- Stats Cards Row -->
				<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
					<div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
						<div>
							<p class="text-sm text-gray-500">Total Words</p>
							<p class="text-2xl font-bold">{{ totalWords }}</p>
						</div>
					</div>
				</div>

				<!-- Word Count By Agency -->
				<div class="bg-white shadow rounded-lg p-6">
					<h2 class="text-lg font-semibold mb-4">Word Count By Title</h2>
					<div id="pie-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
						<span class="text-gray-400">Chart Placeholder</span>
					</div>
				</div>

				<!-- Charts Grid -->
				<div class="grid grid-cols-2 md:grid-cols-2 gap-6 pt-6">
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
						<div id="pie-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
							<span class="text-gray-400">Coming Soon</span>
						</div>
					</div>

					<!-- Frequency of Amendments -->
					<!-- <div class="bg-white shadow rounded-lg p-6">
						<h2 class="text-lg font-semibold mb-4">Frequency of Amendments</h2>
						<div class="bg-gray-100 rounded h-80 flex items-center justify-center">
							<span class="text-gray-400">Chart Placeholder</span>
						</div>
					</div> -->

					<!-- Agencies -->
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
import { defineProps, onMounted } from 'vue';
import * as echarts from 'echarts';

const props = defineProps({
	titles: Array,
	totalWords: Number,
	agencies: Array
});

function calculateWordsByTitle() {
	let data = [];

	for (const title of props.titles) {
		const wordPercent = (title.word_count / props.totalWords * 100).toFixed(2);
		console.log(title.name, wordPercent);
		data.push({
			value: wordPercent,
			name: title.name
		});
	}
	return data;
}

// ECharts Pie Chart
onMounted(() => {
	const data = calculateWordsByTitle();
	var chartDom = document.getElementById('pie-chart');
	var myChart = echarts.init(chartDom);
	var option;

	option = {
		// legend: {
		// 	top: 'bottom'
		// },
		toolbox: {
			show: true,
			feature: {
				mark: { show: true },
				dataView: { show: true, readOnly: false },
				restore: { show: true },
				saveAsImage: { show: true }
			}
		},
		series: [
			{
				name: 'Word Count By Title',
				type: 'pie',
				radius: [50, 250],
				center: ['50%', '50%'],
				roseType: 'area',
				itemStyle: {
					borderRadius: 8
				},
				data: data
			}
		]
	};

	option && myChart.setOption(option);
})


</script>
