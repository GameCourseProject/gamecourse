import {Component, Input, OnInit} from '@angular/core';

import {View, ViewMode} from "../../../_domain/views/view";
import {ViewType} from "../../../_domain/views/view-types/view-type";
import {ViewBlock} from 'src/app/_domain/views/view-types/view-block';
import {ViewButton} from 'src/app/_domain/views/view-types/view-button';
import {ViewChart} from "../../../_domain/views/view-types/view-chart";
import {ViewCollapse} from 'src/app/_domain/views/view-types/view-collapse';
import {ViewIcon} from 'src/app/_domain/views/view-types/view-icon';
import {ViewImage} from 'src/app/_domain/views/view-types/view-image';
import {ViewTable} from 'src/app/_domain/views/view-types/view-table';
import {ViewText} from 'src/app/_domain/views/view-types/view-text';
import {VisibilityType} from "../../../_domain/views/visibility/visibility-type";

import {Event} from "../../../_domain/views/events/event";
import {EventAction} from "../../../_domain/views/events/event-action";
import {GoToPageEvent} from "../../../_domain/views/events/actions/go-to-page-event";
import {ShowTooltipEvent} from 'src/app/_domain/views/events/actions/show-tooltip-event';
import {ActivatedRoute} from "@angular/router";
import { ViewSelectionService } from 'src/app/_services/view-selection.service';
import { ModalService } from 'src/app/_services/modal.service';
import { AlertService, AlertType } from 'src/app/_services/alert.service';
import { ApiHttpService } from 'src/app/_services/api/api-http.service';
import { buildViewTree } from 'src/app/_views/restricted/courses/course/settings/views/views-editor/views-editor.component';

@Component({
  selector: 'bb-any',
  templateUrl: './any.component.html'
})
export class BBAnyComponent implements OnInit {

  @Input() view: View;

  courseID: number;

  classes: string;
  visible: boolean;
  
  newComponentName: string;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public selection: ViewSelectionService
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);

      this.classes = 'bb-any' + (this.view.events.length > 0 ? ' ' + this.view.events.map(ev => 'ev-' + ev.type).join(' ') : '');
      this.visible = (this.view.mode == ViewMode.EDIT || this.view.mode == ViewMode.REARRANGE) ? true : (this.view.visibilityType === VisibilityType.VISIBLE ||
        (this.view.visibilityType === VisibilityType.CONDITIONAL && (this.view.visibilityCondition as boolean)));
    });
  }


  /*** ---------------------------------------- ***/
  /*** ----------------- Views ---------------- ***/
  /*** ---------------------------------------- ***/

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  get ViewBlock(): typeof ViewBlock {
    return ViewBlock;
  }

  get ViewButton(): typeof ViewButton {
    return ViewButton;
  }

  get ViewChart(): typeof ViewChart {
    return ViewChart;
  }

  get ViewCollapse(): typeof ViewCollapse {
    return ViewCollapse;
  }

  get ViewIcon(): typeof ViewIcon {
    return ViewIcon;
  }

  get ViewImage(): typeof ViewImage {
    return ViewImage;
  }

  get ViewTable(): typeof ViewTable {
    return ViewTable;
  }

  get ViewText(): typeof ViewText {
    return ViewText;
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  get EventAction(): typeof EventAction {
    return EventAction;
  }

  get GoToPageEvent(): typeof GoToPageEvent {
    return GoToPageEvent;
  }

  get ShowTooltipEvent(): typeof ShowTooltipEvent {
    return ShowTooltipEvent;
  }

  getEvent(action: EventAction): Event {
    return this.view.events.find(ev => ev.action === action) || null;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isSelected() {
    return this.selection.get() == this.view;
  }

  openSaveComponentModal() {
    ModalService.openModal('save-as-component');
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  editAction() {
    ModalService.openModal('component-editor');
  }
  
  async saveAction() {
    await this.api.saveCustomComponent(this.courseID, this.newComponentName, buildViewTree(this.view)).toPromise();
    ModalService.closeModal('save-as-component');
    AlertService.showAlert(AlertType.SUCCESS, 'Component saved successfully!');
  }

  deleteAction() {
    if (this.view.parent)
      this.view.parent.removeChildView(this.view.id);
  }

}
