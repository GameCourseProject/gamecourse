import { AfterViewInit, Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { EditorView, basicSetup } from "codemirror";
import { EditorState, Compartment, StateField, StateEffect, Range } from "@codemirror/state";
import { syntaxTree } from "@codemirror/language";
import { SearchCursor } from "@codemirror/search";
import { hoverTooltip, Decoration } from "@codemirror/view";
import { ThemingService } from "../../../../_services/theming/theming.service";

// THEMES
import { oneDark } from "@codemirror/theme-one-dark";
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
import { python, pythonLanguage } from "@codemirror/lang-python";
// @ts-ignore
import { javascript, javascriptLanguage } from "@codemirror/lang-javascript";
import {UpdateService} from "../../../../_services/update.service";
import {Reduce} from "../../../../_utils/lists/reduce";
import {moveItemInArray} from "@angular/cdk/drag-drop";

// import {Observable} from "rxjs";

@Component({
  selector: 'app-input-code',
  templateUrl: './input-code.component.html'
})
export class InputCodeComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                            // Unique id
  @Input() title: string;                                         // Textarea title
  @Input() tabOutput: string;                                     // Message of the Output once the code has run

  // Extras
  @Input() size?: 'md' | 'lg' = 'md';                             // Size of input code
  @Input() classList?: string;                                    // Classes to add
  @Input() disabled?: boolean;                                    // Make it disabled
  @Input() helperText?: string;                                   // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';  // Helper position
  @Input() showTabs?: boolean = true;                             // Boolean to show/hide tabs (this will only show content of first tab)
  @Input() receiveFunctionSelection?: MySelection = {
    selection: "",
    functionName: null,
    argumentsArray: null,
    library: null
  }; // TODO -- explain

  @Input() tabs?: ( CodeTab | OutputTab | ReferenceManualTab )[] = [
    { name: 'Code', type: "code", active: true, mode: "python"},
    { name: 'Output', type: "output", active: false, running: false, specificFunction: false }];

  // Validity
  @Input() required?: boolean;                                    // Make it required
  // Errors
  @Input() requiredErrorMessage?: string;                         // Message for required error


  @Output() valueChange = new EventEmitter<string>();
  @Output() isCompleted = new EventEmitter<boolean>();
  @Output() runOutput = new EventEmitter<any>();
  @Output() refreshOutput = new EventEmitter<any>();
  @Output() sendFunctionSelection = new EventEmitter<MySelection>();   // TODO -- explain

  options: Completion[] = [];                                     // Editor options for autocompletion
  views: {[tabID: number]: EditorView};                           // All EditorView's sorted by tabs
  editorTheme: Compartment = new Compartment;                     // Theme to toggle between dark and light mode

  // If there's anything in complete (doesn't matter from which tab), the alert will be visible
  showAlert: boolean = false;                                    // Boolean for incomplete lines

  // SEARCH AND FILTER
  // NOTE: Because there can only be 1 tab active at a time, we can take these variables as 'global' for the entire component
  reduce = new Reduce();
  originalFunctions: CustomFunction[] = [];
  functionsToShow: CustomFunction[] = [];
  filteredFunctions: CustomFunction[] = [];

  // REFERENCE MANUAL
  namespaces: Set<string>;
  selectedFunction: CustomFunction = null;                       // Selected function to show information in reference manual

  constructor(
    private themeService: ThemingService,
    private updateService: UpdateService
  ) { }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    let views = {};
    for (let i = 0; i < this.tabs.length; i++){
      views[i] = new EditorView();

      if (this.tabs[i].type === 'code'){
        this.setUpKeywords((this.tabs[i] as CodeTab));

      } else if (this.tabs[i].type === 'manual') {
        this.filteredFunctions = (this.tabs[i] as ReferenceManualTab).customFunctions;
        this.functionsToShow = (this.tabs[i] as ReferenceManualTab).customFunctions;
      }
    }
    this.views = views;

    this.updateService.update.subscribe((value: any) => {
      // Trigger your function here based on the variable change
      this.loadTheme();
    });
  }

  // Setups all keywords and functions for code
  setUpKeywords(tab: CodeTab) {

    const modeKeywords = getLanguageKeywords(tab.mode);

    // Gets keywords from the specific language
    // FIXME -- could be refactored to a more elegant way
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
        section: option.name,
        info: option.args.map(arg =>
              { return ( arg === option.args[0] ? "(" : " ") + arg.name + (arg.optional ? "? " : "") + ": " +
                arg.type + ( arg === option.args[option.args.length - 1] ? ") " : " ") }) + option.description,
        apply: (view, completion, from, to) => {
          const replacement = option.name !== 'gamerules' ?
            `${option.name}.${completion.label}` : `${completion.label}`;
          const transaction = view.state.update({
            changes: { from, to, insert: replacement }
          });
          view.dispatch(transaction);
        }
      })));

    this.options = options;
  }


  /*** --------------------------------------------- ***/
  /*** --------------- AfterViewInit --------------- ***/
  /*** --------------------------------------------- ***/

  ngAfterViewInit(): void {
    for (let i = 0; i < this.tabs.length; i++){
      if (this.tabs[i].type === 'code') {
        this.initCodeMirror(i, (this.tabs[i] as CodeTab));
      }
    }
  }

  // Initializes code editor and basic setup
  initCodeMirror(index: number, tab: CodeTab) {

    const element = document.getElementById(this.getId(index, tab.name)) as Element;
    let tabSize = new Compartment;
    const readonly = tab.readonly ? tab.readonly : false;

    let query = tab.highlightQuery;

    // Initializes with the device's theme
    const theme = this.themeService.getTheme() === 'dark' ? oneDark : basicLight;

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
                width: "80%",
              },
              ".cm-tooltip-cursor": {
                border: "none",
                padding: "5px",
                borderRadius: "4px",
                "& .cm-tooltip-arrow:before": {
                  borderTopColor: "#66b !important"
                },
                "& .cm-tooltip-arrow:after": {
                  borderTopColor: "transparent",
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

    const highlightEffect = StateEffect.define<Range<Decoration>[]>();

    const highlightExtension = StateField.define({
      create() { return Decoration.none },
      update(value, transaction) {
        value = value.map(transaction.changes)

        for (let effect of transaction.effects) {
          if (effect.is(highlightEffect)) value = value.update({add: effect.value, sort: true})
        }

        return value
      },
      provide: f => EditorView.decorations.from(f)
    });

    // State and View basic definition
    let state = EditorState.create({
      doc: tab.value ? tab.value + "\n" : ("# " + (tab.placeholder ? tab.placeholder : "Write your code here!") + "\n"),
      extensions: [
        basicSetup,
        highlightExtension,
        this.editorTheme.of(theme),
        tabSize.of(EditorState.tabSize.of(8)),
        this.chooseMode(tab.mode),
        autocompletion({
          override: [completePy]
        }),
        EditorView.lineWrapping,
        EditorView.updateListener.of((update) => {
          if (update.docChanged && update.selectionSet && update.viewportChanged){
            this.valueChange.emit(view.state.doc.toString());
            if (query) this.toggleAlert(view, query);
            this.isCompleted.emit(!this.showAlert);
            //insertCommentCommand(view);
          }
          let firstRange = view.state.selection.ranges[0];

          let selection = state.doc.toString().substring(firstRange.from,firstRange.to);

          if (selection){
            let text = selection.split(/(\w+)\((.*)\)/);

            const mySelection : MySelection = {
              selection: selection,
              functionName: text[1],
              argumentsArray: text[2].split(',').map(arg => arg.trim()),
              library: selection.split(".")[0] ?? 'gamerules'
            };
            console.log(mySelection);

            this.sendFunctionSelection.emit(mySelection);
          }
        }),
        wordHover,
        EditorState.readOnly.of(readonly),
        EditorView.editable.of(!readonly),
      ],
    });

    // Only show autocompletion when starting to type
    const context = new CompletionContext(state, 0, true);

    this.views[index] = new EditorView({
      state,
      parent: element
    });

    let view = this.views[index];
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


    if (query) {

      let showAlert = this.showAlert;
      // highlights incomplete lines of code
      highlight(view, query);
      function highlight(view: EditorView, query: string) {
        // cursor based on the doc content and the substring
        let cursor = new SearchCursor(view.state.doc, query);

        const highlightDecoration = Decoration.mark({
          attributes: {style: "background-color: yellow"}
        });

        while (!cursor.done) {
          cursor.next();

          // Makes warning visible
          showAlert = true;

          // this is where the change takes effect by the dispatch. The of method instantiate the effect. You need to put this code where you want the change to take place
          view.dispatch({
            effects: highlightEffect.of([highlightDecoration.range(cursor.value.from, cursor.value.to)])
          });
        }
      }
      this.showAlert = showAlert;
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
    /*const insertCommentCommand = (view: EditorView) => {

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
    }*/

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

  // Function to make the warning visible or not
  toggleAlert(view: EditorView, query: string){
    this.showAlert = (view.state.doc.toString()).includes(query);
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Output ------------------- ***/
  /*** --------------------------------------------- ***/

  getTooltip(tab: OutputTab | CodeTab): string {
    if ((tab as OutputTab).type === "output") {
      if ((tab as OutputTab).specificFunction) {
        return (tab as OutputTab).tooltip ?? "Select the function you want to preview alongside its arguments with cursor!";
      }
      else if (!(tab as OutputTab).specificFunction) {
        return (tab as OutputTab).tooltip ?? "Click \'Run\' to simulate output!";
      }
    }
    return null;
  }

  isRunning(tab: OutputTab | CodeTab): boolean {
    if ((tab as OutputTab).type === "output") {
      return (tab as OutputTab).running;
    }
    return null;
  }

  simulateOutput(indexTab: number, tab: OutputTab | CodeTab){
    if ((tab as OutputTab).type === "output") {
      (tab as OutputTab).running = true;
      this.runOutput.emit([indexTab, this.receiveFunctionSelection]);
    }
  }

  refreshingOutput(indexTab: number, tab: OutputTab | CodeTab) {
    if ((tab as OutputTab).type === "output") {
      if (this.tabOutput) {
        tab.value = this.tabOutput;
        (tab as OutputTab).running = false;
        this.tabOutput = null;
        return;
      }
      this.refreshOutput.emit(indexTab);
    }

  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.originalFunctions, query);
  }

  filterFunctions(searchQuery?: string) {
    if (searchQuery) {
      console.log("hey");
      let functions: CustomFunction[] = [];
      for (let i = 0; i < this.filteredFunctions.length; i++){
        if (((this.filteredFunctions[i].keyword).toLowerCase()).includes(searchQuery.toLowerCase())) {
          functions.push(this.filteredFunctions[i]);
        }
      }
      this.functionsToShow = functions;
    }
    else {
      this.functionsToShow = this.filteredFunctions;
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  containsFunctions(namespace: string) {
    let namespaces = this.functionsToShow.map(fx => fx.name);
    return namespaces.includes(namespace)
  }

  isSelected(fx: CustomFunction){
    if (this.selectedFunction !== null){
      return this.selectedFunction.keyword === fx.keyword;
    }
    return false;
  }

  getId(index: number, tabName: string): string {
    return (this.title ? this.title : "") + "-" + index.toString() + "-" + tabName
  }

  isInFunctions(word: string): boolean{
    let functions = this.options.filter(option => option.type === "function");
    return functions.map(myFunction => { return myFunction.label }).includes(word);
  }

  getTheme(): string{
    return this.themeService.getTheme();
  }

  loadTheme() {
    for (let i = 0; i < this.tabs.length; i++){
      this.views[i].dispatch({
        effects: this.editorTheme.reconfigure(this.themeService.getTheme() === "light" ? basicLight : oneDark)
      });
    }
  }

  toggleTabs(index: number): void {
    for (let i = 0; i < this.tabs.length; i++){
      this.tabs[i].active = i === index;
    }
  }
}

export interface CodeTab {
  name: string,                                    // Name of the tab that will appear above
  type: "code",                                    // Specifies type of tab in editor
  active: boolean,                                 // Indicates which tab is active (only one at a time!)
  highlightQuery?: string,                         // Text to highlight
  value?: string,                                  // Value on init
  mode?: "python" | "javascript",                  // Type of code to write. E.g. python, javascript, ... NOTE: only python-lang and javascript-lang installed. Must install more packages for others
  placeholder?: string,                            // Message to show by default
  nrLines?: number,                                // Number of lines already added to the editor. Default = 10 lines
  customKeywords?: string[],                       // Personalized keywords
  customFunctions?: CustomFunction[],              // Personalized functions
  readonly?: boolean,                              // Make editor readonly
}

export interface OutputTab {
  name: string,                              // Name of the tab that will appear above
  type: "output",                            // Specifies type of tab in editor
  active: boolean,                           // Indicates which tab is active (only one at a time!)
  running: boolean,                          // Boolean to show if code is running in background
  specificFunction: boolean,                 // Boolean to indicate whether entire code should be run or only a small selection
  runMessage?: string,                       // Custom message when running code
  value?: string,                            // Output value once run
  tooltip?: string,                          // Message to show by default
}

export interface ReferenceManualTab {
  name: string,                              // Name of the tab that will appear above
  type: "manual",                            // Specifies type of tab in editor
  active: boolean,                           // Indicates which tab is active (only one at a time!)
  customFunctions?: CustomFunction[],        // Personalized functions
  namespaces?: string[]                      // Namespaces of functions
}

export interface CustomFunction {
  moduleId?: string,                                      // Module from which the functions belong to (e.g. gamerules) - not really used here (hence being optional)
  name: string,                                           // Name of the module or library the function belongs to
  keyword: string,                                        // Name of the function
  description: string,                                    // Description of the function (what it does + return type)
  args: {name: string, optional: boolean, type: any}[],   // Arguments that each function receives
  returnType: string,                                     // Type of value it returns
}

export interface MySelection {
  selection: string,
  functionName: string,
  argumentsArray: string[],
  library: string
}
