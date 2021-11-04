import {Directive, HostListener, Input} from '@angular/core';
import {View, ViewMode} from "../_domain/views/view";
import {ViewSelectionService} from "../_services/view-selection.service";

@Directive({
  selector: '[viewSelection]'
})
export class ViewSelectionDirective {

  @Input('viewSelection') view: View;

  constructor(
    private selection: ViewSelectionService
  ) { }

  @HostListener('click', ['$event'])
  onClick(event: any) {
    if (this.view.mode === ViewMode.EDIT) {
      // Makes sure click only works on closest view
      event.stopPropagation();

      this.selection.update(this.view, event.target);
    }
  }

}
