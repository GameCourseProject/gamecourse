import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-import-helper',
  templateUrl: './import-helper.component.html'
})
export class ImportHelperComponent implements OnInit {

  @Input() id: string;                      // Helper ID
  @Input() format: '.csv' | '.zip';         // Import file format
  @Input() requirements: string[];          // List with requirements

  // Format: CSV
  @Input() csvHeaders?: string[];            // CSV file headers
  @Input() csvRows?: string[][];             // CSV file example rows

  constructor() { }

  ngOnInit(): void {
  }

  openHelper() {
    const helper = document.getElementById(this.id);
    helper.classList.remove('hidden');
  }

  closeHelper() {
    const helper = document.getElementById(this.id);
    helper.classList.add('hidden');
  }

}
