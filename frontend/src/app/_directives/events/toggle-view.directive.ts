import {Directive, HostListener, Input} from '@angular/core';
import {EventToggleView} from "../../_domain/events/event-toggle-view";
import {exists} from "../../_utils/misc/misc";
import {View, ViewMode} from "../../_domain/views/view";

@Directive({
  selector: '[toggleView]'
})
export class ToggleViewDirective {

  @Input('toggleView') event: EventToggleView;
  @Input() mode: ViewMode;

  constructor() { }


  /**
   * Toggle view.
   *
   * @param label
   */
  toggleView(label: string): void {
    label = label.substr(1, label.length - 2); // NOTE: remove ''
    const view = document.querySelector('.' + View.VIEW_CLASS + '[data-label="' + label + '"]') as HTMLHtmlElement;
    view.style.display = view.style.display === 'none' ? '' : 'none';
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
      this.toggleView(this.event.label);
  }
}
