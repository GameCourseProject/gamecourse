import {
  AfterViewInit,
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild
} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

@Component({
  selector: 'app-input-file',
  templateUrl: './input-file.component.html',
  styleUrls: ['./input-file.component.scss']
})
export class InputFileComponent implements OnInit, AfterViewInit, OnChanges {

  // Essentials
  @Input() id: string;                        // Unique id
  @Input() form: NgForm;                      // Form it's part of

  // Extras
  @Input() label?: string;                    // Label prepend
  @Input() multiple?: boolean;                // Accept multiple files
  @Input() accept?: string;                   // Types of files to accept
  @Input() camera?: boolean;                  // Accept camera input
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<FileList>();

  @ViewChild('inputFile', { static: false }) inputFile: NgModel;

  files: FileList;
  value: any;

  constructor() { }

  ngOnInit(): void {
  }

  ngAfterViewInit(): void {
    if (this.form) this.form.addControl(this.inputFile);
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (this.camera && changes?.accept && changes?.accept.currentValue.includes('image')) {
      this.accept += ';capture=camera';
    }
  }

  onFilesSelected(event) {
    this.files = event.target.files;

    // Check if file format correct
    if (this.accept.includes('image')) {
      for (let i = 0; i < this.files.length; i++) {
        const file = this.files.item(i);

        if (!file.type.includes('image')) {
          // this.dialogManagerService.createAlert("error", 'You can only upload images.'); TODO: error dialog
          this.files = null;
          return;
        }
      }
    }

    this.valueChange.emit(this.files);
  }

}
