import { Injectable } from '@angular/core';
import {View, ViewMode} from "../_domain/views/view";
import {exists} from "../_utils/misc/misc";

@Injectable({
  providedIn: 'root'
})
export class ViewSelectionService {

  static readonly IGNORE_SELECTION_CLASS = 'ignore-selection';

  private selected: View;
  private disabled: boolean;
  private rearrange: boolean;

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
      if (this.rearrange) this.switchMode();
      this.selected = null;
    }
    else { // Different view
      if (this.selected) {
        if (this.rearrange) this.switchMode();
      }
      this.selected = view;
      if (this.rearrange) this.switchMode();
    }
  }

  public clear() {
    this.selected = null;
  }

  private isSelected(view: View): boolean {
    return this.selected && view.id === this.selected.id;
  }

  public toggleState(): void {
    this.disabled = !this.disabled;
  }

  public setRearrange(state: boolean): void {
    this.rearrange = state;
    this.switchMode();
  }

  private switchMode(): void {
    if (this.selected?.mode === ViewMode.REARRANGE) {
      this.selected.mode = ViewMode.EDIT;
    }
    else if (this.selected?.mode === ViewMode.EDIT) {
      this.selected.mode = ViewMode.REARRANGE;
    }
  }
}
