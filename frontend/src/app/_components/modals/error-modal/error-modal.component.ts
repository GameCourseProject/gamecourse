import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

@Component({
  selector: 'app-error-modal',
  templateUrl: './error-modal.component.html',
  styleUrls: ['./error-modal.component.scss']
})
export class ErrorModalComponent implements OnInit {

  @Input() id?: string = 'message-api-box';             // Modal id
  @Input() classList?: string;                          // Classes to append

  @Input() isModalOpen: boolean;                        // Whether or not the modal is visible
  @Input() actionInProgress?: boolean;                  // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;            // Whether to close the modal when clicking outside

  @Input() text: string;                                // Text to display
  @Input() negativeBtnText: string = 'Confirm';         // Negative btn text

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() negativeBtnClicked: EventEmitter<void> = new EventEmitter();


  constructor() { }

  ngOnInit(): void {
  }

}
