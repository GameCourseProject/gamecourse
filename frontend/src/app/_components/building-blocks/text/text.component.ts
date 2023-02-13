import {Component, Input, OnInit} from '@angular/core';

import {ViewText} from "../../../_domain/views/view-types/view-text";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-text',
  templateUrl: './text.component.html'
})
export class BBTextComponent implements OnInit {

  @Input() view: ViewText;

  edit: boolean;
  classes: string;

  readonly DEFAULT = '(Empty text)';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-text' + (this.view.link ? ' bb-text-link' : '');
  }
}
