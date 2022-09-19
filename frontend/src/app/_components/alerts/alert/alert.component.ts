import {Component, Input, OnInit} from '@angular/core';
import {AlertType} from "../../../_services/alert.service";

@Component({
  selector: 'app-alert',
  templateUrl: './alert.component.html'
})
export class AlertComponent implements OnInit {

  @Input() type: AlertType;     // Alert type
  @Input() msg: string;         // Message to display

  alerts = {
    [AlertType.INFO]: {alert: 'alert-info', text: 'text-info-content', icon: 'feather-info'},
    [AlertType.SUCCESS]: {alert: 'alert-success', text: 'text-success-content', icon: 'feather-check-circle'},
    [AlertType.WARNING]: {alert: 'alert-warning', text: 'text-warning-content', icon: 'feather-alert-triangle'},
    [AlertType.ERROR]: {alert: 'alert-error', text: 'text-error-content', icon: 'feather-x-circle'}
  }

  get AlertType(): typeof AlertType {
    return AlertType;
  }

  constructor() { }

  ngOnInit(): void {
  }

}
