import {Component, Input, OnInit} from '@angular/core';

import {ViewIcon} from "../../../_domain/views/view-types/view-icon";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-icon',
  templateUrl: './icon.component.html'
})
export class BBIconComponent implements OnInit {

  @Input() view: ViewIcon;

  edit: boolean;
  classes: string;

  readonly DEFAULT = 'tabler-question-mark';

  constructor() { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-icon';
  }

}
