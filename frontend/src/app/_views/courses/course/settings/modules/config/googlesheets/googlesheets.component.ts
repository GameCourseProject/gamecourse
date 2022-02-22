import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {error} from "jquery";
import {exists} from "../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-googlesheets',
  templateUrl: './googlesheets.component.html',
  styleUrls: ['./googlesheets.component.scss']
})
export class GooglesheetsComponent implements OnInit {

  loading: boolean;
  canAuthenticate: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  spreadsheetID: string;
  sheets: {name: string, owner: string}[];
  periodicity = {
    nr: 0,
    time: 'Minutes'
  };
  isEnabled: boolean;

  credentials: Credentials;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getGoogleSheetsVars();
    });
  }

  getGoogleSheetsVars() {
    this.loading = true;
    this.api.getGoogleSheetsVars(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        vars => {
          this.spreadsheetID = vars.spreadsheetId;
          this.sheets = [];
          for (let i = 0; i < vars.sheetName.length; i++) {
            this.sheets.push({name: vars.sheetName[i], owner: vars.ownerName[i]});
          }
          this.periodicity.nr = vars.periodicityNumber;
          this.periodicity.time = vars.periodicityTime;
          this.isEnabled = vars.isEnabled;
        },
        error => ErrorService.set(error)
      )
  }

  saveGoogleSheets() {
    this.loading = true;
    this.api.setGoogleSheetsVars(this.courseID, this.spreadsheetID, this.sheets, this.periodicity.nr, this.periodicity.time, this.isEnabled)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => this.getGoogleSheetsVars(),
        error => ErrorService.set(error)
      )
  }

  addSheet() {
    this.sheets.push({name: '', owner: ''});
  }

  removeSheet(name: string, owner: string) {
    const index = this.sheets.findIndex(sheet => sheet.name === name && sheet.owner === owner);
    this.sheets.splice(index, 1);
  }

  authenticate() {
    this.loading = true;
    this.api.setGoogleSheetsCredentials(this.courseID, this.credentials)
      .pipe(finalize(() => this.loading = false))
      .subscribe(
        url => {
          const width = 550;
          const height = 650;
          const left = (screen.width - width) / 2;
          const top = (screen.height - height) / 4;
          window.open(url, 'Authenticate', 'toolbar=no, location=no, directories=no, status=no, menubar=no, ' +
            'scrollbars=no, resizable=no, copyhistory=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);
        },
        error => ErrorService.set(error())
      )
  }

  onFileSelected(files: FileList): void {
    const credentialsFile = files.item(0);
    const reader = new FileReader();
    reader.onload = (e) => {
      this.credentials = JSON.parse(reader.result as string);
      this.canAuthenticate = true;
    }
    reader.readAsText(credentialsFile);
  }

  isReadyToSubmit(): boolean {
    return exists(this.spreadsheetID) && !this.spreadsheetID.isEmpty()
      && this.sheets.length > 0;
  }
}

export interface Credentials {
  [key: string]: {  // FIXME: why is there a key?
    client_id: string,
    project_id: string,
    auth_uri: string,
    token_uri: string,
    auth_provider_x509_cert_url: string,
    client_secret: string,
    redirect_uris: string[]
  }
}
