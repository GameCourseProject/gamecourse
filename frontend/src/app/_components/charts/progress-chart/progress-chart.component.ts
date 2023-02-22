import {AfterViewInit, Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-progress-chart',
  templateUrl: './progress-chart.component.html'
})
export class ProgressChartComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                          // Unique ID
  @Input() value: number;                                                       // Current progress value
  @Input() max: number;                                                         // Max. progress value

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'sm';                             // Size
  @Input() classList?: string;                                                  // Classes to add
  @Input() tooltip?: 'relative' | 'absolute' | string;                          // Show tooltip on hover:
                                                                                // -> relative: %progress | -> absolute: progress
  // Labels
  @Input() labelInside?: string;                                                // Label inside progress bar (only for sizes 'md' and 'lg')
  @Input() labelLeft?: string;                                                  // Label in the top-left corner
  @Input() labelMiddle?: string;                                                // Label on top centered
  @Input() labelRight?: string;                                                 // Label in the top-right corner

  // Colors
  @Input() progressColor?: 'primary' | 'secondary' | 'accent' | 'info' |        // Progress bar color
    'success' | 'warning' | 'error' | 'neutral' | string = 'neutral';
  @Input() trackColor?: 'primary' | 'secondary' | 'accent' | 'info' |           // Track color
    'success' | 'warning' | 'error' | 'neutral' | string = 'neutral';

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    this.setColor('bar', this.progressColor);
    this.setColor('track', this.trackColor);
    this.setTextColor(this.progressColor);
    if (this.tooltip) this.setTooltip();
  }

  get ProgressSize(): typeof ProgressSize {
    return ProgressSize;
  }

  get progress(): number {
    return Math.round(this.value * 100 / this.max);
  }

  setColor(el: 'bar' | 'track', color: string) {
    const progress: HTMLElement = document.querySelector('#' + this.id + ' .progress-' + el);
    if (Object.keys(ProgressColor).includes(color)) progress.classList.add(ProgressColor[color]);
    else progress.style.backgroundColor = color;
    if (el === 'track') progress.classList.add('bg-opacity-10');
  }

  setTextColor(color: string) {
    const progress: HTMLElement = document.querySelector('#' + this.id + ' .progress-bar');
    if (Object.keys(ProgressTextColor).includes(color)) progress.classList.add(ProgressTextColor[color]);
    else progress.classList.add('text-white/90');
  }

  setTooltip() {
    const progress: HTMLElement = document.querySelector('#' + this.id + ' .progress-bar');
    let classes = 'tooltip hover:outline outline-2 outline-offset-2';
    if (Object.keys(ProgressOutlineColor).includes(this.progressColor)) classes += ' ' + ProgressOutlineColor[this.progressColor];
    else progress.style.outlineColor = this.progressColor;
    classes.split(' ').forEach(cl => progress.classList.add(cl.noWhiteSpace('')));
    progress.setAttribute('data-tip', this.tooltip === 'relative' ? this.progress + '%' :
      this.tooltip === 'absolute' ? this.value.toString() : this.tooltip);
  }

}

enum ProgressSize {
  xs = 'h-1.5',
  sm = 'h-2.5',
  md = 'h-4',
  lg = 'h-6'
}

enum ProgressColor {
  primary = 'bg-primary',
  secondary = 'bg-secondary',
  accent = 'bg-accent',
  info = 'bg-info',
  success = 'bg-success',
  warning = 'bg-warning',
  error = 'bg-error',
  neutral = 'bg-neutral'
}

enum ProgressTextColor {
  primary = 'text-primary-content',
  secondary = 'text-secondary-content',
  accent = 'text-accent-content',
  info = 'text-info-content',
  success = 'text-success-content',
  warning = 'text-warning-content',
  error = 'text-error-content',
  neutral = 'text-neutral-content'
}

enum ProgressOutlineColor {
  primary = 'outline-primary',
  secondary = 'outline-secondary',
  accent = 'outline-accent',
  info = 'outline-info',
  success = 'outline-success',
  warning = 'outline-warning',
  error = 'outline-error',
  neutral = 'outline-neutral'
}
