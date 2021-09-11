import {Component, EventEmitter, Input, OnInit, Output, TemplateRef} from '@angular/core';

@Component({
  selector: 'app-modal',
  templateUrl: './modal.component.html',
  styleUrls: ['./modal.component.scss']
})
export class ModalComponent implements OnInit {

  @Input() id?: string;                      // Modal id
  @Input() title: string;                    // Modal title
  @Input() isModalOpen: boolean;             // Whether or not the modal is visible
  @Input() positiveBtnText: string;          // Right btn text
  @Input() positiveBtnDisabled: boolean;     // Whether or not the right btn is disabled
  @Input() actionInProgress?: boolean;       // Show loader while action in progress

  @Input() templateRef: TemplateRef<any>;    // Custom template for modal

  @Output() closeBtnClicked: EventEmitter<void> = new EventEmitter();
  @Output() positiveBtnClicked: EventEmitter<void> = new EventEmitter();

  // ignore 1st click outside (the one that triggers the modal)
  ignore = true;

  constructor() { }

  ngOnInit(): void {
  }

  clickedOutside(): void {
    if (this.ignore) this.ignore = false;
    else this.closeBtnClicked.emit();
  }

}
