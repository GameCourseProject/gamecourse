import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class AlertService {

  constructor() { }

  static showAlert(type: AlertType, msg: string, duration?: number) {
    const alert = document.getElementById(type + '-alert');
    alert.querySelector('.alert-msg').textContent = msg;

    alert.classList.add('hidden');
    alert.classList.remove('hidden');

    setTimeout(() => alert.classList.add('hidden'), duration ?? 5000);
  }
}

export enum AlertType {
  INFO = 'info',
  SUCCESS = 'success',
  WARNING = 'warning',
  ERROR = 'error',
}
