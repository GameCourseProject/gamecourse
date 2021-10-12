import {Component, Input, OnInit} from '@angular/core';
import {ViewImage} from "../../../_domain/views/view-image";
import {ImageManager} from "../../../_utils/images/image-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";
import {requireValues} from "../../../_utils/misc/misc";

@Component({
  selector: 'bb-image',
  templateUrl: './image.component.html',
  styleUrls: ['./image.component.scss']
})
export class ImageComponent implements OnInit {

  @Input() view: ViewImage;
  @Input() edit: boolean;

  readonly IMAGE_CLASS = 'image';

  isEmpty: boolean;
  image: ImageManager;

  constructor(
    private sanitizer: DomSanitizer,
  ) {
    this.image = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    requireValues([this.view.src]);

    this.view.class += ' ' + this.IMAGE_CLASS;

    if (this.view.src.isEmpty()) this.isEmpty = true;
    else this.image.set(ApiEndpointsService.API_ENDPOINT + '/' + this.view.src);
  }

}
