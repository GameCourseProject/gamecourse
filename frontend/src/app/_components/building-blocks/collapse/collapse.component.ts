import {Component, Input, OnInit} from '@angular/core';

import {ViewCollapse} from "../../../_domain/views/view-types/view-collapse";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-collapse',
  templateUrl: './collapse.component.html'
})
export class BBCollapseComponent implements OnInit {

  @Input() view: ViewCollapse;

  edit: boolean;
  classes: string;

  readonly DEFAULT_TITLE = 'Click me to show/hide content';
  readonly DEFAULT_CONTENT = '(Empty collapse)';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-collapse';
    if (this.view.icon) this.classes += ' bb-collapse-' + this.view.icon;
  }

}
