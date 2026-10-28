/**
 * ActivosIT - Graficos de reportes.
 *
 * Lee la configuracion que genera el backend en el bloque JSON
 * #reportChartsData y dibuja cada grafico con Chart.js. Los colores se
 * resuelven desde las variables CSS del tema para mantener la identidad
 * visual, y cada grafico falla de forma aislada para que un error no
 * afecte al resto de la pagina.
 */
(function () {
  'use strict';

  var COLOR_TOKENS = {
    brand: '--brand-blue',
    brandDeep: '--brand-blue-deep',
    info: '--color-info',
    success: '--color-success',
    warning: '--color-warning',
    danger: '--color-danger',
    neutral: '--color-neutral'
  };

  var FALLBACK_COLORS = {
    brand: '#1F3B73',
    brandDeep: '#0B1B3A',
    info: '#175CD3',
    success: '#137A4B',
    warning: '#9A6700',
    danger: '#B42318',
    neutral: '#667085'
  };

  var REDUCED_MOTION = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
    : false;

  // Lee una variable CSS del tema y usa un valor de respaldo si no existe.
  function readCssVar(name, fallback) {
    var value = getComputedStyle(document.documentElement)
      .getPropertyValue(name)
      .trim();
    return value !== '' ? value : fallback;
  }

  // Convierte un token de color del backend en un color CSS valido.
  function resolveColor(token, fallback) {
    if (!token) {
      return fallback || readCssVar('--brand-blue', FALLBACK_COLORS.brand);
    }

    if (token.charAt(0) === '#') {
      return token;
    }

    var cssVar = COLOR_TOKENS[token];
    if (cssVar) {
      return readCssVar(cssVar, FALLBACK_COLORS[token]);
    }

    return fallback || readCssVar('--brand-blue', FALLBACK_COLORS.brand);
  }

  // Agrega transparencia a un color hexadecimal.
  function withAlpha(color, alpha) {
    if (color.charAt(0) !== '#' || (color.length !== 7 && color.length !== 4)) {
      return color;
    }

    var hex = color.slice(1);
    if (hex.length === 3) {
      hex = hex.charAt(0) + hex.charAt(0) + hex.charAt(1) + hex.charAt(1)
        + hex.charAt(2) + hex.charAt(2);
    }

    var r = parseInt(hex.slice(0, 2), 16);
    var g = parseInt(hex.slice(2, 4), 16);
    var b = parseInt(hex.slice(4, 6), 16);

    return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
  }

  // Devuelve el sufijo de unidad usado en tooltips y leyendas.
  function unitLabel(config, value) {
    if (!config.unit) {
      return '';
    }

    var singular = config.unitSingular;
    return ' ' + (Number(value) === 1 && singular ? singular : config.unit);
  }

  // Construye las opciones comunes de Chart.js.
  function baseOptions(config) {
    var muted = readCssVar('--text-muted', '#667085');
    var border = readCssVar('--border-subtle', '#DCE4F0');

    return {
      responsive: true,
      maintainAspectRatio: false,
      resizeDelay: 150,
      animation: REDUCED_MOTION ? false : undefined,
      interaction: {
        mode: config.type === 'doughnut' ? 'nearest' : 'index',
        intersect: config.type === 'doughnut'
      },
      plugins: {
        legend: {
          display: config.legend !== false,
          position: config.legendPosition || 'bottom',
          labels: {
            boxWidth: 9,
            boxHeight: 9,
            usePointStyle: true,
            pointStyle: 'circle',
            padding: 14,
            color: muted,
            font: {
              family: 'Roboto, sans-serif',
              size: 11,
              weight: '500'
            }
          }
        },
        tooltip: {
          backgroundColor: readCssVar('--brand-blue-ink', '#081226'),
          titleColor: '#FFFFFF',
          bodyColor: '#FFFFFF',
          padding: 10,
          cornerRadius: 8,
          displayColors: true,
          boxWidth: 8,
          boxHeight: 8,
          usePointStyle: true,
          callbacks: {
            label: function (context) {
              var value = context.parsed;
              if (config.type === 'doughnut') {
                value = context.parsed;
              } else if (config.horizontal && typeof context.parsed === 'object') {
                value = context.parsed.x;
              } else if (typeof context.parsed === 'object') {
                value = context.parsed.y;
              }

              var name = config.type === 'doughnut'
                ? (context.label || '')
                : (context.dataset.label || '');

              if (config.type === 'doughnut') {
                var series = Array.isArray(context.dataset.data)
                  ? context.dataset.data
                  : [];
                var total = series.reduce(function (acc, item) {
                  return acc + Number(item || 0);
                }, 0);
                var share = total > 0
                  ? Math.round((Number(value || 0) / total) * 100)
                  : 0;
                return name + ': ' + value + unitLabel(config, value)
                  + ' (' + share + '%)';
              }

              var label = name ? name + ': ' : '';
              return label + value + unitLabel(config, value);
            }
          }
        }
      },
      scales: config.type === 'doughnut'
        ? {}
        : {
          x: {
            grid: { display: false },
            border: { color: border },
            ticks: {
              color: muted,
              maxRotation: 0,
              autoSkip: true,
              font: { family: 'Roboto, sans-serif', size: 10 }
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: withAlpha(border, 0.55), drawTicks: false },
            border: { display: false },
            ticks: {
              color: muted,
              precision: 0,
              maxTicksLimit: 6,
              font: { family: 'Roboto, sans-serif', size: 10 }
            }
          }
        },
      elements: config.type === 'doughnut' ? {} : {
        bar: {
          borderRadius: 6,
          maxBarThickness: config.maxBarThickness || 34
        },
        line: {
          borderWidth: 2,
          tension: 0.35
        },
        point: {
          radius: 2.5,
          hoverRadius: 5,
          borderWidth: 2
        }
      }
    };
  }

  // Convierte un dataset del backend al formato de Chart.js.
  function buildDataset(dataset, config, palette) {
    var color = resolveColor(dataset.color, palette[0]);
    var data = Array.isArray(dataset.data) ? dataset.data : [];

    if (config.type === 'doughnut') {
      var colors = Array.isArray(dataset.colors)
        ? dataset.colors.map(function (token, index) {
          return resolveColor(token, palette[index % palette.length]);
        })
        : palette;

      return {
        label: dataset.label || '',
        data: data,
        backgroundColor: colors,
        borderColor: readCssVar('--surface-card', '#FFFFFF'),
        borderWidth: 2,
        hoverOffset: 4
      };
    }

    if (config.type === 'line') {
      return {
        label: dataset.label || '',
        data: data,
        borderColor: color,
        backgroundColor: withAlpha(color, 0.12),
        fill: !!dataset.fill,
        pointBackgroundColor: color,
        pointBorderColor: readCssVar('--surface-card', '#FFFFFF')
      };
    }

    return {
      label: dataset.label || '',
      data: data,
      backgroundColor: withAlpha(color, config.type === 'bar' ? 0.85 : 1),
      borderColor: color,
      borderWidth: 1,
      borderRadius: 6,
      maxBarThickness: config.maxBarThickness || 34
    };
  }

  // Crea una instancia de Chart.js para un canvas.
  function createChart(canvas, config, palette) {
    if (typeof window.Chart !== 'function') {
      throw new Error('Chart.js no esta disponible');
    }

    var options = baseOptions(config);

    if (config.horizontal) {
      options.indexAxis = 'y';
      options.scales.x.beginAtZero = true;
      options.scales.y.grid.display = false;
      options.scales.x.grid.display = true;
      options.scales.y.ticks.maxRotation = 0;
    }

    return new window.Chart(canvas.getContext('2d'), {
      type: config.type,
      data: {
        labels: Array.isArray(config.labels) ? config.labels : [],
        datasets: (Array.isArray(config.datasets) ? config.datasets : [])
          .map(function (dataset) {
            return buildDataset(dataset, config, palette);
          })
      },
      options: options
    });
  }

  // Muestra un mensaje cuando Chart.js no puede dibujar el grafico.
  function markFallback(canvas) {
    var wrapper = canvas.closest('.report-chart');
    if (!wrapper || wrapper.classList.contains('report-chart--failed')) {
      return;
    }

    wrapper.classList.add('report-chart--failed');

    var note = document.createElement('p');
    note.className = 'report-chart__noscript';
    note.textContent = canvas.getAttribute('aria-label')
      || 'No se pudieron cargar los gráficos.';
    wrapper.appendChild(note);
  }

  // Lee el JSON del backend y crea todos los graficos de la pagina.
  function boot() {
    var dataNode = document.getElementById('reportChartsData');
    if (!dataNode) {
      return;
    }

    var payload;
    try {
      payload = JSON.parse(dataNode.textContent || '{}');
    } catch (error) {
      return;
    }

    var charts = Array.isArray(payload.charts) ? payload.charts : [];
    if (charts.length === 0) {
      return;
    }

    var palette = [
      resolveColor('brand'),
      resolveColor('info'),
      resolveColor('success'),
      resolveColor('warning'),
      resolveColor('danger'),
      resolveColor('neutral')
    ];

    charts.forEach(function (config) {
      if (!config || !config.id) {
        return;
      }

      var canvas = document.querySelector(
        'canvas[data-chart="' + config.id.replace(/"/g, '') + '"]'
      );
      if (!canvas) {
        return;
      }

      try {
        createChart(canvas, config, palette);
      } catch (error) {
        markFallback(canvas);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
