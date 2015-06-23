Highcharts.setOptions({
	chart: {
		animation: false
	},
	plotOptions: {
		series: {
			animation: false
		}
	}
});

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

function nelioabShowCurrentGraphics( id, delay ) {
    var timeouts = [];
    var elemNum = 0;
    jQuery("#nelio-container-" + id + " .highcharts-container").parent().each(function() {
        var elem = jQuery(this);
        aux = setTimeout( function() {
            elem.hide();
            elem.css('visibility', 'visible');
            elem.fadeIn();
        }, delay + elemNum * 300 );
        timeouts.push( aux );
        elemNum++;
    });
    return timeouts;
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
			borderWidth: 0,
			borderColor: '#C0C0C0'
		},
		title: {
			text: labels['title'],
			style: {
				 color: '#464646',
				 fontFamily: "'Open Sans', sans-serif"
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif"
			}
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
            lineColor: '#ddd',
			title: {
				text: null,
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal"
				}
			},
			labels: {
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal"
				}
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis'],
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal"
				}
			},
			lineWidth: 2,
            lineColor: '#ddd',
			maxPadding: 0.1,
			plotLines: [{
				value: 0,
				width: 4,
				color: '#ddd',
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
						 fontWeight: "normal"
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
			data: data,
			color: 'black',
			shadow: {
				color: 'black',
				width: 3,
				offsetX: 0,
				offsetY: 0
			}
		}]
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
			borderWidth: 0,
			borderColor: '#C0C0C0'
		},
		title: {
			text: labels['title'],
			style: {
				 color: '#464646',
				 fontFamily: "'Open Sans', sans-serif"
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif"
			}
		},
		xAxis: {
			categories: categories,
			lineWidth: 2,
            lineColor: '#ddd',
			title: {
				text: null
			},
			labels: {
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif",
					 fontWeight: "normal"
				}
			}
		},
		yAxis: {
			title: {
				text: labels['yaxis'],
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal"
				}
			},
			lineWidth: 2,
            lineColor: '#ddd',
			maxPadding: 0.1,
			plotLines: [{
				value: 0,
				width: 4,
				color: '#ddd',
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
						 fontWeight: "normal"
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
			data: data,
			color: 'black',
			shadow: {
				color: 'black',
				width: 3,
				offsetX: 0,
				offsetY: 0
			}
		}]
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
function makeVisitorsGraphic(divName, labels, categories, visitors, conversions, colors) {
	return new Highcharts.Chart({
		chart: {
			renderTo: divName,
			type: 'bar',
			borderWidth: 1,
			spacingRight: 30,
			borderColor: '#C0C0C0',
			marginLeft: 40
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
				 fontFamily: "'Open Sans', sans-serif"
			}
		},
		subtitle: {
			text: labels['subtitle'],
			style: {
				 color: '#808080',
				 fontFamily: "'Open Sans', sans-serif"
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
				fontWeight: "normal"
			},
			labels: {
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal",
					paddingLeft: 20
				},
				rotation: -90
			}
		},
		yAxis: {
			title: {
				text: null,
				style: {
					color: '#464646',
					fontFamily: "'Open Sans', sans-serif",
					fontWeight: "normal"
				}
			},
			min: 0,
			allowDecimals: false,
			maxPadding: 0.1
		},
		legend: {
            navigation: {
            },
			backgroundColor: '#FFFFFF',
			reversed: true
		},
		series: [{
			name: labels['visitors'],
			data: visitors,
			color: colors[1]
		}, {
			name: labels['conversions'],
			data: conversions,
			color: colors[0]
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
				backgroundColor: '#F7F7F7'
		  },
		  title: {
				text: labels['title'],
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif"
				}
		  },
		  subtitle: {
				text: document.ontouchstart === undefined ?
					 labels['subtitle1'] :
					 labels['subtitle2'],
				style: {
					 color: '#808080',
					 fontFamily: "'Open Sans', sans-serif"
				}
		  },
		  xAxis: {
				type: 'datetime',
				maxZoom: 24 * 3600000, // one day
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
						 fontWeight: "normal"
					 }
				},
				min: 0.1,
				startOnTick: true,
				allowDecimals: false,
				maxPadding: 0.1
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

function makeTimelinePerAlternativeGraphic(divName, labels, alternatives, startingDate, max) {
		var series = [];
        var colors = ['#00b193', '#13b5ea', '#ffd200', '#f47b20', '#00958f', '#a0d5b5', '#f05133',
            '#5d87a1', '#afbd22', '#e31b23', '#83cfca', '#532e63', '#215352', '#00467f', '#bec0c2'];

		series.push( {
			name: labels['original'],
			pointInterval: 24 * 3600 * 1000, //every day
			pointStart: startingDate,
			color: '#CC0000',
			data: alternatives[0]
		} );

        var j=0; var i;
		for ( i=1; i<alternatives.length; ++i ) {
			series.push( {
				name: labels['alternative'].replace('%s', i),
				pointInterval: 24 * 3600 * 1000, //every day
				pointStart: startingDate,
                color: colors[j],
				data: alternatives[i]
			} );
            ++j;
            if (j > colors.length) j = 0;
		}

	return new Highcharts.Chart({
	chart: {
				renderTo: divName,
				zoomType: 'x',
				spacingRight: 20,
				borderWidth: 0,
				backgroundColor: '#FFF'
		  },
		  title: {
				text: labels['title'],
				style: {
					 color: '#464646',
					 fontFamily: "'Open Sans', sans-serif"
				}
		  },
		  subtitle: {
				text: document.ontouchstart === undefined ?
					 labels['subtitle1'] :
					 labels['subtitle2'],
				style: {
					 color: '#808080',
					 fontFamily: "'Open Sans', sans-serif"
				}
		  },
		  xAxis: {
				type: 'datetime',
				maxZoom: 24 * 3600000, // one day
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
						 fontWeight: "normal"
					 }
				},
				min: 0,
				max: max,
				startOnTick: true,
				allowDecimals: false,
				maxPadding: 0.1
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
					 threshold: null
				}
		  },
		  credits: {
				enabled: false
		  },
		  series: series
	 });
}

function drawGraphic( id, data, label, baseColor ) {
    if ( baseColor == undefined )
        baseColor = '#CCCCCC';
    var $ = jQuery;
    Highcharts.getOptions().plotOptions.pie.colors = function () {
        var divider = 25;
        var numOfAlts = data.length;
        if (numOfAlts < 10) divider = 20;
        if (numOfAlts < 8) divider = 15;
        if (numOfAlts < 4) divider = 6;
        var colors = [],
            i;
        for (i = 0; i < 10; i++)
            colors.push(Highcharts.Color(baseColor).brighten(i / divider).get());
        return colors;
    }();

    // Build the chart
    $('#' + id).highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            margin: [0, 0, 0, 0]
        },
        title: { text:'' },
        exporting: { enabled: false },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y:.0f}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: false,
                cursor: 'pointer',
                dataLabels: { enabled: false }
            }
        },
        series: [{
            type: 'pie',
            name: label,
            data: data
        }]
    });
}

function drawAlternativeGraphic( id, portionValue, portionLabel, portionColor, totalValue, totalLabel ) {
    if ( portionColor == undefined )
        portionColor = '#CCCCCC';

    if ( totalValue == 0.0 )
        totalValue += 0.1;
    var $ = jQuery;

		var series = [{
			name: totalLabel,
			data: [{name: totalLabel, y: totalValue, color: Highcharts.Color(portionColor).brighten(0.3).get()}]
		}];
		if ( portionValue > 0 ) {
			series.push({
				name: portionLabel,
				data: [{name: portionLabel, y: portionValue, color: portionColor},{name: "", y: totalValue-portionValue, color: "none"}]
			});
		}

    // Build the chart
    var chart = $('#' + id).highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            margin: [0, 0, 0, 0],
            type: 'pie'
        },
        credits: {
            enabled: false
        },
        title: { text:'' },
        exporting: { enabled: false },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y:.0f}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: false,
                cursor: 'pointer',
                dataLabels: { enabled: false }
            }
        },
				series: series
    });
}
