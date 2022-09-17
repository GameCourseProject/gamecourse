import { Component, EventEmitter, Input, OnInit, Output, TemplateRef } from '@angular/core';
import { Observable } from "rxjs";

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html'
})
export class ModalComponent implements OnInit {

  @Input() id: string;                            // Modal ID
  @Input() classList?: string;                    // Classes to append
  @Input() templateRef: TemplateRef<any>;         // Custom template for modal

  @Input() size?: 'sm' | 'md' | 'lg' = 'sm';      // Modal width
  @Input() static?: boolean = false;              // Disable closing when clicked outside
  @Input() responsive?: boolean = true;           // Modal goes bottom on mobile & middle on desktop

  @Input() header?: string;                       // Modal title
  @Input() headerColor?: string;                  // Modal title color
  @Input() headerMarginBottom?: boolean = true;   // Modal title margin bottom

  @Input() closeBtnText?: string = 'Cancel';      // Left button text
  @Input() closeBtnColor?: string;                // Left button color
  @Input() extraBtnText?: string;                 // Middle button text
  @Input() extraBtnColor?: string;                // Middle button color
  @Input() extraBtnOutline?: boolean;             // Make middle button outline
  @Input() submitBtnText?: string = 'Submit';     // Right button text
  @Input() submitBtnColor?: string;               // Right button color
  @Input() submitBtnOutline?: boolean;            // Make right button outline

  @Input() closeModal?: Observable<void>;         // Close modal on demand
  @Input() loading?: boolean;                     // Show modal spinner while loading
  @Input() actionInProgress?: boolean;            // Show button spinner while action in progress

  @Output() onClose: EventEmitter<void> = new EventEmitter();
  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() submitBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() extraBtnClicked: EventEmitter<void> = new EventEmitter();

  btnClicked: 'close' | 'extra' | 'submit';

  constructor() { }

  ngOnInit(): void {
    if (this.closeModal) {
      this.closeModal.subscribe(() => {
        // Force modal closing
        const toggler = document.getElementById(this.id) as HTMLInputElement;
        toggler.checked = false;

        // Emit event
        this.onClose.emit();
      });
    }
  }

}
