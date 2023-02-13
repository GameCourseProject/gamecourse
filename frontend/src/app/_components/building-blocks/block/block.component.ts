import {Component, Input, OnInit} from '@angular/core';

import {ViewBlock} from "../../../_domain/views/view-types/view-block";
import {ViewMode} from "../../../_domain/views/view";
import {ViewEditorService} from "../../../_services/view-editor.service";

@Component({
  selector: 'bb-block',
  templateUrl: './block.component.html'
})
export class BBBlockComponent implements OnInit {

  @Input() view: ViewBlock;

  edit: boolean;
  classes: string;

  readonly DEFAULT = '(Empty block)';

  constructor(public actionManager: ViewEditorService) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-block bb-block-' + this.view.direction;
  }
}
