import {Component, Input, OnInit} from '@angular/core';
import {ViewHeader} from "../../../_domain/views/view-header";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";

@Component({
  selector: 'bb-header',
  templateUrl: './header.component.html'
})
export class HeaderComponent implements OnInit {

  @Input() view: ViewHeader;
  edit: boolean;

  readonly HEADER_CLASS = 'header';
  readonly IMAGE_CLASS = 'header_image';
  readonly TITLE_CLASS = 'header_title';

  constructor() { }

  ngOnInit(): void {
    requireValues(this.view, [this.view.image, this.view.title]);
    this.edit = this.view.mode === ViewMode.EDIT;

    this.view.class += ' ' + this.HEADER_CLASS;
    this.view.image.class += ' ' + this.IMAGE_CLASS;
    this.view.title.class += ' ' + this.TITLE_CLASS;
  }

}
