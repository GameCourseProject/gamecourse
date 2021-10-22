import {Component, OnInit} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";
import {UpdateService, UpdateType} from "../../../_services/update.service";

import {User} from "../../../_domain/users/user";
import {UserData} from "../../my-info/my-info/my-info.component";
import {AuthType} from "../../../_domain/auth/auth-type";

import {ImageManager} from "../../../_utils/images/image-manager";
import {DownloadManager} from "../../../_utils/download/download-manager";
import {Order, Sort} from "../../../_utils/display/order";
import {Reduce} from "../../../_utils/display/reduce";

import _ from 'lodash';
import {exists} from "../../../_utils/misc/misc";


@Component({
  selector: 'app-main',
  templateUrl: './users.component.html',
  styleUrls: ['./users.component.scss']
})
export class UsersComponent implements OnInit {

  loading = true;
  loadingAction = false;

  user: User;

  allUsers: User[];

  reduce = new Reduce();
  order = new Order();

  filters = ['Admin', 'NonAdmin', 'Active', 'Inactive'];
  orderBy = ['Name', 'Nickname', 'Student Number', '# Courses', 'Last Login'];

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ImageManager;    // Photo to be displayed

  importedFile: File;

  isUserModalOpen: boolean;
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  saving: boolean;

  mode: 'add' | 'edit';
  newUser: UserData = {
    name: null,
    nickname: null,
    studentNumber: null,
    email: null,
    major: null,
    isAdmin: null,
    isActive: null,
    auth: null,
    username: null,
  };
  userToEdit: User;
  userToDelete: User;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ImageManager(sanitizer);
  }

  ngOnInit(): void {
    this.getLoggedUser();
    this.getUsers();
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getLoggedUser(): void {
    this.api.getLoggedUser()
      .subscribe(
        user => this.user = user,
        error => ErrorService.set(error)
      );
  }

  getUsers(): void {
    this.api.getUsers()
      .subscribe(data => {
          this.allUsers = data;

          this.order.active = { orderBy: this.orderBy[0], sort: Sort.ASCENDING };
          this.reduceList(undefined, _.cloneDeep(this.filters));

          this.loading = false;
        },
        error => ErrorService.set(error));
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string, filters?: string[]): void {
    this.reduce.searchAndFilter(this.allUsers, query, filters);
    this.orderList();
  }

  orderList(): void {
    switch (this.order.active.orderBy) {
      case "Name":
        this.reduce.items.sort((a, b) => Order.byString(a.name, b.name, this.order.active.sort))
        break;

      case "Nickname":
        this.reduce.items.sort((a, b) => Order.byString(a.nickname, b.nickname, this.order.active.sort))
        break;

      case "Student Number":
        this.reduce.items.sort((a, b) => Order.byNumber(a.studentNumber, b.studentNumber, this.order.active.sort))
        break;

      case "# Courses":
        this.reduce.items.sort((a, b) => Order.byNumber(a.courses.length, b.courses.length, this.order.active.sort))
        break;

      case "Last Login":
        this.reduce.items.sort((a, b) => Order.byDate(a.lastLogin, b.lastLogin, this.order.active.sort))
        break;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  toggleAdmin(userID: number) {
    this.loadingAction = true;

    const user = this.allUsers.find(user => user.id === userID);
    user.isAdmin = !user.isAdmin;

    this.api.setUserAdmin(user.id, user.isAdmin)
      .subscribe(
        res => this.loadingAction = false,
        error => ErrorService.set(error)
      );
  }

  toggleActive(userID: number) {
    this.loadingAction = true;

    const user = this.allUsers.find(user => user.id === userID);
    user.isActive = !user.isActive;

    this.api.setUserActive(user.id, user.isActive)
      .subscribe(
        res => this.loadingAction = false,
        error => ErrorService.set(error)
      );
  }

  async createUser(): Promise<void> {
    this.loadingAction = true;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.api.createUser(this.newUser)
      .subscribe(
        res => {
          this.allUsers.push(res);
          this.reduceList();
        },
        error => ErrorService.set(error),
        () => {
          this.isUserModalOpen = false;
          this.clearObject(this.newUser);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New user created");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  async editUser(): Promise<void> {
    this.loadingAction = true;
    this.newUser['id'] = this.userToEdit.id;

    if (this.photoToAdd)
      await ImageManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.api.editUser(this.newUser)
      .subscribe(
        res => {
          this.getUsers();
          if (this.user.id === this.newUser.id && this.newUser.image)
            this.updateManager.triggerUpdate(UpdateType.AVATAR); // Trigger change on navbar
        },
        error => ErrorService.set(error),
        () => {
          this.isUserModalOpen = false;
          this.clearObject(this.newUser);
          this.loadingAction = false;
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + this.userToEdit.name + " edited");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  deleteUser(user: User): void {
    this.loadingAction = true;
    this.api.deleteUser(user.id)
      .subscribe(
        res => {
          const index = this.allUsers.findIndex(el => el.id === user.id);
          this.allUsers.splice(index, 1);
          this.reduceList();
        },
        error => ErrorService.set(error),
        () => {
          this.isDeleteVerificationModalOpen = false;
          this.loadingAction = false
          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: " + user.name  + ' - ' + user.studentNumber + " deleted");
          successBox.show().delay(3000).fadeOut();
        }
      )
  }

  importUsers(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const importedUsers = reader.result;
      this.api.importUsers({file: importedUsers, replace})
        .subscribe(
          nUsers => {
            this.getUsers();
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nUsers + " Users" + (nUsers > 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          },
          error => ErrorService.set(error),
          () => {
            this.isImportModalOpen = false;
            this.loadingAction = false;
          }
        )
    }
    reader.readAsDataURL(this.importedFile);
  }

  exportUsers(): void {
    this.saving = true;

    this.api.exportUsers()
      .subscribe(
        contents => DownloadManager.downloadAsCSV('users', contents),
        error => ErrorService.set(error),
        () => this.saving = false
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  isReadyToSubmit() {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    return isValid(this.newUser.name) && isValid(this.newUser.studentNumber) && isValid(this.newUser.email) &&
      isValid(this.newUser.major) && isValid(this.newUser.auth) && isValid(this.newUser.username);
  }

  initEditUser(user: User): void {
    this.newUser = {
      name: user.name,
      nickname: user.nickname,
      studentNumber: user.studentNumber,
      email: user.email,
      major: user.major,
      isAdmin: user.isAdmin,
      isActive: user.isActive,
      auth: user.authMethod,
      username: user.username,
    };
    this.userToEdit = user;
    this.photo.set(user.photoUrl);
  }

  onFileSelected(files: FileList, type: 'image' | 'file'): void {
    if (type === 'image') {
      this.photoToAdd = files.item(0);
      this.photo.set(this.photoToAdd);

    } else {
      this.importedFile = files.item(0);
    }
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  get AuthType(): typeof AuthType {
    return AuthType;
  }

}

export interface ImportUsersData {
  file: string | ArrayBuffer,
  replace: boolean
}
