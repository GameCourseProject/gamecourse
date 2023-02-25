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
  children: string;

  readonly DEFAULT = '(Empty block)';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-block bb-block-' + this.view.direction;
    if (this.view.columns) this.classes += ' bb-block-cols-' + this.view.columns;
    if (this.view.responsive) this.classes += ' bb-block-responsive'
    this.children = 'bb-block-children';

    // Include certain classes on parent
    if (this.view.classList) {
      for (const cl of this.view.classList.split(' ')) {
        if (cl.startsWith('rounded')) this.classes += ' ' + cl;
        if (cl === 'flex-wrap') this.classes += ' bb-block-wrap';
      }
    }
  }
}
