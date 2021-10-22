import {Directive, HostListener, Input} from '@angular/core';
import {EventToggleView} from "../../_domain/views/events/event-toggle-view";
import {exists} from "../../_utils/misc/misc";

@Directive({
  selector: '[toggleView]'
})
export class ToggleViewDirective {

  @Input('toggleView') event: EventToggleView;

  constructor() { }


  /**
   * Toggle view.
   *
   * @param viewId
   */
  toggleView(viewId: number): void {
    const view = document.querySelectorAll('[data-viewId="' + viewId + '"]')[0] as HTMLHtmlElement;
    if (view.style.display === 'none') view.style.display = 'unset';
    else view.style.display = 'none';
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
      this.toggleView(this.event.viewId);
  }
}
