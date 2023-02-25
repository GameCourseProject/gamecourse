import {Component, OnInit, ViewChild} from '@angular/core';
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";

import {ApiHttpService} from "../../../../../../../../../_services/api/api-http.service";
import {AlertService, AlertType} from "../../../../../../../../../_services/alert.service";
import {PopupService} from "../../../../../../../../../_services/popup.service";

@Component({
  selector: 'app-googlesheets',
  templateUrl: './googlesheets.component.html'
})
export class GooglesheetsComponent implements OnInit {

  loading = {
    page: true,
    auth: false,
    action: false
  }

  courseID: number;

  credentials: Credentials;
  needsAuthentication: boolean
  canAuthenticate: boolean;
  @ViewChild('fAuth', { static: false }) fAuth: NgForm;

  spreadsheetID: string;
  sheets: {name: string, owner: string}[];
  users: {value: string, text: string}[];
  @ViewChild('fSheets', { static: false }) fSheets: NgForm;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.parent.params.subscribe(async params => {
      this.courseID = parseInt(params.id);
      await this.getConfig();
      await this.getUsers();
      this.loading.page = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getConfig() {
    const {config, needsAuth} = await this.api.getGoogleSheetsConfig(this.courseID).toPromise();
    this.spreadsheetID = config.spreadsheetId;
    this.sheets = [];
    for (let i = 0; i < config.sheetNames.length; i++) {
      this.sheets.push({name: config.sheetNames[i], owner: config.ownerNames[i]});
    }

    // Must have one sheet
    if (this.sheets.length === 0) {
      this.sheets.push({name: undefined, owner: undefined});
    }

    this.needsAuthentication = needsAuth;
  }

  async getUsers(): Promise<void> {
    const users = (await this.api.getUsers().toPromise()).sort((a, b) => a.name.localeCompare(b.name));
    this.users = users.map(user => {
      return {value: user.username, text: user.name};
    });
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async authenticate() {
    if (this.fAuth.valid) {
      this.loading.auth = true;

      try {
        const authURL = await this.api.authenticateGoogleSheets(this.courseID, this.credentials).toPromise();

        // Open Google authentication window
        const width = 550;
        const height = 650;
        PopupService.openPopUp(authURL, 'Authenticate', width, height);

        this.loading.auth = false;

      } catch (error) {
        this.loading.auth = false;
      }

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async discardChanges() {
    this.loading.action = true;

    this.fSheets.resetForm();
    await this.getConfig();

    this.loading.action = true;
  }

  async saveConfig() {
    if (this.fSheets.valid) {
      this.loading.action = true;

      try {
        await this.api.setGoogleSheetsConfig(this.courseID, this.spreadsheetID, this.sheets.map(sheet => sheet.name), this.sheets.map(sheet => sheet.owner)).toPromise();
        this.loading.action = false;

      } catch (error) {
        this.loading.action = false;
      }

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  async addSheet() {
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
