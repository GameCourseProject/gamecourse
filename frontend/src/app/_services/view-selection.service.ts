import { Injectable } from '@angular/core';
import {View} from "../_domain/views/view";
import {exists} from "../_utils/misc/misc";

@Injectable({
  providedIn: 'root'
})
export class ViewSelectionService {

  static readonly SELECTION_CLASS = 'gc-view-highlight';
  static readonly IGNORE_SELECTION_CLASS = 'ignore-selection';

  private selected: View;
  private disabled: boolean;

  constructor() {
  }

  public hasSelection(): boolean {
    return exists(this.selected);
  }

  public get(): View {
    return this.selected;
  }

  public update(view: View, target?: HTMLElement) {
    if (this.disabled) return;
    if (target && target.classList.contains(ViewSelectionService.IGNORE_SELECTION_CLASS))
      return;

    if (this.isSelected(view)) { // Same view
      ViewSelectionService.unselect(view);
      this.selected = null;

    } else { // Different view
      if (this.selected) ViewSelectionService.unselect(this.selected);
      ViewSelectionService.select(view);
      this.selected = view;
    }
  }

  public clear() {
    if (this.selected) ViewSelectionService.unselect(this.selected);
    this.selected = null;
  }

  private isSelected(view: View): boolean {
    return this.selected && view.id === this.selected.id &&
      view.classList.containsWord(ViewSelectionService.SELECTION_CLASS);
  }

  private static select(view: View): void {
    view.classList += ' ' + ViewSelectionService.SELECTION_CLASS;
  }

  public static unselect(view: View): void {
    const split = view.classList.split(' ');
    const index = split.findIndex(cl => cl === ViewSelectionService.SELECTION_CLASS);
    split.splice(index, 1);
    view.classList = split.join(' ');
  }

  public toggleState(): void {
    this.disabled = !this.disabled;
  }
}
