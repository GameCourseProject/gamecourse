import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class SidebarService {

  constructor() { }

  toggle() {
    this.sidebar.classList.toggle('-translate-x-full');
    this.overlay.classList.toggle('hidden');
  }

  togglePC() {
    this.sidebar.classList.toggle('lg:translate-x-0');
    this.sidebar.classList.toggle('lg:-translate-x-[110%]');
    this.main.classList.toggle('lg:pl-80');
    this.navbar.classList.toggle('lg:pl-72');
  }

  open() {
    this.sidebar.classList.remove('-translate-x-full');
    this.overlay.classList.remove('hidden');
    this.toggler.checked = true;
  }

  close() {
    this.sidebar.classList.add('-translate-x-full');
    this.overlay.classList.add('hidden');
    this.toggler.checked = false;
  }

  private get sidebar(): HTMLElement {
    return document.getElementById('sidebar');
  }

  private get overlay(): HTMLElement {
    return document.getElementById('sidebar-overlay');
  }

  private get toggler(): HTMLInputElement {
    return document.getElementById('sidebar-toggler') as HTMLInputElement;
  }

  private get main(): HTMLElement {
    return document.getElementsByTagName('main')[0];
  }

  private get navbar(): HTMLElement {
    return document.getElementById('navbar-left-container');
  }
}
