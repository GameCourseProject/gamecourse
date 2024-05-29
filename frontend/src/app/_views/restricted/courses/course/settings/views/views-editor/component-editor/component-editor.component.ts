import {ChangeDetectorRef, Component, ElementRef, Input, OnChanges, OnInit, ViewChild} from "@angular/core";
import {
  CodeTab, CookbookRecipe, CookbookTab,
  CustomFunction,
  OutputTab,
  PreviewTab,
  ReferenceManualTab
} from "src/app/_components/inputs/code/input-code/input-code.component";
import {View, ViewMode} from "src/app/_domain/views/view";
import {BlockDirection, ViewBlock} from "src/app/_domain/views/view-types/view-block";
import {ViewButton} from "src/app/_domain/views/view-types/view-button";
import {CollapseIcon, ViewCollapse} from "src/app/_domain/views/view-types/view-collapse";
import {ViewIcon} from "src/app/_domain/views/view-types/view-icon";
import {ViewText} from "src/app/_domain/views/view-types/view-text";
import {ViewType} from "src/app/_domain/views/view-types/view-type";
import {VisibilityType} from "src/app/_domain/views/visibility/visibility-type";
import {AlertService, AlertType} from "src/app/_services/alert.service";
import {ModalService} from "src/app/_services/modal.service";
import {Event} from "src/app/_domain/views/events/event";
import {Variable} from "src/app/_domain/views/variables/variable";
import {EventType} from "src/app/_domain/views/events/event-type";
import {buildEvent} from "src/app/_domain/views/events/build-event";
import * as _ from "lodash"
import {ViewTable} from "src/app/_domain/views/view-types/view-table";
import {BBAnyComponent} from "src/app/_components/building-blocks/any/any.component";
import {ViewImage} from "src/app/_domain/views/view-types/view-image";
import {RowType, ViewRow} from "src/app/_domain/views/view-types/view-row";
import {ApiHttpService} from "src/app/_services/api/api-http.service";
import {moveItemInArray} from "@angular/cdk/drag-drop";
import {ActivatedRoute} from "@angular/router";
import {ChartType, ViewChart} from "src/app/_domain/views/view-types/view-chart";
import {
  addToGroupedChildren,
  addVariantToGroupedChildren,
  getFakeId,
  groupedChildren,
  viewsDeleted
} from "src/app/_domain/views/build-view-tree/build-view-tree";
import {ViewEditorService} from "src/app/_services/view-editor.service";
import {Aspect} from "../../../../../../../../_domain/views/aspects/aspect";

@Component({
  selector: 'app-component-editor',
  templateUrl: './component-editor.component.html'
})
export class ComponentEditorComponent implements OnInit, OnChanges {

  @Input() view: View;
  @Input() saveButton?: boolean = false;        // Adds a button at the end of all the options to save them

  loading: boolean = true;
  show: boolean = true;
  courseId: number;

  @ViewChild('previewComponent', { static: true }) previewComponent: BBAnyComponent;
  @ViewChild('additionalTools') additionalToolsRef: ElementRef;

  viewToEdit: ViewManageData;
  viewToPreview: View;

  tableSelectedTab: string = "Overall";
  cellToEdit?: View = null;
  rowToEdit?: ViewRow = null;

  categoryToAdd?: string = "";
  labelToAdd?: string = "";

  strippedGridHorizontal?: boolean = false;
  strippedGridVertical?: boolean = false;

  additionalToolsTabs: (CodeTab | OutputTab | ReferenceManualTab | PreviewTab | CookbookTab)[];
  ELfunctions: CustomFunction[];
  cookbook: CookbookRecipe[];
  namespaces: { [name: string]: string };

  higherInHierarchy: {aspect: Aspect, view: View}[] = [];

  // Helpers for collapse
  newHeaderType: ViewType;
  newContentType: ViewType;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    public service: ViewEditorService,
    private cdr: ChangeDetectorRef
  ) { }

  async ngOnInit() {
    this.route.parent.params.subscribe(async params => {
      this.courseId = parseInt(params.id);
      await this.getCustomFunctions(this.courseId);
      await this.getCookbook(this.courseId);
      this.prepareAdditionalTools();
      this.higherInHierarchy = this.service.higherInHierarchy(this.view);
      this.loading = false;
    })

    if (window.localStorage.getItem('openContext') === null) {
      window.localStorage.setItem('openContext', JSON.stringify(true));
    }

    if (!window.localStorage.getItem('openInherited') === null) {
      window.localStorage.setItem('openInherited', JSON.stringify(true));
    }
  }

  ngOnChanges() {
    this.loading = true;
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
    this.loading = false;
  }

  // Additional Tools --------------------------------------
  // code from the rules editor

  scroll() {
    this.additionalToolsRef.nativeElement.scrollIntoView({behavior: 'smooth'});
  }

  prepareAdditionalTools() {
    this.additionalToolsTabs = [
      { name: 'Reference Manual', type: "manual", active: true, customFunctions: this.ELfunctions,
        namespaces: this.namespaces
      },

      { name: 'Cookbook', type: "cookbook", active: false, documentation: this.cookbook
      },

      { name: 'Preview Expression', type: "preview", active: false, running: null, debug: false, mode: "el",
        customFunctions: this.ELfunctions, courseId: this.courseId, nrLines: 3, placeholder: "Write an expression to preview." }
    ]
  }

  async getCustomFunctions(courseID: number){
    const res = await this.api.getELFunctions().toPromise();
    this.ELfunctions = res.functions;
    this.namespaces = res.namespaces;
  }

  async getCookbook(courseID: number) {
    this.cookbook = await this.api.getCookbook(courseID).toPromise();
  }

  // Variables --------------------------------------

  getCollapseContext(): boolean {
    return JSON.parse(window.localStorage.getItem('openContext'));
  }
  toggleCollapseContext() {
    window.localStorage.setItem('openContext', JSON.stringify(!this.getCollapseContext()));
  }
  getCollapseInherited(): boolean {
    return JSON.parse(window.localStorage.getItem('openInherited'));
  }
  toggleCollapseInherited() {
    window.localStorage.setItem('openInherited', JSON.stringify(!this.getCollapseInherited()));
  }
  getAuxVarsToPreview() {
    const viewVars = this.view.parent?.getAllVariablesForPreview() ?? {};

    // Add %item for this view
    const loopedView = this.viewToEdit.loopData ? this.viewToEdit : this.view.parent?.getViewWithLoop();
    if (loopedView) {
      let loopExpression = loopedView.loopData;
      if (loopExpression.startsWith("{") && loopExpression.endsWith("}")) {
        loopExpression = loopExpression.slice(1, -1).trim();
      }
      viewVars["item"] = loopExpression + ".item(0)";
      viewVars["index"] = "0";
    }

    for (let auxVar of this.viewToEdit.variables) {
      let value = auxVar.value;

      // Remove outer '{' and '}'
      if (value.startsWith("{") && value.endsWith("}")) {
        value = value.slice(1, -1).trim();
      }

      viewVars[auxVar.name] = value;
    }

    return viewVars;
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
      loopData: this.view.loopData,
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
    else if (this.view instanceof ViewCollapse) {
      viewToEdit.collapseIcon = this.view.icon;
      viewToEdit.header = this.view.header;
      viewToEdit.content = this.view.content;
      this.newHeaderType = this.view.header.type;
      this.newContentType = this.view.content.type;
    }

    if (this.view instanceof ViewBlock) {
      viewToEdit.direction = this.view.direction;
      viewToEdit.responsive = this.view.responsive;
      viewToEdit.columns = this.view.columns;
    } else { // needed in case user changes type to block
      viewToEdit.direction = BlockDirection.VERTICAL;
    }

    if (this.view instanceof ViewChart) {
      viewToEdit.chartType = this.view.chartType;
      viewToEdit.options = this.view.options;

      // By default, if the tooltip isn't set then it shows up...
      if (viewToEdit.options.tooltip == undefined) viewToEdit.options.tooltip = true;

      if (viewToEdit.chartType != ChartType.PROGRESS) {
        if (typeof this.view.data == 'object') viewToEdit.data = JSON.stringify(this.view.data);
        else viewToEdit.data = this.view.data;
        viewToEdit.progressData = {value: "", max: null};
      }
      else {
        viewToEdit.progressData = this.view.data;
      }

      if (viewToEdit.options.stripedGrid === 'vertical') this.strippedGridVertical = true;
      else if (viewToEdit.options.stripedGrid === 'horizontal') this.strippedGridHorizontal = true;
    }
    else { // needed in case user changes type to chart
      viewToEdit.options = {colors: [], datalabels: [], tooltip: true};
      viewToEdit.chartType = ChartType.LINE;
      viewToEdit.progressData = {value: "", max: null};
    }

    if (this.view instanceof ViewTable) {
      viewToEdit.headerRows = this.view.headerRows;
      viewToEdit.bodyRows = this.view.bodyRows;
      viewToEdit.footers = this.view.footers;
      viewToEdit.searching = this.view.searching;
      viewToEdit.columnFiltering = this.view.columnFiltering;
      viewToEdit.paging = this.view.paging;
      viewToEdit.lengthChange = this.view.lengthChange;
      viewToEdit.info = this.view.info;
      viewToEdit.ordering = this.view.ordering;

      if (this.view.orderingBy) {
        [viewToEdit.orderingByType, viewToEdit.orderingByIndex] = this.view.orderingBy.split(": ");
      }

      viewToEdit.bodyRows.forEach((row, rowIndex) => {
        row.children.forEach((cell, cellIndex) => {
          cell.fakeIndex = cellIndex + 1;
        });
        row.fakeIndex = rowIndex + 1;
      })
    }
    else { // have rows prepared in case user switches type to table
      viewToEdit.headerRows = [ViewRow.getDefault(getFakeId(), this.view, this.view.viewRoot, this.view.aspect, RowType.HEADER)];
      viewToEdit.bodyRows = [ViewRow.getDefault(getFakeId(), this.view, this.view.viewRoot, this.view.aspect, RowType.BODY)];
      viewToEdit.headerRows[0].children = [ViewText.getDefault(viewToEdit.headerRows[0], this.view.viewRoot, getFakeId(), this.service.selectedAspect, "Header")];
      viewToEdit.bodyRows[0].children = [ViewText.getDefault(viewToEdit.bodyRows[0], this.view.viewRoot, getFakeId(), this.service.selectedAspect, "Cell")];
    }

    return viewToEdit;
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getHigherInHierarchyString() {
    return this.higherInHierarchy.map(e => "viewer: " + (e.aspect.viewerRole ?? "none") + ", user: " + (e.aspect.userRole ?? "none")).join(" ; ")
  }

  changeComponentType(view: View, type: ViewType): View {
    let newView;
    if (type === ViewType.BLOCK) {
      newView = ViewBlock.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.BUTTON) {
      newView = ViewButton.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.CHART) {
      newView = ViewChart.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.COLLAPSE) {
      newView = ViewCollapse.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.ICON) {
      newView = ViewIcon.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.IMAGE) {
      newView = ViewImage.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.TABLE) {
      newView = ViewTable.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    else if (type === ViewType.TEXT) {
      newView = ViewText.getDefault(view.parent, view.viewRoot, view.id, view.aspect);
    }
    return newView;
  }

  updateView(to: View, from: ViewManageData): View {
    to.type = from.type;
    to.visibilityType = from.visibilityType;
    to.visibilityCondition = from.visibilityCondition;
    to.cssId = from.cssId;
    to.classList = from.classList;
    to.styles = from.style;
    to.events = from.events;
    to.variables = from.variables;
    to.loopData = from.loopData != "" ? from.loopData : null;

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
      to.orderingBy = from.orderingByType + ": " + from.orderingByIndex;
    }
    else if (to instanceof ViewChart) {
      to.chartType = from.chartType;
      to.options = from.options;

      if (to.chartType === ChartType.PROGRESS) to.data = from.progressData;
      else {
        if (Array.isArray(to.data)) to.data = JSON.parse(from.data);
        else to.data = from.data;
      }
    }
    return to;
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  getComponentTypes() {
    return Object.values(ViewType).filter(e => e !== ViewType.ROW).map((value) => { return ({ value: value, text: value.capitalize() }) })
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
      "tabler-users", "tabler-trophy", "tabler-puzzle", "tabler-bell", "tabler-plug",
      "tabler-database", "tabler-clipboard-list", "tabler-color-swatch", "feather-info",
      "feather-file", "feather-home", "feather-sun", "feather-moon", "feather-sliders",
      "feather-alert-triangle"
    ]
  }

  getChartTypes() {
    return Object.values(ChartType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  getXAxisDataTypes() {
    return [{ value: "category", text: "Category" },
      { value: "datetime", text: "Datetime" },
      { value: "numeric", text: "Numeric" }];
  }

  getChartLegendPositionOptions() {
    return [{ value: "top", text: "Top" }, { value: "right", text: "Right" }, { value: "bottom", text: "Bottom" }, { value: "left", text: "Left" }]
  }

  getChartAlignmentOptions() {
    return [{ value: "left", text: "Left" }, { value: "center", text: "Center" }, { value: "right", text: "Right" }]
  }

  getChartStrokeCurveOptions() {
    return [{ value: "smooth", text: "Smooth" }, { value: "straight", text: "Straight" }, { value: "stepline", text: "Stepline" }]
  }

  getChartLineCapOptions() {
    return [{ value: "butt", text: "Butt" }, { value: "square", text: "Square" }, { value: "round", text: "Round" }]
  }

  getProgressSizeOptions() {
    return [{ value: "xs", text: "Extra Small" }, { value: "sm", text: "Small" }, { value: "md", text: "Medium" }, { value: "lg", text: "Large" }]
  }

  getColumnOrderingTypeOptions() {
    return [{ value: "ASC", text: "ASC - Ascending" }, { value: "DESC", text: "DESC - Descending" }]
  }

  getColumnOrderingIndexOptions() {
    return this.viewToEdit.headerRows[0].children.map((e, index) => { return { value: index.toString(), text: "Column " + index }})
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async saveView() {
    // Changing the type requires creating a new instance of the new class
    // need to update all references
    let changedType = false;
    if (this.view.type != this.viewToEdit.type) {
      this.view = this.changeComponentType(this.view, this.viewToEdit.type);
      this.updateView(this.view, this.viewToEdit);
      this.view.switchMode(ViewMode.EDIT);
      this.service.viewsByAspect.find((e) => _.isEqual(this.service.selectedAspect, e.aspect)).view.replaceView(this.view.id, this.view);
      changedType = true;
    }
    else {
      this.updateView(this.view, this.viewToEdit);
      this.view.switchMode(ViewMode.EDIT);
    }

    // For aspects --------------------------------------
    const viewsWithThis = this.service.viewsByAspect.filter((e) => !_.isEqual(this.service.selectedAspect, e.aspect) && e.view?.findView(this.view.id));

    if (viewsWithThis.length > 0) {
      const lowerInHierarchy = viewsWithThis.filter((e) =>
        (e.aspect.userRole === this.service.selectedAspect.userRole && this.service.isMoreSpecific(e.aspect.viewerRole, this.service.selectedAspect.viewerRole))
        || (e.aspect.userRole !== this.service.selectedAspect.userRole && this.service.isMoreSpecific(e.aspect.userRole, this.service.selectedAspect.userRole))
      );

      // this view isn't used in any other version "above"
      if (viewsWithThis.filter((e) => !lowerInHierarchy.includes(e)).length == 0) {

        // if the type changed need to delete the old one and create a new in backend
        const oldId = this.view.id;
        if (changedType) {
          viewsDeleted.push(oldId);
          this.view.id = getFakeId();

          let entry = groupedChildren.get(this.view.parent.id);
          if (entry) {
            const group = entry.find((e) => e.includes(oldId));
            const index = group.findIndex((e) => e === oldId);
            group.splice(index, 1, this.view.id);
            groupedChildren.set(this.view.parent.id, entry);
          }
        }
        // else no need to create a new id

        // propagate the changes to the views lower in hierarchy
        for (let el of lowerInHierarchy) {
          const view = el.view.findView(oldId);
          if (changedType) { view.parent.replaceView(oldId, this.view); }
          else { this.updateView(view, this.viewToEdit); }
        }
      }

      // this is a new view in the tree with a new id
      else {
        const oldId = this.view.id;
        this.view.replaceWithFakeIds();
        this.view.aspect = this.service.selectedAspect;

        addToGroupedChildren(this.view, null);
        addVariantToGroupedChildren(this.view.parent.id, oldId, this.view.id);

        // propagate the changes to the views lower in hierarchy that have the oldId
        for (let el of lowerInHierarchy) {
          let view = el.view.findView(oldId);
          if (changedType) { view.parent.replaceView(view.id, this.view); }
          else { this.updateView(view, this.viewToEdit); }
          view = el.view.findView(oldId);
          view.id = this.view.id;
          view.aspect = this.view.aspect;
        }
        // views above in the hierarchy keep the old version
      }
    }

    // this view is the only that uses this component, but component type changed
    // need to delete in backend and force creating new with new id
    else if (changedType) {
      const oldId = this.view.id;
      viewsDeleted.push(oldId);
      this.view.replaceWithFakeIds();

      addToGroupedChildren(this.view, null);
      addVariantToGroupedChildren(this.view.parent.id, oldId, this.view.id);
    }

    if (!this.saveButton) ModalService.closeModal('component-editor');
    AlertService.showAlert(AlertType.SUCCESS, 'Component Saved');
  }

  discardView() {
    this.ngOnChanges();
  }

  reloadPreview() {
    if (this.viewToPreview.type != this.viewToEdit.type) {
      this.viewToPreview = this.changeComponentType(this.viewToPreview, this.viewToEdit.type);
      this.updateView(this.viewToPreview, this.viewToEdit);
      this.viewToPreview.switchMode(ViewMode.PREVIEW);
    }
    else {
      this.updateView(this.viewToPreview, this.viewToEdit);
      this.viewToPreview.switchMode(ViewMode.PREVIEW);
    }
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

  changeStripedGrid(event: boolean, orientation: string) {
    if (event) {
      this.viewToEdit.options.stripedGrid = orientation;
    }
    else {
      this.viewToEdit.options.stripedGrid = null;
    }
  }

  selectIcon(icon: string, required: boolean) {
    if (required) {
      this.viewToEdit.icon = icon;
    }
    else {
      this.viewToEdit.icon = this.viewToEdit.icon == icon ? null : icon;
    }
  }

  addXAxisCategory() {
    if (this.viewToEdit.options.XAxisCategories) {
      this.viewToEdit.options.XAxisCategories.push(this.categoryToAdd);
    }
    else {
      this.viewToEdit.options.XAxisCategories = [this.categoryToAdd];
    }
    this.categoryToAdd = "";
  }

  removeXAxisCategory(index: number) {
    this.viewToEdit.options.XAxisCategories.splice(index, 1);
  }

  addPieChartLabel() {
    if (this.viewToEdit.options.labels) {
      this.viewToEdit.options.labels.push(this.labelToAdd);
    }
    else {
      this.viewToEdit.options.labels = [this.labelToAdd];
    }
    this.labelToAdd = "";
  }

  removePieChartLabel(index: number) {
    this.viewToEdit.options.labels.splice(index, 1);
  }

  // Exclusives for tables ---------------------

  getNumberOfCols() {
    return this.viewToEdit.bodyRows[0]?.children.length ?? this.viewToEdit.headerRows[0]?.children.length ?? 0
  }
  addBodyRow(index: number) {
    const newRow = ViewRow.getDefault(getFakeId(), this.view, this.view.viewRoot, this.view.aspect, RowType.BODY);
    const iterations = this.getNumberOfCols() == 0 ? 1 : this.getNumberOfCols();
    for (let i = 0; i < iterations; i++) {
      const newCell = ViewText.getDefault(newRow, this.view.viewRoot, getFakeId(), this.service.selectedAspect, "Cell " + (i + 1));
      newCell.fakeIndex = i + 1;
      newRow.children.push(newCell);
    }
    newRow.fakeIndex = this.viewToEdit.bodyRows.length + 1;
    this.viewToEdit.bodyRows.splice(index, 0, newRow);

    addToGroupedChildren(newRow, this.view.id);
  }
  deleteBodyRow(index: number) {
    this.viewToEdit.bodyRows.splice(index, 1);
  }
  moveBodyRow(from: number, to: number) {
    if (0 <= to && to < this.viewToEdit.bodyRows.length) {
      this.viewToEdit.bodyRows[to] = this.viewToEdit.bodyRows.splice(from, 1, this.viewToEdit.bodyRows[to])[0];
    }
  }

  selectCell(cell: View) {
    if (this.cellToEdit === cell) {
      this.cellToEdit = null;
    }
    else {
      this.cellToEdit = null;
      this.cdr.detectChanges(); // Manually trigger change detection
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
      const newHeaderCell = ViewText.getDefault(this.viewToEdit.headerRows[0], this.view.viewRoot, getFakeId(), this.service.selectedAspect, "Header");
      this.viewToEdit.headerRows[0].children.splice(index, 0, newHeaderCell);

      const entry = groupedChildren.get(this.viewToEdit.headerRows[0].id);
      entry.splice(index, 0, [newHeaderCell.id]);
      groupedChildren.set(this.viewToEdit.headerRows[0].id, entry);
    }
    for (let row of this.viewToEdit.bodyRows) {
      const newCell = ViewText.getDefault(row, this.view.viewRoot, getFakeId(), this.service.selectedAspect, "Cell");
      newCell.fakeIndex = row.children.length + 1;
      row.children.splice(index, 0, newCell);

      const entry = groupedChildren.get(row.id);
      entry.splice(index, 0, [newCell.id]);
      groupedChildren.set(row.id, entry);
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
  orderingByType?: string,
  orderingByIndex?: string,
  src?: string,
  chartType?: ChartType,
  data?: string,
  progressData?: {value: string, max: number},
  options?: {[key: string]: any},
}
