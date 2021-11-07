import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";

export class EventHideView extends Event {

  private _viewId: number;  // FIXME: use label or delete

  constructor(type: EventType, viewId: number) {
    super(type, EventAction.HIDE_VIEW);
    this.viewId = viewId;
  }

  get viewId(): number {
    return this._viewId;
  }

  set viewId(value: number) {
    this._viewId = value;
  }

  print(): string {
    return EventAction.HIDE_VIEW + '(' + this.viewId + ')';
  }
}
