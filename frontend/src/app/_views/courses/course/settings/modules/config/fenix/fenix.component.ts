import {Component, OnInit} from '@angular/core';

@Component({
  selector: 'app-config-fenix',
  templateUrl: './fenix.component.html',
  styleUrls: ['./fenix.component.scss']
})
export class FenixConfigComponent implements OnInit {

  hasUnsavedChanges: boolean;
  importedFile: File;

  constructor() { }

  ngOnInit(): void {
  }

  saveFenixVars() {
    // TODO
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

}
