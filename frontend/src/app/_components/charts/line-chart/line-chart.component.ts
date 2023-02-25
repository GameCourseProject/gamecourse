import {Component, Input, OnInit, ViewChild} from '@angular/core';

import {
  annotations,
  ChartAnnotation, dataLabels,
  general,
  grid,
  legend,
  markers,
  stroke,
  subtitle,
  title,
  tooltip,
  update,
  xaxis,
  yaxis
} from "../ChartOptions";
import {BGDarkColor, BGLightColor, LineColor, TextColor} from "../ChartColors";

import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";

import {
  ApexAnnotations,
  ApexAxisChartSeries,
  ApexChart,
  ApexDataLabels,
  ApexGrid,
  ApexLegend,
  ApexMarkers,
  ApexStroke,
  ApexTitleSubtitle,
  ApexTooltip,
  ApexXAxis,
  ApexYAxis,
  ChartComponent
} from 'ng-apexcharts';
import {Theme} from "../../../_services/theming/themes-available";

export type ChartOptions = {
  series: ApexAxisChartSeries;
  chart: ApexChart;
  annotations: ApexAnnotations;
  colors: string[];
  dataLabels: ApexDataLabels;
  grid: ApexGrid;
  legend: ApexLegend;
  markers: ApexMarkers;
  stroke: ApexStroke;
  subtitle: ApexTitleSubtitle;
  title: ApexTitleSubtitle;
  tooltip: ApexTooltip;
  xaxis: ApexXAxis;
  yaxis: ApexYAxis;
};

@Component({
  selector: 'app-line-chart',
  templateUrl: './line-chart.component.html'
})
export class LineChartComponent implements OnInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() series: ApexAxisChartSeries;                                     // Data series to plot
  @Input() classList?: string;                                              // Classes to add

  // Annotations
  @Input() annotations?: ChartAnnotation[];                                 // Annotations on specific axis values

  // Size
  @Input() height?: string = 'auto';                                        // Chart height
  @Input() width?: string = '100%';                                         // Chart width

  // Colors
  @Input() colors?: string[];                                               // Colors for data series

  // DataLabels
  @Input() dataLabels?: boolean;                                            // Show data labels
  @Input() dataLabelsOnSeries?: number[];                                   // Show data labels only on specific series
  @Input() dataLabelsFormatter?: string;                                    // Data labels formatter expression

  // Grid
  @Input() XAxisGrid?: boolean;                                             // Show grid on X-axis
  @Input() YAxisGrid?: boolean;                                             // Show grid on Y-axis
  @Input() stripedGrid?: 'vertical' | 'horizontal';                         // Show striped grid

  // Legend
  @Input() legend?: boolean;                                                // Show legend
  @Input() legendPosition?: 'top' | 'right' | 'bottom' | 'left' = 'bottom'; // Legend position

  // Markers
  @Input() markersSize?: number = 0;                                        // Data points marker size

  // Stroke
  @Input() strokeCurve?: 'smooth' | 'straight' | 'stepline' = 'smooth';     // Whether to draw smooth or straight lines
  @Input() strokeLineCap?: 'butt' | 'square' | 'round' = 'round';           // Sets the start and end points of stroke
  @Input() strokeWidth?: number = 4;                                        // Stroke width

  // Title & Subtitle
  @Input() title?: string;                                                  // Title for chart
  @Input() subtitle?: string;                                               // Subtitle for chart
  @Input() align?: 'left' | 'center' | 'right' = 'left';                    // Title and subtitle alignment

  // Tooltip
  @Input() tooltip?: boolean = true;                                        // Data points tooltip
  @Input() tooltipXFormatter?: string;                                      // Tooltip formatter expression for X values
  @Input() tooltipYFormatter?: string;                                      // Tooltip formatter expression for Y values

  // X-Axis
  @Input() XAxisType?: 'category' | 'datetime' | 'numeric';                 // Type of X-axis
  @Input() XAxisCategories?: (string | number)[];                           // Categories for X-axis
  @Input() XAxisLabel?: string;                                             // X-axis label
  @Input() XAxisTickAmount?: number;                                        // Number of ticks for X-Axis

  // Y-Axis
  @Input() YAxisLabel?: string;                                             // Y-axis label
  @Input() YAxisMin?: number;                                               // Lowest value for Y-axis
  @Input() YAxisMax?: number;                                               // Highest value for Y-axis
  @Input() YAxisTickAmount?: number;                                        // Number of ticks for Y-Axis
  @Input() YAxisReversed?: boolean;                                         // Reverse Y-axis

  // Extras
  @Input() sparkline?: boolean;                                             // Hide everything but primary paths
  @Input() toolbar?: boolean;                                               // Show toolbar with actions
  @Input() toolbarActions?: ('download' | 'selection' | 'zoom' |            // Toolbar actions available
    'zoomin' | 'zoomout' | 'pan' | 'reset')[] = []

  @ViewChild("chart") chart: ChartComponent;
  public chartOptions: Partial<ChartOptions>;

  readonly CHART_TYPE = 'line';

  constructor(
    private themeService: ThemingService,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    const theme = this.themeService.getTheme();

    // Set chart options
    this.chartOptions = {
      series: this.series,
      chart: general(this.CHART_TYPE, this.height, this.width, TextColor(theme), this.sparkline, this.toolbar, this.toolbarActions),
      annotations: !this.sparkline ? annotations(this.annotations) : undefined,
      colors: this.colors,
      dataLabels: dataLabels(this.dataLabels, this.dataLabelsOnSeries, this.dataLabelsFormatter),
      grid: grid(this.XAxisGrid || this.YAxisGrid, this.XAxisGrid, this.YAxisGrid, LineColor(theme),
        this.stripedGrid, {dark: BGDarkColor(theme), light: BGLightColor(theme)}, this.sparkline),
      legend: legend(this.legend, this.legendPosition),
      markers: markers(this.markersSize),
      stroke: stroke(this.strokeCurve, this.strokeLineCap, this.strokeWidth),
      subtitle: subtitle(this.subtitle, this.align),
      title: title(this.title, this.align),
      tooltip: tooltip(!this.sparkline && this.tooltip, {xaxis: this.tooltipXFormatter, yaxis: this.tooltipYFormatter},
        theme === Theme.DARK ? 'dark' : 'light'),
      xaxis: xaxis(this.XAxisType, this.XAxisCategories, LineColor(theme), this.XAxisLabel,
        this.XAxisTickAmount || (this.series[0].data.length > 10 ? 10 : undefined)),
      yaxis: yaxis(this.YAxisReversed, this.YAxisTickAmount, this.YAxisMin, this.YAxisMax, this.YAxisLabel, false)
    };

    // Whenever theme changes, update colors
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.THEME) {
        const theme = this.themeService.getTheme();
        update(this.chart, this.chartOptions, theme === Theme.DARK ? 'dark' : 'light', this.chartOptions.grid.show ? this.stripedGrid : false,
          {text: TextColor(theme), line: LineColor(theme), bg: {dark: BGDarkColor(theme), light: BGLightColor(theme)}})
      }
    });
  }
}
