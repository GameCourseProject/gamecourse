import {Component, OnInit} from '@angular/core';
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../../_services/api/api-http.service";
import {UpdateService, UpdateType} from "../../../../_services/update.service";

import {User} from "../../../../_domain/users/user";
import {UserData} from "../../my-info/my-info/my-info.component";
import {AuthType} from "../../../../_domain/auth/auth-type";

import {ResourceManager} from "../../../../_utils/resources/resource-manager";
import {DownloadManager} from "../../../../_utils/download/download-manager";
import {Order, Sort} from "../../../../_utils/lists/order";
import {Reduce} from "../../../../_utils/lists/reduce";

import {exists} from "../../../../_utils/misc/misc";
import {finalize} from "rxjs/operators";


@Component({
  selector: 'app-main',
  templateUrl: './users.component.html',
  styleUrls: ['./users.component.scss']
})
export class UsersComponent implements OnInit {

  loading = true;
  loadingAction = false;

  loggedUser: User;
  users: User[];

  reduce = new Reduce();
  order = new Order();

  filters = ['Admin', 'NonAdmin', 'Active', 'Inactive'];
  orderBy = ['Name', 'Nickname', 'Student Number', '# Courses', 'Last Login'];

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ResourceManager; // Photo to be displayed

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
    image: null
  };
  userToEdit: User;
  userToDelete: User;

  constructor(
    private api: ApiHttpService,
    private sanitizer: DomSanitizer,
    private updateManager: UpdateService
  ) {
    this.photo = new ResourceManager(sanitizer);
  }

  async ngOnInit(): Promise<void> {
    this.loggedUser = await this.api.getLoggedUser().toPromise();
    await this.getUsers();
    this.loading = false;
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  async getUsers(): Promise<void> {
    this.users = await this.api.getUsers().toPromise();
    this.order.active = {orderBy: this.orderBy[0], sort: Sort.ASCENDING};
    this.reduceList(undefined, [...this.filters]);
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  reduceList(query?: string, filters?: string[]): void {
    this.reduce.searchAndFilter(this.users, query, filters);
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
        this.reduce.items.sort((a, b) => Order.byNumber(a.nrCourses, b.nrCourses, this.order.active.sort))
        break;

      case "Last Login":
        this.reduce.items.sort((a, b) => Order.byDate(a.lastLogin, b.lastLogin, this.order.active.sort))
        break;
    }
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  async createUser(): Promise<void> {
    this.loadingAction = true;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.api.createUser(this.newUser)
      .pipe( finalize(() => {
        this.isUserModalOpen = false;
        this.clearObject(this.newUser);
        this.loadingAction = false;
      }) )
      .subscribe(
        newUser => {
          this.users.push(newUser);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("New user created");
          successBox.show().delay(3000).fadeOut();
        })
  }

  async editUser(): Promise<void> {
    this.loadingAction = true;
    this.newUser['id'] = this.userToEdit.id;

    if (this.photoToAdd)
      await ResourceManager.getBase64(this.photoToAdd).then(data => this.newUser.image = data);

    this.api.editUser(this.newUser)
      .pipe( finalize(() => {
        this.isUserModalOpen = false;
        this.clearObject(this.newUser);
        this.loadingAction = false;
      }) )
      .subscribe(
        async userEdited => {
          const index = this.users.findIndex(user => user.id === userEdited.id);
          this.users.removeAtIndex(index);

          this.users.push(userEdited);
          this.reduceList();

          const loggedUser = await this.api.getLoggedUser().toPromise();
          if (loggedUser.id === userEdited.id && this.photoToAdd)
            this.updateManager.triggerUpdate(UpdateType.AVATAR); // Trigger change on navbar

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User: '" + userEdited.name + "' edited");
          successBox.show().delay(3000).fadeOut();
        })
  }

  deleteUser(user: User): void {
    this.loadingAction = true;
    this.api.deleteUser(user.id)
      .pipe( finalize(() => {
        this.isDeleteVerificationModalOpen = false;
        this.loadingAction = false
      }) )
      .subscribe(
        () => {
          const index = this.users.findIndex(el => el.id === user.id);
          this.users.removeAtIndex(index);
          this.reduceList();

          const successBox = $('#action_completed');
          successBox.empty();
          successBox.append("User '" + user.name  + ' - ' + user.studentNumber + "' deleted");
          successBox.show().delay(3000).fadeOut();
        })
  }

  toggleAdmin(userID: number) {
    this.loadingAction = true;

    const user = this.users.find(user => user.id === userID);
    user.isAdmin = !user.isAdmin;

    this.api.setUserAdmin(user.id, user.isAdmin)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {});
  }

  toggleActive(userID: number) {
    this.loadingAction = true;

    const user = this.users.find(user => user.id === userID);
    user.isActive = !user.isActive;

    this.api.setUserActive(user.id, user.isActive)
      .pipe( finalize(() => this.loadingAction = false) )
      .subscribe(res => {});
  }

  importUsers(replace: boolean): void {
    this.loadingAction = true;

    const reader = new FileReader();
    reader.onload = (e) => {
      const file = reader.result;
      this.api.importUsers({file, replace})
        .pipe( finalize(() => {
          this.isImportModalOpen = false;
          this.loadingAction = false;
        }) )
        .subscribe(
          async nrUsers => {
            await this.getUsers();
            const successBox = $('#action_completed');
            successBox.empty();
            successBox.append(nrUsers + " User" + (nrUsers != 1 ? 's' : '') + " Imported");
            successBox.show().delay(3000).fadeOut();
          })
    }
    reader.readAsText(this.importedFile);
  }

  exportUsers(): void {
    this.saving = true;
    this.api.exportUsers()
      .pipe( finalize(() => this.saving = false) )
      .subscribe(contents => DownloadManager.downloadAsCSV('users', contents))
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
      image: null
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
