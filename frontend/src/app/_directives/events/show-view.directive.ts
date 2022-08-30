import {Directive, HostListener, Input} from '@angular/core';
import {EventShowView} from "../../_domain/views/events/event-show-view";
import {exists} from "../../_utils/misc/misc";
import {ViewMode} from "../../_domain/views/view";

@Directive({
  selector: '[showView]'
})
export class ShowViewDirective {

  @Input('showView') event: EventShowView;
  @Input() mode: ViewMode;

  constructor() { }


  /**
   * Show view.
   *
   * @param label
   */
  showView(label: string): void {
    const view = document.querySelectorAll('[data-label="' + label + '"]')[0] as HTMLHtmlElement;
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
    if (!exists(this.event) || this.mode === ViewMode.EDIT) return;
    if (event.type === this.event.type)
      this.showView(this.event.label);
  }
}
