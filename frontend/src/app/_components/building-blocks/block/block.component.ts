import {Component, Input, OnInit} from '@angular/core';

import {ViewBlock} from "../../../_domain/views/view-types/view-block";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html'
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;

  edit: boolean;
  classes: string;

  readonly DEFAULT = '(Empty block)';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-block bb-block-' + this.view.direction;
  }
}
