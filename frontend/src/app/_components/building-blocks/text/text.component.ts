import {Component, Input, OnInit} from '@angular/core';
import {ViewText} from "../../../_domain/views/view-text";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {EventGoToPage} from "../../../_domain/events/event-go-to-page";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import { EventHideView } from 'src/app/_domain/events/event-hide-view';
import {EventShowView} from "../../../_domain/events/event-show-view";
import { EventToggleView } from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html'
})
export class TextComponent implements OnInit {

  @Input() view: ViewText;
  edit: boolean;

  isEmpty: boolean;

  readonly DEFAULT = '(Empty value)';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.value]);
    if (!!this.view.events?.click) this.view.class += ' clickable';
    this.edit = this.view.mode === ViewMode.EDIT;
    this.isEmpty = this.view.value.isEmpty();
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  getEvent(action: EventAction): Event {
    if (!exists(this.view.events)) return null;
    return getEventFromAction(this.view.events, action);
  }

  get EventAction(): typeof EventAction {
    return EventAction;
  }

  get EventGoToPage(): typeof EventGoToPage {
    return EventGoToPage;
  }

  get EventHideView(): typeof EventHideView {
    return EventHideView;
  }

  get EventShowView(): typeof EventShowView {
    return EventShowView;
  }

  get EventToggleView(): typeof EventToggleView {
    return EventToggleView;
  }

}
