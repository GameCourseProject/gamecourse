import {Directive, HostListener, Input} from '@angular/core';
import {View} from "../_domain/views/view";
import {ViewSelectionService} from "../_services/view-selection.service";
import {ModalService} from "../_services/modal.service";

@Directive({
  selector: '[viewSelection]'
})
export class ViewSelectionDirective {

  @Input('viewSelection') view: View;

  constructor(
    private selection: ViewSelectionService
  ) { }

  @HostListener('click', ['$event'])
  onClick(event: MouseEvent) {
    if (!ModalService.isAnyOpen() && (event.target as Element).id != 'component-editor') {
      // Makes sure click only works on closest view
      event.stopPropagation();

      this.selection.update(this.view);
    }
  }

}
