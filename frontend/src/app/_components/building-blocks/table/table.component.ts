import {Component, Input, OnInit} from '@angular/core';
import {ViewTable} from "../../../_domain/views/view-table";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode, VisibilityType} from "../../../_domain/views/view";
import {Event} from "../../../_domain/views/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/views/events/event-action";
import { EventGoToPage } from 'src/app/_domain/views/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/views/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/views/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/views/events/event-toggle-view';
import {EditorAction, ViewEditorService} from "../../../_services/view-editor.service";
import { ViewRow } from 'src/app/_domain/views/view-row';

@Component({
  selector: 'bb-table',
  templateUrl: './table.component.html'
})
export class TableComponent implements OnInit {

  @Input() view: ViewTable;
  edit: boolean;

  constructor(public actionManager: ViewEditorService) { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.headerRows, this.view.rows, this.view.nrColumns]);
    this.edit = this.view.mode === ViewMode.EDIT;

    if (!this.edit && !!this.view.events?.click) this.view.class += ' gc-clickable';
    this.view.headerRows.forEach(row => {
      if (!this.edit && !!row.events?.click) row.class += ' gc-clickable';
    });
    this.view.rows.forEach(row => {
      if (!this.edit && !!row.events?.click) row.class += ' gc-clickable';
    });

    if (this.view.visibilityType === VisibilityType.INVISIBLE && !this.edit) {
      this.view.style = this.view.style || '';
      this.view.style = this.view.style.concatWithDivider('display: none', ';');
    }
  }

  get ViewTable(): typeof ViewTable {
    return ViewTable;
  }

  get ViewRow(): typeof ViewRow {
    return ViewRow;
  }

  get EditorAction(): typeof EditorAction {
    return EditorAction;
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  getEvent(view: ViewTable | ViewRow, action: EventAction): Event {
    if (!exists(view.events)) return null;
    return getEventFromAction(view.events, action);
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
