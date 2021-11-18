import {Component, Input, OnInit} from '@angular/core';
import {ViewTable} from "../../../_domain/views/view-table";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/events/event-action";
import { EventGoToPage } from 'src/app/_domain/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/events/event-toggle-view';

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html'
})
export class TableComponent implements OnInit {

  @Input() view: ViewTable;
  edit: boolean;
  isEditingLayout: boolean;

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.headerRows, this.view.rows, this.view.nrColumns]);
    this.edit = this.view.mode === ViewMode.EDIT;

    if (!!this.view.events?.click) this.view.class += ' gc-clickable';
    this.view.headerRows.forEach(row => row.children.forEach(header => {
      if (!!header.events?.click) header.class += ' gc-clickable';
    }));
    this.view.rows.forEach(row => row.children.forEach(r => {
      if(!!r.events?.click) r.class += ' gc-clickable';
    }));
  }

  get ViewTable(): typeof ViewTable {
    return ViewTable;
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
