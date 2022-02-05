import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";

export class EventHideView extends Event {

  private _label: string;

  constructor(type: EventType, label: string) {
    super(type, EventAction.HIDE_VIEW);
    this.label = label;
  }

  get label(): string {
    return this._label;
  }

  set label(value: string) {
    this._label = value;
  }

  print(): string {
    return EventAction.HIDE_VIEW + '(' + this.label + ')';
  }
}
