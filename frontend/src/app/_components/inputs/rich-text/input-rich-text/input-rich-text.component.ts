import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {Observable} from "rxjs";

import * as Quill from 'quill';
import htmlEditButton from 'quill-html-edit-button';
import imageResize from 'quill-image-resize';
import {exists} from "../../../../_utils/misc/misc";

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

  @Output() valueChange = new EventEmitter<string>();

  quill: Quill;

  constructor() { }

  ngOnInit(): void {
    this.canInit.subscribe(() => this.initQuill());
  }

  initQuill() {
    if (this.quill) return;

    if (!this.options) {
      this.options = {
        modules: {
          toolbar: [
            [{ 'font': [] }, { header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ 'script': 'sub' }, { 'script': 'super' }],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }, { 'indent': '-1' }, { 'indent': '+1' }],
            ['link', 'image', 'video', 'code-block']
          ],
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

    const that = this;
    this.quill.on('text-change', function (delta, oldDelta, source) {
      that.valueChange.emit(that.quill.root.innerHTML);
    });
  }

}
