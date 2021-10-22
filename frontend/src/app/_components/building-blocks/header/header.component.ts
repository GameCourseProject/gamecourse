import {Component, Input, OnInit} from '@angular/core';
import {ViewHeader} from "../../../_domain/views/view-header";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/views/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/views/events/event-action";
import { EventGoToPage } from 'src/app/_domain/views/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/views/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/views/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/views/events/event-toggle-view';

@Component({
  selector: 'bb-header',
  templateUrl: './header.component.html'
})
export class HeaderComponent implements OnInit {

  @Input() view: ViewHeader;
  edit: boolean;

  readonly HEADER_CLASS = 'header';
  readonly IMAGE_CLASS = 'header_image';
  readonly TITLE_CLASS = 'header_title';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.image, this.view.title]);
    this.edit = this.view.mode === ViewMode.EDIT;

    this.view.class += ' ' + this.HEADER_CLASS + (!!this.view.events?.click ? ' clickable' : '');
    this.view.image.class += ' ' + this.IMAGE_CLASS + (!!this.view.image.events?.click ? ' clickable' : '');
    this.view.title.class += ' ' + this.TITLE_CLASS + (!!this.view.title.events?.click ? ' clickable' : '');
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
