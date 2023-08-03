import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';

import * as Quill from 'quill';
// import htmlEditButton from 'quill-html-edit-button';
import imageResize from 'quill-image-resize';

import {exists} from "../../../../_utils/misc/misc";
import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";
import {ApiHttpService} from "../../../../_services/api/api-http.service";

@Component({
  selector: 'app-input-rich-text',
  templateUrl: './input-rich-text.component.html',
  styleUrls: ['./input-rich-text.component.scss']
})
export class InputRichTextComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() placeholder: string;               // Message to show by default
  @Input() init: string;                      // Value on init

  // Extras
  @Input() title?: string;                                          // Textarea title
  @Input() helperText?: string;                                     // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';    // Helper position
  @Input() disabled?: boolean;                                      // Make it disabled
  @Input() required?: boolean;                                      // Make it required
  @Input() classList?: string;                                      // Classes to add
  @Input() options?: any;                                           // Quill options
  @Input() container?: string;                                      // Container ID

  // Image upload & search
  @Input() courseFolder: string;              // Course data folder path (where to look for images)
  @Input() whereToStore: string;              // Folder path of where to store images (relative to course folder)

  @Output() valueChange = new EventEmitter<string>();

  quill: Quill;

  isPickingImage: boolean;
  isAddingImage: boolean;

  resourceManager: ResourceManager;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
  ) {
    this.resourceManager = new ResourceManager(sanitizer);
  }

  ngOnInit(){
  }

  ngAfterViewInit(): void {
    this.initQuill();
    console.log(this.courseFolder);
    console.log(this.whereToStore);
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
              'image': () => this.onImageUpload()
            }
          },
          imageResize: {},
          // htmlEditButton: {},
          clipboard: {
            matchVisual: false
          }
        },
        theme: 'snow'
      };
    }

    this.options['placeholder'] = this.placeholder;

    if (this.container) this.options['scrollingContainer'] = '#' + this.container;

    Quill.register({
      'modules/imageResize': imageResize,
      // 'modules/htmlEditButton': htmlEditButton
    });

    const container = $('#' + this.id)[0] as HTMLElement;
    this.quill = new Quill(container, this.options);

    if (exists(this.init) && !this.init.isEmpty())
      this.quill.clipboard.dangerouslyPasteHTML(this.init);

    this.quill.on('text-change', function (delta, oldDelta, source) {
      that.valueChange.emit(that.quill.root.innerHTML);
    });
  }

  onImageUpload(){
    this.isPickingImage = true;
    console.log(this.isPickingImage);
  }

  addFile(file: {path: string, type: 'image' | 'video' | 'audio'}) {
    this.resourceManager.set(ApiEndpointsService.API_ENDPOINT + '/' + file.path);
    const url = this.resourceManager.get('URL');
    this.quill.insertEmbed(this.quill.getSelection(true).index, file.type, url); // FIXME: embed audio
  }

}
