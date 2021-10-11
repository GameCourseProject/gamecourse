import {Component, Input, OnInit} from '@angular/core';
import {ViewHeader} from "../../../_domain/views/view-header";
import {requireValues} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {

  @Input() view: ViewHeader;
  @Input() edit: boolean;

  readonly IMAGE_CLASS = 'header_image';
  readonly TITLE_CLASS = 'header_title';

  constructor() { }

  ngOnInit(): void {
    requireValues([this.view.image, this.view.title]);

    if (!this.view.image.class || this.view.image.class.isEmpty()) this.view.image.class = this.IMAGE_CLASS;
    else this.view.image.class += ' ' + this.IMAGE_CLASS;

    if (!this.view.title.class || this.view.title.class.isEmpty()) this.view.title.class = this.TITLE_CLASS;
    else this.view.title.class += ' ' + this.TITLE_CLASS;
  }

}
