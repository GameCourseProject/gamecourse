import {Component, Input, OnInit} from '@angular/core';

import {ViewImage} from "../../../_domain/views/view-types/view-image";
import {ViewMode} from "../../../_domain/views/view";

import {Theme} from "../../../_services/theming/themes-available";
import {ThemingService} from "../../../_services/theming/theming.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";
import {environment} from "../../../../environments/environment";
import {exists} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-image',
  templateUrl: './image.component.html'
})
export class BBImageComponent implements OnInit {

  @Input() view: ViewImage;
  imageURL: string;

  edit: boolean;
  classes: string;

  DEFAULT = this.themeService.getTheme() === Theme.DARK ? environment.img.dark : environment.img.light;

  constructor(
    private themeService: ThemingService,
    private updateManager: UpdateService
  ) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;
    this.classes = 'bb-image' + (this.view.link ? ' bb-image-link' : '');

    this.imageURL = !exists(this.view.src) ? this.DEFAULT : this.view.src;

    // Whenever theme changes, update colors
    this.updateManager.update.subscribe(type => {
      if (type === UpdateType.THEME) {
        const theme = this.themeService.getTheme();
        this.DEFAULT = theme === Theme.DARK ? environment.img.dark : environment.img.light;
        this.imageURL = !exists(this.view.src) ? this.DEFAULT : this.view.src;
      }
    });
  }
}
