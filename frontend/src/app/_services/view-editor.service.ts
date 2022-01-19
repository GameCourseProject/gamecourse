import { Injectable } from '@angular/core';
import {Subject} from "rxjs";

/**
 * This service can notify the view editor that an action of a certain
 * type should be done.
 *
 * The view editor needs to have this service injected and then subscribe
 * to the Observable via the getter.
 *
 * Any building block that wants to signal an action needs to have this
 * service injected and then use the trigger method to signal it.
 */
@Injectable({
  providedIn: 'root'
})
export class ViewEditorService {

  private _action: Subject<{action: EditorAction, params?: any}> = new Subject<{action: EditorAction, params?: any}>();

  constructor() { }

  get action(): Subject<{action: EditorAction, params?: any}> {
    return this._action;
  }

  public triggerAction(action: {action: EditorAction, params?: any}): void {
    this._action.next(action);
  }
}

export enum EditorAction {
  BLOCK_ADD_CHILD,
  TABLE_MOVE_LEFT,
  TABLE_MOVE_RIGHT,
  TABLE_MOVE_UP,
  TABLE_MOVE_DOWN,
  TABLE_INSERT_LEFT,
  TABLE_INSERT_RIGHT,
  TABLE_INSERT_UP,
  TABLE_INSERT_DOWN,
  TABLE_DELETE_COLUMN,
  TABLE_DELETE_ROW,
  TABLE_EDIT_ROW,
  TABLE_ADD_HEADER_ROW,
  TABLE_ADD_ROW,
}
