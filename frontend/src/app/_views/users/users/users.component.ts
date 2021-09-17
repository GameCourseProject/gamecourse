import { Component, OnInit } from '@angular/core';
import {Subject} from "rxjs";
import {DomSanitizer} from "@angular/platform-browser";

import {ApiHttpService} from "../../../_services/api/api-http.service";
import {ErrorService} from "../../../_services/error.service";

import {User} from "../../../_domain/User";
import {UserData} from "../../my-info/my-info/my-info.component";
import {AuthType} from "../../../_domain/AuthType";
import {ImageManager} from "../../../_utils/image-manager";

import {orderByDate, orderByNumber, orderByString} from "../../../_utils/order-by";
import {swapPTCharacters} from "../../../_utils/swap-pt-chars";
import {downloadAsCSV} from "../../../_utils/download-files";

import _ from 'lodash';


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
  filteredUsers: User[];

  searchQuery: string;

  filters = ['Admin', 'NonAdmin', 'Active', 'Inactive'];
  filtersActive: string[];

  orderBy = ['Name', 'Nickname', 'Student Number', '# Courses', 'Last Login'];
  orderByActive: {orderBy: string, sort: number};
  DEFAULT_SORT = 1;

  originalPhoto: string;  // Original photo
  photoToAdd: File;       // Any photo that comes through the input
  photo: ImageManager;    // Photo to be displayed
  updatePhotoSubject: Subject<void> = new Subject<void>();    // Trigger photo update on navbar

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
    private sanitizer: DomSanitizer
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
          this.filteredUsers = _.cloneDeep(data); // deep copy

          this.filtersActive = _.cloneDeep(this.filters);
          this.orderByActive = { orderBy: this.orderBy[0], sort: this.DEFAULT_SORT };
          this.reduceList();

          this.loading = false;
        },
        error => ErrorService.set(error));
  }


  /*** --------------------------------------------- ***/
  /*** ---------- Search, Filter & Order ----------- ***/
  /*** --------------------------------------------- ***/

  onSearch(query: string): void {
    this.searchQuery = query;
    this.reduceList();
  }

  onFilterChanged(filter: {filter: string, state: boolean}): void {
    if (filter.state) {
      this.filtersActive.push(filter.filter);

    } else {
      const index = this.filtersActive.findIndex(el => el === filter.filter);
      this.filtersActive.splice(index, 1);
    }

    this.reduceList();
  }

  onOrderByChanged(order: {orderBy: string, sort: number}): void {
    this.orderByActive = order;
    this.orderList();
  }

  reduceList(): void {
    this.filteredUsers = [];

    this.allUsers.forEach(user => {
      if (this.isQueryTrueSearch(user) && this.isQueryTrueFilter(user))
        this.filteredUsers.push(user);
    });

    this.orderList();
  }

  orderList(): void {
    switch (this.orderByActive.orderBy) {
      case "Name":
        this.filteredUsers.sort((a, b) => orderByString(a.name, b.name, this.orderByActive.sort))
        break;

      case "Nickname":
        this.filteredUsers.sort((a, b) => orderByString(a.nickname, b.nickname, this.orderByActive.sort))
        break;

      case "Student Number":
        this.filteredUsers.sort((a, b) => orderByNumber(a.studentNumber, b.studentNumber, this.orderByActive.sort))
        break;

      case "# Courses":
        this.filteredUsers.sort((a, b) => orderByNumber(a.nrCourses, b.nrCourses, this.orderByActive.sort))
        break;

      case "Last Login":
        this.filteredUsers.sort((a, b) => orderByDate(a.lastLogin, b.lastLogin, this.orderByActive.sort))
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
          if (this.user.id === this.newUser.id && this.newUser.image) // Trigger change on navbar
            this.updatePhotoSubject.next();
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
        contents => downloadAsCSV('users', contents),
        error => ErrorService.set(error),
        () => this.saving = false
      )
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  parseForSearching(query: string): string[] {
    let res: string[];
    let temp: string;
    query = swapPTCharacters(query);

    res = query.toLowerCase().split(' ');

    temp = query.replace(' ', '').toLowerCase();
    if (!res.includes(temp)) res.push(temp);

    temp = query.toLowerCase();
    if (!res.includes(temp)) res.push(temp);
    return res;
  }

  isQueryTrueSearch(user: User): boolean {
    return !this.searchQuery ||
      (user.name && !!this.parseForSearching(user.name).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (user.nickname && !!this.parseForSearching(user.nickname).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (user.studentNumber && !!this.parseForSearching(user.studentNumber.toString()).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (user.email && !!this.parseForSearching(user.email).find(a => a.includes(this.searchQuery.toLowerCase()))) ||
      (user.username && !!this.parseForSearching(user.username).find(a => a.includes(this.searchQuery.toLowerCase())));
  }

  isQueryTrueFilter(user: User): boolean {
    if (this.filters.length === 0)
      return true;

    for (const filter of this.filtersActive) {
      if ((filter === 'Admin' && user.isAdmin) || (filter === 'NonAdmin' && !user.isAdmin) ||
        (filter === 'Active' && user.isActive) || (filter === 'Inactive' && !user.isActive))
        return true;
    }
    return false;
  }

  isReadyToSubmit() {
    let isValid = function (text) {
      return (text != "" && text != undefined)
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
