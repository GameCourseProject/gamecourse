import { Injectable } from '@angular/core';
import {View} from "../_domain/views/view";

@Injectable({
  providedIn: 'root'
})
export class ViewSelectionService {

  readonly SELECTION_CLASS = 'highlight';

  private selected: View;

  constructor() {
  }

  public get(): View {
    return this.selected;
  }

  public update(event, view: View) {
    if (this.isSelected(view)) { // Same view
      this.toggleSelection(view);
      this.selected = null;

    } else { // Different view
      if (this.selected) this.toggleSelection(this.selected);
      this.toggleSelection(view);
      this.selected = view;
    }
    console.log(this.selected?.type)
  }

  private isSelected(view: View): false | number {
    const split = view.class.split(' ');
    const index = split.findIndex(cl => cl === this.SELECTION_CLASS);
    return index !== -1 ? index : false;
  }

  private toggleSelection(view: View): void {
    const selected = this.isSelected(view);
    if (selected === false) { // Adding
      view.class += ' ' + this.SELECTION_CLASS;

    } else { // Removing
      const split = view.class.split(' ');
      split.splice(selected, 1);
      view.class = split.join(' ');
    }
  }
}
