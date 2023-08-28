import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';

import * as QuillNamespace from 'quill';
const Quill: any = QuillNamespace;
// import htmlEditButton from 'quill-html-edit-button';
import imageResize from 'quill-image-resize';

import {exists} from "../../../../_utils/misc/misc";
import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {FilePickerModalComponent} from "../../../modals/file-picker-modal/file-picker-modal.component";


@Component({
  selector: 'app-input-rich-text',
  templateUrl: './input-rich-text.component.html'
})
export class InputRichTextComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() placeholder: string;               // Message to show by default
  @Input() init: string;                      // Value on init
  @Input() moduleId?: string;                 // In case the rich-text its to open in a module config

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

  quill: QuillNamespace;                      // editor
  isPickingImage: boolean;                    // Indicates if file picker modal is open or not

  resourceManager: ResourceManager;

  @ViewChild(FilePickerModalComponent) filePickerModal: FilePickerModalComponent;

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

    // For audio files
    const BlockEmbed = Quill.import('blots/embed');

    class AudioBlot extends BlockEmbed {
      static create(url) {
        const node = super.create();
        node.setAttribute('controls', 'true');
        node.setAttribute('src', url);
        return node;
      }
    }
    AudioBlot.blotName = 'audio';
    AudioBlot.tagName = 'audio';

    Quill.register(AudioBlot);

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
              'image': () => that.onImageUpload()
            },
          },
          imageResize: {},
          // htmlEditButton: {},
          clipboard: {
            matchVisual: false
          }
        },
        theme: 'snow',
      };
    }

    this.options['placeholder'] = this.placeholder;

    if (this.container) this.options['scrollingContainer'] = '#' + this.container;

    Quill.register({
      'modules/imageResize': imageResize,
      // 'modules/htmlEditButton': htmlEditButton
    }, true);

    const container = $('#' + this.id)[0] as HTMLElement;
    this.quill = new Quill(container, this.options);

    if (exists(this.init) && !this.init.isEmpty())
      this.quill.clipboard.dangerouslyPasteHTML(this.init);

    this.quill.on('text-change', function (delta, oldDelta, source) {
      that.valueChange.emit(that.quill.root.innerHTML);
    });
  }

  onImageUpload(){
    this.filePickerModal.openModal();
  }

  addFile(file: {path: string, type: 'image' | 'video' | 'audio'}) {
    this.resourceManager.set(ApiEndpointsService.API_ENDPOINT + '/' + file.path);

    const url = this.resourceManager.get('URL');
    this.quill.focus();
    let range = this.quill.getSelection(true);

    this.quill.insertEmbed(range.index, file.type, url);
  }

}


