import {AfterViewInit, Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {basicSetup, EditorView} from "codemirror";
import {syntaxTree} from "@codemirror/language";
import {SearchCursor} from "@codemirror/search";
import {ThemingService} from "../../../../_services/theming/theming.service";

// THEMES
import {oneDark} from "@codemirror/theme-one-dark";
import {githubLight} from '@ddietr/codemirror-themes/github-light'

// @ts-ignore
import {highlightTree} from '@codemirror/highlight';

// @ts-ignore
import {Decoration, gutter, GutterMarker, hoverTooltip} from "@codemirror/view";

// @ts-ignore
import {Compartment, EditorState, Range, RangeSet, StateEffect, StateField} from "@codemirror/state";

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
import {UpdateService} from "../../../../_services/update.service";
import {Reduce} from "../../../../_utils/lists/reduce";
import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../_services/alert.service";

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

  @Input() tabs?: ( CodeTab | OutputTab | ReferenceManualTab | PreviewTab)[] = [
    { name: 'Code', type: "code", active: true, debug: true, mode: "python"},
    { name: 'Output', type: "output", active: false, running: false, debugOutput: false }];

  // Validity
  @Input() required?: boolean;                                    // Make it required
  // Errors
  @Input() requiredErrorMessage?: string;                         // Message for required error


  @Output() valueChange = new EventEmitter<string>();
  @Output() isCompleted = new EventEmitter<boolean>();
  @Output() runOutput = new EventEmitter<any>();
  @Output() refreshOutput = new EventEmitter<any>();
  @Output() debugSelection = new EventEmitter<string>();

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

  //selection: string

  expressionToPreview: string = "";
  outputPreview: any = null;

  constructor(
    private themeService: ThemingService,
    private updateService: UpdateService,
    private api: ApiHttpService
  ) { }

  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  ngOnInit(): void {
    let views = {};
    for (let i = 0; i < this.tabs.length; i++){
      views[i] = new EditorView();

      if (this.tabs[i].type === 'code' || this.tabs[i].type === 'preview'){
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
        case "el": return ["true", "false"];
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
      else if (this.tabs[i].type === 'preview') {
        this.initCodeMirror(i, (this.tabs[i] as PreviewTab));
      }
    }
  }

  // Initializes code editor and basic setup
  initCodeMirror(index: number, tab: CodeTab | PreviewTab) {

    const element = document.getElementById(this.getId(index, tab.name)) as Element;
    let tabSize = new Compartment;
    const readonly = tab.readonly ? tab.readonly : false;

    let query = tab.highlightQuery;

    // Initializes with the device's theme
    const theme = this.themeService.getTheme() === 'dark' ? oneDark : githubLight;

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
        let text = `<span class="text-secondary font-semibold text-sm">${myFunction.keyword + " (" +
                    myFunction.args.map(arg => {
                      return (arg === myFunction.args[0] ? "" : " ") +
                                `<span class="text-primary">${arg.name + (arg.optional ? "? " : "") + ": " + arg.type}</span>`;
                    }).join("") + ")"} <br /></span>
            <span class="text-sm">${myFunction.description}</span>`;

        return {
          pos: start,
          end,
          above: false,
          create(view) {
            let dom = document.createElement("tag-div")
            dom.className = "cm-tooltip-cursor"
            dom.innerHTML = text
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
          if ((update.docChanged && update.viewportChanged) || update.selectionSet){
            if (tab.type === 'code') {
              this.valueChange.emit(view.state.doc.toString());
              if (query) this.toggleAlert(view, query);
              this.isCompleted.emit(!this.showAlert);
            }
            else if (tab.type === 'preview') {
              this.expressionToPreview = view.state.doc.toString();
            }
            //insertCommentCommand(view);

            // Manages selection for 'preview function' functionality
            /*if (this.previewFunction && update.selectionSet){
              let selection = view.state.sliceDoc(view.state.selection.main.from, view.state.selection.main.to);

              let text = selection ? selection.split(/(\w+)\((.*)\)/) : [];

              let mySelection = this.initMySelection(selection);
              if (text.length >= 3){
                let argumentsArray = text[2].split(',').map(arg => arg.trim());

                const libraryPattern = /(\w+)\.\w+\(/;
                const library = selection.match(libraryPattern)?.[1] ?? 'gamerules';

                mySelection = this.initMySelection(selection, text[1], argumentsArray, library);
              }

              this.sendFunctionSelection.emit(mySelection);
            }*/

          }

        }),
        wordHover,
        EditorState.readOnly.of(readonly),
        EditorView.editable.of(!readonly),
        EditorView.baseTheme({
          ".cm-tooltip-lint": {
            width: "80%"
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
          },
          "&light .cm-tooltip-below" : {
            backgroundColor: "#dedfe1 !important",
          },
          " &light .cm-tooltip.cm-completionInfo": {
            backgroundColor: "#dedfe1 !important"
          },
          ".cm-completionInfo": {
            fontSize: "0.875rem",
            left: "0 !important",
            top: "100% !important",
            width: "300px !important"
          },
          ".cm-tooltip.cm-tooltip-autocomplete > ul": {
            width: "300px !important"
          }
        }),
        this.makeGutter(tab)
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
    this.updateToMinNumberOfLines(view, nrLines);

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

  // Creates additional number of lines if desired
  updateToMinNumberOfLines(view, minNumOfLines) {
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

  // Creates visual marker for debugging
  makeGutter(tab: any){
    const breakpointEffect = StateEffect.define<{pos: number, on: boolean}>({
      map: (val, mapping) => ({pos: mapping.mapPos(val.pos), on: val.on})
    })

    const breakpointState = StateField.define<RangeSet<GutterMarker>>({
      create() { return RangeSet.empty },
      update(set, transaction) {
        set = set.map(transaction.changes)
        for (let e of transaction.effects) {
          if (e.is(breakpointEffect)) {
            if (e.value.on)
              set = set.update({add: [breakpointMarker.range(e.value.pos)]})
            else
              set = set.update({filter: from => from != e.value.pos})
          }
        }
        return set
      }
    })

    function toggleBreakpoint(view: EditorView, pos: number) {
      let breakpoints = view.state.field(breakpointState)
      let hasBreakpoint = false
      breakpoints.between(pos, pos, () => {hasBreakpoint = true})
      view.dispatch({
        effects: breakpointEffect.of({pos, on: !hasBreakpoint})
      })
    }

    const breakpointMarker = new class extends GutterMarker {
      toDOM() { return document.createTextNode("🔴") }
    }

    let debugSelection = this.debugSelection;
    return [
      breakpointState,
      gutter({
        class: "cm-breakpoint-gutter",
        markers: v => v.state.field(breakpointState),
        initialSpacer: () => breakpointMarker,
        domEventHandlers: {
          mousedown(view, line) {
            if (tab.debug) {
              toggleBreakpoint(view, line.from)
              let nextLine = view.state.doc.lineAt(line.to + 1);
              let text = view.state.sliceDoc(view.state.selection.main.from, nextLine.from);
              debugSelection.emit(text);
              return true
            } else return false;
          }
        }
      }),
      EditorView.baseTheme({
        ".cm-breakpoint-gutter .cm-gutterElement": {
          color: "red",
          cursor: "default"
        }
      })
    ]
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Output ------------------- ***/
  /*** --------------------------------------------- ***/

  getTooltip(tab: OutputTab | CodeTab): string {
    if ((tab as OutputTab).type === "output") {
      return (tab as OutputTab).tooltip ?? "Click \'Run\' to simulate output!";
    }
    return null;
  }

  isRunning(tab: CodeTab | OutputTab | ReferenceManualTab | PreviewTab): boolean {
    if ((tab as OutputTab).type === "output" || (tab as PreviewTab).type === "preview") {
      return (tab as OutputTab).running;
    }
    return null;
  }

  simulateOutput(indexTab: number, tab: OutputTab | CodeTab | PreviewTab){
    if ((tab as OutputTab).type === "output" || (tab as PreviewTab).type === "preview") {
      (tab as OutputTab).running = true;
      this.runOutput.emit(indexTab);
    }
  }

  refreshingOutput(indexTab: number, tab: OutputTab | CodeTab | PreviewTab) {
    if ((tab as OutputTab).type === "output" || (tab as PreviewTab).type === "preview") {
      if ((tab as OutputTab).value) {
        (tab as OutputTab).running = false;
        return;
      }
      this.refreshOutput.emit(indexTab);
    }
  }

  async previewExpression(tab: PreviewTab){
    (tab as PreviewTab).running = true;

    let toPreview = this.expressionToPreview
      .replace(/#.*/g, '') // Remove comments
      .replace(/^\s*[\r\n]/gm, '') // Remove empty lines
      .replace(/(\s*:\s*)/g, ':') // Replace ' : ', ' :' and ': ' with ':'
      .trim(); // Trim any trailing whitespace or newline characters

    // Put expression between {} if it isn't already
    if (toPreview[0] != "{" && toPreview[toPreview.length - 1] != "}") {
      toPreview = `{${toPreview.trim()}}`;
    }

    try {
      this.outputPreview = await this.api.previewExpression(tab.courseId, toPreview).toPromise();
    } catch {
      AlertService.showAlert(AlertType.ERROR, "Something went wrong... You might want to check your expression.");
    }

    (tab as PreviewTab).running = false;
  }

  /*** --------------------------------------------- ***/
  /*** -------------- Search & Filter -------------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string): void {
    this.reduce.search(this.originalFunctions, query);
  }

  filterFunctions(searchQuery?: string) {
    if (searchQuery) {
      let functions: CustomFunction[] = [];
      for (let i = 0; i < this.filteredFunctions.length; i++){
        if (((this.filteredFunctions[i].keyword).toLowerCase()).includes(searchQuery.toLowerCase())
          ||this.filteredFunctions[i].name.toLowerCase().includes(searchQuery.toLowerCase())) {
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

  getExampleParts(text: string){
    text = '\n' + text;
    return (text.split('\n>')).slice(1);
  }

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
        effects: this.editorTheme.reconfigure(this.themeService.getTheme() === "light" ? githubLight : oneDark)
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
  debug: boolean,                                  // Allows debug to occur or not (in case of metadata should be false, for instance)
  highlightQuery?: string,                         // Text to highlight
  value?: string,                                  // Value on init
  mode?: "python" | "javascript",                  // Type of code to write. E.g. python, javascript, ... NOTE: only python-lang and javascript-lang installed. Must install more packages for others
  placeholder?: string,                            // Message to show by default
  nrLines?: number,                                // Number of lines already added to the editor. Default = 10 lines
  customKeywords?: string[],                       // Personalized keywords
  customFunctions?: CustomFunction[],              // Personalized functions
  readonly?: boolean,                              // Make editor readonly
}

export interface PreviewTab {
  name: string,                                    // Name of the tab that will appear above
  type: "preview",                                 // Specifies type of tab in editor
  active: boolean,                                 // Indicates which tab is active (only one at a time!)
  debug: boolean,                                  // Allows debug to occur or not (in case of metadata should be false, for instance)
  highlightQuery?: string,                         // Text to highlight
  value?: string,                                  // Value on init
  mode?: "el",                                     // Type of code to write.
  placeholder?: string,                            // Message to show by default
  nrLines?: number,                                // Number of lines already added to the editor. Default = 10 lines
  customKeywords?: string[],                       // Personalized keywords
  customFunctions?: CustomFunction[],              // Personalized functions
  readonly?: boolean,                              // Make editor readonly
  running: boolean,                                // Boolean to show if code is running in background
  courseId: number
}

export interface OutputTab {
  name: string,                              // Name of the tab that will appear above
  type: "output",                            // Specifies type of tab in editor (debugging tabs also enter here)
  active: boolean,                           // Indicates which tab is active (only one at a time!)
  running: boolean,                          // Boolean to show if code is running in background
  debugOutput: boolean,                      // Tab to show debug results or entire output
  tutorialMessage?: string,                  // Small message if there's need for a small explanation
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
  example?: string
  args: {name: string, optional: boolean, type: string, description: string}[],   // Arguments that each function receives
  returnType: string,                                     // Type of value it returns
}
