import {
  AfterViewInit,
  Component,
  EventEmitter,
  Input,
  OnInit,
  Output,
  ViewChild
} from '@angular/core';
import {NgForm, NgModel} from "@angular/forms";

import {AlertService, AlertType} from "../../../../_services/alert.service";
import {InputColor, InputGroupBtnColor, InputGroupLabelColor} from '../../InputColors';
import {InputGroupSize, InputSize } from '../../InputSizes';

@Component({
  selector: 'app-input-file',
  templateUrl: './input-file.component.html'
})
export class InputFileComponent implements OnInit, AfterViewInit {

  // Essentials
  @Input() id: string;                                                // Unique ID
  @Input() form: NgForm;                                              // Form it's part of
  @Input() accept?: string[];                                         // Types of files to accept
  @Input() multiple?: boolean;                                        // Accept multiple files

  // Extras
  @Input() size?: 'xs' | 'sm' | 'md' | 'lg' = 'md';                   // Size
  @Input() color?: 'ghost' | 'primary' | 'secondary' | 'accent' |     // Color
    'info' | 'success' | 'warning' | 'error';
  @Input() classList?: string;                                        // Classes to add
  @Input() disabled?: boolean;                                        // Make it disabled

  @Input() label?: string;                                            // Top label text

  @Input() helperText?: string;                                       // Text for helper tooltip
  @Input() helperPosition?: 'top' | 'bottom' | 'left' | 'right';      // Helper position

  // Validity
  @Input() required?: boolean;                                        // Make it required

  // Errors
  @Input() requiredErrorMessage?: string = 'Required';                // Message for required error

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

  onDrop(event) {
    // Add file
    let files;
    if (this.multiple) {
      files = this.getFiles();
      files.push(event[0]);
    }
    else { files = [event[0]] }

    // Create new filelist
    const dt = new DataTransfer();
    for (let file of files) {
      dt.items.add(file);
    }
    this.files = dt.files;

    this.valueChange.emit(this.files);
  }

  getFiles(): File[] {
    return this.files ? Array.from(this.files) : [];
  }

  getFileTypes(): string {
    if (!this.accept) return 'Any file type';

    const types = {
      image: ['SVG', 'PNG', 'JPG', 'WEBP', 'GIF'],
      video: ['MP4', 'MOV'],
      audio: ['MP3', 'MID', 'WAV']
    };

    let formats: string[] = [];
    if (this.accept.includes('image/*')) formats = formats.concat(types.image);
    if (this.accept.includes('video/*')) formats = formats.concat(types.video);
    if (this.accept.includes('audio/*')) formats = formats.concat(types.audio);

    const singleTypes = this.accept.filter(a => a !== 'image/*' && a !== 'video/*' && a !== 'audio/*');
    const parts = singleTypes.map(part => part.trim().substring(1).toUpperCase()).filter(part => !!part);
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

    this.valueChange.emit(this.files);
  }


  heights = {
    xs: 'h-fit',
    sm: 'h-40',
    md: 'h-48',
    lg: 'h-64'
  }

  labelColors = {
    ghost: '',
    primary: 'border-primary text-primary',
    secondary: 'border-secondary text-secondary',
    accent: 'border-accent text-accent',
    info: 'border-info text-info',
    success: 'border-success text-success',
    warning: 'border-warning text-warning',
    error: 'border-error text-error'
  }

  get InputSize(): typeof InputSize {
    return InputSize;
  }

  get InputGroupSize(): typeof InputGroupSize {
    return InputGroupSize;
  }

  get InputColor(): typeof InputColor {
    return InputColor;
  }

  get InputGroupLabelColor(): typeof InputGroupLabelColor {
    return InputGroupLabelColor;
  }

  get InputGroupBtnColor(): typeof InputGroupBtnColor {
    return InputGroupBtnColor;
  }

}
