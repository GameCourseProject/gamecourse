import {Component, Input, OnInit} from '@angular/core';

import {ViewImage} from "../../../_domain/views/view-types/view-image";
import {ViewMode} from "../../../_domain/views/view";

import {Theme} from "../../../_services/theming/themes-available";
import {ThemingService} from "../../../_services/theming/theming.service";
import {environment} from "../../../../environments/environment";

@Component({
  selector: 'bb-image',
  templateUrl: './image.component.html'
})
export class BBImageComponent implements OnInit {

  @Input() view: ViewImage;
  imageURL: string;

  edit: boolean;
  classes: string;

  readonly DEFAULT = this.themeService.getTheme() === Theme.DARK ? environment.img.dark : environment.img.light;

  constructor(private themeService: ThemingService) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-image' + (this.view.link ? ' bb-image-link' : '');

    this.imageURL = this.view.src.isEmpty() ? this.DEFAULT : this.view.src;
  }
}
