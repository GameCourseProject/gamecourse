import {EventType} from "./event-type";
import {EventAction} from "./event-action";

export abstract class Event {

  private _type: EventType;
  private _action: EventAction;
  private _expression: string;

  protected constructor(type: EventType, action: EventAction, expression: string) {
    this.type = type;
    this.action = action;
    this._expression = expression;
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

  get expression(): string {
    return this._expression;
  }

  set expression(value: string) {
    this._expression = value;
  }


  /**
   * Transforms an event into a string.
   *
   * @example action: goToPage, args: [1, 2, 3] --> {actions.goToPage(1, 2, 3)}
   * @example action: goToPage, args: [1] --> {actions.goToPage(1)}
   * @example action: goToPage, args: [] --> {actions.goToPage()}
   */
  abstract print(): string;

  protected printHelper(args: string[]): string {
    return '{actions.' + this.action + '(' + args.join(', ') + ')}';
  }
}
