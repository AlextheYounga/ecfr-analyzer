<template>

	<Head title="Dashboard" />

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
				<div class="grid lg:grid-cols-2 md:grid-cols-2 gap-6 pt-6">
					<div class="bg-white shadow rounded-lg p-6">
						<h2 class="text-lg font-semibold mb-4">Word Count By Agency</h2>
						<h3 class="text-md mb-4">Total Agencies <i>(that we know of)</i>: <b>{{ agencyCount }}</b></h3>
						<div id="agency-pie-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
							<span class="text-gray-400">Chart Placeholder</span>
						</div>
					</div>
					<!-- Frequency of Amendments -->
					<div class="bg-white shadow rounded-lg p-6">
						<h2 class="text-lg font-semibold mb-4">Number of Amendments</h2>
						<h3 class="text-md mb-4">Total Amendments since 2013 by Title</h3>
						<div id="amendments-bar-chart" class="bg-gray-100 rounded flex items-center justify-center" style="height: 60vh;">
							<span class="text-gray-400">Chart Placeholder</span>
						</div>
					</div>
				</div>
			</main>
		</div>
	</div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { defineProps, onMounted } from 'vue';
import * as echarts from 'echarts';

const props = defineProps({
	agencyCount: Number,
	titles: Array,
	totalWords: Number,
	agencies: Array
});

function calculateWordsByTitle() {
	let data = [];

	for (const title of props.titles) {
		const wordPercent = (title.word_count / props.totalWords * 100).toFixed(2);
		data.push({
			value: wordPercent,
			name: title.name
		});
	}
	return data;
}

function calculateWordsByAgency() {
	let data = [];
	const totalWords = () => {
		let total = 0;
		for (const agency of props.agencies) {
			total += agency.word_count;
		}
		return total;
	}

	let total = totalWords();
	for (const agency of props.agencies) {
		const wordPercent = (agency.word_count / total * 100).toFixed(2);
		data.push({
			value: wordPercent,
			name: agency.name
		});
	}
	return data;
}

function calculateAmendmentsByTitle() {
	let data = [];

	for (const title of props.titles) {
		data.push({
			value: title.properties.amendments,
			name: title.name
		});
	}
	return data;
}

function titlePieChart() {
	const data = calculateWordsByTitle();
	var chartDom = document.getElementById('title-pie-chart');
	var myChart = echarts.init(chartDom);
	var option;

	option = {
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
}

function agencyPieChart() {
	const data = calculateWordsByAgency();
	var chartDom = document.getElementById('agency-pie-chart');
	var myChart = echarts.init(chartDom);
	var option;

	option = {
		tooltip: {
			trigger: 'item'
		},
		legend: false,
		series: [
			{
				name: 'Words',
				type: 'pie',
				radius: '98%',
				data: data,
				label: {
					show: false,
				},
				emphasis: {
					itemStyle: {
						shadowBlur: 10,
						shadowOffsetX: 0,
						shadowColor: 'rgba(0, 0, 0, 0.5)'
					}
				}
			}
		]
	};

	option && myChart.setOption(option);
}

function amendmentsBarChart() {
	const data = calculateAmendmentsByTitle();
	const names = data.map(item => item.name);
	const values = data.map(item => item.value);
	const maxVal = Math.max(...values) * 1.1;
	var chartDom = document.getElementById('amendments-bar-chart');
	var myChart = echarts.init(chartDom);

	var option = {
		polar: {
			radius: [30, '80%']
		},
		radiusAxis: {
			max: maxVal,
		},
		angleAxis: {
			type: 'category',
			data: names,
			startAngle: 75
		},
		tooltip: {},
		series: {
			type: 'bar',
			data: values,
			coordinateSystem: 'polar',
		},
		animation: false
	};

	option && myChart.setOption(option);
}

// ECharts Pie Chart
onMounted(() => {
	titlePieChart();
	agencyPieChart();
	amendmentsBarChart();
});


</script>
