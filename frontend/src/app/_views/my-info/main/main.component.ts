import { Component, OnInit } from '@angular/core';
import {User} from "../../../_domain/User";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {DomSanitizer, SafeUrl} from "@angular/platform-browser";
import {Subject, throwError} from "rxjs";
import {ImageManager} from "../../../_utils/image-manager";

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  NOT_SET = 'Not set';

  attributes: string[] = ['Name', 'Nickname', 'Student Number', 'Email', 'Authentication', 'Username'];
  values = ['name', 'nickname', 'studentNumber', 'email', 'authMethod', 'username']

  user: User;
  editUser: {
    name: string,
    nickname: string,
    studentNumber: number,
    email: string,
    auth: string,
    username: string
  };

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

    let photoData;
    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => photoData = data);

    let data = {
      userName: this.editUser.name,
      userStudentNumber: this.editUser.studentNumber,
      userNickname: this.editUser.nickname,
      userEmail: this.editUser.email,
      userUsername: this.editUser.username,
      userAuthService: this.editUser.auth,
      userHasImage: !!photoData
    };

    if (data.userHasImage)
      data['userImage'] = photoData;

    this.api.editSelfInfo(data)
      .subscribe(res => {
          this.getUserInfo();
          if (data.userHasImage) // Trigger change on navbar
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
