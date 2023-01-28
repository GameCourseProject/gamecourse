import {AfterViewInit, ChangeDetectionStrategy, Component, Input, OnInit} from '@angular/core';

import {Theme} from 'src/app/_services/theming/themes-available';

import {ThemingService} from "../../../_services/theming/theming.service";

@Component({
  selector: 'app-logs-viewer',
  templateUrl: './logs-viewer.component.html',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class LogsViewerComponent implements OnInit, AfterViewInit {

  items = Array.from({length: 100000}).map((_, i) => `Item #${i}`);

  @Input() logs: string;

  lines: {text: string, color: 'default' | 'success' | 'warning' | 'error'}[];
  loading: boolean = true;

  constructor(
    public themeService: ThemingService
  ) { }

  ngOnInit(): void {
    this.lines = this.getLines(this.logs);
    this.loading = false;
  }

  ngAfterViewInit(): void {
    this.scrollTo('bottom');
  }

  scrollTo(to: 'top' | 'bottom') {
    setTimeout(() => {
      const div = document.getElementsByClassName("mockup-code")[0];
      div.scrollTop = to === 'top' ? 0 : div.scrollHeight;
    }, 0)
  }

  getBgColor(): string {
    if (this.themeService.getTheme() === Theme.DARK) return 'bg-[#191D24]';
    return 'bg-[#3D4451]';
  }

  getTextColor(): string {
    if (this.themeService.getTheme() === Theme.DARK) return 'text-base-content';
    return '';
  }

  getLines(text: string): {text: string, color: 'default' | 'success' | 'warning' | 'error'}[] {
    const lines: {text: string, color: 'default' | 'success' | 'warning' | 'error'}[] = [];
    const splitted = text.split(/\r?\n/)

    let currentColor: 'default' | 'success' | 'warning' | 'error' = 'default';
    let error: number = 0;
    for (let i = 0; i < splitted.length; i++) {
      const line = splitted[i];

      if (line.containsWord('[SUCCESS]')) { currentColor = 'success'; lines[i - 1].color = currentColor; }
      else if (line.containsWord('[WARNING]')) { currentColor = 'warning'; lines[i - 1].color = currentColor; }
      else if (line.containsWord('[ERROR]')) { currentColor = 'error'; lines[i - 1].color = currentColor; error = 0; }

      lines.push({text: splitted[i], color: currentColor});

      if (line === '================================================================================') {
        if (currentColor === 'error') error++;
        if (currentColor === 'success' || currentColor === 'warning' || (currentColor === 'error' && error === 2)) {
          currentColor = "default";
          error = 0;
        }
      }
    }

    return lines;
  }

  get LineColor(): typeof LineColor {
    return LineColor;
  }
}

enum LineColor {
  default = '',
  success = 'text-success',
  warning = 'text-warning',
  error = 'text-error'
}
