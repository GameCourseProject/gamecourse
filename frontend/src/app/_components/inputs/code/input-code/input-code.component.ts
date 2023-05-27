import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {EditorView, basicSetup} from "codemirror";
import {EditorState, Compartment} from "@codemirror/state";
import {syntaxTree} from "@codemirror/language";
import {oneDark} from "@codemirror/theme-one-dark";
import {Tooltip, hoverTooltip} from "@codemirror/view";
import {HighlightStyle, Language, LRLanguage } from '@codemirror/language';

// @ts-ignore
import { highlightTree } from '@codemirror/highlight';

// @ts-ignore
import {
  autocompletion,
  Completion,
  CompletionContext,
  CompletionResult,
  CompletionSource,
  completionStatus
} from "@codemirror/autocomplete";

// @ts-ignore
import {python, pythonLanguage} from "@codemirror/lang-python";
// @ts-ignore
import {javascript, javascriptLanguage} from "@codemirror/lang-javascript";
import {ThemingService} from "../../../../_services/theming/theming.service";


// import {Observable} from "rxjs";

@Component({
  selector: 'app-input-code',
  templateUrl: './input-code.component.html'
})
export class InputCodeComponent implements OnInit, AfterViewInit {

  // Extras
  // Essentials
  @Input() title: string;                                     // Textarea title
  @Input() id: string;                                        // Unique id
  //@Input() mode: "python" | "javascript" = "python";        // Type of code to write. E.g. python, javascript, ... NOTE: only python-lang and javascript-lang installed. Must install more packages for others
  //@Input() value: string;                                   // Value on init

  //@Input() placeholder: string = "Write your code here!";     // Message to show by default
  @Input() size?: 'md' | 'lg' = 'md';                         // Size of input code
  //@Input() nrLines?: number = 10;             // Number of lines already added to the editor. Default = 10 lines
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled
  //@Input() customKeywords?: string[] = [];    // Personalized keywords

  @Input() showTabs?: boolean = true;           // Boolean to show/hide tabs (this will only show content of first tab)
  //@Input() readonly?: boolean = false;          // Make editor readonly

  // FIXME: Refactor this to be flexible and accept more than only 2 tabs
  //@Input() tabNames?: string[] = ['Code', 'Output']        // Names of the tabs that will be shown

  @Input() tabs?: tabInfo[] = [
    { name: 'Code', type: "code", show: true, mode: "python"},
    {name: 'Output', type: "output", show: false}];

  // Personalized functions
  //@Input() customFunctions?: customFunctions[] = [];

  @Input() helperText?: string;                                               // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';              // Helper position

  // Validity
  @Input() required?: boolean;                                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;                     // Message for required error

  @Output() valueChange = new EventEmitter<string>();
  @Output() output = new EventEmitter<string>();

  options: Completion[] = [];                                   // Editor options for autocompletion

  // FIXME -- remove later
  //tabs: {[tabName: string]: boolean} = {};                      // Tabs with name as key and boolean to enable

  constructor(
    private themeService: ThemingService
  ) { }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    for (let i = 0; i < this.tabs.length; i++){
      if (this.tabs[i].type === 'code'){
        this.setUpKeywords(this.tabs[i])
      }
    }
    //this.setUpKeywords();
    // if (this.showTabs) this.setUpTabs();
  }

  // Setups all keywords and functions for code
  setUpKeywords(tab: tabInfo) {

    const modeKeywords = getLanguageKeywords(tab.mode);

    // Gets keywords from the specific language
    function getLanguageKeywords(mode: string): string[] {
      switch (mode) {
        case "python": return ['and', 'as', 'assert', 'async', 'await', 'break', 'class', 'continue', 'def', 'del',
          'elif', 'else', 'except', 'False', 'finally', 'for', 'from', 'global', 'if', 'import', 'in', 'is', 'lambda',
          'None', 'nonlocal', 'not', 'or', 'pass', 'raise', 'return', 'True', 'try', 'while', 'with', 'yield'];
        case "javascript": return ["abstract", "await", "boolean", "break", "byte", "case", "catch", "char", "class", "const",
          "continue", "debugger", "default", "delete", "do", "double", "else", "enum", "export", "extends", "false",
          "final", "finally", "float", "for", "function", "goto", "if", "implements", "import", "in", "instanceof", "int",
          "interface", "let", "long", "native", "new", "null", "package", "private", "protected", "public", "return", "short",
          "static", "super", "switch", "synchronized", "this", "throw", "throws", "transient", "true", "try", "typeof", "var",
          "void", "volatile", "while", "with", "yield"];
        default: return [];
      }
    }

    let options = modeKeywords.map(keyword => ({label: keyword, type: "keyword"}));

    // Add personalized keywords to the options array
    let customKeywords = tab.customKeywords ?? [];
    options = options.concat(customKeywords.map(option => ({label: option, type: "keyword"})));

    // Add personalized functions to the options array
    let customFunctions = tab.customFunctions ?? [];
    options = options.concat(customFunctions.map(option => ({label: option.keyword, type: "function", detail: this.extractReturnType(option.description, false), info: option.args.map(arg => {
        return ( arg === option.args[0] ? "" : " ") + arg.name + (arg.optional ? "? " : "") + ": " + arg.type
      }) + ")\n" + this.extractReturnType(option.description, true)})));

    this.options = options;
  }

  extractReturnType(description: string, getDescription: boolean): string {
    let returnType = description.indexOf(":returns:")

    // If false then extract only the return type
    if (!getDescription){
      let finalString = description.slice(returnType);
      return finalString.replace(":returns:", "->");
    }

    return description.slice(0, returnType); // FIXME

  }

  /*setUpTabs(){


    for (let i = 0; i < this.tabNames.length; i++){
      this.tabs[this.tabNames[i]] = (i === 0);
    }
  }*/

  /*** --------------------------------------------- ***/
  /*** --------------- AfterViewInit --------------- ***/
  /*** --------------------------------------------- ***/

  ngAfterViewInit(): void {
    for (let i = 0; i < this.tabs.length; i++){
      if (this.tabs[i].type === 'code') {
        this.initCodeMirror(i, this.tabs[i]);
      }
    }
  }

  // Initializes code editor and basic setup
  initCodeMirror(index: number, tab: tabInfo) {

    const element = document.getElementById(this.title ?? "" + index.toString() + tab.name) as Element;
    let tabSize = new Compartment;
    const readonly = tab.readonly ? tab.readonly : false;

    const wordHover = hoverTooltip((view, pos, side) => {
      let {from, to, text} = view.state.doc.lineAt(pos)
      let start = pos, end = pos

      while (start > from && /\w/.test(text[start - from - 1])) start--
      while (end < to && /\w/.test(text[end - from])) end++

      if (start == pos && side < 0 || end == pos && side > 0)
        return null

      let word = text.slice(start - from, end - from);
      if (this.isInFunctions(word)){
        //console.log(this.customFunctions);
        let myFunction = tab.customFunctions.find(option => option.keyword === word);
        let text = myFunction.name + " (" +
          myFunction.args.map(arg => {
            return ( arg === myFunction.args[0] ? "" : " ") + arg.name + (arg.optional ? "? " : "") + ": " + arg.type
          }) + ")\n" + this.extractReturnType(myFunction.description, true);

        return {
          pos: start,
          end,
          above: false,
          create(view) {
            let dom = document.createElement("tag-div")
            dom.className = "cm-tooltip-cursor"
            EditorView.baseTheme({
              ".cm-tooltip-lint": {
                width: "80%"
              },
              ".cm-tooltip-cursor": {
                backgroundColor: "#66b !important",
                color: "white",
                border: "none",
                padding: "5px",
                borderRadius: "4px",
                "& .cm-tooltip-arrow:before": {
                  borderTopColor: "#66b !important"
                },
                "& .cm-tooltip-arrow:after": {
                  borderTopColor: "transparent"
                }
              }
            })
            dom.textContent = text
            return {dom}
          }
        }
      }

      return null;

    })

    // State and View basic definition
    let state = EditorState.create({
      doc: tab.value ? tab.value + "\n" : "# " + tab.placeholder ? tab.placeholder : "Write your code here!" + "\n",
      extensions: [
        basicSetup,
        oneDark,
        tabSize.of(EditorState.tabSize.of(8)),
        this.chooseMode(tab.mode),
        autocompletion({override: [completePy]}),
        EditorView.lineWrapping,
        EditorView.updateListener.of((update) => {
          if (update.docChanged && update.selectionSet && update.viewportChanged){
            insertCommentCommand(view);
          }
        }),
        wordHover,
        EditorState.readOnly.of(readonly),
        EditorView.editable.of(!readonly)
      ],
    });

    // Only show autocompletion when starting to type
    const context = new CompletionContext(state, 0, true);

    let view = new EditorView({
      state,
      parent: element
    });

    const options = this.options;

    // Autocompletion feature
    function completePy(context: CompletionContext): CompletionResult {
      let nodeBefore = syntaxTree(context.state).resolveInner(context.pos, -1);
      let textBefore = context.state.sliceDoc(nodeBefore.from, context.pos);
      let lastWord = /^\w*$/.exec(textBefore);

      if (!lastWord && !context.explicit) return null
      return {
        from: lastWord ? nodeBefore.from + lastWord.index : context.pos,
        options: options,
        validFor: /^\w*$/
      }
    }


    // Set number of lines initialized
    const nrLines = tab.nrLines ? tab.nrLines : 10;
    updateToMinNumberOfLines(view, nrLines);
    function updateToMinNumberOfLines(view, minNumOfLines) {
      const currentNumOfLines = view.state.doc.lines;
      let currentStr = view.state.doc.toString();

      if (currentNumOfLines >= minNumOfLines) {
        return;
      }
      const lines = minNumOfLines - currentNumOfLines;
      const appendLines = "\n".repeat(lines);
      view.dispatch({
        changes: {from: currentStr.length, insert: appendLines}
      })
    }


    /*return state.selection.ranges
      .filter(range => range.empty)
      .map(range => {
        let line = state.doc.lineAt(range.head)
        let text = line.number + ":" + (range.head - line.from)
        return {
          pos: range.head,
          above: true,
          strictSide: true,
          arrow: true,
          create: () => {
            let dom = document.createElement("div")
            dom.className = "cm-tooltip-cursor"
            dom.textContent = text
            return {dom}
          }
        }
      })*/



    // FIXME
    const insertCommentCommand = (view: EditorView) => {
      this.valueChange.emit(view.state.doc.toString());

      let words = (view.state.doc.toString()).split(/\s+/);

      let lastWord = words.splice(words.length - 2, 1)[0]; // ignore last element (is empty) // FIXME

      if (lastWord && this.isInFunctions(lastWord)) {
        console.log(state.selection.ranges.filter(range => range.empty).map(range => {return state.doc.lineAt(range.head).number}));
        //let lineNr = state.doc.lineAt(state.selection.ranges[-1].head).number
        //console.log(lineNr);
        const comment = "# this is a new comment!\n";
        const line = view.state.doc.lineAt(state.doc.lineAt(state.selection.main.head).number - 1);
        const tr = view.state.update({changes: {from: line.from, to: line.from, insert: comment}});
        view.dispatch(tr);

        return true;
      }
      return false;
    }

  }

  // Function to select which language should the editor provide
  chooseMode(mode: string) {
    let language = new Compartment;

    switch (mode){
      case "python": return language.of(python());
      case "javascript": return language.of(javascript()); // NOTE: not tested
      default: return language.of(python());
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Output ------------------- ***/
  /*** --------------------------------------------- ***/

  simulateOutput(){
    this.output.emit("emittingOutput"); // FIXME should emit code in editor 'Code' tab
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isInFunctions(word: string): boolean{
    let functions = this.options.filter(option => option.type === "function");
    return functions.map(myFunction => { return myFunction.label }).includes(word);
  }

  getTheme(): string{
    return this.themeService.getTheme();
  }

  toggleTabs(index: number): void {
    for (let i = 0; i < this.tabs.length; i++){
      this.tabs[i].show = i === index;
    }
  }
}

export interface tabInfo {
  name: string,
  type: "code" | "output",
  show: boolean,
  value?: string,
  mode?: "python" | "javascript",
  placeholder?: string,
  nrLines?: number,
  customKeywords?: string[],
  customFunctions?: customFunctions[],
  readonly?: boolean
}

export interface customFunctions {
  moduleId: string,
  name: string,
  keyword: string,
  description: string,
  args: {name: string, optional: boolean, type: any}[]
}
