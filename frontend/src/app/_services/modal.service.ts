import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ModalService {

  constructor() { }

  static openModal(id: string) {
    setTimeout(() => {
      const modal = document.getElementById(id) as HTMLInputElement;
      modal.checked = true;
    }, 0);
  }

  static closeModal(id: string) {
    const modal = document.getElementById(id) as HTMLInputElement;
    modal.checked = false;
  }

  static toggleModal(id: string) {
    setTimeout(() =>  {
      const modal = document.getElementById(id) as HTMLInputElement;
      modal.checked = !modal.checked;
    }, 0);
  }

  static isOpen(id: string): boolean {
    const modal = document.getElementById(id) as HTMLInputElement;
    return modal?.checked;
  }

  static isAnyOpen(): boolean {
    const modals = document.getElementsByClassName('modal-toggle');
    const modalArray = Array.from(modals) as HTMLInputElement[];
    return modalArray.some(e => e.checked == true);
  }
}
