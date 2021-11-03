import { Injectable } from '@angular/core';
import {View} from "../_domain/views/view";
import {exists} from "../_utils/misc/misc";

@Injectable({
  providedIn: 'root'
})
export class ViewSelectionService {

  static readonly SELECTION_CLASS = 'view-highlight';
  static readonly IGNORE_SELECTION_CLASS = 'ignore-selection';

  private selected: View;

  constructor() {
  }

  public hasSelection(): boolean {
    return exists(this.selected);
  }

  public get(): View {
    return this.selected;
  }

  public update(target: HTMLElement, view: View) {
    if (target.classList.contains(ViewSelectionService.IGNORE_SELECTION_CLASS)) return;

    if (this.isSelected(view)) { // Same view
      this.unselect(view);
      this.selected = null;

    } else { // Different view
      if (this.selected) this.unselect(this.selected);
      this.select(view);
      this.selected = view;
    }
  }

  public clear() {
    if (this.selected) {
      this.unselect(this.selected);
      this.selected = null;
    }
  }

  private isSelected(view: View): boolean {
    return view.class.containsWord(ViewSelectionService.SELECTION_CLASS);
  }

  private select(view: View): void {
    view.class += ' ' + ViewSelectionService.SELECTION_CLASS;
  }

  private unselect(view: View): void {
    const split = view.class.split(' ');
    const index = split.findIndex(cl => cl === ViewSelectionService.SELECTION_CLASS);
    split.splice(index, 1);
    view.class = split.join(' ');
  }
}
