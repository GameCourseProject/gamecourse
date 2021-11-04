import {Component, EventEmitter, Input, OnInit, Output, TemplateRef} from '@angular/core';

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss']
})
export class ModalComponent implements OnInit {

  @Input() id?: string;                         // Modal id
  @Input() classList?: string;                  // Classes to append

  @Input() isModalOpen: boolean;                // Whether or not the modal is visible
  @Input() actionInProgress?: boolean;          // Show loader while action in progress
  @Input() innerClickEvents: boolean = true;    // Whether to close the modal when clicking outside

  @Input() templateRef: TemplateRef<any>;       // Custom template for modal

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();

  // ignore 1st click outside (the one that triggers the modal)
  ignore = true;

  constructor() { }

  ngOnInit(): void {
  }

  clickedOutside(): void {
    if (this.ignore) this.ignore = false;
    else if (this.innerClickEvents) this.closeBtnClicked.emit();
  }

}
