import {Event} from "../event";
import {EventType} from "../event-type";
import {EventAction} from "../event-action";

export class ShowTooltipEvent extends Event {
  private _text: string;
  private _position: string;

  constructor(type: EventType, text: string, position: string = 'top') {
    super(type, EventAction.SHOW_TOOLTIP);
    this.text = text.replace(/["']/g, '');
    this.position = position.replace(/["']/g, '');
  }


  get text(): string {
    return this._text;
  }

  set text(value: string) {
    this._text = value;
  }

  get position(): string {
    return this._position;
  }

  set position(value: string) {
    this._position = value;
  }

  print(): string {
    const args: string[] = [this.text, this.position];
    return this.printHelper(args);
  }
}
