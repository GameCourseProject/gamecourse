import {Component, Input, OnInit} from '@angular/core';
import {ViewHeader} from "../../../_domain/views/view-header";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import { EventGoToPage } from 'src/app/_domain/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-header',
  templateUrl: './header.component.html'
})
export class HeaderComponent implements OnInit {

  @Input() view: ViewHeader;
  edit: boolean;

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.image, this.view.title]);
    this.edit = this.view.mode === ViewMode.EDIT;

    if (!!this.view.events?.click) this.view.class += ' gc-clickable';
    if (!!this.view.image.events?.click) this.view.image.class += ' gc-clickable';
    if (!!this.view.title.events?.click) this.view.title.class += ' gc-clickable';
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
