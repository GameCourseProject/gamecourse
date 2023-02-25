import {Directive, HostListener, Input} from '@angular/core';

import {ShowTooltipEvent} from "../../../../_domain/views/events/actions/show-tooltip-event";
import {View, ViewMode} from "../../../../_domain/views/view";
import {exists} from "../../../../_utils/misc/misc";

@Directive({selector: '[showTooltip]'})
export class ShowTooltipDirective {

  @Input('showTooltip') info: {event: ShowTooltipEvent, view: View};

  constructor() { }


  /**
   * Shows a tooltip text message on a view.
   * Tooltip position options: top, bottom, left, right.
   *
   * @param el
   * @param text
   * @param position
   */
  showTooltip(el: HTMLElement, text: string, position: string): void {
    // Find view element
    while (el.getAttribute('data-view-id') != this.info.view.id.toString()) {
      el = el.parentElement;
    }

    // Show tooltip
    el.classList.add('tooltip');
    el.classList.add('tooltip-' + position);
    el.setAttribute('data-tip', text);
  }

  @HostListener('click', ['$event'])
  @HostListener('dblclick', ['$event'])
  @HostListener('mouseover', ['$event'])
  @HostListener('mouseout', ['$event'])
  @HostListener('mouseup', ['$event'])
  @HostListener('wheel', ['$event'])
  @HostListener('drag', ['$event'])
  onEvent(event: Event) {
    // Do nothing
    if (!exists(this.info.event) || this.info.view.mode === ViewMode.EDIT) return;

    // Perform event
    if (event.type === this.info.event.type)
      this.showTooltip(event.target as HTMLElement, this.info.event.text, this.info.event.position);
  }

}
