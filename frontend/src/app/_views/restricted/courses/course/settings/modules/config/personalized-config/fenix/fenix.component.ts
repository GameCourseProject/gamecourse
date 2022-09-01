import {Component, OnInit} from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";

@Component({
  selector: 'app-fenix',
  templateUrl: './fenix.component.html',
  styleUrls: ['./fenix.component.scss']
})
export class FenixComponent implements OnInit {

  loading: boolean = true;
  hasUnsavedChanges: boolean;

  courseID: number;

  importedFile: File;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.loading = false;
    });
  }

  importFenixStudents() {
    this.loading = true;

    const reader = new FileReader();
    reader.onload = async (e) => {
      const nrStudents = await this.api.importFenixStudents(this.courseID, reader.result).toPromise();

      const successBox = $('#action_completed');
      successBox.empty();
      successBox.append(nrStudents + " Student" + (nrStudents > 1 ? 's' : '') + " Imported");
      successBox.show().delay(3000).fadeOut();

      this.loading = false;
    }
    reader.readAsText(this.importedFile);
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

}
