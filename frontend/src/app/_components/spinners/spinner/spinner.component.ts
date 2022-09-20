import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-spinner',
  templateUrl: './spinner.component.html',
  styleUrls: ['./spinner.component.scss']
})
export class SpinnerComponent implements OnInit {

  @Input() color?: 'primary' | 'primary-content' | 'secondary' | 'secondary-content' |        // Color
    'accent' | 'accent-content' | 'neutral' | 'neutral-content' | 'info' | 'info-content' |
    'success' | 'success-content' | 'warning' | 'warning-content' | 'error' | 'error-content' = 'primary';
  @Input() size?: 'sm' | 'md' | 'lg' = 'md';                                                  // Size

  @Input() classList?: string;                                                                // Classes to add

  sizes = {
    sm: 'h-5 w-5',
    md: 'h-8 w-8',
    lg: 'h-12 w-12'
  }

  colors = {
    primary: 'text-primary',
    primaryContent: 'text-primary-content',
    secondary: 'text-secondary',
    secondaryContent: 'text-secondary-content',
    accent: 'text-accent',
    accentContent: 'text-accent-content',
    neutral: 'text-neutral',
    neutralContent: 'text-neutral-content',
    info: 'text-info',
    infoContent: 'text-info-content',
    success: 'text-success',
    successContent: 'text-success-content',
    warning: 'text-warning',
    warningContent: 'text-warning-content',
    error: 'text-error',
    errorContent: 'text-error-content',
  }

  constructor() { }

  ngOnInit(): void {
  }

}
