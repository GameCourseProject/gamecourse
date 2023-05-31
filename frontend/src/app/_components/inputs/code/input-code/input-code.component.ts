import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {EditorView, basicSetup} from "codemirror";
import {EditorState, Compartment} from "@codemirror/state";
import {syntaxTree} from "@codemirror/language";
import {oneDark} from "@codemirror/theme-one-dark";
import {Tooltip, hoverTooltip} from "@codemirror/view";
import {HighlightStyle, Language, LRLanguage } from '@codemirror/language';
import { basicLight } from "cm6-theme-basic-light";

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

  // Essentials
  @Input() id: string;                                            // Unique id
  @Input() title: string;                                         // Textarea title

  // Extras
  @Input() size?: 'md' | 'lg' = 'md';                             // Size of input code
  @Input() classList?: string;                                    // Classes to add
  @Input() disabled?: boolean;                                    // Make it disabled
  @Input() helperText?: string;                                   // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';  // Helper position
  @Input() showTabs?: boolean = true;                             // Boolean to show/hide tabs (this will only show content of first tab)
  @Input() tabs?: (codeTab | outputTab)[] = [
    { name: 'Code', type: "code", active: true, mode: "python"},
    { name: 'Output', type: "output", active: false, running: false }];
  //@Input() codeOutput?: string = "";                              // Result from running the code (to be shown at the 'output' tab)

  // Validity
  @Input() required?: boolean;                                    // Make it required
  // Errors
  @Input() requiredErrorMessage?: string;                         // Message for required error


  @Output() valueChange = new EventEmitter<string>();
  @Output() runOutput = new EventEmitter<any>();
  @Output() refreshOutput = new EventEmitter<any>();

  options: Completion[] = [];                                     // Editor options for autocompletion
  @Input() tabOutput: string;

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
  }

  // Setups all keywords and functions for code
  setUpKeywords(tab: codeTab) {

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
    options = options.concat(customFunctions.map(option => (
      { label: option.keyword,
        type: "function",
        detail: option.returnType,
        info: option.args.map(arg =>
              { return ( arg === option.args[0] ? "(" : " ") + arg.name + (arg.optional ? "? " : "") + ": " +
                arg.type + ( arg === option.args[option.args.length - 1] ? ") " : " ") }) + option.description})));

    this.options = options;
  }


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
  initCodeMirror(index: number, tab: codeTab) {

    const element = document.getElementById(this.getId(index, tab.name)) as Element;
    let tabSize = new Compartment;
    const readonly = tab.readonly ? tab.readonly : false;

    const prefersDarkTheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = prefersDarkTheme ? oneDark : basicLight;

    const wordHover = hoverTooltip((view, pos, side) => {
      let {from, to, text} = view.state.doc.lineAt(pos)
      let start = pos, end = pos

      while (start > from && /\w/.test(text[start - from - 1])) start--
      while (end < to && /\w/.test(text[end - from])) end++

      if (start == pos && side < 0 || end == pos && side > 0)
        return null

      let word = text.slice(start - from, end - from);
      if (this.isInFunctions(word)){
        let myFunction = tab.customFunctions.find(option => option.keyword === word);
        let text = myFunction.keyword + " (" +
          myFunction.args.map(arg => {
            return ( arg === myFunction.args[0] ? "" : " ") + arg.name + (arg.optional ? "? " : "") + ": " + arg.type
          }) + ")\n" + myFunction.description;

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
      doc: tab.value ? tab.value + "\n" : ("# " + (tab.placeholder ? tab.placeholder : "Write your code here!") + "\n"),
      extensions: [
        basicSetup,
        theme,
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
    const nrLines = tab.nrLines ?? 10;
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
        //console.log(state.selection.ranges.filter(range => range.empty).map(range => {return state.doc.lineAt(range.head).number}));
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

  getTooltip(tab: outputTab | codeTab): string {
    if ((tab as outputTab).type === "output"){
      return (tab as outputTab).tooltip ?? "Click \'Run\' to simulate output!";
    }
    return  "Click \'Run\' to simulate output!";
  }

  isRunning(tab: outputTab | codeTab): boolean {
    if ((tab as outputTab).type === "output") {
      return (tab as outputTab).running;
    }
    return null;
  }

  simulateOutput(tab: outputTab | codeTab){
    if ((tab as outputTab).type === "output") {
      (tab as outputTab).running = true;
      this.runOutput.emit();
    }
  }

  refreshingOutput(tab: outputTab | codeTab) {
    if ((tab as outputTab).type === "output") {
      if (this.tabOutput) {
        tab.value = this.tabOutput;
        (tab as outputTab).running = false;
        this.tabOutput = null;
        return;
      }
      this.refreshOutput.emit();
    }

  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  getId(index: number, tabName: string): string{
    return (this.title ? this.title : "") + "-" + index.toString() + "-" + tabName
  }

  isInFunctions(word: string): boolean{
    let functions = this.options.filter(option => option.type === "function");
    return functions.map(myFunction => { return myFunction.label }).includes(word);
  }

  getTheme(): string{
    return this.themeService.getTheme();
  }

  toggleTabs(index: number): void {
    for (let i = 0; i < this.tabs.length; i++){
      this.tabs[i].active = i === index;
    }
  }
}

export interface codeTab {
  name: string,                              // Name of the tab that will appear above
  type: "code" | "output",                   // Specifies type of tab in editor
  active: boolean,                           // Indicates which tab is active (only one at a time!)
  value?: string,                            // Value on init
  mode?: "python" | "javascript",            // Type of code to write. E.g. python, javascript, ... NOTE: only python-lang and javascript-lang installed. Must install more packages for others
  placeholder?: string,                      // Message to show by default
  nrLines?: number,                          // Number of lines already added to the editor. Default = 10 lines
  customKeywords?: string[],                 // Personalized keywords
  customFunctions?: customFunction[],        // Personalized functions
  readonly?: boolean,                        // Make editor readonly
}

export interface outputTab {
  name: string,                              // Name of the tab that will appear above
  type: "code" | "output",                   // Specifies type of tab in editor
  active: boolean,                           // Indicates which tab is active (only one at a time!)
  running: boolean,                          // Boolean to show if code is running in background
  value?: string,                            // Output value once run
  tooltip?: string,                      // Message to show by default
}

export interface customFunction {
  moduleId?: string,                                      // Module from which the functions belong to (e.g. gamerules) - not really used here (hence being optional)
  name: string,                                           // Name of the module or library the function belongs to
  keyword: string,                                        // Name of the function
  description: string,                                    // Description of the function (what it does + return type)
  args: {name: string, optional: boolean, type: any}[],   // Arguments that each function receives
  returnType: string,                                     // Type of value it returns
  example?: string                                        // Example of how the function should be used and what it returns
}
