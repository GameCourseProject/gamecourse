import {AfterViewInit, Component, ElementRef, EventEmitter, Input, OnInit, Output, ViewChild} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-notification-text',
  templateUrl: './input-notification-text.component.html',
  styleUrls: ['./input-notification-text.component.scss']
})
export class InputNotificationTextComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                      // Unique ID
  @Input() form: NgForm;                                                    // Form it's part of
  @Input() value: string;                                                   // Where to store the value
  @Input() placeholder: string;                                             // Message to show by default
  @Input() itemsToSuggest: string[];                                        // Options on autocomplete

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                         // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |           // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                              // Classes to add
  @Input() label?: string;                                                  // Top label text

  @Input() helperText?: string;                                             // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';            // Helper position

  @Input() specialCharStyle?: string = "inline-block bg-base-300 rounded-full px-2" // Style that will be applied to the suggested items

  // Validity
  @Input() pattern?: string;                                                // The pattern to be applied
  @Input() required?: boolean;                                              // Make it required
  @Input() minLength?: number;                                              // Enforce a minimum length
  @Input() maxLength?: number;                                              // Enforce a maximum length

  // Errors
  @Input() patternErrorMessage?: string;                                    // Message for pattern error
  @Input() requiredErrorMessage?: string = 'Required';                      // Message for required error
  @Input() minLengthErrorMessage?: string;                                  // Message for minLength error
  @Input() maxLengthErrorMessage?: string;                                  // Message for maxLength error
  @Input() incorrectErrorMessage?: string;                                  // Message for incorrect error

  @Output() valueChange = new EventEmitter<string>();
  @Output() btnClicked = new EventEmitter<void>();

  @ViewChild('inputTextarea', { static: false }) inputTextarea: NgModel;
  @ViewChild('textElement', { static: false }) textElement: ElementRef;

  mentionConfig: { triggerChar: string; items: string[]; mentionSelect: (e: any) => any; };

  constructor() { }

  ngOnInit(): void {
    // Init default min/max length error messaged
    if (!this.minLengthErrorMessage) this.minLengthErrorMessage = 'Text size needs to be greater than or equal to ' + this.minLength;
    if (!this.maxLengthErrorMessage) this.maxLengthErrorMessage = 'Text size needs to be smaller than or equal to ' + this.maxLength;

    this.mentionConfig = {
      triggerChar: '%',
      items: this.itemsToSuggest,
      mentionSelect: (e: any) => {
        return '##' + e.label + '##';
      },
    }
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputTextarea);
    this.colorSpecialWords();
  }

  get TextareaSize(): typeof TextareaSize {
    return TextareaSize;
  }

  get TextareaColor(): typeof TextareaColor {
    return TextareaColor;
  }

  colorSpecialWords() {
    for (let word of this.itemsToSuggest) {
      this.textElement.nativeElement.innerHTML = this.textElement.nativeElement.innerHTML
        .replace("%" + word, `<div class="${this.specialCharStyle}" contenteditable="false">%${word}</div>`);
    }
  }

  itemSelected(event: any) {
    setTimeout(() => {
      this.textElement.nativeElement.innerHTML = this.textElement.nativeElement.innerHTML.replace(
        '##' + event.label + '##',
        `<div class="${this.specialCharStyle}" contenteditable="false">%${event.label}</div>&nbsp;`
      );
      // put the cursor to the end of field again...
      this.selectEnd();
    }, 10);
  }

  selectEnd() {
    let range, selection;
    range = document.createRange();
    range.selectNodeContents(this.textElement.nativeElement);
    range.collapse(false);
    selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
  }

  updateInput() {
    this.value = this.textElement.nativeElement.innerText;
    this.valueChange.emit(this.value);
  }
}

enum TextareaSize {
  xs = 'h-10',
  sm = 'h-20',
  md = 'h-32',
  lg = 'h-48'
}

enum TextareaColor {
  ghost = 'textarea-ghost',
  primary = 'textarea-primary',
  secondary = 'textarea-secondary',
  accent = 'textarea-accent',
  info = 'textarea-info',
  success = 'textarea-success',
  warning = 'textarea-warning',
  error = 'textarea-error'
}
