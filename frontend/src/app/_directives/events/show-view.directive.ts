import {Directive, HostListener, Input} from '@angular/core';
import {EventShowView} from "../../_domain/events/event-show-view";
import {exists} from "../../_utils/misc/misc";

@Directive({
  selector: '[showView]'
})
export class ShowViewDirective {

  @Input('showView') event: EventShowView;

  constructor() { }


  /**
   * Show view.
   *
   * @param viewId
   */
  showView(viewId: number): void {
    const view = document.querySelectorAll('[data-viewId="' + viewId + '"]')[0] as HTMLHtmlElement;
    view.style.display = 'unset';
  }

  @HostListener('click', ['$event'])
  @HostListener('dblclick', ['$event'])
  @HostListener('mouseover', ['$event'])
  @HostListener('mouseout', ['$event'])
  @HostListener('mouseup', ['$event'])
  @HostListener('wheel', ['$event'])
  @HostListener('drag', ['$event'])
  onEvent(event: Event) {
    if (!exists(this.event)) return;
    if (event.type === this.event.type)
      this.showView(this.event.viewId);
  }
}