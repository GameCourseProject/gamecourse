import { Injectable } from '@angular/core';
import {Subject} from "rxjs";

/**
 * This service can notify components that active pages have changed.
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
export class PagesUpdateService {

  private _update: Subject<void> = new Subject<void>();

  constructor() { }

  get update(): Subject<void> {
    return this._update;
  }

  public triggerUpdate(): void {
    this._update.next();
  }
}
