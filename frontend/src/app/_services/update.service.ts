import { Injectable } from '@angular/core';
import {Subject} from "rxjs";

/**
 * This service can notify components that an update of a certain type
 * should be done.
 *
 * Any component interested in the update need to have this service
 * injected and then subscribe to the Observable via the getter.
 *
 * Any component that want to signal the update need to have this service
 * injected and then use the trigger method to signal it.
 */
@Injectable({
  providedIn: 'root'
})
export class UpdateService {

  private _update: Subject<UpdateType> = new Subject<UpdateType>();

  constructor() { }

  get update(): Subject<UpdateType> {
    return this._update;
  }

  public triggerUpdate(type: UpdateType): void {
    this._update.next(type);
  }
}

export enum UpdateType {
  AVATAR,
  ACTIVE_PAGES,
}
