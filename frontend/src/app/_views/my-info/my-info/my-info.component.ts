import {Component, OnInit} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";

import {User} from "../../../_domain/users/user";

import {ResourceManager} from "../../../_utils/resources/resource-manager";
import {AuthType} from "../../../_domain/auth/auth-type";
import {exists} from "../../../_utils/misc/misc";
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

  ngOnInit(): void {
    this.getUserInfo();
  }

  getUserInfo(): void {
    this.api.getLoggedUser()
      .pipe( finalize(() => this.loading = false) )
      .subscribe(user => {
        this.user = user;

        this.editUser = {
          name: user.name,
          nickname: user.nickname,
          studentNumber: user.studentNumber,
          email: user.email,
          major: user.major,
          auth: user.authMethod,
          username: user.username
        };
        this.originalPhoto = user.photoUrl;
        this.photo.set(user.photoUrl);
      },
        error => ErrorService.set(error))
  }

  isReadyToEdit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    };

    // Validate inputs
    return isValid(this.editUser.username) && isValid(this.editUser.email) && isValid(this.editUser.studentNumber)
      && isValid(this.editUser.username) && isValid(this.editUser.auth);
  };

  onFileSelected(files: FileList): void {
    this.photoToAdd = files.item(0);
    this.photo.set(this.photoToAdd);
  }

  async submitEditUser(): Promise<void> {
    this.saving = true;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.editUser.image = data);

    this.api.editSelfInfo(this.editUser)
      .pipe( finalize(() => {
        this.saving = false;
        this.isEditModalOpen = false;
        this.photoToAdd = null;
      }) )
      .subscribe(res => {
          this.getUserInfo();
          if (this.editUser.image)
            this.updateManager.triggerUpdate(UpdateType.AVATAR) // Trigger change on navbar
        },
        error => ErrorService.set(error))
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
  roles?: string[],
  isAdmin?: boolean,
  isActive?: boolean,
  image?: string | ArrayBuffer
}
