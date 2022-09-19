import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-spinner',
  templateUrl: './spinner.component.html',
  styleUrls: ['./spinner.component.scss']
})
export class SpinnerComponent implements OnInit {

  @Input() color?: 'primary' | 'secondary' | 'accent' |                       // Color
    'neutral' | 'info' | 'success' | 'warning' | 'error' = 'primary';
  @Input() size?: 'sm' | 'md' | 'lg' = 'md';                                  // Size

  @Input() classList?: string;                                                // Classes to add

  sizes = {
    sm: 'h-5 w-5',
    md: 'h-8 w-8',
    lg: 'h-12 w-12'
  }

  colors = {
    primary: 'text-primary',
    secondary: 'text-secondary',
    accent: 'text-accent',
    neutral: 'text-neutral',
    info: 'text-info',
    success: 'text-success',
    warning: 'text-warning',
    error: 'text-error',
  }

  constructor() { }

  ngOnInit(): void {
  }

}
