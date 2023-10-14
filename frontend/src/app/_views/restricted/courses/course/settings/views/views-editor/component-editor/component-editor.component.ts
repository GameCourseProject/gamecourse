import { Component, Input, OnInit, ViewChild } from "@angular/core";
import { NgForm } from "@angular/forms";
import { View } from "src/app/_domain/views/view";
import { ViewButton } from "src/app/_domain/views/view-types/view-button";
import { ViewType } from "src/app/_domain/views/view-types/view-type";
import { VisibilityType } from "src/app/_domain/views/visibility/visibility-type";
import { AlertService, AlertType } from "src/app/_services/alert.service";
import { ModalService } from "src/app/_services/modal.service";

@Component({
  selector: 'app-component-editor',
  templateUrl: './component-editor.component.html'
})
export class ComponentEditorComponent implements OnInit {

  @Input() view: View;
  viewToEdit: ViewManageData;

  @ViewChild('q', { static: false }) q: NgForm;

  constructor(
  ) { }

  ngOnInit(): void {
    this.viewToEdit = this.initViewToEdit();
  }

  initViewToEdit(): ViewManageData {
    const viewToEdit: ViewManageData = {
      type: this.view.type,
      visibilityType: this.view.visibilityType,
      visibilityCondition: "",
      cssId: this.view.cssId,
      classList: this.view.classList,
      style: this.view.styles,
    };
    if (this.view instanceof ViewButton) {
      viewToEdit.text = this.view.text;
      viewToEdit.color = this.view.color;
      viewToEdit.icon = this.view.icon;
    }
    return viewToEdit;
  }

  async saveView(){
    this.view.classList = this.viewToEdit.classList;

    ModalService.closeModal('component-editor');
    AlertService.showAlert(AlertType.SUCCESS, 'Component Saved');
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

  getComponentTypes() {
    return Object.values(ViewType).map((value) => { return ({ value: value, text: value.capitalize() }) })
  }

  getVisibilityTypes() {
    return Object.values(VisibilityType);
  }
}

export interface ViewManageData{
  type: ViewType,
  cssId?: string,
  classList?: string,
  style?: string,
  visibilityType?: VisibilityType,
  visibilityCondition?: string,
  loopData?: string,
  variables?: {name: string, value: string, position: number}[];
  events?: { type: string, action: string }[],
  text?: string,
  color?: string,
  icon?: string;
}