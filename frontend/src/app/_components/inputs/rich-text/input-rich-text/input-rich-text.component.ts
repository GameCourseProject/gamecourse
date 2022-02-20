import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {Observable} from "rxjs";

import * as Quill from 'quill';
import htmlEditButton from 'quill-html-edit-button';
import imageResize from 'quill-image-resize';
import {exists} from "../../../../_utils/misc/misc";
import {ImageManager} from "../../../../_utils/images/image-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";

@Component({
  selector: 'app-input-rich-text',
  templateUrl: './input-rich-text.component.html',
  styleUrls: ['./input-rich-text.component.scss']
})
export class InputRichTextComponent implements OnInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() placeholder: string;               // Message to show by default
  @Input() init: string;                      // Value on init
  @Input() canInit: Observable<void>;         // Trigger init

  // Extras
  @Input() classList?: string;                // Classes to add
  @Input() options?: any;                     // Quill options
  @Input() container?: string;                // Container ID

  // Image upload & search
  @Input() whereToLook: string;               // Folder path of where to look for images
  @Input() whereToStore: string;              // Folder path of where to store images

  @Output() valueChange = new EventEmitter<string>();

  quill: Quill;

  isPickingImage: boolean;
  isAddingImage: boolean;

  imageManager: ImageManager;

  constructor(
    private sanitizer: DomSanitizer,
  ) {
    this.imageManager = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.canInit.subscribe(() => this.initQuill());
  }

  initQuill() {
    if (this.quill) return;

    const that = this;
    if (!this.options) {
      this.options = {
        modules: {
          toolbar: {
            container: [
              [{ 'font': [] }, { header: [1, 2, 3, false] }],
              ['bold', 'italic', 'underline'],
              [{ 'script': 'sub' }, { 'script': 'super' }],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'align': [] }],
              [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'indent': '-1' }, { 'indent': '+1' }],
              ['link', 'image', 'video', 'code-block']
            ],
            handlers: {
              'image': () => that.isPickingImage = true
            }
          },
          imageResize: {},
          htmlEditButton: {}
        },
        theme: 'snow'
      };
    }

    this.options['placeholder'] = this.placeholder;
    if (this.container) this.options['scrollingContainer'] = '#' + this.container;

    Quill.register({
      'modules/imageResize': imageResize,
      'modules/htmlEditButton': htmlEditButton
    });

    const container = $('#' + this.id)[0] as HTMLElement;
    this.quill = new Quill(container, this.options);


    if (exists(this.init) && !this.init.isEmpty())
      this.quill.clipboard.dangerouslyPasteHTML(this.init);

    this.quill.on('text-change', function (delta, oldDelta, source) {
      that.valueChange.emit(that.quill.root.innerHTML);
    });
  }

  addImage(image: string) {
    this.imageManager.set(ApiEndpointsService.API_ENDPOINT + '/' + image);
    const url = this.imageManager.get('URL');
    this.quill.insertEmbed(this.quill.getSelection, 'image', url);
  }

}
