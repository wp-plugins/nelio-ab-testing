/**
 *
 */
if (!String.prototype.nelioabformat) {
  String.prototype.nelioabformat = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) {
		  return typeof args[number] != 'undefined' ? args[number] : match;
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
	var conchart = new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'column',
			borderWidth: 1,
			borderColor: '#C0C0C0'
		},
		title: {
			text: labels['title'],
			style: {
				 color: '#464646',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
			title: {
				text: null,
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal",
				},
			},
			labels: {
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal",
				},
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis'],
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal",
				}
			},
			lineWidth: 2,
			maxPadding: 0.1,
			plotLines: [{
				value: 0,
				width: 4,
				color: '#C0D0E0',
				zIndex: 4
			}],
			min: 0
		},
		plotOptions: {
			column: {
				dataLabels: {
					enabled: true,
					color: 'black',
					style: {
						 color: '#464646',
						 fontFamily: "'Open Sans', sans-serif",
						 fontWeight: "normal",
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

	if ( conchart.yAxis[0].getExtremes().dataMax < 100 )
		conchart.yAxis[0].setExtremes(0, 100);

	return conchart;
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
	var imchart = new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'column',
			borderWidth: 1,
			borderColor: '#C0C0C0'
		},
		title: {
			text: labels['title'],
			style: {
				 color: '#464646',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
			title: {
				text: null,
			},
			labels: {
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal",
				},
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis'],
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal",
				}
			},
			lineWidth: 2,
			maxPadding: 0.1,
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
						 color: '#464646',
						 fontFamily: "'Open Sans', sans-serif",
						 fontWeight: "normal",
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

   if ( imchart.yAxis[0].getExtremes().dataMin >= 0 &&
	     imchart.yAxis[0].getExtremes().dataMax < 100 ) {
		imchart.yAxis[0].setExtremes(0, 100);
	}
   else if (imchart.yAxis[0].getExtremes().dataMin > -100 &&
	         imchart.yAxis[0].getExtremes().dataMax <= 0 ) {
		imchart.yAxis[0].setExtremes(-100, 0);
	}

   return imchart;
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
			spacingRight: 30,
			borderColor: '#C0C0C0',
			marginLeft: 2,
			marginLeft: 40
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
			text: labels['title'],
			style: {
				 color: '#464646',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif",
			}
		},
		tooltip: {
			pointFormat: labels['detail']
		},
		xAxis: {
			categories: categories,
			style: {
				color: '#464646',
				fontFamily: "'Open Sans', sans-serif",
				fontWeight: "normal",
			},
			labels: {
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal",
					paddingLeft: 20,
				},
				rotation: -90,
			}
		},
		yAxis: {
			title: {
				text: null,
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal",
				}
			},
			min: 0,
			allowDecimals: false,
			maxPadding: 0.1,
		},
		legend: {
			backgroundColor: '#FFFFFF',
			reversed: true
		},
		series: [{
			name: labels['visitors'],
			data: visitors,
			color: '#009BD9',
		}, {
			name: labels['conversions'],
			data: conversions,
			color: '#003245',
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
				borderWidth: 0,
				backgroundColor: '#F7F7F7',
		  },
		  title: {
				text: labels['title'],
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
				}
		  },
		  subtitle: {
				text: document.ontouchstart === undefined ?
					 labels['subtitle1'] :
					 labels['subtitle2'],
				style: {
					 color: '#808080',
					 fontFamily: "'Open Sans', sans-serif",
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
					 align: 'low',
					 style: {
						 color: '#464646',
						 fontFamily: "'Open Sans', sans-serif",
						 fontWeight: "normal",
					 }
				},
				min: 0.1,
				startOnTick: true,
				allowDecimals: false,
				maxPadding: 0.1,
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
					 threshold: null,
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

function makeTimelinePerAlternativeGraphic(divName, labels, alternatives, startingDate) {
		var series = [];

		series.push( {
			name: labels['original'],
			pointInterval: 24 * 3600 * 1000, //every day
			pointStart: startingDate,
			color: '#CC0000',
			data: alternatives[0]
		} );

		for ( i=1; i<alternatives.length; ++i ) {
			series.push( {
				name: labels['alternative'].replace('%s', i),
				pointInterval: 24 * 3600 * 1000, //every day
				pointStart: startingDate,
				data: alternatives[i]
			} );
		}

	return new Highcharts.Chart({
	chart: {
				renderTo: divName,
				zoomType: 'x',
				spacingRight: 20,
				borderWidth: 0,
				backgroundColor: '#F7F7F7',
		  },
		  title: {
				text: labels['title'],
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
				}
		  },
		  subtitle: {
				text: document.ontouchstart === undefined ?
					 labels['subtitle1'] :
					 labels['subtitle2'],
				style: {
					 color: '#808080',
					 fontFamily: "'Open Sans', sans-serif",
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
					 align: 'low',
					 style: {
						 color: '#464646',
						 fontFamily: "'Open Sans', sans-serif",
						 fontWeight: "normal",
					 }
				},
				min: 0,
				max: 100,
				startOnTick: true,
				allowDecimals: false,
				maxPadding: 0.1,
		  },
		  tooltip: {
            pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y:.1f}%</b><br/>',
				shared: false
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
					 threshold: null,
				}
		  },
		  credits: {
				enabled: false
		  },
		  series: series,
	 });
}
