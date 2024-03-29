import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output, ViewChild, ViewEncapsulation} from '@angular/core';

import * as QuillNamespace from 'quill';
const Quill: any = QuillNamespace;
import {htmlEditButton} from "quill-html-edit-button";
import imageResize from 'quill-image-resize';

import {exists} from "../../../../_utils/misc/misc";
import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {DomSanitizer} from "@angular/platform-browser";
import {ApiEndpointsService} from "../../../../_services/api/api-endpoints.service";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {FilePickerModalComponent} from "../../../modals/file-picker-modal/file-picker-modal.component";


@Component({
  selector: 'app-input-rich-text',
  templateUrl: './input-rich-text.component.html',
  styleUrls: ['./input-rich-text.component.scss'],
  encapsulation: ViewEncapsulation.None,
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
  @Input() subfolderToOpen?: string;          // Subfolder that should be open by default (although you can go to a less deep one)

  @Output() valueChange = new EventEmitter<string>();

  quill: QuillNamespace;                      // editor
  isPickingImage: boolean;                    // Indicates if file picker modal is open or not

  resourceManager: ResourceManager;

  mode: "link" | "embed" = "embed"

  @ViewChild(FilePickerModalComponent) filePickerModal: FilePickerModalComponent;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
  ) {
    this.resourceManager = new ResourceManager(sanitizer);
  }

  ngOnInit() {
    var icons = Quill.import('ui/icons');
    icons['code-block'] = '<svg viewbox="0 -2 15 18">\n' + '\t<polyline class="ql-even ql-stroke" points="2.48 2.48 1 3.96 2.48 5.45"/>\n' + '\t<polyline class="ql-even ql-stroke" points="8.41 2.48 9.9 3.96 8.41 5.45"/>\n' + '\t<line class="ql-stroke" x1="6.19" y1="1" x2="4.71" y2="6.93"/>\n' + '\t<polyline class="ql-stroke" points="12.84 3 14 3 14 13 2 13 2 8.43"/>\n' + '</svg>';
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
              ['link', 'image', 'video', 'file', 'blockquote', 'code-block'],
            ],
            handlers: {
              'image': () => that.onImageUpload()
            },
          },
          imageResize: {},
          htmlEditButton: {
            msg: "Edit html",
            okText: "save",
            buttonHTML: '<svg viewBox="0 0 18 18"><polyline class="ql-even ql-stroke" points="5 7 3 9 5 11"></polyline><polyline class="ql-even ql-stroke" points="13 7 15 9 13 11"></polyline> <line class="ql-stroke" x1="10" x2="8" y1="5" y2="13"></line></svg>'
          },
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
      'modules/htmlEditButton': htmlEditButton
    }, true);

    const container = $('#' + this.id)[0] as HTMLElement;
    this.quill = new Quill(container, this.options);

    if (exists(this.init) && !this.init.isEmpty())
      this.quill.clipboard.dangerouslyPasteHTML(this.init);

    this.quill.on('text-change', function (delta, oldDelta, source) {
      that.valueChange.emit(that.quill.root.innerHTML);
    });

    const customFileButton = document.querySelector('.ql-file');
    customFileButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path class="ql-fill" d="M18 15.75q0 2.6-1.825 4.425T11.75 22q-2.6 0-4.425-1.825T5.5 15.75V6.5q0-1.875 1.313-3.187T10 2q1.875 0 3.188 1.313T14.5 6.5v8.75q0 1.15-.8 1.95t-1.95.8q-1.15 0-1.95-.8T9 15.25V6h2v9.25q0 .325.213.538t.537.212q.325 0 .538-.213t.212-.537V6.5q-.025-1.05-.737-1.775T10 4q-1.05 0-1.775.725T7.5 6.5v9.25q-.025 1.775 1.225 3.013T11.75 20q1.75 0 2.975-1.237T16 15.75V6h2z"/></svg>';
    customFileButton.addEventListener('click', () => this.onFileLink())
  }

  onImageUpload() {
    this.mode = "embed";
    this.filePickerModal.openModal();
  }
  
  onFileLink(){
    this.mode = "link";
    this.filePickerModal.openModal();
  }

  addFile(file: {path: string, type: 'image' | 'video' | 'audio'}) {
    this.resourceManager.set(ApiEndpointsService.API_ENDPOINT + '/' + file.path);

    const url = this.resourceManager.get('URL');
    this.quill.focus();
    let range = this.quill.getSelection(true);

    if (this.mode === "embed") {
      this.quill.insertEmbed(range.index, file.type, url);
    }
    else {
      const filename = file.path.split('/').slice(-1).join('/');
      if (range.length == 0) {
        this.quill.insertText(range.index, filename, 'user');
        this.quill.setSelection(range.index, filename.length);
      }
      this.quill.theme.tooltip.edit('link', url);
      this.quill.theme.tooltip.save();
    }
  }

}


