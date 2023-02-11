import { Component, EventEmitter, Input, OnInit, Output, TemplateRef } from '@angular/core';

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html'
})
export class ModalComponent implements OnInit {

  @Input() id: string;                                                // Modal ID
  @Input() classList?: string;                                        // Classes to append
  @Input() templateRef: TemplateRef<any>;                             // Custom template for modal

  @Input() size?: 'sm' | 'md' | 'lg' | string = 'sm';                 // Modal width
  @Input() static?: boolean = false;                                  // Disable closing when clicked outside
  @Input() responsive?: boolean = true;                               // Modal goes bottom on mobile & middle on desktop

  @Input() header?: string;                                           // Modal title
  @Input() headerColor?: 'primary' | 'secondary' | 'accent' |         // Modal title color
    'neutral' | 'info' | 'success' | 'warning' | 'error';
  @Input() headerMarginBottom?: boolean = true;                       // Modal title margin bottom

  @Input() closeBtnText?: string = 'Cancel';                          // Left button text
  @Input() closeBtnColor?: 'primary' | 'secondary' | 'accent' |       // Modal title color
    'neutral' | 'info' | 'success' | 'warning' | 'error';             // Left button color
  @Input() xButton?: boolean = true;                                  // Right top button 'X'

  @Input() extraBtnText?: string;                                     // Middle button text
  @Input() extraBtnColor?: 'primary' | 'secondary' | 'accent' |       // Middle button color
    'neutral' | 'info' | 'success' | 'warning' | 'error';
  @Input() extraBtnOutline?: boolean;                                 // Make middle button outline
  @Input() extraBtnDisabled?: boolean;                                // Make it disabled

  @Input() submitBtnText?: string = 'Submit';                         // Right button text
  @Input() submitBtnColor?: 'primary' | 'secondary' | 'accent' |      // Right button color
    'neutral' | 'info' | 'success' | 'warning' | 'error' = 'primary';
  @Input() submitBtnOutline?: boolean;                                // Make right button outline
  @Input() submitBtnDisabled?: boolean;                               // Make it disabled

  @Input() loading?: boolean;                                         // Show modal spinner while loading
  @Input() actionInProgress?: boolean;                                // Show button spinner while action in progress

  @Output() onClose: EventEmitter<void> = new EventEmitter();
  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() submitBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() extraBtnClicked: EventEmitter<void> = new EventEmitter();

  isOpen: boolean;
  btnClicked: 'close' | 'extra' | 'submit';

  colors: {[key: string]: {text: string, btn: string, btnText: 'primary-content' | 'secondary-content' | 'accent-content' |
        'neutral-content' | 'info-content' | 'success-content' | 'warning-content' | 'error-content'}} = {

    primary: {text: 'text-primary', btn: 'btn-primary', btnText: 'primary-content'},
    secondary: {text: 'text-secondary', btn: 'btn-secondary', btnText: 'secondary-content'},
    accent: {text: 'text-accent', btn: 'btn-accent', btnText: 'accent-content'},
    neutral: {text: 'text-neutral', btn: 'btn-neutral', btnText: 'neutral-content'},
    info: {text: 'text-info', btn: 'btn-info', btnText: 'info-content'},
    success: {text: 'text-success', btn: 'btn-success', btnText: 'success-content'},
    warning: {text: 'text-warning', btn: 'btn-warning', btnText: 'warning-content'},
    error: {text: 'text-error', btn: 'btn-error', btnText: 'error-content'},
  }

  constructor() { }

  ngOnInit(): void {
  }

}
