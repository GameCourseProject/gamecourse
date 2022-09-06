import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {exists} from "../../../../../../../../../_utils/misc/misc";

@Component({
  selector: 'app-googlesheets',
  templateUrl: './googlesheets.component.html',
  styleUrls: ['./googlesheets.component.scss']
})
export class GooglesheetsComponent implements OnInit {

  loading: boolean = true;

  canAuthenticate: boolean;
  hasUnsavedChanges: boolean;

  courseID: number;

  spreadsheetID: string;
  sheets: {name: string, owner: string}[];

  credentials: Credentials;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getConfig();
      this.loading = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getConfig() {
    const config = await this.api.getGoogleSheetsConfig(this.courseID).toPromise();
    this.spreadsheetID = config.spreadsheetId;
    this.sheets = [];
    for (let i = 0; i < config.sheetNames.length; i++) {
      this.sheets.push({name: config.sheetNames[i], owner: config.ownerNames[i]});
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async authenticate() {
    this.loading = true;

    const authURL = await this.api.authenticateGoogleSheets(this.courseID, this.credentials).toPromise();
    const width = 550;
    const height = 650;
    const top = (window.screen.availHeight + (window.screen.availHeight / 2)) - (height / 2);
    const left = (window.screen.availWidth + (window.screen.availWidth / 2)) - (width / 2);
    window.open(authURL, 'Authenticate', 'toolbar=no, location=no, directories=no, status=no, menubar=no, ' +
      'scrollbars=no, resizable=no, copyhistory=no, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left);

    this.loading = false;
  }

  async saveConfig() {
    this.loading = true;
    await this.api.setGoogleSheetsConfig(this.courseID, this.spreadsheetID, this.sheets.map(sheet => sheet.name), this.sheets.map(sheet => sheet.owner)).toPromise();
    this.loading = false;
  }

  addSheet() {
    this.sheets.push({name: '', owner: ''});
  }

  removeSheet(name: string, owner: string) {
    const index = this.sheets.findIndex(sheet => sheet.name === name && sheet.owner === owner);
    this.sheets.splice(index, 1);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

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
  [key: string]: {
    client_id: string,
    project_id: string,
    auth_uri: string,
    token_uri: string,
    auth_provider_x509_cert_url: string,
    client_secret: string,
    redirect_uris: string[]
  }
}

export interface GoogleSheetsConfig {
  spreadsheetId: string;
  sheetNames: string[];
  ownerNames: string[];
}
