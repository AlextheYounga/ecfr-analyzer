import * as echarts from 'echarts';

function calculateWordsByTitle(props) {
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

function calculateWordsByAgency(props) {
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

function calculateAmendmentsByTitle(props) {
	let data = [];

	for (const title of props.titles) {
		data.push({
			value: title.properties.amendments,
			name: title.name
		});
	}
	return data;
}

export function titlePieChart(props) {
	const data = calculateWordsByTitle(props);
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

export function agencyPieChart(props) {
	const data = calculateWordsByAgency(props);
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

export function amendmentsBarChart(props) {
	const data = calculateAmendmentsByTitle(props);
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