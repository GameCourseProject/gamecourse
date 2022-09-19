import {Component, EventEmitter, HostListener, Input, OnInit, Output} from '@angular/core';

@Component({
  selector: 'app-top-actions',
  templateUrl: './top-actions.component.html'
})
export class TopActionsComponent implements OnInit {

  @Input() leftActions?: {action: string, icon?: string}[];   // NOTE: after two actions it goes to 'More actions' dropdown
  @Input() rightActions?: {action: string, icon?: string, outline?: boolean,
    color?: 'ghost' | 'primary' | 'secondary' | 'accent' | 'neutral' | 'info' | 'success' | 'warning' | 'error'}[];

  @Output() btnClicked: EventEmitter<string> = new EventEmitter<string>();

  mobile: boolean;

  buttonColors = {
    ghost: 'btn-ghost',
    primary: 'btn-primary',
    secondary: 'btn-secondary',
    accent: 'btn-accent',
    neutral: 'btn-neutral',
    info: 'btn-info',
    success: 'btn-success',
    warning: 'btn-warning',
    error: 'btn-error'
  }

  constructor() { }

  ngOnInit(): void {
    this.onWindowResize();
  }

  @HostListener('window:resize', [])
  onWindowResize(): void {
    this.mobile = window.innerWidth < 640;
  }

}
