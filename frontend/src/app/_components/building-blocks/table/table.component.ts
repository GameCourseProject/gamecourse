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

  readonly TABLE_CLASS = 'table';
  readonly TABLE_HEADER_CLASS = 'table_header';
  readonly TABLE_BODY_CLASS = 'table_body';
  readonly TABLE_TOOLBAR_CLASS = 'table_toolbar';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.headerRows, this.view.rows, this.view.nrColumns]);
    this.edit = this.view.mode === ViewMode.EDIT;

    this.view.class += ' ' + this.TABLE_CLASS + (!!this.view.events?.click ? ' clickable' : '');
    this.view.headerRows.forEach(row => row.values.forEach(header => header.class += ' ' + this.TABLE_HEADER_CLASS + (!!header.events?.click ? ' clickable' : '')));
    this.view.rows.forEach(row => row.values.forEach(r => r.class += ' ' + this.TABLE_BODY_CLASS + (!!r.events?.click ? ' clickable' : '')));
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
