import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class AlertService {

  constructor() { }

  static clear(type: AlertType) {
    const alert = document.getElementById(type + '-alert');
    alert.classList.add('hidden');
  }

  static showAlert(type: AlertType, msg: string, duration?: number) {
    // Hide previous alert of same type
    const alert = document.getElementById(type + '-alert');
    alert.classList.add('hidden');

    // Set alert message
    alert.querySelector('.alert-msg').textContent = msg;

    // Hide alert button (only used for error stack)
    const alertBtn = document.getElementById('error-alert-btn');
    alertBtn.classList.add('hidden');

    // Show alert
    alert.classList.remove('hidden');

    // Hide alert automatically
    if (type != AlertType.ERROR) setTimeout(() => alert.classList.add('hidden'), duration ?? 5000);
  }

  static showErrorAlert(error: {message: string, stack: string, full: string}) {
    // Show alert
    this.showAlert(AlertType.ERROR, error.message ?? error.full);

    // Show alert button
    if (error.stack) {
      const alertBtn = document.getElementById('error-alert-btn');
      alertBtn.classList.remove('hidden');
    }
  }
}

export enum AlertType {
  INFO = 'info',
  SUCCESS = 'success',
  WARNING = 'warning',
  ERROR = 'error',
}
