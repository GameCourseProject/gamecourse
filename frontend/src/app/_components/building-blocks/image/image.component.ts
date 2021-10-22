import {Component, Input, OnInit} from '@angular/core';
import {ViewImage} from "../../../_domain/views/view-image";
import {ImageManager} from "../../../_utils/images/image-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {exists, requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";
import {Event} from "../../../_domain/views/events/event";
import {EventAction, getEventFromAction} from "../../../_domain/views/events/event-action";
import { EventGoToPage } from 'src/app/_domain/views/events/event-go-to-page';
import { EventHideView } from 'src/app/_domain/views/events/event-hide-view';
import { EventShowView } from 'src/app/_domain/views/events/event-show-view';
import { EventToggleView } from 'src/app/_domain/views/events/event-toggle-view';

@Component({
  selector: 'bb-image',
  templateUrl: './image.component.html'
})
export class ImageComponent implements OnInit {

  @Input() view: ViewImage;
  edit: boolean;

  isEmpty: boolean;
  image: ImageManager;

  readonly IMAGE_CLASS = 'image';

  constructor(
    private sanitizer: DomSanitizer,
  ) {
    this.image = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    requireValues(this.view, [this.view.src]);
    this.view.class += ' ' + this.IMAGE_CLASS + (!!this.view.events?.click ? ' clickable' : '');
    this.edit = this.view.mode === ViewMode.EDIT;

    if (this.view.src.isEmpty()) this.isEmpty = true;
    else this.image.set(ApiEndpointsService.API_ENDPOINT + '/' + this.view.src);
  }


  /*** ---------------------------------------- ***/
  /*** ---------------- Events ---------------- ***/
  /*** ---------------------------------------- ***/

  getEvent(action: EventAction): Event {
    if (!exists(this.view.events)) return null;
    return getEventFromAction(this.view.events, action);
  }

  get EventAction(): typeof EventAction {
    return EventAction;
  }

  get EventGoToPage(): typeof EventGoToPage {
    return EventGoToPage;
  }

  get EventHideView(): typeof EventHideView {
    return EventHideView;
  }

  get EventShowView(): typeof EventShowView {
    return EventShowView;
  }

  get EventToggleView(): typeof EventToggleView {
    return EventToggleView;
  }

}
