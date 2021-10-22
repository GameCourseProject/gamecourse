import {Component, Input, OnInit} from '@angular/core';
import { ViewHeader } from 'src/app/_domain/views/view-header';
import {ViewBlock} from "../../../_domain/views/view-block";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/views/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/views/events/event-action";
import { EventGoToPage } from 'src/app/_domain/views/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/views/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/views/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/views/events/event-toggle-view';

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html'
})
export class BlockComponent implements OnInit {

  @Input() view: ViewBlock;
  edit: boolean;
  isEditingLayout: boolean;

  readonly BLOCK_CLASS = 'block';
  readonly BLOCK_CHILDREN_CLASS = 'block_children';
  readonly BLOCK_EMPTY_CLASS = 'block_empty';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.children]);
    this.view.class += ' ' + this.BLOCK_CLASS + (!!this.view.events?.click ? ' clickable' : '');
    this.edit = this.view.mode === ViewMode.EDIT;
  }

  get ViewHeader(): typeof ViewHeader {
    return ViewHeader;
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
