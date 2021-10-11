import {Component, Input, OnInit} from '@angular/core';
import {ViewImage} from "../../../_domain/views/view-image";
import {ErrorService} from "../../../_services/error.service";
import {ImageManager} from "../../../_utils/images/image-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../_services/api/api-endpoints.service";

@Component({
  selector: 'bb-image',
  templateUrl: './image.component.html',
  styleUrls: ['./image.component.scss']
})
export class ImageComponent implements OnInit {

  @Input() view: ViewImage;
  @Input() edit: boolean;

  isEmpty: boolean;
  image: ImageManager;

  constructor(
    private sanitizer: DomSanitizer,
  ) {
    this.image = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    if (this.view.src === null || this.view.src === undefined)
      ErrorService.set('ViewImage requires a value \'src\'.');

    if (this.view.src.isEmpty())
      this.isEmpty = true;
    else this.image.set(ApiEndpointsService.API_ENDPOINT + '/' + this.view.src);
  }

}
