import {Component, Input, OnInit} from '@angular/core';
import {ViewImage} from "../../../_domain/views/view-image";
import {ImageManager} from "../../../_utils/images/image-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {requireValues} from "../../../_utils/misc/misc";
import {ViewMode} from "../../../_domain/views/view";

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
    this.view.class += ' ' + this.IMAGE_CLASS;
    this.edit = this.view.mode === ViewMode.EDIT;

    if (this.view.src.isEmpty()) this.isEmpty = true;
    else this.image.set(ApiEndpointsService.API_ENDPOINT + '/' + this.view.src);
  }

}
