import {Directive, HostListener, Input} from '@angular/core';
import {EventHideView} from "../../_domain/views/events/event-hide-view";
import {exists} from "../../_utils/misc/misc";

@Directive({
  selector: '[hideView]'
})
export class HideViewDirective {

  @Input('hideView') event: EventHideView;

  constructor() { }


  /**
   * Hide view.
   *
   * @param viewId
   */
  hideView(viewId: number): void {
    const view = document.querySelectorAll('[data-viewId="' + viewId + '"]')[0] as HTMLHtmlElement;
    view.style.display = 'none';
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
      this.hideView(this.event.viewId);
  }
}
