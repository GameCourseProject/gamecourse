import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Observable } from "rxjs";

@Component({
  selector: 'app-simple-modal',
  templateUrl: './simple-modal.component.html'
})
export class SimpleModalComponent implements OnInit {

  @Input() id: string;                                                  // Modal ID
  @Input() classList?: string;                                          // Classes to append

  @Input() static?: boolean = false;                                    // Disable closing when clicked outside
  @Input() responsive?: boolean = true;                                 // Modal goes bottom on mobile & middle on desktop

  @Input() title?: string;                                              // Modal title
  @Input() text?: string;                                               // Modal text
  @Input() xButton?: boolean = true;                                    // Right top button 'X'

  @Input() closeBtn?: boolean = true;                                   // Show left button

  @Input() closeBtnText?: string = 'Cancel';                            // Left button text
  @Input() closeBtnColor?: 'primary' | 'secondary' | 'accent' |         // Modal title color
    'neutral' | 'info' | 'success' | 'warning' | 'error';               // Left button color

  @Input() extraBtnText?: string;                                       // Middle button text
  @Input() extraBtnColor?: 'primary' | 'secondary' | 'accent' |         // Modal title color
    'neutral' | 'info' | 'success' | 'warning' | 'error';               // Middle button color
  @Input() extraBtnOutline?: boolean;                                   // Make middle button outline
  @Input() extraBtnDisabled?: boolean;                                  // Make it disabled

  @Input() submitBtnText?: string = 'Submit';                           // Right button text
  @Input() submitBtnColor?: 'primary' | 'secondary' | 'accent' |        // Modal title color
    'neutral' | 'info' | 'success' | 'warning' | 'error';               // Right button color
  @Input() submitBtnOutline?: boolean;                                  // Make right button outline
  @Input() submitBtnDisabled?: boolean;                                // Make it disabled

  @Input() loading?: boolean;                                           // Show modal spinner while loading
  @Input() actionInProgress?: boolean;                                  // Show button spinner while action in progress

  @Output() onClose: EventEmitter<void> = new EventEmitter();
  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() submitBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() extraBtnClicked: EventEmitter<void> = new EventEmitter();

  constructor() { }

  ngOnInit(): void {
  }

}
