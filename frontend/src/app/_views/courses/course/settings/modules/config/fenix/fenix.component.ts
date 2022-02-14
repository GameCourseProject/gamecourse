import {Component, OnInit} from '@angular/core';
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";

@Component({
  selector: 'app-config-fenix',
  templateUrl: './fenix.component.html',
  styleUrls: ['./fenix.component.scss']
})
export class FenixConfigComponent implements OnInit {

  loading: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  importedFile: File;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
    });
  }

  saveFenixVars() {
    this.loading = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const students = reader.result;
      this.api.courseFenix(this.courseID, students)
        .pipe( finalize(() => this.loading = false) )
        .subscribe(
          nrStudents => console.log("nrStudents", nrStudents),
          error => ErrorService.set(error)
        )
    }
    reader.readAsText(this.importedFile);
  }

  onFileSelected(files: FileList): void {
    this.importedFile = files.item(0);
    this.hasUnsavedChanges = true;
  }

}
