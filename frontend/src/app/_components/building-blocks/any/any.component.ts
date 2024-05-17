import {ChangeDetectorRef, Component, Input, OnInit, ViewChild} from '@angular/core';
import * as _ from "lodash";

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
import {ExchangeTokensEvent} from "../../../_domain/views/events/actions/exchange-tokens-event";
import {ActivatedRoute} from "@angular/router";
import {ViewSelectionService} from 'src/app/_services/view-selection.service';
import {ModalService} from 'src/app/_services/modal.service';
import {
  ComponentEditorComponent
} from 'src/app/_views/restricted/courses/course/settings/views/views-editor/component-editor/component-editor.component';
import {groupedChildren} from 'src/app/_domain/views/build-view-tree/build-view-tree';
import {HistoryService} from 'src/app/_services/history.service';
import {ViewEditorService} from 'src/app/_services/view-editor.service';
import {AlertService, AlertType} from "../../../_services/alert.service";

@Component({
  selector: 'bb-any',
  templateUrl: './any.component.html',
  styleUrls: ['./any.component.scss']
})
export class BBAnyComponent implements OnInit {

  @Input() view: View;
  @Input() isExistingRoot: boolean = false;

  @ViewChild(ComponentEditorComponent) componentEditor?: ComponentEditorComponent;

  courseID: number;

  classes: string;
  visible: boolean;
  delete: boolean = false;

  contextMenuPos = { x: '0', y: '0' };

  constructor(
    private route: ActivatedRoute,
    public selection: ViewSelectionService,
    private history: HistoryService,
    public service: ViewEditorService,
    private cdr: ChangeDetectorRef
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);

      this.classes = 'bb-any' + (this.view.events.length > 0 ? ' ' + this.view.events.map(ev => 'ev-' + ev.type).join(' ') : '');
      this.visible = this.view.visibilityType === VisibilityType.VISIBLE ||
        (this.view.visibilityType === VisibilityType.CONDITIONAL && (this.view.visibilityCondition as boolean));
    });

    if (this.view.mode === ViewMode.EDIT) {
      addEventListener('keydown', (event: KeyboardEvent) => {
        if (this.isSelected() && !ModalService.isOpen("component-editor")) {
          if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            ModalService.openModal('component-delete-' + this.view.id);
          }
        }
      })
    }
  }

  onRightClick(event: MouseEvent) {
    if (!ModalService.isOpen("component-editor")) {
      event.preventDefault();
      event.stopPropagation();
      this.selection.open(this.view);

      if (event.clientX + 192 > window.innerWidth) {
        this.contextMenuPos.x = event.clientX- 192 + 'px';
      } else {
        this.contextMenuPos.x = event.clientX + 'px';
      }
      this.contextMenuPos.y = event.clientY + 'px';
    }
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

  get ExchangeTokensEvent(): typeof ExchangeTokensEvent {
    return ExchangeTokensEvent;
  }

  getEvent(action: EventAction): Event {
    if (this.view.mode == ViewMode.DISPLAY) {
      return this.view.events.find(ev => ev.action === action) || null;
    } else return null;
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isSelected() {
    return this.selection.get() == this.view;
  }

  isOpen() {
    return this.isSelected() && this.selection.hasOpen();
  }

  openSaveComponentModal() {
    ModalService.openModal('save-component');
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  editAction() {
    ModalService.openModal('component-editor');
  }

  submitEditAction() {
    this.componentEditor.saveView();

    // Force rerender to show changes
    // and recalculates visibility since it might have changed
    this.visible = false;
    this.selection.refresh();
    this.cdr.detectChanges();
    this.selection.refresh();
    this.visible = this.visible = this.view.visibilityType === VisibilityType.VISIBLE ||
      (this.view.visibilityType === VisibilityType.CONDITIONAL && (this.view.visibilityCondition as boolean));

    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren
    });
  }

  cancelEditAction() {
    this.componentEditor.discardView();
  }

  deleteAction() {
    ModalService.openModal('component-delete-' + this.view.id);
  }

  submitDeleteAction() {
    if (this.isExistingRoot) {
      AlertService.showAlert(AlertType.WARNING, "You can't delete the root of an existing page/template! Edit it instead...");
      ModalService.closeModal('component-delete-' + this.view.id);
    }
    else {
      this.service.delete(this.view);
      this.selection.clear();
      this.delete = true;
      this.history.saveState({
        viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
        groupedChildren: groupedChildren
      });
    }
  }

  duplicateAction() {
    this.service.duplicate(this.view);
    this.history.saveState({
      viewsByAspect: _.cloneDeep(this.service.viewsByAspect),
      groupedChildren: groupedChildren
    });
  }

  selectParentAction() {
    if (this.view.parent) {
      this.selection.update(this.view.parent);
    }
  }

}
