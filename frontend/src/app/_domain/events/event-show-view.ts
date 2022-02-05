import {Event} from "./event";
import {EventType} from "./event-type";
import {EventAction} from "./event-action";

export class EventShowView extends Event {

  private _label: string;

  constructor(type: EventType, label: string) {
    super(type, EventAction.SHOW_VIEW);
    this.label = label;
  }

  get label(): string {
    return this._label;
  }

  set label(value: string) {
    this._label = value;
  }

  print(): string {
    return EventAction.SHOW_VIEW + '(' + this.label + ')';
  }
}
