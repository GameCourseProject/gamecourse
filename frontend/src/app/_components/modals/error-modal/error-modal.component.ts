import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

import html2canvas from "html2canvas";
import * as moment from "moment";

@Component({
  selector: 'app-error-modal',
  templateUrl: './error-modal.component.html'
})
export class ErrorModalComponent implements OnInit {

  @Input() error: { message: string, stack: string, full: string };
  @Output() onClose: EventEmitter<void> = new EventEmitter();

  loading: boolean;

  constructor() { }

  ngOnInit(): void {
  }

  takeScreenshot() {
    this.loading = true;
    html2canvas(document.body).then(canvas => {
      const a = document.createElement('a');
      a.download = moment().format('YYYY-MM-DD HH:mm:ss') + '.png';
      a.href = canvas.toDataURL('image/png');
      a.click();
      this.loading = false;
    });
  }

}
