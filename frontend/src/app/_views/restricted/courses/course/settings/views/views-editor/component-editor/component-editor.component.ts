import { Component, Input, OnInit, ViewChild } from "@angular/core";
import { NgForm } from "@angular/forms";
import { CodeTab, OutputTab, ReferenceManualTab } from "src/app/_components/inputs/code/input-code/input-code.component";
import { View } from "src/app/_domain/views/view";
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
import { EventAction } from "src/app/_domain/views/events/event-action";
import { buildEvent } from "src/app/_domain/views/events/build-event";

@Component({
  selector: 'app-component-editor',
  templateUrl: './component-editor.component.html'
})
export class ComponentEditorComponent implements OnInit {

  @Input() view: View;
  
  viewToEdit: ViewManageData;
  variableToAdd: { name: string, value: string, position: number };
  eventToAdd: { type: EventType, action: string };
  additionalToolsTabs: (CodeTab | OutputTab | ReferenceManualTab)[];

  @ViewChild('q', { static: false }) q: NgForm;

  constructor(
  ) { }

  ngOnInit(): void {
    this.viewToEdit = this.initViewToEdit();
    this.variableToAdd = { name: "", value: "", position: 0 };
    this.eventToAdd = { type: null, action: "" };
    
    let helpVariables =
        "# These are the variables available in this component, from the component's parents.\n\n";

    for (const variable of this.viewToEdit.variables){
      helpVariables += "%" + variable.name + " = " + variable.value + "\n";
    }

    this.additionalToolsTabs = [
      { name: 'Available Variables', type: "code", active: true, value: helpVariables, debug: false, readonly: true},
    ]
  }

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
      viewToEdit.color = this.view.color;
      viewToEdit.icon = this.view.icon;
    }
    else if (this.view instanceof ViewText) {
      viewToEdit.text = this.view.text;
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
    return viewToEdit;
  }

  async saveView(){
    this.view.classList = this.viewToEdit.classList;
    this.view.type = this.viewToEdit.type;
    this.view.visibilityType = this.viewToEdit.visibilityType;
    this.view.visibilityCondition = this.viewToEdit.visibilityCondition;
    this.view.cssId = this.viewToEdit.cssId;
    this.view.classList = this.viewToEdit.classList;
    this.view.styles = this.viewToEdit.style;
    this.view.events = this.viewToEdit.events;
    
    if (this.view instanceof ViewButton) {
      this.view.text = this.viewToEdit.text;
      this.view.color = this.viewToEdit.color;
      this.view.icon = this.viewToEdit.icon;
    }
    else if (this.view instanceof ViewText) {
      this.view.text = this.viewToEdit.text;
      this.view.link = this.viewToEdit.link;
    }
    else if (this.view instanceof ViewIcon) {
      this.view.icon = this.viewToEdit.icon;
      this.view.size = this.viewToEdit.size;
    }
    else if (this.view instanceof ViewBlock) {
      this.view.direction = this.viewToEdit.direction;
      this.view.responsive = this.viewToEdit.responsive;
      this.view.columns = this.viewToEdit.columns;
    }
    else if (this.view instanceof ViewCollapse) {
      this.view.icon = this.viewToEdit.collapseIcon;
      this.view.header = this.viewToEdit.header;
      this.view.content = this.viewToEdit.content;
    }

    ModalService.closeModal('component-editor');
    AlertService.showAlert(AlertType.SUCCESS, 'Component Saved');
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
}