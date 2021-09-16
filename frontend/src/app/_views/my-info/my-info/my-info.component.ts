import { Component, OnInit } from '@angular/core';
import {User} from "../../../_domain/User";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {Subject, throwError} from "rxjs";
import {ImageManager} from "../../../_utils/image-manager";

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

  photoToAdd: File;       // Any photo that comes through the input
  photo: ImageManager;    // Photo to be displayed
  updatePhotoSubject: Subject<void> = new Subject<void>();    // Trigger photo update on navbar

  loading = true;
  isEditModalOpen: boolean;
  saving: boolean;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getUserInfo();
  }

  getUserInfo(): void {
    this.api.getLoggedUser()
      .subscribe(user => {
        this.user = user;

        this.editUser = {
          name: user.name,
          nickname: user.nickname,
          studentNumber: user.studentNumber,
          email: user.email,
          auth: user.authMethod,
          username: user.username
        };
        this.photo.set(user.photoUrl);

        this.loading = false;
      })
  }

  isReadyToEdit() {
    let isValid = function (text) {
      return text != "" && text != undefined;
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
      await ImageManager.getBase64(this.photoToAdd).then(data => this.editUser.image = data);

    this.api.editSelfInfo(this.editUser)
      .subscribe(res => {
          this.getUserInfo();
          if (this.editUser.image) // Trigger change on navbar
            this.updatePhotoSubject.next();
        },
        error => throwError(error),
        () => {
          this.saving = false;
          this.isEditModalOpen = false;
          this.photoToAdd = null;
        })
  }

}

export interface UserData {
  name: string,
  nickname: string,
  studentNumber: number,
  email: string,
  auth: string,
  username: string,
  image?: string | ArrayBuffer
}
