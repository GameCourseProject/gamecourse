import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {Observable} from "rxjs";

import * as CodeMirror from 'codemirror';
import 'codemirror/mode/css/css';
import 'codemirror/addon/hint/show-hint';
import 'codemirror/addon/hint/css-hint';
import 'codemirror/addon/display/placeholder';

@Component({
  selector: 'app-input-code',
  templateUrl: './input-code.component.html',
  styleUrls: ['./input-code.component.scss']
})
export class InputCodeComponent implements OnInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() mode: string;                      // Type of code to write
  @Input() init: string;                      // Value on init
  @Input() placeholder: string;               // Message to show by default
  @Input() canInit: Observable<void>;         // Trigger init

  // Extras
  @Input() title?: string;                    // Textarea title
  @Input() options?: any;                     // Codemirror options
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<string>();

  codemirror: CodeMirror.Editor;

  constructor() { }

  ngOnInit(): void {
    this.canInit.subscribe(() => this.initCodeMirror());
  }

  initCodeMirror(): void {
    if (this.codemirror) return;

    if (!this.options) {
      this.options = {
        lineNumbers: true,
        styleActiveLine: true,
        autohint: true,
        lineWrapping: true,
        theme: "mdn-like"
      };
    }

    this.options['mode'] = this.mode;
    this.options['value'] = !this.init?.isEmpty() ? this.init : null;

    const textarea = $('#' + this.id)[0] as HTMLTextAreaElement;
    this.codemirror = CodeMirror.fromTextArea(textarea, this.options);

    const that = this;
    this.codemirror.on("keyup", function (cm, event) {
      cm.showHint(CodeMirror.hint[that.mode]);
    });

    this.codemirror.on("change", function (cm, event) {
      that.valueChange.emit(that.codemirror.getValue());
    });
  }

}
