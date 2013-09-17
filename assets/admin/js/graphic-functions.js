/**
 *
 */
if (!String.prototype.nelioabformat) {
  String.prototype.nelioabformat = function() {
	var args = arguments;
	return this.replace(/{(\d+)}/g, function(match, number) { 
	  return typeof args[number] != 'undefined'
		? args[number]
		: match
	  ;
	});
  };
}

/**
 *
 * labels:
 *	title		=>
 *	subtitle =>
 *	xaxis		=>
 *	yaxis		=>
 * column	=>
 * detail	=>
 *
 */
function makeConversionRateGraphic(divName, labels, categories, data) {
	return new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'column',
			borderWidth: 1
		},
		title: {
			text: labels['title']
		},
		subtitle: {
			text: labels['subtitle']
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
			title: {
				text: labels['xaxis']
			},
			labels: {
				style: {
					fontWeight: 'bold',
					color: 'black'
				}
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis']
			},
			lineWidth: 2
		},
		plotOptions: {
			column: {
				dataLabels: {
					enabled: true,
					color: 'black',
					style: {
						fontWeight: 'bold'
					},
					formatter: function () {
						return labels['column'].nelioabformat(this.y)
					}
				}
			}
		},
		tooltip: {
			formatter: function () {
				// TODO: use sprintf
				return labels['detail'].nelioabformat(this.x, this.y)
			}
		},
		legend: {
			enabled: false
		},
		credits: {
			enabled: false
		},
		series: [{
			name: name,
			data: data,
			color: 'black',
			shadow: {
				color: 'black',
				width: 3,
				offsetX: 0,
				offsetY: 0
			}
		}],
	});
}

/**
 *
 * labels:
 *	title		=>
 *	subtitle =>
 *	xaxis		=>
 *	yaxis		=>
 * column	=>
 * detail	=>
 *
 */
function makeImprovementFactorGraphic(divName, labels, categories, data) {
	return new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'column',
			borderWidth: 1
		},
		title: {
			text: labels['title']
		},
		subtitle: {
			text: labels['subtitle']
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
			title: {
				text: labels['xaxis']
			},
			labels: {
				style: {
					fontWeight: 'bold',
					color: 'black'
				}
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis']
			},
			lineWidth: 2,
			plotLines: [{
				value: 0,
				width: 4,
				color: '#C0D0E0',
				zIndex: 4
			}]
		},
		plotOptions: {
			column: {
				dataLabels: {
					enabled: true,
					color: 'black',
					style: {
						fontWeight: 'bold'
					},
					formatter: function () {
						return labels['column'].nelioabformat(this.y)
					}
				}
			}
		},
		tooltip: {
			formatter: function () {
				return labels['detail'].nelioabformat(this.x, this.y)
			}
		},
		legend: {
			enabled: false
		},
		credits: {
			enabled: false
		},
		series: [{
			name: name,
			data: data,
			color: 'black',
			shadow: {
				color: 'black',
				width: 3,
				offsetX: 0,
				offsetY: 0
			}
		}],
	});
}


/**
 *
 * labels:
 *	title			=>
 *	subtitle	 =>
 *	xaxis			=>
 * detail		=>
 * visitors	 =>
 * conversions =>
 *
 */
function makeVisitorsGraphic(divName, labels, categories, visitors, conversions) {
	return new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'bar',
			borderWidth: 1,
			spacingRight: 30
		},
		legend: {
			navigation: {
				animation: false
			}
		},
		credits: {
			enabled: false
		},
		plotOptions: {
			column: {
				events: {
					legendItemClick: function () {
						return false;
					}
				}
			},
			allowPointSelect: false,
			series: {
				pointPadding: 0,
				groupPadding: 0.2,
				borderWidth: 0
			}
		},
		title: {
			text: labels['title']
		},
		subtitle: {
			text: labels['subtitle']
		},
		tooltip: {
			pointFormat: labels['detail']
		},
		xAxis: {
			categories: categories,
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
		},
		legend: {
			backgroundColor: '#FFFFFF',
			reversed: true
		},
		series: [{
			name: labels['visitors'],
			data: visitors
		}, {
			name: labels['conversions'],
			data: conversions
		}]
	});
}


/**
 *
 * labels:
 *	title			=>
 *	subtitle	 =>
 *	yaxis			=>
 * subtitle1	=> 
 * subtitle2	=> 
 * visitors	 => 
 * conversions => 
 *
 */
function makeTimelineGraphic(divName, labels, visitors, conversions, startingDate) {
	return new Highcharts.Chart({
		chart: {
				renderTo: divName,
				zoomType: 'x',
				spacingRight: 20,
				type: 'area',
				borderWidth: 1,
		  },
		  title: {
				text: labels['title'],
				//style: {
				//	 color: '#585858'
				//}
		  },
		  subtitle: {
				text: document.ontouchstart === undefined ?
					 labels['subtitle1'] :
					 labels['subtitle2'],
				style: {
					 color: '#848484'
				}
		  },
		  xAxis: {
				type: 'datetime',
				maxZoom: 1 * 24 * 3600000, // one day
				title: {
					 text: null
				}
		  },
		  yAxis: {
				title: {
					 text: labels['yaxis'],
					 //style: {
					 //	 color: '#848484'
					 //}
				},
				min: 0,
				allowDecimals: false,
		  },
		  tooltip: {
				shared: true
		  },
		  legend: {
				enabled: true
		  },
		  plotOptions: {
				area: {
					 lineWidth: 1,
					 marker: {
						  enabled: false,
						  symbol: 'circle',
						  radius: 2,
						  states: {
								hover: {
									 enabled: true
								}
						  }
					 },
					 shadow: false,
					 states: {
						  hover: {
								lineWidth: 1,
								enabled: true
						  }
					 },
					 threshold: null
				}
		  },
		  credits: {
				enabled: false
		  },
		  series: [{
				name: labels['visitors'],
				pointInterval: 24 * 3600 * 1000, //every day
				pointStart: startingDate,
				data: visitors
		  }, {
				name: labels['conversions'],
				pointInterval: 24 * 3600 * 1000, //every day
				pointStart: startingDate,
				data: conversions
		  }]
	 });
}

