import {Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges} from '@angular/core';

@Component({
  selector: 'app-input-file',
  templateUrl: './input-file.component.html',
  styleUrls: ['./input-file.component.scss']
})
export class InputFileComponent implements OnInit, OnChanges {

  // Essentials
  @Input() id: string;                        // Unique id

  // Extras
  @Input() multiple?: boolean;                // Accept multiple files
  @Input() accept?: string;                   // Types of files to accept
  @Input() classList?: string;                // Classes to add
  @Input() disabled?: boolean;                // Make it disabled

  // Validity
  @Input() required?: boolean;                // Make it required

  // Errors
  @Input() requiredErrorMessage?: string;     // Message for required error

  @Output() valueChange = new EventEmitter<FileList>();

  files: FileList;

  constructor() { }

  ngOnInit(): void {
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes?.accept && changes?.accept.currentValue.includes('image')) {
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
          // this.dialogManagerService.createAlert("error", 'You can only upload images.');
          this.files = null;
          return;
        }
      }
    }

    this.valueChange.emit(this.files);
  }

}
