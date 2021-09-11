import {Component, EventEmitter, Input, OnInit, Output, TemplateRef} from '@angular/core';

@Component({
  selector: 'app-verification-modal',
  templateUrl: './verification-modal.component.html',
  styleUrls: ['./verification-modal.component.scss']
})
export class VerificationModalComponent implements OnInit {

  @Input() id?: string;                                 // Modal id
  @Input() classList?: string;                          // Classes to append

  @Input() isModalOpen: boolean;                        // Whether or not the modal is visible
  @Input() actionInProgress?: boolean;                  // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;            // Whether to close the modal when clicking outside

  @Input() text: string;                                // Text to display
  @Input() target: string;                              // Target of action
  @Input() positiveBtnText: string;                     // Positive btn text
  @Input() negativeBtnText: string = 'Cancel';          // Negative btn text

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() positiveBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() negativeBtnClicked: EventEmitter<void> = new EventEmitter();


  constructor() { }

  ngOnInit(): void {
  }

}
