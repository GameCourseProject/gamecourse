import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {EditorView, basicSetup} from "codemirror";
import {EditorState, Compartment} from "@codemirror/state";
import {syntaxTree} from "@codemirror/language";
import {oneDark} from "@codemirror/theme-one-dark";
// @ts-ignore
import {autocompletion, CompletionContext, CompletionResult, CompletionSource} from "@codemirror/autocomplete";
// @ts-ignore
import {python, pythonLanguage} from "@codemirror/lang-python";
// @ts-ignore
import {javascript, javascriptLanguage} from "@codemirror/lang-javascript";


// import {Observable} from "rxjs";

@Component({
  selector: 'app-input-code',
  templateUrl: './input-code.component.html',
  styleUrls: ['./input-code.component.css']
})
export class InputCodeComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                     // Unique id
  @Input() mode: "python" | "javascript" = "python";       // Type of code to write. E.g. python, javascript, ... NOTE: only python-lang and javascript-lang installed. Must install more packages for others
  @Input() value: string;                                  // Value on init
  @Input() placeholder: string = "Write your code here!";  // Message to show by default

  // Extras
  @Input() nrLines?: number = 10;             // Number of lines already added to the editor. Default = 10 lines
  @Input() title?: string;                    // Textarea title
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled
  @Input() customKeywords?: any[];            // Codemirror options

  @Input() helperText?: string;                                               // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';              // Helper position

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<string>();

  constructor() { }

  ngOnInit(): void { }

  ngAfterViewInit(): void {
    this.initCodeMirror();
  }

  // Initializes code editor and basic setup
  initCodeMirror() {

    const element = document.getElementById(this.id) as Element;
    let tabSize = new Compartment;

    console.log(this.value);
    // State and Editor basic definition
    let state = EditorState.create({
      doc: "# " + this.placeholder + "\n" + (this.value ?? "") + "\n",
      extensions: [
        basicSetup,
        oneDark,
        tabSize.of(EditorState.tabSize.of(8)),
        this.chooseMode(),
        autocompletion({override: [completePy]}),
        /*EditorView.theme({
          '.cm-tooltip-autocomplete': {
            //top: '300px !important',
          }
        }),*/ // FIXME -- delete later
        EditorView.lineWrapping
      ],
    });

    // Only show autocompletion when starting to type
    const context = new CompletionContext(state, 0, true);

    let editor = new EditorView({
      state,
      parent: element
    });

    const myOptions = this.customKeywords;

    // Autocompletion feature
    function completePy(context: CompletionContext): CompletionResult {
      let nodeBefore = syntaxTree(context.state).resolveInner(context.pos, -1)
      let textBefore = context.state.sliceDoc(nodeBefore.from, context.pos)
      let lastWord = /^\w*$/.exec(textBefore)

      // Python keywords in a list
      const pyKeywords = ['and', 'as', 'assert', 'async', 'await', 'break', 'class', 'continue', 'def', 'del',
        'elif', 'else', 'except', 'False', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda',
        'None', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'True', 'try', 'while', 'with', 'yield'];

      let options = pyKeywords.map(keyword => ({label: keyword, type: "keyword"}));

      // Add the Python keywords to the options array
      options = options.concat(myOptions.map(option => ({label: option, type: "function"})));

      if (!lastWord && !context.explicit) return null
      return {
        from: lastWord ? nodeBefore.from + lastWord.index : context.pos,
        options: options,
        validFor: /^\w*$/
      }
    }


    // Set number of lines initialized
    updateToMinNumberOfLines(editor, this.nrLines);
    function updateToMinNumberOfLines(editor, minNumOfLines) {
      const currentNumOfLines = editor.state.doc.lines;
      let currentStr = editor.state.doc.toString();

      if (currentNumOfLines >= minNumOfLines) {
        return;
      }
      const lines = minNumOfLines - currentNumOfLines;
      const appendLines = "\n".repeat(lines);
      editor.dispatch({
        changes: {from: currentStr.length, insert: appendLines}
      })
    }

  }

  // Function to select which language should the editor provide
  chooseMode() {
    let language = new Compartment;

    switch (this.mode){
      case "python": return language.of(python());
      case "javascript": return language.of(javascript()); // NOTE: not tested
    }
  }

}
