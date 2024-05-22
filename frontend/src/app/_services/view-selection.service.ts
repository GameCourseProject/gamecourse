import {Injectable} from '@angular/core';
import {View, ViewMode} from "../_domain/views/view";
import {exists} from "../_utils/misc/misc";

@Injectable({
  providedIn: 'root'
})
export class ViewSelectionService {
  private selected: View;
  private openContext: boolean = false;

  constructor() {
  }

  public hasSelection(): boolean {
    return exists(this.selected);
  }

  public hasOpen(): boolean {
    return this.openContext;
  }

  public get(): View {
    return this.selected;
  }

  public set(view: View) {
    this.selected = view;
  }

  public update(view: View) {
    this.openContext = false;

    if (this.isSelected(view)) { // Same view
      this.selected = null;
    }
    else { // Different view
      this.selected = view;
    }
  }

  public open(view: View) {
    this.selected = view;
    this.openContext = true;
  }

  public close() {
    this.openContext = false;
  }

  public clear() {
    this.selected = null;
  }

  private isSelected(view: View): boolean {
    return this.selected && view.id === this.selected.id;
  }

  public refresh(): void {
    if (this.selected.mode === ViewMode.EDIT) this.selected.mode = ViewMode.DISPLAY;
    else if (this.selected.mode === ViewMode.DISPLAY) this.selected.mode = ViewMode.EDIT;
  }
}
