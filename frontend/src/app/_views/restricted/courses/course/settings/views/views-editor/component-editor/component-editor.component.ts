import { Component, Input, OnChanges, OnInit, ViewChild } from "@angular/core";
import { CodeTab, CustomFunction, OutputTab, ReferenceManualTab } from "src/app/_components/inputs/code/input-code/input-code.component";
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
import { ApiHttpService } from "src/app/_services/api/api-http.service";
import { moveItemInArray } from "@angular/cdk/drag-drop";
import { ActivatedRoute } from "@angular/router";
import { ChartType, ViewChart } from "src/app/_domain/views/view-types/view-chart";
import { getFakeId, groupedChildren, selectedAspect } from "src/app/_domain/views/build-view-tree/build-view-tree";
import { isMoreSpecific, viewsByAspect } from "../views-editor.component";

@Component({
  selector: 'app-component-editor',
  templateUrl: './component-editor.component.html'
})
export class ComponentEditorComponent implements OnInit, OnChanges {

  @Input() view: View;
  @Input() saveButton?: boolean = false;        // Adds a button at the end of all the options to save them

  show: boolean = true;

  @ViewChild('previewComponent', { static: true }) previewComponent: BBAnyComponent;
  
  viewToEdit: ViewManageData;
  viewToPreview: View;

  tableSelectedTab: string = "Overall";
  cellToEdit?: View = null;
  rowToEdit?: ViewRow = null;

  additionalToolsTabs: (CodeTab | OutputTab | ReferenceManualTab)[];
  functions: CustomFunction[];
  ELfunctions: CustomFunction[];
  namespaces: string[];

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
  ) { }

  async ngOnInit() {
    this.route.parent.params.subscribe(async params => { 
      const courseID = parseInt(params.id);
      await this.getCustomFunctions(courseID);
      this.prepareAdditionalTools();
    })
  }

  ngOnChanges() {
    this.viewToEdit = this.initViewToEdit();

    if (this.view instanceof ViewTable) {
      this.viewToPreview = new ViewTable(ViewMode.PREVIEW, this.view.id, this.view.viewRoot, null, this.view.aspect, this.view.footers, this.view.searching,
              this.view.columnFiltering, this.view.paging, this.view.lengthChange, this.view.info, this.view.ordering, this.view.orderingBy,
              this.view.headerRows.concat(this.view.bodyRows), this.view.cssId, this.view.classList, this.view.styles, this.view.visibilityType,
              this.view.visibilityCondition, this.view.loopData, this.view.variables, this.view.events);
    }
    else {
      this.viewToPreview = _.cloneDeep(this.view);
      this.viewToPreview.switchMode(ViewMode.PREVIEW);
    }
  }

  // Additional Tools --------------------------------------
  // code from the rules editor

  prepareAdditionalTools() {
    let helpVariables =
    "# These are the variables available in this component, from the component's parents.\n\n";

    for (const variable of this.viewToEdit.variables){
      helpVariables += "%" + variable.name + " = " + variable.value + "\n";
    }

    this.additionalToolsTabs = [
      { name: 'Available variables', type: "code", active: true, value: helpVariables, debug: false, readonly: true }, // FIXME
      { name: 'Preview expression', type: "output", active: false, running: null, debugOutput: false, runMessage: 'Preview expression', value: null },
      { name: 'Manual', type: "manual", active: false, customFunctions: this.functions.concat(this.ELfunctions),
        namespaces: this.namespaces
      },
    ]
  }

  async getCustomFunctions(courseID: number){
    this.functions = await this.api.getRuleFunctions(courseID).toPromise();

    // Remove 'gc' and 'transform' functions (not needed for rule editor)
    let index = this.functions.findIndex(fn => fn.keyword === 'gc');
    this.functions.splice(index, 1);
    index = this.functions.findIndex(fn => fn.keyword === 'transform');
    this.functions.splice(index, 1);

    for (let i = 0; i < this.functions.length; i++) {
      let description = this.functions[i].description;
      const startMarker = ":example:";
      const startIndex = description.indexOf(startMarker);

      if (startIndex !== -1) {
        this.functions[i].example = description.substring(startIndex + startMarker.length).trim();
        description = description.substring(0, startIndex).trim();
      }

      // Now 'description' contains the modified string without ':example:' and 'exampleText' contains the extracted text.
      this.functions[i].description = description;
      this.functions[i].returnType = "-> " + this.functions[i].returnType;
    }

    this.ELfunctions = await this.api.getELFunctions().toPromise();
    this.ELfunctions.map(ELfunction => ELfunction.returnType = "-> " + ELfunction.returnType);

    // set namespaces of functions
    let names = this.functions.concat(this.ELfunctions)
      .map(fn => fn.name).sort((a, b) => a.localeCompare(b));   // order by name
    this.namespaces = Array.from(new Set(names).values())
    moveItemInArray(this.namespaces, this.namespaces.indexOf('gamerules'), this.namespaces.length - 1);      // leave 'gamerules' at the end of array
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
    else if (this.view instanceof ViewChart) {
      viewToEdit.chartType = this.view.chartType;
      viewToEdit.data = this.view.data;
      viewToEdit.options = this.view.options;
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
    else if (to instanceof ViewChart) {
      to.chartType = from.chartType;
      to.data = from.data;
      to.options = from.options;
    }
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  getComponentTypes() {
    return Object.values(ViewType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }
  
  getChartTypes() {
    return Object.values(ChartType).map((value) => { return ({ value: value, text: value.capitalize() }) })
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

  async saveView() {
    this.updateView(this.view, this.viewToEdit);
    
    // For aspects --------------------------------------
    const viewsWithThis = viewsByAspect.filter((e) => !_.isEqual(selectedAspect, e.aspect) && e.view.findView(this.view.id));
    
    if (viewsWithThis.length > 0) {
      const lowerInHierarchy = viewsWithThis.filter((e) =>
        (e.aspect.userRole === selectedAspect.userRole && isMoreSpecific(e.aspect.viewerRole, selectedAspect.viewerRole))
        || (e.aspect.userRole !== selectedAspect.userRole && isMoreSpecific(e.aspect.userRole, selectedAspect.userRole))
      );
      
      if (viewsWithThis.filter((e) => !lowerInHierarchy.includes(e)).length == 0) {
        // this view isn't used in any other version "above"
        // no need to create a new id, keep it and just
        // propagate the changes to the views lower in hierarchy
        for (let el of lowerInHierarchy) {
          const view = el.view.findView(this.view.id);
          this.updateView(view, this.viewToEdit);
        }
      }
      else {
        // this is a new view in the tree with a new id
        const oldId = this.view.id;
        this.view.id = getFakeId();
        this.view.aspect = selectedAspect;

        let group = groupedChildren.get(this.view.parent.id);
        if (group) {
          group.find((e) => e.includes(oldId)).push(this.view.id);
          groupedChildren.set(this.view.parent.id, group);
        }
        else {
          // this is the first child inserted
          groupedChildren.set(this.view.parent.id, [[this.view.id]]);
        }

        // propagate the changes to the views lower in hierarchy that have the oldId
        for (let el of lowerInHierarchy) {
          const view = el.view.findView(oldId);
          this.updateView(view, this.viewToEdit);
          view.id = this.view.id;
          view.aspect = this.view.aspect;
        }
        // views above in the hierarchy keep the old version
      }
    }

    if (!this.saveButton) ModalService.closeModal('component-editor');
    AlertService.showAlert(AlertType.SUCCESS, 'Component Saved');
  }

  reloadPreview() {
    this.updateView(this.viewToPreview, this.viewToEdit);
    this.show = false;

    setTimeout(() => {
      this.show = true
    }, 100);
  }

  addAuxVar(event: { name: string; value: string }) {
    const new_var = new Variable(event.name, event.value, 0);
    this.viewToEdit.variables.push(new_var);
  }

  updateAuxVar(event: { name: string; value: string }, index: number) {
    this.viewToEdit.variables[index].name = event.name;
    this.viewToEdit.variables[index].value = event.value;
  }

  deleteAuxVar(index: number) {
    this.viewToEdit.variables.splice(index, 1);
  }
  
  addEvent(event: { type: EventType; expression: string }) {
    this.viewToEdit.events.push(buildEvent(event.type, event.expression));
  }
  
  updateEvent(event: { type: EventType; expression: string }, index: number) {
    this.viewToEdit.events.splice(index, 1, buildEvent(event.type, event.expression));
  }
  
  deleteEvent(index: number) {
    this.viewToEdit.events.splice(index, 1);
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

  getNumberOfCols() {
    return this.viewToEdit.bodyRows[0]?.children.length ?? this.viewToEdit.headerRows[0]?.children.length ?? 0
  }
  addBodyRow(index: number) {
    const rowId = getFakeId();
    const newRow = ViewRow.getDefault(rowId, this.view, this.view.id, this.view.aspect, RowType.BODY);
    const iterations = this.getNumberOfCols() == 0 ? 1 : this.getNumberOfCols();
    for (let i = 0; i < iterations; i++) {
      const newCell = ViewText.getDefault(getFakeId(), rowId, newRow, selectedAspect, "Cell");
      newRow.children.push(newCell);
    }
    this.viewToEdit.bodyRows.splice(index, 0, newRow);
  }
  addHeaderRow(index: number) {
    const rowId = getFakeId();;
    const newRow = ViewRow.getDefault(rowId, this.view, this.view.id, this.view.aspect, RowType.HEADER);
    const iterations = this.getNumberOfCols() == 0 ? 1 : this.getNumberOfCols();
    for (let i = 0; i < iterations; i++) {
      const newCell = ViewText.getDefault(getFakeId(), rowId, newRow, selectedAspect, "Header");
      newRow.children.push(newCell);
    }
    this.viewToEdit.headerRows.splice(index, 0, newRow);
  }
  deleteBodyRow(index: number) {
    this.viewToEdit.bodyRows.splice(index, 1);
  }
  deleteHeaderRow(index: number) {
    this.viewToEdit.headerRows.splice(index, 1);
  }
  moveBodyRow(from: number, to: number) {
    if (0 <= to && to < this.viewToEdit.bodyRows.length) {
      this.viewToEdit.bodyRows[to] = this.viewToEdit.bodyRows.splice(from, 1, this.viewToEdit.bodyRows[to])[0];
    }
  }
  moveHeaderRow(from: number, to: number) {
    if (0 <= to && to < this.viewToEdit.headerRows.length) {
      this.viewToEdit.headerRows[to] = this.viewToEdit.headerRows.splice(from, 1, this.viewToEdit.headerRows[to])[0];
    }
  }
  selectCell(cell: View) {
    if (this.cellToEdit === cell) {
      this.cellToEdit = null;
    }
    else {
      this.cellToEdit = cell;
    } 
  }
  selectRow(row: ViewRow) {
    if (this.rowToEdit === row) {
      this.rowToEdit = null;
    }
    else {
      this.rowToEdit = row;
    } 
  }
  addColumn(index: number) {
    if (this.viewToEdit.headerRows[0]) {
      const newHeaderCell = ViewText.getDefault(getFakeId(), this.viewToEdit.headerRows[0].id, this.viewToEdit.headerRows[0], selectedAspect, "Header");
      this.viewToEdit.headerRows[0].children.splice(index, 0, newHeaderCell);
    }
    for (let row of this.viewToEdit.bodyRows) {
      const newCell = ViewText.getDefault(getFakeId(), row.id, row, selectedAspect, "Cell");
      row.children.splice(index, 0, newCell);
    }
  }
  moveColumn(from: number, to: number) {
    if (0 <= to && to < this.getNumberOfCols()) {
      if (this.viewToEdit.headerRows[0]) {
        this.viewToEdit.headerRows[0].children[to] = this.viewToEdit.headerRows[0].children.splice(from, 1, this.viewToEdit.headerRows[0].children[to])[0];
      }
      for (let row of this.viewToEdit.bodyRows) {
        row.children[to] = row.children.splice(from, 1, row.children[to])[0];
      }
    }
  }
  deleteColumn(index: number) {
    if (this.viewToEdit.headerRows[0]) {
      this.viewToEdit.headerRows[0].children.splice(index, 1);
    }
    for (let row of this.viewToEdit.bodyRows) {
      row.children.splice(index, 1);
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
  columns?: number,
  headerRows?: ViewRow[],
  bodyRows?: ViewRow[],
  footers?: boolean,
  searching?: boolean,
  columnFiltering?: boolean,
  paging?: boolean,
  lengthChange?: boolean,
  info?: boolean,
  ordering?: boolean,
  orderingBy?: string,
  src?: string,
  chartType?: ChartType,
  data?: string | any,
  options?: {[key: string]: any},
}