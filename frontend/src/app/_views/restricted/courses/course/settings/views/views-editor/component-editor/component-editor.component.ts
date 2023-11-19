import { Component, Input, OnInit, ViewChild } from "@angular/core";
import { NgForm } from "@angular/forms";
import { CodeTab, OutputTab, ReferenceManualTab } from "src/app/_components/inputs/code/input-code/input-code.component";
import { View, ViewMode } from "src/app/_domain/views/view";
import { BlockDirection, ViewBlock } from "src/app/_domain/views/view-types/view-block";
import { ViewButton } from "src/app/_domain/views/view-types/view-button";
import { CollapseIcon, ViewCollapse } from "src/app/_domain/views/view-types/view-collapse";
import { ViewIcon } from "src/app/_domain/views/view-types/view-icon";
import { ViewText } from "src/app/_domain/views/view-types/view-text";
import { ViewType } from "src/app/_domain/views/view-types/view-type";
import { VisibilityType } from "src/app/_domain/views/visibility/visibility-type";
import { AlertService, AlertType } from "src/app/_services/alert.service";
import { ModalService } from "src/app/_services/modal.service";
import { Event } from "src/app/_domain/views/events/event";
import { Variable } from "src/app/_domain/views/variables/variable";
import { EventType } from "src/app/_domain/views/events/event-type";
import { buildEvent } from "src/app/_domain/views/events/build-event";
import * as _ from "lodash"
import { ViewTable } from "src/app/_domain/views/view-types/view-table";
import { BBAnyComponent } from "src/app/_components/building-blocks/any/any.component";
import { ViewImage } from "src/app/_domain/views/view-types/view-image";
import { RowType, ViewRow } from "src/app/_domain/views/view-types/view-row";

@Component({
  selector: 'app-component-editor',
  templateUrl: './component-editor.component.html'
})
export class ComponentEditorComponent implements OnInit {

  @Input() view: View;

  show: boolean = true;

  @ViewChild('previewComponent', { static: true }) previewComponent: BBAnyComponent;
  
  viewToEdit: ViewManageData;
  viewToPreview: View;
  variableToAdd: { name: string, value: string, position: number };
  eventToAdd: { type: EventType, action: string };
  additionalToolsTabs: (CodeTab | OutputTab | ReferenceManualTab)[];
  tableSelectedTab: string = "Overall";

  @ViewChild('q', { static: false }) q: NgForm;

  constructor(
  ) { }

  ngOnInit(): void {
    this.viewToEdit = this.initViewToEdit();
    this.variableToAdd = { name: "", value: "", position: 0 };
    this.eventToAdd = { type: null, action: "" };

    if (this.view instanceof ViewTable) {
      this.viewToPreview = new ViewTable(ViewMode.PREVIEW, this.view.id, this.view.viewRoot, null, this.view.aspect, this.view.footers, this.view.searching,
              this.view.columnFiltering, this.view.paging, this.view.lengthChange, this.view.info, this.view.ordering, this.view.orderingBy,
              this.view.headerRows, this.view.cssId, this.view.classList, this.view.styles, this.view.visibilityType,
              this.view.visibilityCondition, this.view.loopData, this.view.variables, this.view.events);
    }
    else {
      this.viewToPreview = _.cloneDeep(this.view);
      this.viewToPreview.switchMode(ViewMode.PREVIEW);
    }

    let helpVariables =
        "# These are the variables available in this component, from the component's parents.\n\n";

    for (const variable of this.viewToEdit.variables){
      helpVariables += "%" + variable.name + " = " + variable.value + "\n";
    }

    this.additionalToolsTabs = [
      { name: 'Available Variables', type: "code", active: true, value: helpVariables, debug: false, readonly: true},
    ]
  }

  /*** --------------------------------------------- ***/
  /*** ------------------- Init -------------------- ***/
  /*** --------------------------------------------- ***/

  initViewToEdit(): ViewManageData {
    const viewToEdit: ViewManageData = {
      type: this.view.type,
      visibilityType: this.view.visibilityType,
      visibilityCondition: this.view.visibilityCondition?.toString(),
      cssId: this.view.cssId,
      classList: this.view.classList,
      style: this.view.styles,
      events: this.view.events ?? [],
      variables: this.view.variables ?? [],
    };
    if (this.view instanceof ViewButton) {
      viewToEdit.text = this.view.text;
      viewToEdit.color = this.view.color ?? null;
      viewToEdit.icon = this.view.icon;
    }
    else if (this.view instanceof ViewText) {
      viewToEdit.text = this.view.text;
      viewToEdit.link = this.view.link;
    }
    else if (this.view instanceof ViewImage) {
      viewToEdit.src = this.view.src;
      viewToEdit.link = this.view.link;
    }
    else if (this.view instanceof ViewIcon) {
      viewToEdit.icon = this.view.icon;
      viewToEdit.size = this.view.size;
    }
    else if (this.view instanceof ViewBlock) {
      viewToEdit.direction = this.view.direction;
      viewToEdit.responsive = this.view.responsive;
      viewToEdit.columns = this.view.columns;
    }
    else if (this.view instanceof ViewCollapse) {
      viewToEdit.collapseIcon = this.view.icon;
      viewToEdit.header = this.view.header;
      viewToEdit.content = this.view.content;
    }
    else if (this.view instanceof ViewTable) {
      viewToEdit.headerRows = this.view.headerRows;
      viewToEdit.bodyRows = this.view.bodyRows;
      viewToEdit.footers = this.view.footers;
      viewToEdit.searching = this.view.searching;
      viewToEdit.columnFiltering = this.view.columnFiltering;
      viewToEdit.paging = this.view.paging;
      viewToEdit.lengthChange = this.view.lengthChange;
      viewToEdit.info = this.view.info;
      viewToEdit.ordering = this.view.ordering;
      viewToEdit.orderingBy = this.view.orderingBy;
    }
    return viewToEdit;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  updateView(to: View, from: ViewManageData) {
    to.classList = from.classList;
    to.type = from.type;
    to.visibilityType = from.visibilityType;
    to.visibilityCondition = from.visibilityCondition;
    to.cssId = from.cssId;
    to.classList = from.classList;
    to.styles = from.style;
    to.events = from.events;
    
    if (to instanceof ViewButton) {
      to.text = from.text;
      to.color = from.color;
      to.icon = from.icon;
    }
    else if (to instanceof ViewText) {
      to.text = from.text;
      to.link = from.link;
    }
    else if (to instanceof ViewImage) {
      to.src = from.src;
      to.link = from.link;
    }
    else if (to instanceof ViewIcon) {
      to.icon = from.icon;
      to.size = from.size;
    }
    else if (to instanceof ViewBlock) {
      to.direction = from.direction;
      to.responsive = from.responsive;
      to.columns = from.columns;
    }
    else if (to instanceof ViewCollapse) {
      to.icon = from.collapseIcon;
      to.header = from.header;
      to.content = from.content;
    }
    else if (to instanceof ViewTable) {
      to.headerRows = from.headerRows;
      to.bodyRows = from.bodyRows;
      to.footers = from.footers;
      to.searching = from.searching;
      to.columnFiltering = from.columnFiltering;
      to.paging = from.paging;
      to.lengthChange = from.lengthChange;
      to.info = from.info;
      to.ordering = from.ordering;
      to.orderingBy = from.orderingBy;
    }
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  getComponentTypes() {
    return Object.values(ViewType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  getEventTypes() {
    return Object.values(EventType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  getCollapseIconOptions() {
    return Object.values(CollapseIcon).map((value) => { return ({ value: value, text: value.capitalize() }) });
  }

  getIconSizeOptions() {
    return [{ value: "1.3rem", text: "Small"}, { value: "1.8rem", text: "Medium"}, { value: "2.5rem", text: "Large"}, { value: "4rem", text: "Extra-Large"}]
  }

  getIcons() {
    return [
      "tabler-award", "tabler-user-circle", "tabler-list-numbers", "tabler-flame",
      "tabler-coin", "jam-layout", "tabler-bulb", "tabler-checks", "feather-repeat",
      "tabler-users", "tabler-trophy"
    ]
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async saveView(){
    this.updateView(this.view, this.viewToEdit);
    ModalService.closeModal('component-editor');
    AlertService.showAlert(AlertType.SUCCESS, 'Component Saved');
  }

  reloadPreview() {
    this.updateView(this.viewToPreview, this.viewToEdit);
    this.show = false;

    setTimeout(() => {
      this.show = true
    }, 100);
  }

  addAuxVar() {
    const new_var = new Variable(this.variableToAdd.name, this.variableToAdd.value, this.variableToAdd.position);
    this.viewToEdit.variables.push(new_var);
    this.variableToAdd = { name: "", value: "", position: 0 };
  }

  addEvent() {
    const new_event = buildEvent(this.eventToAdd.type, this.eventToAdd.action);
    this.viewToEdit.events.push(new_event);
    this.eventToAdd = { type: null, action: "" };
  }
  
  selectIcon(icon: string, required: boolean) {
    if (required) {
      this.viewToEdit.icon = icon;
    }
    else {
      this.viewToEdit.icon = this.viewToEdit.icon == icon ? null : icon;
    }
  }

  // Exclusives for tables ---------------------
  addBodyRow(index: number) {
    this.viewToEdit.bodyRows.splice(index, 0, ViewRow.getDefault(this.view, this.view.id, this.view.aspect, RowType.BODY));
  }
  addHeaderRow(index: number) {
    this.viewToEdit.headerRows.splice(index, 0, ViewRow.getDefault(this.view, this.view.id, this.view.aspect, RowType.HEADER));
  }
  removeBodyRow(index: number) {
    this.viewToEdit.bodyRows.splice(index, 1);
  }
  removeHeaderRow(index: number) {
    this.viewToEdit.headerRows.splice(index, 1);
  }
  moveBodyRow(index: number, to: number) {
    if (0 <= to && to < this.viewToEdit.bodyRows.length) {
      this.viewToEdit.bodyRows[to] = this.viewToEdit.bodyRows.splice(index, 1, this.viewToEdit.bodyRows[to])[0];
    }
  }
  moveHeaderRow(index: number, to: number) {
    if (0 <= to && to < this.viewToEdit.headerRows.length) {
      this.viewToEdit.headerRows[to] = this.viewToEdit.headerRows.splice(index, 1, this.viewToEdit.headerRows[to])[0];
    }
  }
}

export interface ViewManageData {
  type: ViewType,
  cssId?: string,
  classList?: string,
  style?: string,
  visibilityType?: VisibilityType,
  visibilityCondition?: string,
  loopData?: string,
  variables?: Variable[],
  events?: Event[],
  text?: string,
  color?: string,
  icon?: string,
  link?: string,
  size?: string,
  header?: View,
  content?: View,
  collapseIcon?: CollapseIcon,
  direction?: BlockDirection,
  responsive?: boolean,
  columns?: number;
  headerRows?: ViewRow[];
  bodyRows?: ViewRow[];
  footers?: boolean;
  searching?: boolean;
  columnFiltering?: boolean;
  paging?: boolean;
  lengthChange?: boolean;
  info?: boolean;
  ordering?: boolean;
  orderingBy?: string;
  src?: string;
}