import {Component, OnInit} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";
import {environment} from "../../../../../environments/environment";

import {User} from "../../../../_domain/users/user";

import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {AuthType} from "../../../../_domain/auth/auth-type";
import {exists} from "../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";


@Component({
  selector: 'app-my-info',
  templateUrl: './my-info.component.html',
  styleUrls: ['./my-info.component.scss']
})
export class MyInfoComponent implements OnInit {

  NOT_SET = 'Not set';

  attributes: string[] = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username'];
  values = ['name', 'nickname', 'studentNumber', 'email', 'authMethod', 'username']

  user: User;
  editUser: UserData;

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ResourceManager; // Photo to be displayed

  loading = true;
  isEditModalOpen: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ResourceManager(sanitizer);
  }

  async ngOnInit(): Promise<void> {
    await this.getLoggedUser();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.user = await this.api.getLoggedUser().toPromise();

    this.editUser = {
      id: this.user.id,
      name: this.user.name,
      nickname: this.user.nickname,
      studentNumber: this.user.studentNumber,
      email: this.user.email,
      major: this.user.major,
      isAdmin: this.user.isAdmin,
      isActive: this.user.isActive,
      auth: this.user.authMethod,
      username: this.user.username,
      image: null
    };

    this.originalPhoto = this.user.photoUrl ?? environment.defaultProfilePicture;
    this.photo.set(this.user.photoUrl ?? environment.defaultProfilePicture);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async submitEditUser(): Promise<void> {
    this.saving = true;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.editUser.image = data);

    this.api.editUser(this.editUser)
      .pipe( finalize(() => {
        this.saving = false;
        this.isEditModalOpen = false;
        this.photoToAdd = null;
      }) )
      .subscribe(res => {
        this.user = res;
        if (this.photoToAdd)
          this.updateManager.triggerUpdate(UpdateType.AVATAR) // Trigger change on navbar
      })
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToEdit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    };

    // Validate inputs
    return isValid(this.editUser.name) && isValid(this.editUser.studentNumber) && isValid(this.editUser.email) &&
      isValid(this.editUser.auth) && isValid(this.editUser.username);
  };

  onFileSelected(files: FileList): void {
    this.photoToAdd = files.item(0);
    this.photo.set(this.photoToAdd);
  }
}

export interface UserData {
  id?: number,
  name: string,
  nickname: string,
  studentNumber: number,
  major: string,
  email: string,
  auth: AuthType,
  username: string,
  isAdmin: boolean,
  isActive: boolean,
  image: string | ArrayBuffer,
  roles?: string[]
}
