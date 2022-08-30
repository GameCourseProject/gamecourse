import {Directive, HostListener, Input} from '@angular/core';
import {EventHideView} from "../../_domain/views/events/event-hide-view";
import {exists} from "../../_utils/misc/misc";
import {ViewMode} from "../../_domain/views/view";

@Directive({
  selector: '[hideView]'
})
export class HideViewDirective {

  @Input('hideView') event: EventHideView;
  @Input() mode: ViewMode;

  constructor() { }


  /**
   * Hide view.
   *
   * @param label
   */
  hideView(label: string): void {
    const view = document.querySelectorAll('[data-label="' + label + '"]')[0] as HTMLHtmlElement;
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
    if (!exists(this.event) || this.mode === ViewMode.EDIT) return;
    if (event.type === this.event.type)
      this.hideView(this.event.label);
  }
}
