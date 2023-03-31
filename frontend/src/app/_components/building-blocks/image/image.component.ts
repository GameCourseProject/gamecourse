import {Component, Input, OnInit} from '@angular/core';
import {Router} from "@angular/router";

import {ViewImage} from "../../../_domain/views/view-types/view-image";
import {ViewMode} from "../../../_domain/views/view";
import {GoToPageEvent} from "../../../_domain/views/events/actions/go-to-page-event";
import {EventAction} from "../../../_domain/views/events/event-action";

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
    private updateManager: UpdateService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.edit = this.view.mode === ViewMode.EDIT;

    // Transform goToPage event into a link
    const goToPageEventIndex = this.view.events.findIndex(ev => ev.action === EventAction.GO_TO_PAGE);
    if (goToPageEventIndex !== -1) {
      const goToPageEvent: GoToPageEvent = this.view.events[goToPageEventIndex] as GoToPageEvent;
      this.view.link = environment.url + '/#/' + this.router.url.split('/').slice(1, 4).join('/') + '/' +
        goToPageEvent.pageId + (goToPageEvent.userId ? '/user/' + goToPageEvent.userId : '');
      this.view.events.splice(goToPageEventIndex, 1);
    }
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

  externalLink(link: string): boolean {
    return !link.containsWord(environment.url);
  }
}
