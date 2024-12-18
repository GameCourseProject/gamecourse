import { Injectable } from '@angular/core';
import * as _ from "lodash"
import { View } from '../_domain/views/view';
import { Aspect } from '../_domain/views/aspects/aspect';

@Injectable({
  providedIn: 'root'
})
export class HistoryService {
    private states: HistoryEntry[] = [];
    private currentStateIndex: number = -1;

    constructor() {}

    saveState(state: HistoryEntry): void {
        // Remove any future states
        this.states.splice(this.currentStateIndex + 1);
        // Add the new state
        this.states.push(_.cloneDeep(state));
        // Update the current state index
        this.currentStateIndex++;
    }

    undo(): HistoryEntry | null {
        if (this.currentStateIndex > 0) {
            this.currentStateIndex--;
            return _.cloneDeep(this.states[this.currentStateIndex]);
        } else {
          return null;
        }
    }

    redo(): HistoryEntry | null {
        if (this.currentStateIndex < this.states.length - 1) {
            this.currentStateIndex++;
            return _.cloneDeep(this.states[this.currentStateIndex]);
        } else {
          return null;
        }
    }

    clear() {
        this.states = [];
        this.currentStateIndex = -1;
    }

    hasUndo(): boolean {
        if (this.currentStateIndex > 0) return true;
        else return false;
    }

    hasRedo(): boolean {
        if (this.currentStateIndex < this.states.length - 1) return true;
        else return false;
    }

    getMostRecent() {
      return _.cloneDeep(this.states[this.currentStateIndex]);
    }
}

export type HistoryEntry = {
  viewsByAspect: { aspect: Aspect, view: View | null }[],
  groupedChildren: Map<number, number[][]>,
  viewsDeleted: number[]
}
