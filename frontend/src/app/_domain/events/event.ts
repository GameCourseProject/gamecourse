import {EventType} from "./event-type";
import {EventAction} from "./event-action";

export abstract class Event {

  private _type: EventType;
  private _action: EventAction;

  protected constructor(type: EventType, action: EventAction) {
    this.type = type;
    this.action = action;
  }

  get type(): EventType {
    return this._type;
  }

  set type(value: EventType) {
    this._type = value;
  }

  get action(): EventAction {
    return this._action;
  }

  set action(value: EventAction) {
    this._action = value;
  }

  abstract print(): string;
}
