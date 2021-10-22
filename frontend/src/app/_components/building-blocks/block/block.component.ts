import {Component, Input, OnInit} from '@angular/core';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import {ViewBlock} from "../../../_domain/views/view-block";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import { EventGoToPage } from 'src/app/_domain/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html'
})
export class BlockComponent implements OnInit {

  @Input() view: ViewBlock;
  edit: boolean;
  isEditingLayout: boolean;

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.children]);
    if (!!this.view.events?.click) this.view.class += ' clickable';
    this.edit = this.view.mode === ViewMode.EDIT;
  }

  get ViewHeader(): typeof ViewHeader {
    return ViewHeader;
  }

  get ViewBlock(): typeof ViewBlock {
    return ViewBlock;
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
