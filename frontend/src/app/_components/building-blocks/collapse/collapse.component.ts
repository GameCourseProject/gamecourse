import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

import {ViewCollapse} from "../../../_domain/views/view-types/view-collapse";
import {ViewMode} from "../../../_domain/views/view";
import { ViewType } from 'src/app/_domain/views/view-types/view-type';

@Component({
  selector: 'bb-collapse',
  templateUrl: './collapse.component.html'
})
export class BBCollapseComponent implements OnInit {

  @Input() view: ViewCollapse;
  @Output() addComponentEvent = new EventEmitter<void>();

  classes: string;

  readonly DEFAULT_TITLE = 'Click me to show/hide content';
  readonly DEFAULT_CONTENT = '(Empty collapse)';

  constructor() { }

  ngOnInit(): void {
    this.classes = 'bb-collapse';
    if (this.view.icon) this.classes += ' bb-collapse-' + this.view.icon;
  }

  get ViewMode(): typeof ViewMode {
    return ViewMode;
  }

  get ViewType(): typeof ViewType {
    return ViewType;
  }

}
