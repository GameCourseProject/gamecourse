import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {EditorView, basicSetup} from "codemirror";
import {EditorState, Compartment} from "@codemirror/state";
// @ts-ignore
import {autocompletion, CompletionContext, CompletionSource} from "@codemirror/autocomplete";
// @ts-ignore
import {python} from "@codemirror/lang-python";


// import {Observable} from "rxjs";

@Component({
  selector: 'app-input-code',
  templateUrl: './input-code.component.html',
  styleUrls: ['./input-code.component.scss']
})
export class InputCodeComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() mode: string;                      // Type of code to write
  @Input() value: string;                     // Value on init
  @Input() placeholder: string;               // Message to show by default
  //@Input() canInit: Observable<void>;         // Trigger init

  // Extras
  @Input() title?: string;                    // Textarea title
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled
  @Input() options?: any[];                     // Codemirror options

  @Input() helperText?: string;                                               // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';              // Helper position

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<string>();

  codemirror: EditorView;

  constructor() { }

  ngOnInit(): void {
    //this.canInit.subscribe(() => this.initCodeMirror());
    //this.initCodeMirror();
  }

  ngAfterViewInit(): void {
    this.initCodeMirror()
  }

  initCodeMirror() {

    const element = document.getElementById(this.id) as Element;
    let language = new Compartment, tabSize = new Compartment;

    const state = EditorState.create();
    const context = new CompletionContext(state, 0, true);

    const completions = this.myCompletions(context, this.options);

    let view = new EditorView({
      doc: "// Type a 'p'\n",
      extensions: [
        basicSetup,
        language.of(python()),
        //autocompletion({override: [completions]}),
      ],
      parent: element
    })


  }

  myCompletions(context: CompletionContext, options: any[]) {
    console.log("hey");
    let before = context.matchBefore(/\w+/)
    // If completion wasn't explicitly started and there
    // is no word before the cursor, don't open completions.
    if (!context.explicit && !before) return null

    console.log(options);
    return {
      from: before ? before.from : context.pos,
      options: options,
      validFor: /^\w*$/
    }
  }
    /*"use strict";

    this.mode = "python";
    CodeMirror(document.getElementById("my-div"), {
      value: "",
      mode: "python",
      tabSize: 5,
      lineNumbers: true,
      firstLineNumber: 50,
      extraKeys: {"Ctrl-Space": "autocomplete"},
      showHint: true
    });

    let WORD = /[\w$]+/;
    let RANGE = 500;
    let EXTRAWORDS = ['amazing', 'extra', 'yeah', 'toto'];

    CodeMirror.registerHelper('hint', "anyword", function (editor, options) {
      let word = options && options.word || WORD;
      let range = options && options.range || RANGE;
      let extraWords = options && options.extrawords || EXTRAWORDS;
      let cur = editor.getCursor(), curLine = editor.getLine(cur.line);

      let end = cur.ch, start = end;
      while (start && word.test(curLine.charAt(start - 1))) --start;
      let curWord = start != end && curLine.slice(start, end);

      let list = options && options.list || [], seen = {};
      let re = new RegExp(word.source, "g");

      for (let dir = -1; dir <= 1; dir += 2) {
        let line = cur.line,
          endLine = Math.min(Math.max(line + dir * range, editor.firstLine()), editor.lastLine()) + dir;

        for (; line != endLine; line += dir) {
          let text = editor.getLine(line), m;
          while (m = re.exec(text)) {
            if (line == cur.line && m[0] === curWord) continue;
            if ((!curWord || m[0].lastIndexOf(curWord, 0) == 0) && !Object.prototype.hasOwnProperty.call(seen, m[0]))
            {
              seen[m[0]] = true;
              list.push(m[0]);
            }
          }
        }
      }
      list.push(...(extraWords.filter(el => el.startsWith(curWord || ''))));
      return {list: list, from: CodeMirror.Pos(cur.line, start), to: CodeMirror.Pos(cur.line, end)};
    });


    const that = this;
    console.log(CodeMirror.hint);
    CodeMirror.commands.autocomplete = function (cm) {
      cm.showHint({hint: CodeMirror.hint[that.mode]})
    };
  }
    /*if (this.codemirror) return;

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
  }*/


}
