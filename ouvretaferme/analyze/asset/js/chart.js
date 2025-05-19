class Analyze {

	static createBar(target, labelBar, valuesBar, labels, labelSuffix = '€') {

		new Chart(target, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [
					{
						label: labelBar,
						data: valuesBar,
						backgroundColor: '#c14269',
						type: 'bar',
						yAxisID: 'bar'
					}
				]
			},
			options: {
				responsive: true,
				animation: {
					duration: 0
				},
				scales: {
					bar: {
						position: 'left',
						ticks: {
							color: '#c14269',
							callback: function(value) {
								return value + ' '+ labelSuffix;
							}
						}
					}
				},
				plugins: {
					legend: {
						position: 'bottom'
					}
				},
				maintainAspectRatio: false
			}
		});

	}

	static createDoubleBar(target, labelBar, valuesBar, label2Bar, values2Bar, labels, labelSuffix = '€') {

		new Chart(target, {
			type: 'bar',
			plugins: [{
			 afterUpdate: function(chart) {
				var dataset = chart.config.data.datasets[1];
				for (var i = 0; i < dataset.data.length; i++) {
				  var model = chart.getDatasetMeta(0).data[i];
				  model.x += model.width / 4;
				}
			 }
		  }],
			data: {
				labels: labels,
				datasets: [
					{
						label: labelBar,
						data: valuesBar,
						backgroundColor: '#c14269',
						type: 'bar',
						yAxisID: 'bar',
						barPercentage: 1.5
					},
					{
						label: label2Bar,
						data: values2Bar,
						backgroundColor: '#ec9fb7',
						type: 'bar',
						yAxisID: 'bar',
						barPercentage: 1.5
					}
				]
			},
			options: {
				responsive: true,
				animation: {
					duration: 0
				},
				scales: {
					bar: {
						position: 'left',
						ticks: {
							color: '#c14269',
							callback: function(value) {
								return value + ' '+ labelSuffix;
							}
						}
					}
				},
				plugins: {
					legend: {
						position: 'bottom'
					}
				},
				maintainAspectRatio: false
			}
		});

	}

	static createMonthly(target, values, labels, legends, colors, labelSuffix = '€') {

		if(colors === undefined) {
			colors = this.getDefaultColors();
		}

		let datasets = [];

		values.forEach((elementValues, key) => {

			let data = [];

			for(let month = 0; month < 12; month++) {
				data[month] = elementValues[month];
			}

			datasets[datasets.length] = {
				label: legends[key],
				data: data,
				borderColor: colors[key],
				backgroundColor: colors[key],
				fill: (key === 0) ? 'origin' : '-1'
			}
		});

		new Chart(target, {
			type: 'line',
			data: {
				labels: labels,
				datasets: datasets
			},
			options: {
				responsive: true,
				animation: {
					duration: 0
				},
				scales: {
					x: {
						stacked: true
					},
					y: {
						stacked: true,
						position: 'left',
						ticks: {
							color: '#c14269',
							callback: function(value) {
								return value + ' '+ labelSuffix;
							}
						}
					}
				},
				plugins: {
					filler: {
					  propagate: false
					},
					legend: {
						position: 'bottom'
					},
				},
				maintainAspectRatio: false
			}
		});

	}

	static createBarLine(target, labelBar, valuesBar, labelLine, valuesLine, labels) {

		new Chart(target, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{
						label: labelLine,
						data: valuesLine,
						backgroundColor: '#212529',
						borderColor: '#212529',
						yAxisID: 'line'
					},
					{
						label: labelBar,
						data: valuesBar,
						backgroundColor: '#c14269',
						type: 'bar',
						yAxisID: 'bar'
					}
				]
			},
			options: {
				responsive: true,
				animation: {
					duration: 0
				},
				scales: {
					line: {
						position: 'right',
						ticks: {
							color: '#212529'
						}
					},
					bar: {
						position: 'left',
						ticks: {
							color: '#c14269',
							callback: function(value) {
								return value + ' €';
							}
						}
					}
				},
				plugins: {
					legend: {
						position: 'bottom'
					}
				},
				maintainAspectRatio: false
			}
		});

	}

	static createPie(target, values, labels, colors) {

		if(colors === undefined) {
			colors = this.getDefaultColors();
		}

		new Chart(target, {
			type: 'pie',
			data: {
				labels: labels,
				datasets: [
					{
						data: values,
						backgroundColor: colors,
					}
				]
			},
			options: {
				responsive: true,
				animation: {
					duration: 0
				},
				plugins: {
					legend: {
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							label: function(data) {
								return ' '+ data.label + ' → ' + data.formattedValue +' %';
							}
						}
					}
				},
				maintainAspectRatio: false
			}
		});

	}

	static getDefaultColors() {

		return [
			'#348a63',
			'#ffa600',
			'#ff6e54',
			'#dd5182',
			'#955196',
			'#444e86',
			'#003f5c'
		];

	}

}