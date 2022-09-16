import {Component, Input, OnInit} from '@angular/core';
import {AlertType} from "../../../_services/alert.service";

@Component({
  selector: 'app-alert',
  templateUrl: './alert.component.html'
})
export class AlertComponent implements OnInit {

  @Input() type: AlertType;     // Alert type
  @Input() msg: string;         // Message to display

  constructor() { }

  ngOnInit(): void {
  }

  getIcon(): string {
    if (this.type === AlertType.INFO) return 'feather-info';
    else if (this.type === AlertType.SUCCESS) return 'feather-check-circle';
    else if (this.type === AlertType.WARNING) return 'feather-alert-triangle';
    else return 'feather-x-circle';
  }

  hide() {
    const alert = document.getElementById(this.type + '-alert');
    alert.classList.add('hidden');
  }

}
