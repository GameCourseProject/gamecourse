import {Component, OnInit, ViewChild} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";
import {ActivatedRoute} from "@angular/router";
import {NgForm} from "@angular/forms";
import {environment} from "../../../../../environments/environment";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";
import {AlertService, AlertType} from "../../../../_services/alert.service";
import {ThemingService} from "../../../../_services/theming/theming.service";
import {ResourceManager} from "../../../../_utils/resources/resource-manager";

import {User} from "../../../../_domain/users/user";
import {Theme} from "../../../../_services/theming/themes-available";
import {AuthType} from "../../../../_domain/auth/auth-type";
import {UserManageData} from "../../users/users/users.component";
import {clearEmptyValues} from "../../../../_utils/misc/misc";


@Component({
  selector: 'app-profile',
  templateUrl: './profile.component.html'
})
export class ProfileComponent implements OnInit {

  loading = {
    page: true,
    action: false
  }

  loggedUser: User;
  isATeacher: boolean;
  user: User;
  userPhoto: ResourceManager;
  userToManage: UserManageData;
  @ViewChild('f', { static: false }) f: NgForm;

  authMethods: {value: any, text: string}[] = this.initAuthMethods();

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService,
    private themeService: ThemingService
  ) {
    this.userPhoto = new ResourceManager(sanitizer);
  }

  async ngOnInit(): Promise<void> {
    this.route.parent.params.subscribe(async params => {
      const userID = parseInt(params.id);
      await this.getLoggedUser();
      await this.getUser(userID);
      this.loading.page = false;
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getLoggedUser(): Promise<void> {
    this.loggedUser = await this.api.getLoggedUser().toPromise();
    this.isATeacher = await this.api.isATeacher(this.loggedUser.id).toPromise();
  }

  async getUser(userID: number): Promise<void> {
    if (this.loggedUser.id !== userID) this.user = await this.api.getUserById(userID).toPromise();
    else this.user = this.loggedUser;

    this.userPhoto.set(this.user.photoUrl);
    this.updateManager.update.subscribe(type => { // Whenever updates are received
      if (type === UpdateType.AVATAR)
        this.userPhoto.set(this.userToManage.photoURL);
    });

    this.userToManage = this.initUserToManage(this.user);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async editUser(): Promise<void> {
    if (this.f.valid) {
      this.loading.action = true;

      if (this.userToManage.photoToAdd)
        await ResourceManager.getBase64(this.userToManage.photoToAdd).then(data => this.userToManage.photoBase64 = data);

      const userEdited = await this.api.editUser(clearEmptyValues(this.userToManage)).toPromise();
      this.user = userEdited;

      // Trigger image change
      if (this.userToManage.photoToAdd && this.user.id === userEdited.id) {
        this.userPhoto.set(userEdited.photoUrl);
        this.updateManager.triggerUpdate(UpdateType.AVATAR);
      }

      this.loading.action = false;
      AlertService.showAlert(AlertType.SUCCESS, 'Profile edited successfully');

    } else AlertService.showAlert(AlertType.ERROR, 'Invalid form');
  }

  discardChanges() {
    this.userToManage = this.initUserToManage(this.user);
    const formValues = {
      'user-nickname': this.userToManage.nickname,
      'user-major': this.userToManage.major,
      'user-email': this.userToManage.email,
    };
    if (this.loggedUser.isAdmin) {
      formValues['user-name'] = this.userToManage.name;
      formValues['user-number'] = this.userToManage.studentNr;
      formValues['user-auth'] = this.userToManage.authService;
      formValues['user-username'] = this.userToManage.username;
    }
    this.f.resetForm(formValues);
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initUserToManage(user: User): UserManageData {
    const userData: UserManageData = {
      id: user.id,
      name: user.name,
      email: user.email ?? null,
      major: user.major,
      nickname: user.nickname,
      studentNr: user.studentNumber,
      username: user.username,
      authService: user.authMethod,
      photoURL: user.photoUrl,
      photoToAdd: null,
      photoBase64: null,
      photo: new ResourceManager(this.sanitizer)
    };
    if (userData.photoURL) userData.photo.set(userData.photoURL);
    return userData;
  }

  initAuthMethods(): {value: any, text: string}[] {
    return Object.values(AuthType).map(authMethod => {
      return {value: authMethod, text: authMethod.capitalize()}
    });
  }

  get DefaultProfileImg(): string {
    const theme = this.themeService.getTheme();
    return theme === Theme.DARK ? environment.userPicture.dark : environment.userPicture.light;
  }

  onFileSelected(files: FileList): void {
    this.userToManage.photoToAdd = files.item(0);
    this.userToManage.photo.set(this.userToManage.photoToAdd);
  }
}
