import {Component, Input, OnInit} from '@angular/core';
import {ViewButton} from "../../../_domain/views/view-types/view-button";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-button',
  templateUrl: './button.component.html'
})
export class BBButtonComponent implements OnInit {

  @Input() view: ViewButton;

  edit: boolean;
  classes: string;

  readonly DEFAULT = 'tabler-question-mark';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-button btn btn-ghost';
    if (this.view.color) this.classes += ' bb-button-colored';
    if (this.view.icon) this.classes += ' bb-button-icon';
  }

}
