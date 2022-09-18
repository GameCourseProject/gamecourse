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
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {InputGroupSize, InputSize } from '../../Settings';

@Component({
  selector: 'app-input-file',
  templateUrl: './input-file.component.html'
})
export class InputFileComponent implements OnInit, AfterViewInit, OnChanges {

  // Essentials
  @Input() id: string;                                    // Unique ID
  @Input() form: NgForm;                                  // Form it's part of
  @Input() accept?: string;                               // Types of files to accept
  @Input() multiple?: boolean;                            // Accept multiple files
  @Input() camera?: boolean;                              // Accept camera input

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';       // Size
  @Input() color?: string;                                // Color
  @Input() classList?: string;                            // Classes to add
  @Input() disabled?: boolean;                            // Make it disabled

  @Input() label?: string;                                // Top label text

  // Validity
  @Input() required?: boolean;                            // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';    // Message for required error

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
          AlertService.showAlert(AlertType.ERROR, 'You can only upload images.');
          this.files = null;
          return;
        }
      }
    }

    this.valueChange.emit(this.files);
  }

  getFiles(): File[] {
    return Array.from(this.files);
  }

  getFileTypes(): string {
    if (!this.accept) return 'Any file type';

    const types = {
      image: ['SVG', 'PNG', 'JPG', 'WEBP', 'GIF'],
      video: ['MP4', 'MOV'],
      audio: ['MP3', 'MID', 'WAV']
    };

    let formats: string[] = [];
    if (this.accept.containsWord('image/*')) formats = formats.concat(types.image);
    if (this.accept.containsWord('video/*')) formats = formats.concat(types.video);
    if (this.accept.containsWord('audio/*')) formats = formats.concat(types.audio);

    const singleTypes = this.accept.removeWord('image/*').removeWord('video/*').removeWord('audio/*');
    const parts = singleTypes.split(',').map(part => part.trim().substring(1).toUpperCase()).filter(part => !!part);
    formats = [...new Set(formats.concat(parts))]; // unique formats
    return formats.length >= 2 ? (formats.slice(0, -1).join(', ') + ' or ' + formats.slice(-1)) : formats[0];
  }

  removeFile(index: number) {
    // Remove file
    let files = this.getFiles();
    files.splice(index, 1);

    // Create new filelist
    const dt = new DataTransfer();
    for (let file of files) {
      dt.items.add(file);
    }
    this.files = dt.files;

    // Set error
    if (this.required && this.files.length === 0)
      this.form.controls[this.id].setErrors({'required': true});
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

}
