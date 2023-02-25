import {
  ApexAnnotations,
  ApexChart, ApexDataLabels,
  ApexGrid,
  ApexLegend,
  ApexMarkers, ApexStroke,
  ApexTitleSubtitle, ApexTooltip, ApexXAxis, ApexYAxis, ChartComponent,
  ChartType
} from "ng-apexcharts";

/**
 * Updates chart when theme changes.
 */
export function update(chart: ChartComponent, options, theme: 'light' | 'dark', striped: boolean | 'horizontal' | 'vertical',
                       colors: {text: string, line: string, bg: {dark: string, light: string}}) {

  const sparkline = options.chart.sparkline.enabled;
  const transparent = 'transparent';

  options.chart.foreColor = colors.text;
  options.xaxis.axisBorder.color = !sparkline ? colors.line : transparent;
  if (options.grid?.show) {
    options.grid.borderColor = !sparkline ? colors.line : transparent;
    if (!sparkline && striped === 'horizontal') options.grid.row.colors = [colors.bg.dark, colors.bg.light];
    if (!sparkline && striped === 'vertical') options.grid.column.colors = [colors.bg.dark, colors.bg.light];
  }
  options.tooltip.theme = theme;

  if (options.chart.type === 'radar') {
    options.plotOptions.radar.polygons.strokeColors = !sparkline ? colors.line : transparent;
    options.plotOptions.radar.polygons.connectorColors = !sparkline ? colors.line : transparent;
    if (!sparkline && striped) options.plotOptions.radar.polygons.fill.colors = [colors.bg.dark, colors.bg.light];
  }

  chart.updateOptions(options).then(r => r);
}

/*** --------------------------------------------- ***/
/*** -------------- General Options -------------- ***/
/*** --------------------------------------------- ***/

export function general(chartType: ChartType, height: string, width: string, fontColor: string, sparkline: boolean,
                        toolbar: boolean, toolbarActions: string[]): ApexChart {
  return {
    fontFamily: 'Inter, sans-serif',
    foreColor: fontColor,
    height,
    width,
    selection: { enabled: false },
    sparkline: { enabled: sparkline },
    toolbar: {
      show: toolbar,
      tools: {
        download: toolbarActions.includes('download'),
        selection: toolbarActions.includes('selection'),
        zoom: toolbarActions.includes('zoom'),
        zoomin: toolbarActions.includes('zoomin'),
        zoomout: toolbarActions.includes('zoomout'),
        pan: toolbarActions.includes('pan'),
        reset: toolbarActions.includes('reset')
      }
    },
    type: chartType,
    zoom: { enabled: toolbar && toolbarActions.includes('zoom') }
  }
}

/*** --------------------------------------------- ***/
/*** ---------------- Annotations ---------------- ***/
/*** --------------------------------------------- ***/

export interface ChartAnnotation {
  axis: 'x' | 'y',
  value: number | string,
  color: string,
  text: string
}

export function annotations(annotations: ChartAnnotation[]): ApexAnnotations {
  return {
    xaxis: annotations?.filter(a => a.axis === 'x').map(a => { return {
      x: a.value,
      strokeDashArray: 5,
      borderColor: a.color,
      label: {
        borderColor: a.color,
        borderRadius: 4,
        text: a.text,
        orientation: 'horizontal',
        style: {
          background: a.color,
          color: 'white',
          fontSize: '.8rem'
        }
      }
    } }) || [],
    yaxis: annotations?.filter(a => a.axis === 'y').map(a => { return {
      y: a.value,
      strokeDashArray: 5,
      borderColor: a.color,
      label: {
        borderColor: a.color,
        borderRadius: 4,
        text: a.text,
        style: {
          background: a.color,
          color: 'white',
          fontSize: '.8rem'
        }
      }
    } }) || []
  };
}


/*** --------------------------------------------- ***/
/*** -------------------- Axis ------------------- ***/
/*** --------------------------------------------- ***/

export function xaxis(type: 'category' | 'datetime' | 'numeric', categories: (string | number)[], color: string,
                      label: string = undefined, tickAmount: number = undefined): ApexXAxis {
  return {
    type,
    categories: categories || [],
    tickAmount,
    axisBorder: { color },
    title: {
      text: label,
      style: {
        fontSize: '14px',
        cssClass: 'font-semibold'
      }
    }
  };
}

export function yaxis(reversed: boolean, tickAmount: number, min: number, max: number, label: string, opposite: boolean): ApexYAxis {
  return {
    reversed,
    tickAmount,
    min,
    max,
    opposite,
    title: {
      text: label,
      style: {
        fontSize: '14px',
        cssClass: 'font-semibold'
      }
    }
  };
}


/*** --------------------------------------------- ***/
/*** ---------------- DataLabels ----------------- ***/
/*** --------------------------------------------- ***/

export function dataLabels(show: boolean, showOnSeries: number[], formatter: string): ApexDataLabels {
  const dataLabels = {
    enabled: show,
    enabledOnSeries: showOnSeries,
    formatter: (val: string | number | number[], opts?: any) => val as string | number
  };

  if (formatter) dataLabels.formatter = (val: string | number | number[], opts?: any) => {
    return evaluate(formatter, val, opts);
  };

  return dataLabels;
}


/*** --------------------------------------------- ***/
/*** -------------------- Grid ------------------- ***/
/*** --------------------------------------------- ***/

export function grid(show: boolean, xaxis: boolean, yaxis: boolean, color: string, striped: 'horizontal' | 'vertical',
                     stripedColors: {dark: string, light: string}, sparkline: boolean): ApexGrid {
  return {
    show: !sparkline && (xaxis || yaxis),
    borderColor: color,
    xaxis: {lines: {show: xaxis}},
    yaxis: {lines: {show: yaxis}},
    row: {
      colors: !sparkline && striped === 'horizontal' ? [stripedColors.dark, stripedColors.light] : undefined
    },
    column: {
      colors: !sparkline && striped === 'vertical' ? [stripedColors.dark, stripedColors.light] : undefined
    }
  }
}


/*** --------------------------------------------- ***/
/*** ------------------ Legend ------------------- ***/
/*** --------------------------------------------- ***/

export function legend(show: boolean, position: 'top' | 'right' | 'bottom' | 'left'): ApexLegend {
  return {
    show,
    showForSingleSeries: true,
    position,
    fontWeight: 600
  };
}


/*** --------------------------------------------- ***/
/*** ------------------ Markers ------------------ ***/
/*** --------------------------------------------- ***/

export function markers(size: number): ApexMarkers {
  return { size };
}


/*** --------------------------------------------- ***/
/*** ------------------- Stroke ------------------ ***/
/*** --------------------------------------------- ***/

export function stroke(curve: 'smooth' | 'straight' | 'stepline', lineCap: 'butt' | 'square' | 'round', width: number): ApexStroke {
  return { curve,  lineCap,  width };
}


/*** --------------------------------------------- ***/
/*** ------------- Title & Subtitle -------------- ***/
/*** --------------------------------------------- ***/

export function title(title: string, align: 'left' | 'center' | 'right'): ApexTitleSubtitle {
  return {
    text: title,
    align,
    style: {
      fontSize: '16px'
    }
  };
}

export function subtitle(subtitle: string, align: 'left' | 'center' | 'right'): ApexTitleSubtitle {
  return {
    text: subtitle,
    align,
    style: {
      fontSize: '14px'
    }
  };
}


/*** --------------------------------------------- ***/
/*** ------------------ Tooltip ------------------ ***/
/*** --------------------------------------------- ***/

export function tooltip(show: boolean, formatter: {xaxis: string, yaxis: string}, theme: 'light' | 'dark'): ApexTooltip {
  const tooltip = {
    enabled: show,
    theme,
    x: { formatter: undefined },
    y: { formatter: undefined }
  };

  if (formatter.xaxis) tooltip.x = {formatter(val: number, opts?: any): string {
    return evaluate(formatter.xaxis, val, opts).toString();
  }};
  if (formatter.yaxis) tooltip.y = {formatter(val: number, opts?: any): string {
    return evaluate(formatter.yaxis, val, opts).toString();
  }};

  return tooltip;
}

/**
 * Evaluates a formatter expression.
 * @example expr = 'After (?value) days', val = 2 --> 'After 2 days'
 *
 * @param expression
 * @param val
 * @param opts
 */
function evaluate(expression: string, val: string | number | number[], opts?: any): string | number {
  const matches = expression.matchAll(/\(.*?\)/g);
  for (const match of matches) {
    const expr = match[0].substr(1, match[0].length - 2)
      .replaceAll('?value', val.toString())
      .replaceAll('?seriesIndex', opts.seriesIndex.toString());
    const value = eval(expr);
    expression = expression.replaceAll(match[0], value.toString());
  }
  return expression;
}
